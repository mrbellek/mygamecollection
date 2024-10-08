<?php
declare(strict_types=1);

namespace App\Command;

/**
 * TODO:
 * - figure out better way to detect if game has dlc
 * - cut up into multiple classes?
 * - move dom logic to gameparserservice
 */

use App\Entity\Game;
use App\Entity\GameCollection;
use App\Exception\GamertagMismatchException;
use App\Exception\InvalidPlatformValueException;
use App\Exception\InvalidTAContentException;
use App\Service\GameParserService;
use App\Service\GameScraperService;
use Doctrine\Persistence\ManagerRegistry;
use DOMDocument;
use DOMNodeList;
use DOMXPath;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:import')]
class GameImportCommand extends Command
{
    public function __construct(
        private readonly GameParserService $gameParserService,
        private readonly GameScraperService $scraper,
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
        try {
            $gameCollectionPages = $this->scraper->scrape($gamertag);
        } catch (InvalidTAContentException $e) {
            $output->writeLn($e->getMessage());
            return Command::FAILURE;
        }

        //loop through pages
        $parsedArray = [];
        foreach ($gameCollectionPages as $gameCollectionPage) {
            $dom = new DOMDocument();
            $dom->loadHtml($gameCollectionPage);

            //loop through games on page, convert to our game object
            $xpath = new DOMXPath($dom);
            $games = $xpath->query('//tr[contains(@class, "green") or contains(@class, "even") or contains(@class, "odd")]');
            try {
                $parsedArray = array_merge($parsedArray, $this->parseRowsIntoGames($games, $xpath));
            } catch (InvalidPlatformValueException $e) {
                $output->writeLn($e->getMessage());
                return Command::FAILURE;
            }
        }

        $parsedGames = GameCollection::createAssociative($parsedArray);
        $currentGames = $this->fetchCurrentGames();

        //check if scraped gamerid is same as gamerid in database
        try {
            $this->verifySameGamerAsInDatabase($currentGames, $parsedGames);
        } catch (GamertagMismatchException $e) {
            $output->writeLn($e->getMessage());
            return Command::FAILURE;
        }

        //generate and print a report with all the changes the import will be making
        $this->generateReport($currentGames, $parsedGames, $output);

        //save new games and updated games to database
        $this->persistGames($parsedGames, $output);

        //remove deleted games from database
        $this->removeDeletedGames($currentGames, $parsedGames, $output);

        return Command::SUCCESS;
    }

    /**
     * @return array<int, Game>
     */
    private function parseRowsIntoGames(DOMNodeList|false $games, DOMXPath $xpath): array
    {
        if ($games === false || count($games) === 0) {
            return [];
        }

        $parsedArray = [];
        foreach ($games as $tableRow) {
            $parsedGame = $this->gameParserService->parseRowIntoGame($tableRow, $xpath);
            $parsedArray[$parsedGame->getId()] = $parsedGame;
        }

        return $parsedArray;
    }

    private function fetchCurrentGames(): GameCollection
    {
        $manager = $this->doctrine->getManager();
        $gameRepository = $manager->getRepository(Game::class);
        $games = $gameRepository->findBy([], ['id' => 'ASC']);

        return GameCollection::createAssociative($games);
    }

    private function persistGames(GameCollection $games, OutputInterface $output): void
    {
        $manager = $this->doctrine->getManager();
        $gameRepository = $manager->getRepository(Game::class);
        $output->writeLn('Saving games to database...');
        foreach ($games as $game) {
            $existingGame = $gameRepository->find($game->getId());
            if (!($existingGame instanceof Game)) {
                //insert new game
                $manager->persist($game);
                continue;
            }

            $gameHasChanges = count($this->getGameDiff($existingGame, $game)) > 0;
            if ($gameHasChanges === true) {
                //game exists, update with new details if needed
                $existingGame->update($game);
                $manager->persist($existingGame);
            } else {
                //no changes
            }
        }
        $manager->flush();
        $output->writeLn(sprintf('%d games saved to database.', $games->count()));
    }

