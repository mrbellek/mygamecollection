<?php
declare(strict_types=1);

namespace App\Command;

/**
 * TODO:
 * - warn user if import is for different gamer id than database?
 * - figure out better way to detect if game has dlc
 */

use App\Entity\Game;
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
        $parsedGames = [];
        foreach ($gameCollectionPages as $gameCollectionPage) {
            $dom = new DOMDocument();
            $dom->loadHtml($gameCollectionPage);

            //loop through games on page, convert to our game object
            $xpath = new DOMXPath($dom);
            $games = $xpath->query('//tr[contains(@class, "green") or contains(@class, "even") or contains(@class, "odd")]');
            foreach ($games as $tableRow) {
                $parsedGames[] = $this->gameParserService->parseRowIntoGame($tableRow, $xpath, $output);
            }
        }

        //save to database and return
        $this->persistGames($parsedGames, $output);

        return Command::SUCCESS;
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
}