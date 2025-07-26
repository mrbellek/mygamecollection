<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Series;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:setlistcheck')]
class SetlistCheckerCommand extends Command
{
    private $entityManager;

    public function __construct(ManagerRegistry $doctrine)
    {
        parent::__construct();

        $this->entityManager = $doctrine->getManager();
    }

    public function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Text file with list of series setlist names.');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument('file');
        if (!is_readable($file)) {
            $output->writeLn(sprintf(
                'Unable to read file "%s"',
                $file
            ));

            return Command::FAILURE;
        }

        $inputSetlistNames = array_map(function(string $setlist) {
            return trim(strtolower($setlist));
        }, file($file, FILE_IGNORE_NEW_LINES));
        sort($inputSetlistNames);

        $currentSetlists = $this->getSetlists();
        $currentSetlistNames = array_map(function(Series $setlist) {
            return trim(strtolower($setlist->getName()));
        }, $currentSetlists);
        $currentSetlistNames[] = 'better completions matter';
        $currentSetlistNames[] = 'better setlists matter';
        $currentSetlistNames[] = 'series setlist';
        sort($currentSetlistNames);

        $newSetlists = array_diff($inputSetlistNames, $currentSetlistNames);
        $oldSetlists = array_diff($currentSetlistNames, $inputSetlistNames);

        $output->writeLn(sprintf('The following setlists are new: (%d)', count($newSetlists)));
        foreach ($newSetlists as $name) {
            $output->writeLn(sprintf('- %s', $name));
        }

        $output->writeLn('');
        $output->writeLn(sprintf('The following setlists were deleted: (%d)', count($oldSetlists)));
        foreach ($oldSetlists as $name) {
            //@TODO extra db field for deleted ('retired') setlists
            if (!str_contains($name, '(retired')) {
                $output->writeLn(sprintf('- %s', $name));
            }
        }

        return Command::SUCCESS;
    }

    private function getSetlists(): array
    {
        $setlistRepository = $this->entityManager->getRepository(Series::class);

        return $setlistRepository->findBy([], ['name' => 'ASC']);
    }
}
