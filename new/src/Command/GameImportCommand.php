<?php
declare(strict_types=1);

namespace App\Command;

/**
 * TODO:
 * - warn user if import is for different gamer id than database?
 * - figure out better way to detect if game has dlc
 * . implement GameCollection here (foreach doesnt work yet)
 * - game report seems broken after implementing Collection
 */

use App\Entity\Game;
use App\Entity\GameCollection;
use App\Service\GameParserService;
use App\Service\GameScraperService;
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
        $gameCollectionPages = $this->scraper->scrape($gamertag);

        //loop through pages
        $parsedArray = [];
        foreach ($gameCollectionPages as $gameCollectionPage) {
            $dom = new DOMDocument();
            $dom->loadHtml($gameCollectionPage);

            //loop through games on page, convert to our game object
            $xpath = new DOMXPath($dom);
            $games = $xpath->query('//tr[contains(@class, "green") or contains(@class, "even") or contains(@class, "odd")]');
            foreach ($games as $tableRow) {
                $parsedGame = $this->gameParserService->parseRowIntoGame($tableRow, $xpath);
                $parsedArray[$parsedGame->getId()] = $parsedGame;
            }
        }

        $parsedGames = new GameCollection($parsedArray);
        $currentGames = $this->fetchCurrentGames();

        //generate and print a report with all the changes the import will be making
        $this->generateReport($currentGames, $parsedGames, $output);

        //save new games and updated games to database
        $this->persistGames($parsedGames, $output);

        //remove deleted games from database
        $this->removeDeletedGames($currentGames, $parsedGames, $output);

        return Command::SUCCESS;
    }

    private function fetchCurrentGames(): GameCollection
    {
        $manager = $this->doctrine->getManager();
        $gameRepository = $manager->getRepository(Game::class);
        $games = $gameRepository->findBy([], ['id' => 'ASC']);

        return new GameCollection($games);
    }

    private function persistGames(GameCollection $games, OutputInterface $output): void
    {
        $manager = $this->doctrine->getManager();
        $gameRepository = $manager->getRepository(Game::class);
        $output->writeLn('Saving games to database...');
        foreach ($games->toArray() as $game) {
            $existingGame = $gameRepository->find($game->getId());
            if ($existingGame instanceof Game && count($this->getGameDiff($existingGame, $game)) > 0) {
                //game exists, update with new details if needed
                $existingGame->update($game);
                $manager->persist($existingGame);
            } elseif ($existingGame instanceof Game) {
                //no changes
            } else {
                //insert new game
                $manager->persist($game);
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
            if ($parsedGames->hasItem($gameId) === true) {
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
            if ($currentGames->hasItem($gameId) === true) {
                //game gets updated, print diff
                $this->printGameDiff($currentGames->getItem($gameId), $parsedGame, $output);
            } else {
                //game is new
                $output->writeLn(sprintf('[%s] new game!', $parsedGame->getName()));
            }
        }
        foreach ($currentGames as $gameId => $currentGame) {
            if ($parsedGames->hasItem($gameId) === false) {
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
                '%d more achievements unlocked, %d left',
                $parsedGame->getAchievementsWon() - $currentGame->getAchievementsWon(),
                $parsedGame->getAchievementsTotal() - $parsedGame->getAchievementsWon()
            );
        }
        if ($currentGame->getAchievementsTotal() < $parsedGame->getAchievementsTotal()) {
            //new dlc appeared
            $changes[] = sprintf(
                'new dlc appeared! %d new achievements for %d GS',
                $currentGame->getAchievementsTotal() - $parsedGame->getAchievementsTotal(),
                $currentGame->getGamerscoreTotal() - $parsedGame->getGamerscoreTotal()
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
}