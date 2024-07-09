<?php
declare(strict_types=1);

namespace App\Command;

/**
 * TODO:
 * - split up command into more services?
 * - warn user if import is for different gamer id than database?
 * - convert hoursPlayed field from int to float?
 * - figure out better way to detect if game has dlc
 * - make walkthroughUrl field nullable
 */

use App\Enum\Platform;
use App\Entity\Game;
use App\Factory\GameFactory;
use App\Service\GameScraperService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:import')]
class GameImportCommand extends Command
{
    private string $taBaseUrl = 'https://www.trueachievements.com';

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
        //get game collection for given gamertag
        $gamertag = $input->getArgument('gamertag');
        $gameCollectionPages = $this->scraper->scrape($gamertag);

        //loop through pages
        $parsedGames = [];
        foreach ($gameCollectionPages as $gameCollectionPage) {
            $dom = new DOMDocument();
            $dom->loadHtml($gameCollectionPage);

            //loop through games on page, convert to our game object
            $xpath = new DOMXPath($dom);
            $games = $xpath->query('//tr[contains(@class, "green") or contains(@class, "even") or contains(@class, "odd")]');
            foreach ($games as $tableRow) {
                $parsedGames[] = $this->parseRowIntoGame($tableRow, $xpath, $output);
            }
        }

        //save to database and return
        $this->persistGames($parsedGames, $output);

        return Command::SUCCESS;
    }

    private function parseRowIntoGame(
        DOMElement $tableRow,
        DOMXPath $basexpath,
        OutputInterface $output
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
            'available',
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

    private function persistGames(array $games, OutputInterface $output): void
    {
        $manager = $this->doctrine->getManager();
        $gameRepository = $manager->getRepository(Game::class);
        $output->writeLn('Saving games to databae...');
        foreach ($games as $game) {
            $existingGame = $gameRepository->find($game->getId());
            if ($existingGame instanceof Game) {
                //game exists, update with new details
                $existingGame->update($game);
                $manager->persist($existingGame);
            } else {
                //insert new game
                $manager->persist($game);
            }
        }
        $manager->flush();
        $output->writeLn(sprintf('%d games saved to database.', count($games)));

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
        //NB: this assumes a normal XB1 game, not XBLA games that have only 200G
        return $gamerscoreTotal > 1000;
    }

    private function getDlcCompletion(int $gamerscoreWon, int $gamerscoreTotal): int
    {
        //NB this assumes the base game is completed
        return $gamerscoreWon > 1000 && $gamerscoreTotal > 1000 ? intval(round(($gamerscoreWon - 1000) / ($gamerscoreTotal - 1000))) : 0;
    }

    private function parseGameIdAndPlatform(DOMXPAth $basexpath, DOMElement $cell): array
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

    private function parseTaScore(DOMElement $cell): array
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

    private function parseGamerscore(DOMElement $cell): array
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

    private function parseAchievements(DOMElement $cell): array
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

    private function parseCompletionDate(DOMElement $cell): ?DateTime
    {
        $dateCompleted = null;
        $dateCompletedRaw = $cell->textContent;
        if (strlen($dateCompletedRaw) > 0) {
            //TA date here is UK format, e.g. 17 May 21 for 2017-05-17
            $dateCompleted = DateTime::createFromFormat('d M y', $dateCompletedRaw);
        }

        return $dateCompleted;
    }

    private function parseWalkthroughUrl(DOMXPath $basexpath, DOMElement $cell): ?string
    {
        //@TODO walkthrough url can be NULL, but database field isnt nullable yet
        $walkthroughUrl = '';
        $item = $basexpath->query('a', $cell)->item(0);
        if ($item !== null) {
            $walkthroughUrl = $this->taBaseUrl . $item->getAttribute('href');
        }

        return $walkthroughUrl;
    }
}