    private function removeDeletedGames(
        GameCollection $currentGames,
        GameCollection $parsedGames,
        OutputInterface $output
    ): void {
        $manager = $this->doctrine->getManager();
        $deletedCount = 0;
        foreach ($currentGames as $gameId => $currentGame) {
            if ($parsedGames->containsKey($gameId) === false) {
                //game was deleted from collection
                $manager->remove($currentGame);
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $manager->flush();
            $output->writeLn(sprintf('%d games were deleted from database.', $deletedCount));
        }
    }

    private function generateReport(
        GameCollection $currentGames,
        GameCollection $parsedGames,
        OutputInterface $output
    ): void {
        foreach ($parsedGames as $gameId => $parsedGame) {
            if ($currentGames->containsKey($gameId) === true) {
                //game gets updated, print diff
                $this->printGameDiff($currentGames->offsetGet($gameId), $parsedGame, $output);
            } else {
                //game is new
                $output->writeLn(sprintf('[%s] new game!', $parsedGame->getName()));
            }
        }
        foreach ($currentGames as $gameId => $currentGame) {
            if ($parsedGames->containsKey($gameId) === false) {
                //game was deleted
                $output->writeLn(sprintf('[%s] game removed', $currentGame->getName()));
            }
        }
    }

    private function printGameDiff(
        Game $currentGame,
        Game $parsedGame,
        OutputInterface $output
    ): void {
        $changes = $this->getGameDiff($currentGame, $parsedGame);

        foreach ($changes as $change) {
            $output->writeLn(sprintf(
                '[%s] %s',
                $parsedGame->getName(),
                $change
            ));
        }
    }

    /**
     * @return array<string>
     */
    private function getGameDiff(
        Game $currentGame,
        Game $parsedGame,
    ): array {
        $changes = [];
        if ($currentGame->getName() !== $parsedGame->getName()) {
            //name changed
            $changes[] = sprintf('name changed from \'%s\'', $currentGame->getName());
        }
        if ($currentGame->getCompletionPercentage() === 0 && $parsedGame->getCompletionPercentage() > 0) {
            //game started
            $changes[] = 'game started';
        }
        if ($currentGame->getCompletionPercentage() < 100 && $parsedGame->getCompletionPercentage() === 100) {
            //game completed
            $changes[] = 'game completed!';
        }
        if (round($currentGame->getHoursPlayed(), 2) < round($parsedGame->getHoursPlayed(), 2)) {
            //more hours played
            $changes[] = sprintf(
                '%1$.2f more hours played, now %2$.2f hours',
                $parsedGame->getHoursPlayed() - $currentGame->getHoursPlayed(),
                $parsedGame->getHoursPlayed()
            );
        }
        if ($currentGame->getAchievementsWon() < $parsedGame->getAchievementsWon()) {
            //more achievements unlocked
            $changes[] = sprintf(
                '%d more achievements unlocked, now %d/%d unlocked',
                $parsedGame->getAchievementsWon() - $currentGame->getAchievementsWon(),
                $parsedGame->getAchievementsWon(),
                $parsedGame->getAchievementsTotal()
            );
        }
        if ($currentGame->getAchievementsTotal() < $parsedGame->getAchievementsTotal()) {
            //new dlc appeared
            $changes[] = sprintf(
                'new dlc appeared! %d new achievements for %d GS',
                $parsedGame->getAchievementsTotal() - $currentGame->getAchievementsTotal(),
                $parsedGame->getGamerscoreTotal() - $currentGame->getGamerscoreTotal()
            );
        }
        if ($currentGame->getStatus() !== $parsedGame->getStatus()) {
            //game status changed (sold?)
            $changes[] = sprintf(
                'status changed from \'%s\' to \'%s\'',
                $currentGame->getStatus(),
                $parsedGame->getStatus()
            );
        }
        if ($currentGame->getWalkthroughUrl() === null && $parsedGame->getWalkthroughUrl() !== null) {
            //walkthrough added
            $changes[] = 'walkthrough added!';
        }

        return $changes;
    }

    /**
     * Safety check, see if the gamerid in the database matches the gamerid in the imported
     * game collection. We can kludge this because game urls in the game collection
     * end with gamerid=... on TA
     * 
     * @throws GamertagMismatchException
     */
    private function verifySameGamerAsInDatabase(GameCollection $currentGames, GameCollection $parsedGames): void
    {
        $currentGameUrl = $currentGames->first()->getGameUrl();
        $currentGamerId = substr($currentGameUrl, strpos($currentGameUrl, 'gamerid=') + 8);
        $parsedGameUrl = $parsedGames->first()->getGameUrl();
        $parsedGamerId = substr($parsedGameUrl, strpos($parsedGameUrl, 'gamerid=' ) + 8);

        if ($currentGamerId === '' || $parsedGamerId === '') {
            return;
        }

        if ($currentGamerId !== $parsedGamerId) {
            throw new GamertagMismatchException(sprintf(
                'The given gamertag has a TA gamerid (%s) that\'s different from the gamerid in the database (%s). Import aborted.',
                $parsedGamerId,
                $currentGamerId
            ));
        }
    }
}