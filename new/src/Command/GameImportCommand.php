<?php
declare(strict_types=1);

namespace App\Command;

use App\Enum\Platform;
use App\Entity\Game;
use App\Factory\GameFactory;
use App\Service\GameScraperService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use DOMDocument;
use DOMXPath;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:import')]
class GameImportCommand extends Command
{
    private string $taBaseUrl = 'https://www.trueachievements.com/';

    public function __construct(
        private readonly GameScraperService $scraper,
        private readonly GameFactory $gameFactory,
        private readonly ManagerRegistry $doctrine,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument('gamertag', InputArgument::REQUIRED, 'The gamertag of the player.');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $gamertag = $input->getArgument('gamertag');

        $gameCollectionPages = $this->scraper->scrape($gamertag);
        $parsedGames = [];
        //loop through pages
        foreach ($gameCollectionPages as $gameCollectionPage) {
            $dom = new DOMDocument();
            $dom->loadHtml($gameCollectionPage);

            $xpath = new DOMXPath($dom);
            $games = $xpath->query('//tr[contains(@class, "green") or contains(@class, "even") or contains(@class, "odd")]');

            //loop through games on page
            foreach ($games as $tableRow) {
                $parsedGames[] = $this->parseRowIntoGame($tableRow, $xpath, $output);
            }
        }

        $this->persistGames($games);

        return Command::SUCCESS;
    }

    private function parseRowIntoGame($tableRow, $basexpath, OutputInterface $output): Game
    {
        /**
         * cells:
         * 0 - thumb picture
         * 1 - name + url (class smallgame)
         * 2 - platform (game id in id attribute as tdPlatform_xxxx)
         * 3 - TA unlocked/total (class score)
         * 4 - GS unlocked/total (class score)
         * 5 - achievements unlocked/total (class score)
         * 6 - completion percentage
         * 7 - my rating (class rating, nested div with class rating-[xxx]-stars)
         * 8 - started date (class date)
         * 9 - completed date (class date)
         * 10 - last unlocked date (class date)
         * 11 - ownership (class small)
         * 12 - media (class small)
         * 13 - play status (class small)
         * 14 - site completion percentage
         * 15 - completion estimate
         * 16 - walkthrough link
         * 17 - site ratio (class score)
         * 18 - site rating (class rating)
         * 19 - unobtainables
         **/
        $namelink = $basexpath->query('td[@class="smallgame"]/a', $tableRow);
        $name = $namelink->item(0)->textContent;
        $output->writeLn(sprintf('parsing %s..', $name));
        $gameUrl = $this->taBaseUrl . $namelink->item(0)->getAttribute('href');

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
        $ownership = $cells->item(9)->textContent;

        //10 - media
        $media = $cells->item(10)->textContent;
        //repurpase field to include games I sold
        if ($ownership === 'No longer have') {
            $media = 'Sold';
        }

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
            null,
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
            'available',
            null,
            null,
            null,
            0,
            $walkthroughUrl,
            $gameUrl,
            new DateTime(),
            new DateTime(),
        );
    }

    private function persistGames(array $games): void
    {
        //@TODO save games to database

        $manager = $this->doctrine->getManager();
        dd($games);
    }

    private function convertHoursPlayed(string $hoursPlayed): int
    {
        $hours = 0;
        $minutes = 0;
        $matches = [];
        if (preg_match('/(\d+) hr (\d+) mins/', $hoursPlayed, $matches)) {
            $hours = $matches[1];
            $minutes = $matches[2];
        }

        //@TODO TA data is surprisingly accurate. convert to float?
        return intval(round($hours + floatval($minutes) / 60));
    }

    private function getHasDlc(int $gamerscoreTotal): bool
    {
        return $gamerscoreTotal > 1000;
    }

    private function getDlcCompletion(int $gamerscoreWon, int $gamerscoreTotal): int
    {
        //NB this assumes the base game is completed
        return $gamerscoreWon > 1000 && $gamerscoreTotal > 1000 ? intval(round(($gamerscoreWon - 1000) / ($gamerscoreTotal - 1000))) : 0;
    }

    private function parseGameIdAndPlatform($basexpath, $cell): array
    {
        $gameId = 0;
        if (preg_match('/_(\d+)$/', $cell->getAttribute('id'), $m)) {
            $gameId = intval($m[1]);
        }

        $platformCode = $basexpath->query('img', $cell)->item(0)->getAttribute('title');
        $platform = match($platformCode) {
            'xbox-360' => Platform::PLATFORM_360,
            'xbox-one' => Platform::PLATFORM_XB1,
            'xbox-series-x-s' => Platform::PLATFORM_XSX,
            'windows' => Platform::PLATFORM_WIN,
            default => dd($platformCode), //@TODO complete this list
        };

        return [$gameId, $platform];
    }

    private function parseTaScore($cell): array
    {
        $taWon = 0;
        $taTotal = 0;
        if (preg_match('/(.+) \/ (.+)/', $cell->textContent, $m)) {
            $taWon = intval(str_replace(',', '', $m[1]));
            $taTotal = intval(str_replace(',', '', $m[2]));
        }

        return [$taWon, $taTotal];
    }

    private function parseGamerscore($cell): array
    {
        $gamerscoreWon = 0;
        $gamerscoreTotal = 0;
        if (preg_match('/(.+) \/ (.+)/', $cell->textContent, $m)) {
            $gamerscoreWon = intval(str_replace(',', '', $m[1]));
            $gamerscoreTotal = intval(str_replace(',', '', $m[2]));
        }

        return [$gamerscoreWon, $gamerscoreTotal];
    }

    private function parseAchievements($cell): array
    {
        $achievementsWon = 0;
        $achievementsTotal = 0;
        if (preg_match('/(.+) \/ (.+)/', $cell->textContent, $m)) {
            $achievementsWon = intval($m[1]);
            $achievementsTotal = intval($m[2]);
        }

        return [$achievementsWon, $achievementsTotal];
    }

    private function parseCompletionDate($cell): ?DateTime
    {
        $dateCompleted = null;
        $dateCompletedRaw = $cell->textContent;
        if (strlen($dateCompletedRaw) > 0) {
            $dateCompleted = DateTime::createFromFormat('d M y', $dateCompletedRaw);
        }

        return $dateCompleted;
    }

    private function parseWalkthroughUrl($basexpath, $cell): ?string
    {
        //@TODO walkthrough url can be NULL, but database field isnt nullable
        $walkthroughUrl = '';
        $item = $basexpath->query('a', $cell)->item(0);
        if ($item !== null) {
            $walkthroughUrl = $item->getAttribute('href');
        }

        return $walkthroughUrl;
    }
}