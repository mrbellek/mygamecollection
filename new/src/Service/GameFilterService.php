<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\GameRepository;
use InvalidArgumentException;

class GameFilterService
{
    public function __construct(
        private readonly GameRepository $gameRepository,
    ) {}

    //@TODO: GameCollection ipv array
    public function getGamesByFilter(string $filter): array
    {
        return match($filter) {
            'all' => $this->gameRepository->findBy([], ['name' => 'ASC']),
            'completed' => $this->gameRepository->findBy(['completionPercentage' => 100], ['name' => 'ASC']),
            'incomplete' => $this->gameRepository->findIncompleteGames(),
            'notstarted' => $this->gameRepository->findBy(['completionPercentage' => 0], ['name' => 'ASC']),
            'bestrating' => $this->gameRepository->findOrderByBestRating(),
            'notstartedbestrating' => $this- $this->gameRepository->findNotStartedOrderByBestRating(),
            'shortest' => $this->gameRepository->findShortest(),
            'shortestnotstarted' => $this->gameRepository->findShortestNotStarted(),
            'longest' => $this->getGamesByLongest(),
            'mostplayed' => $this->getGamesByMostPlayed(),
            'easiest' => $this->getGamesByEasiest(),
            'hardest' => $this->getGamesByHardest(),
            'recent' => $this->gameRepository->findRecent(),
            'paid' => $this->gameRepository->findPaid(),
            'free' => $this->gameRepository->findFree(),
            'onsale' => $this->gameRepository->findOnSale(),
            'physical' => $this->gameRepository->findBy(['format' => ['Disc', 'Disc & Digital', 'Sold']], ['name' => 'ASC']),
            'sold' => $this->gameRepository->findBy(['format' => 'Sold'], ['name' => 'ASC']),
            'unavailable' => $this->gameRepository->findBy(['status' => ['delisted', 'region-locked']], ['name' => 'ASC']),
            'xb1' => $this->gameRepository->findBy(['platform' => 'Xbox One'], ['name' => 'ASC']),
            '360' => $this->gameRepository->findBy(['platform' => 'Xbox 360'], ['name' => 'ASC']),
            'xsx' => $this->gameRepository->findBy(['platform' => 'Xbox Series X|S'], ['name' => 'ASC']),
            'win' => $this->gameRepository->findBy(['platform' => 'Windows'], ['name' => 'ASC']),
            'bc' => $this->gameRepository->findBy(['platform' => 'Xbox 360', 'backwardsCompatible' => 1], ['name' => 'ASC']),
            'nonbc' => $this->gameRepository->findBy(['platform' => 'Xbox 360', 'backwardsCompatible' => 0], ['name' => 'ASC']),
            'nonbckinect' => $this->gameRepository->findBy(['platform' => 'Xbox 360', 'backwardsCompatible' => 0, 'kinectRequired' => 1], ['name' => 'ASC']),
            'nonbcperiph' => $this->gameRepository->findBy(['platform' => 'Xbox 360', 'backwardsCompatible' => 0, 'peripheralRequired' => 1], ['name' => 'ASC']),
            'nonbconline' => $this->gameRepository->findBy(['platform' => 'Xbox 360', 'backwardsCompatible' => 0, 'onlineMultiplayer' => 1], ['name' => 'ASC']),
            'walkthrough' => $this->gameRepository->findWithWalkthrough(),
            'nowalkthrough' => $this->gameRepository->findBy(['walkthroughUrl' => ''], ['name' => 'ASC']),
            'nodlc' => $this->gameRepository->findBy(['hasDlc' => 0], ['name' => 'ASC']),
            'withdlc' => $this->gameRepository->findBy(['hasDlc' => 1], ['name' => 'ASC']),
            'dlccompleted' => $this->gameRepository->findBy(['hasDlc' => 1, 'dlcCompletionPercentage' => 100], ['name' => 'ASC']),
            'dlcnotcompleted' => $this->gameRepository->findNotCompletedDlc(),
            default => throw new InvalidArgumentException(sprintf('Error: filter %s is invalid.', $filter)),
        };
    }
}
