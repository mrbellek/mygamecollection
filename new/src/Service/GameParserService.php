<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Enum\Platform;
use App\Factory\GameFactory;
use DateTime;
use DOMNode;
use DOMXPath;
use RuntimeException;

class GameParserService
{
    private string $taBaseUrl = 'https://www.trueachievements.com';

    public function __construct(
        private readonly GameFactory $gameFactory,
    ) {}

    public function parseRowIntoGame(
        DOMNode $tableRow,
        DOMXPath $basexpath,
    ): Game {
        /**
         * These are the configured fields to get in the scraper service:
         * 0 - thumb picture (unused)
         * 1 - name + url
         * 2 - platform + game id
         * 3 - TA unlocked/total
         * 4 - GS unlocked/total
         * 5 - achievements unlocked/total
         * 6 - completion percentage
         * 7 - hours played
         * 8 - completion date
         * 9 - ownership
         * 10 - media
         * 11 - completion estimate
         * 12 - walkthrough url
         * 13 - site ratio (currently unused, might add later)
         * 14 - site rating
         **/

        //1 - name + url
        $namelink = $basexpath->query('td[@class="smallgame"]/a', $tableRow);
        $name = utf8_decode($namelink->item(0)->textContent);
        $gameUrl = $this->taBaseUrl . $namelink->item(0)?->getAttribute('href');

        $cells = $basexpath->query('td', $tableRow);

        //2 - gameid + platforom
        [$gameId, $platform] = $this->parseGameIdAndPlatform($basexpath, $cells->item(2));

        //3 - trueachievements score unlocked + total
        [$taWon, $taTotal] = $this->parseTaScore($cells->item(3));

        //4 - gamerscore unlocked + total
        [$gamerscoreWon, $gamerscoreTotal] = $this->parseGamerscore($cells->item(4));

        //5 - achievements unlocked + total
        [$achievementsWon, $achievementsTotal] = $this->parseAchievements($cells->item(5));

        //6 - completion percentage
        $completionPercentage = intval($cells->item(6)->textContent);

        //7 - played hours
        $hoursPlayed = $this->convertHoursPlayed($cells->item(7)->textContent);

        //8 - completion date
        $completionDate = $this->parseCompletionDate($cells->item(8));

        //9 - ownership status
        $status = $this->parseOwnershipStatus($cells->item(9)->textContent);

        //10 - media
        $media = $cells->item(10)->textContent;

        //11 - completion estimate
        $completionEstimate = $cells->item(11)->textContent;

        //12 - walkthrough url
        $walkthroughUrl = $this->parseWalkthroughUrl($basexpath, $cells->item(12));

        //13 - site ratio
        //@TODO add column?
        //$siteRatio = $cells->item(13)->textContent;

        //14 - site rating
        $siteRating = floatval($cells->item(14)->textContent);

        return $this->gameFactory->create(
            $gameId,
            $name,
            $platform,
            null, //these 4 fields are user-provided
            null,
            null,
            null,
            $completionPercentage,
            $completionEstimate,
            $hoursPlayed,
            $achievementsWon,
            $achievementsTotal,
            $gamerscoreWon,
            $gamerscoreTotal,
            $taWon,
            $taTotal,
            $this->getHasDlc($gamerscoreTotal),
            $this->getDlcCompletion($gamerscoreWon, $gamerscoreTotal),
            $completionDate,
            $siteRating,
            $media,
            $status,
            null, //these 4 fields are currently unused, TA has asked me to
            null, //stop scraping the price info
            null,
            0,
            $walkthroughUrl,
            $gameUrl,
            new DateTime(),
            new DateTime(),
        );
    }

    private function convertHoursPlayed(string $hoursPlayed): float
    {
        $hours = 0.0;
        $minutes = 0;
        $matches = [];
        if (preg_match('/(\d+) hrs? (\d+) mins?/', $hoursPlayed, $matches)) {
            $hours = $matches[1];
            $minutes = $matches[2];
        }

        return $hours + floatval($minutes) / 60;
    }

