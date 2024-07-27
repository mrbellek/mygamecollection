<?php
declare(strict_types=1);

namespace App\Service;

/**
 * @TODO:
 * - move long filters to functions
 */

use App\Entity\Game;
use App\Entity\GameCollection;
use App\Enum\Format as FormatEnum;
use App\Enum\Platform as PlatformEnum;
use App\Repository\GameRepository;
use InvalidArgumentException;

class GameFilterService
{
    public function __construct(
        private readonly GameRepository $gameRepository,
    ) {}

    public function getGamesByFilter(string $filter): GameCollection
    {
        return match($filter) {
            'all' => new GameCollection($this->gameRepository->findBy([], ['name' => 'ASC'])),
            'completed' => new GameCollection($this->gameRepository->findBy(['completionPercentage' => 100], ['name' => 'ASC'])),
            'incomplete' => new GameCollection($this->gameRepository->findIncompleteGames()),
            'notstarted' => new GameCollection($this->gameRepository->findBy(['completionPercentage' => 0], ['name' => 'ASC'])),
            'bestrating' => new GameCollection($this->gameRepository->findOrderByBestRating()),
            'notstartedbestrating' => new GameCollection($this->gameRepository->findNotStartedOrderByBestRating()),
            'shortest' => new GameCollection($this->gameRepository->findShortest()),
            'shortestnotstarted' => new GameCollection($this->gameRepository->findShortestNotStarted()),
            'longest' => $this->getGamesByLongest(),
            'mostplayed' => $this->getGamesByMostPlayed(),
            'easiest' => $this->getGamesByEasiest(),
            'hardest' => $this->getGamesByHardest(),
            'recent' => new GameCollection($this->gameRepository->findRecent()),
            'paid' => new GameCollection($this->gameRepository->findPaid()),
            'free' => new GameCollection($this->gameRepository->findFree()),
            'onsale' => new GameCollection($this->gameRepository->findOnSale()),
            'physical' => new GameCollection($this->gameRepository->findBy(['format' => [FormatEnum::FORMAT_DISC, FormatEnum::FORMAT_BOTH, 'Sold']], ['name' => 'ASC'])),
            'sold' => new GameCollection($this->gameRepository->findBy(['status' => 'Sold'], ['name' => 'ASC'])),
            'unavailable' => new GameCollection($this->gameRepository->findUnavailable()),
            'xb1' => new GameCollection($this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_XB1], ['name' => 'ASC'])),
            '360' => new GameCollection($this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_360], ['name' => 'ASC'])),
            'xsx' => new GameCollection($this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_XSX], ['name' => 'ASC'])),
            'win' => new GameCollection($this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_WIN], ['name' => 'ASC'])),
            'bc' => new GameCollection($this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_360, 'backwardsCompatible' => 1], ['name' => 'ASC'])),
            'nonbc' => new GameCollection($this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_360, 'backwardsCompatible' => 0], ['name' => 'ASC'])),
            'nonbckinect' => new GameCollection($this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_360, 'backwardsCompatible' => 0, 'kinectRequired' => 1], ['name' => 'ASC'])),
            'nonbcperiph' => new GameCollection($this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_360, 'backwardsCompatible' => 0, 'peripheralRequired' => 1], ['name' => 'ASC'])),
            'nonbconline' => new GameCollection($this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_360, 'backwardsCompatible' => 0, 'onlineMultiplayer' => 1], ['name' => 'ASC'])),
            'walkthrough' => new GameCollection($this->gameRepository->findWithWalkthrough()),
            'nowalkthrough' => new GameCollection($this->gameRepository->findBy(['walkthroughUrl' => ''], ['name' => 'ASC'])),
            'nodlc' => new GameCollection($this->gameRepository->findBy(['hasDlc' => 0], ['name' => 'ASC'])),
            'withdlc' => new GameCollection($this->gameRepository->findBy(['hasDlc' => 1], ['name' => 'ASC'])),
            'dlccompleted' => new GameCollection($this->gameRepository->findBy(['hasDlc' => 1, 'dlcCompletionPercentage' => 100], ['name' => 'ASC'])),
            'dlcnotcompleted' => new GameCollection($this->gameRepository->findNotCompletedDlc()),
            default => throw new InvalidArgumentException(sprintf('Error: filter "%s" is invalid.', $filter)),
        };
    }

    private function getGamesByLongest(): GameCollection
    {
        $games = new GameCollection($this->gameRepository->findLongest());
        $games->usort(function(Game $a, Game $b) {
            if ($a->getCompletionEstimate() !== $b->getCompletionEstimate()) {
                return $a->getCompletionEstimate() <=> $b->getCompletionEstimate();
            } else {
                return $a->getName() <=> $b->getName();
            }
        });

        return $games;
    }

    private function getGamesByMostPlayed(): GameCollection
    {
        //get all started games and remove all played <100 horus
        $games = new GameCollection($this->gameRepository->findPlayed());
        $games = $games->filter(function (Game $game): bool {
            $hoursPlayed = $game->getHoursPlayed();
            //if hoursPlayed is blank and game is completed, use completion estimate as hoursPlayed
            if ($hoursPlayed === 0.0 && $game->getCompletionPercentage() === 100) {
                $hoursPlayed = intval($game->getCompletionEstimate());
            }
            return $hoursPlayed >= 80;
        });

        //sort by hours played, or if missing (and game is completed) by comp estimate
        $games->usort(function(Game $a, Game $b) {
            $hoursPlayedA = $a->getHoursPlayed();
            if ($hoursPlayedA === 0.0 && $a->getCompletionPercentage() === 100) {
                $hoursPlayedA = intval($a->getCompletionEstimate());
            }
            $hoursPlayedB = $b->getHoursPlayed();
            if ($hoursPlayedB === 0.0 && $b->getCompletionPercentage() === 100) {
                $hoursPlayedB = intval($b->getCompletionEstimate());
            }
            return $hoursPlayedB <=> $hoursPlayedA;
        });

        return $games;
    }

    private function getGamesByEasiest(): GameCollection
    {
        $games = new GameCollection($this->gameRepository->findWithNonZeroTaTotal());
        $games = $games->filter(function(Game $game) {
            return $game->getTaTotal() / $game->getGamerscoreTotal() < 2;
        });

        $games->usort(function(Game $a, Game $b) {
            $aRatio = $a->getTaTotal() / $a->getGamerscoreTotal();
            $bRatio = $b->getTaTotal() / $b->getGamerscoreTotal();
            if (number_format($bRatio, 2) === number_format($aRatio, 2)) {
                return $a->getName() <=> $b->getName();
            } else {
                return $aRatio <=> $bRatio;
            }
        });

        return $games;
    }

    private function getGamesByHardest(): GameCollection
    {
        $games = new GameCollection($this->gameRepository->findWithNonZeroTaTotal());
        $games = $games->filter(function(Game $game) {
            return $game->getTaTotal() / $game->getGamerscoreTotal() > 5;
        });
        $games->usort(function(Game $a, Game $b) {
            $aRatio = $a->getTaTotal() / $a->getGamerscoreTotal();
            $bRatio = $b->getTaTotal() / $b->getGamerscoreTotal();
            if (number_format($bRatio, 2) == number_format($aRatio, 2)) {
                return $a->getName() <=> $b->getName();
            } else {
                return $bRatio <=> $aRatio;
            }
        });

        return $games;
    }
}