    private function getHasDlc(int $gamerscoreTotal): bool
    {
        //NB: this assumes a normal XB1 game, not XBLA games that have only 200G
        return $gamerscoreTotal > 1000;
    }

    private function getDlcCompletion(int $gamerscoreWon, int $gamerscoreTotal): int
    {
        //NB this assumes the base game is completed
        if ($gamerscoreWon <= 1000 || $gamerscoreTotal <= 1000) {
            return 0;
        }
        return intval(round(($gamerscoreWon - 1000) * 100 / ($gamerscoreTotal - 1000)));
    }

    private function parseGameIdAndPlatform(DOMXPAth $basexpath, DOMNode $cell): array
    {
        $gameId = 0;
        $m = [];
        if (preg_match('/_(\d+)$/', $cell->getAttribute('id'), $m)) {
            $gameId = intval($m[1]);
        }

        $platformCode = $basexpath->query('img', $cell)->item(0)->getAttribute('title');
        $platform = match($platformCode) {
            'xbox-360'          => Platform::PLATFORM_360,
            'xbox-one'          => Platform::PLATFORM_XB1,
            'xbox-series-x-s'   => Platform::PLATFORM_XSX,
            'windows'           => Platform::PLATFORM_WIN,
            'android'           => Platform::PLATFORM_ANDROID,
            'web'               => Platform::PLATFORM_WEB,
            'nintendo-switch'   => Platform::PLATFORM_SWITCH,
            default             => throw new RuntimeException(sprintf('Invalid platform "%s" found.', $platformCode)),
        };

        return [$gameId, $platform];
    }

    private function parseTaScore(DOMNode $cell): array
    {
        $taWon = 0;
        $taTotal = 0;
        $m = [];
        if (preg_match('/(.+) \/ (.+)/', $cell->textContent, $m)) {
            $taWon = intval(str_replace(',', '', $m[1]));
            $taTotal = intval(str_replace(',', '', $m[2]));
        }

        return [$taWon, $taTotal];
    }

    private function parseGamerscore(DOMNode $cell): array
    {
        $gamerscoreWon = 0;
        $gamerscoreTotal = 0;
        $m = [];
        if (preg_match('/(.+) \/ (.+)/', $cell->textContent, $m)) {
            $gamerscoreWon = intval(str_replace(',', '', $m[1]));
            $gamerscoreTotal = intval(str_replace(',', '', $m[2]));
        }

        return [$gamerscoreWon, $gamerscoreTotal];
    }

    private function parseAchievements(DOMNode $cell): array
    {
        $achievementsWon = 0;
        $achievementsTotal = 0;
        $m = [];
        if (preg_match('/(.+) \/ (.+)/', $cell->textContent, $m)) {
            $achievementsWon = intval($m[1]);
            $achievementsTotal = intval($m[2]);
        }

        return [$achievementsWon, $achievementsTotal];
    }

    private function parseCompletionDate(DOMNode $cell): ?DateTime
    {
        $dateCompleted = null;
        $dateCompletedRaw = $cell->textContent;
        if (strlen($dateCompletedRaw) > 0) {
            //TA date here is UK format, e.g. 17 May 21 for 2017-05-17
            //note that it can also display 'Today' or 'Yesterday'
            $dateCompleted = new DateTime($dateCompletedRaw);
        }

        return $dateCompleted;
    }

    private function parseWalkthroughUrl(DOMXPath $basexpath, DOMNode $cell): ?string
    {
        $walkthroughUrl = null;
        $item = $basexpath->query('a', $cell)->item(0);
        if ($item !== null) {
            $walkthroughUrl = $this->taBaseUrl . $item->getAttribute('href');
        }

        return $walkthroughUrl;
    }

    private function parseOwnershipStatus(string $ownership): string
    {
        /**
         * There's more options for this field, but they're no longer used
         * since the price scraper was disabled (as requested by TA staff):
         * - delisted
         * - region-locked
         */
        $status = 'available';
        if ($ownership === 'No longer have') {
            $status = 'sold';
        }

        return $status;
    }
}
