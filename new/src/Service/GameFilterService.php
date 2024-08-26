<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Entity\GameCollection;
use App\Enum\Format as FormatEnum;
use App\Enum\Platform as PlatformEnum;
use App\Exception\InvalidFilterException;
use App\Repository\GameRepository;

/**
 * Returns a filtered subset of the stored games in the database.
 */
class GameFilterService
{
    public function __construct(
        private readonly GameRepository $gameRepository,
    ) {}

    /**
     * @throws InvalidFilterException
     */
    public function getGamesByFilter(string $filter): GameCollection
    {
        return match($filter) {
            'all' => GameCollection::createAssociative($this->gameRepository->findBy([], ['name' => 'ASC'])),
            'completed' => GameCollection::createAssociative($this->gameRepository->findBy(['completionPercentage' => 100], ['name' => 'ASC'])),
            'incomplete' => GameCollection::createAssociative($this->gameRepository->findIncompleteGames()),
            'notstarted' => GameCollection::createAssociative($this->gameRepository->findBy(['completionPercentage' => 0], ['name' => 'ASC'])),
            'bestrating' => GameCollection::createAssociative($this->gameRepository->findOrderByBestRating()),
            'notstartedbestrating' => GameCollection::createAssociative($this->gameRepository->findNotStartedOrderByBestRating()),
            'shortest' => GameCollection::createAssociative($this->gameRepository->findShortest()),
            'shortestnotstarted' => GameCollection::createAssociative($this->gameRepository->findShortestNotStarted()),
            'longest' => $this->getGamesByLongest(),
            'mostplayed' => $this->getGamesByMostPlayed(),
            'easiest' => $this->getGamesByEasiest(),
            'hardest' => $this->getGamesByHardest(),
            'recent' => GameCollection::createAssociative($this->gameRepository->findRecent()),
            'paid' => GameCollection::createAssociative($this->gameRepository->findPaid()),
            'free' => GameCollection::createAssociative($this->gameRepository->findFree()),
            'physical' => $this->getPhysical(),
            'sold' => GameCollection::createAssociative($this->gameRepository->findBy(['status' => 'Sold'], ['name' => 'ASC'])),
            'unavailable' => GameCollection::createAssociative($this->gameRepository->findUnavailable()),
            'xb1' => GameCollection::createAssociative($this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_XB1], ['name' => 'ASC'])),
            '360' => GameCollection::createAssociative($this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_360], ['name' => 'ASC'])),
            'xsx' => GameCollection::createAssociative($this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_XSX], ['name' => 'ASC'])),
            'win' => GameCollection::createAssociative($this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_WIN], ['name' => 'ASC'])),
            'bc' => $this->getBackwardsCompatible(),
            'nonbc' => $this->getNonBackwardsCompatible(),
            'nonbckinect' => $this->getNonBcWithKinectRequired(),
            'nonbcperiph' => $this->getNonBcWithPeripheralRequired(),
            'nonbconline' => $this->getNonBcWithOnlineMultiplayer(),
            'walkthrough' => GameCollection::createAssociative($this->gameRepository->findWithWalkthrough()),
            'nowalkthrough' => GameCollection::createAssociative($this->gameRepository->findBy(['walkthroughUrl' => ''], ['name' => 'ASC'])),
            'nodlc' => GameCollection::createAssociative($this->gameRepository->findBy(['hasDlc' => 0], ['name' => 'ASC'])),
            'withdlc' => GameCollection::createAssociative($this->gameRepository->findBy(['hasDlc' => 1], ['name' => 'ASC'])),
            'dlccompleted' => $this->getDlcCompleted(),
            'dlcnotcompleted' => GameCollection::createAssociative($this->gameRepository->findNotCompletedDlc()),
            default => throw new InvalidFilterException(sprintf('Error: filter "%s" is invalid.', $filter)),
        };
    }

    private function getDlcCompleted(): GameCollection
    {
        return GameCollection::createAssociative($this->gameRepository->findBy(
            ['hasDlc' => 1, 'dlcCompletionPercentage' => 100],
            ['name' => 'ASC']
        ));
    }

    private function getBackwardsCompatible(): GameCollection
    {
        return GameCollection::createAssociative($this->gameRepository->findBy(
            ['platform' => PlatformEnum::PLATFORM_360, 'backwardsCompatible' => 1],
            ['name' => 'ASC']
        ));
    }

    private function getNonBackwardsCompatible(): GameCollection
    {
        return GameCollection::createAssociative($this->gameRepository->findBy(
            ['platform' => PlatformEnum::PLATFORM_360, 'backwardsCompatible' => 0],
            ['name' => 'ASC']
        ));
    }

    private function getNonBcWithKinectRequired(): GameCollection
    {
        return GameCollection::createAssociative($this->gameRepository->findBy(
            ['platform' => PlatformEnum::PLATFORM_360, 'backwardsCompatible' => 0, 'kinectRequired' => 1],
            ['name' => 'ASC']
        ));
    }

    private function getNonBcWithPeripheralRequired(): GameCollection
    {
        return GameCollection::createAssociative($this->gameRepository->findBy(
            ['platform' => PlatformEnum::PLATFORM_360, 'backwardsCompatible' => 0, 'peripheralRequired' => 1],
            ['name' => 'ASC']
        ));
    }

    private function getNonBcWithOnlineMultiplayer(): GameCollection
    {
        return GameCollection::createAssociative($this->gameRepository->findBy(
            ['platform' => PlatformEnum::PLATFORM_360, 'backwardsCompatible' => 0, 'onlineMultiplayer' => 1],
            ['name' => 'ASC']
        ));
    }

    private function getPhysical(): GameCollection
    {
        return GameCollection::createAssociative($this->gameRepository->findBy(
            ['format' => [FormatEnum::FORMAT_DISC, FormatEnum::FORMAT_BOTH, 'Sold']],
            ['name' => 'ASC']
        ));
    }

    private function getGamesByLongest(): GameCollection
    {
        $games = GameCollection::createAssociative($this->gameRepository->findLongest());
        return $games->usort(function(Game $a, Game $b) {
            if ($a->getCompletionEstimate() !== $b->getCompletionEstimate()) {
                return intval($b->getCompletionEstimate()) <=> intval($a->getCompletionEstimate());
            } else {
                return $a->getName() <=> $b->getName();
            }
        });
    }

    private function getGamesByMostPlayed(): GameCollection
    {
        //get all started games and remove all played <100 horus
        $games = GameCollection::createAssociative($this->gameRepository->findPlayed());
        $games = $games->filter(function (Game $game): bool {
            $hoursPlayed = $game->getHoursPlayed();
            //if hoursPlayed is blank and game is completed, use completion estimate as hoursPlayed
            if ($hoursPlayed === 0.0 && $game->getCompletionPercentage() === 100) {
                $hoursPlayed = intval($game->getCompletionEstimate());
            }
            return $hoursPlayed >= 80;
        });

        //sort by hours played, or if missing (and game is completed) by comp estimate
        return $games->usort(function(Game $a, Game $b) {
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
    }

    private function getGamesByEasiest(): GameCollection
    {
        $games = GameCollection::createAssociative($this->gameRepository->findWithNonZeroTaTotal());
        $games = $games->filter(function(Game $game) {
            return $game->getTaTotal() / $game->getGamerscoreTotal() < 2;
        });

        return $games->usort(function(Game $a, Game $b) {
            $aRatio = $a->getTaTotal() / $a->getGamerscoreTotal();
            $bRatio = $b->getTaTotal() / $b->getGamerscoreTotal();
            if (number_format($bRatio, 2) === number_format($aRatio, 2)) {
                return $a->getName() <=> $b->getName();
            } else {
                return $aRatio <=> $bRatio;
            }
        });
    }

    private function getGamesByHardest(): GameCollection
    {
        $games = GameCollection::createAssociative($this->gameRepository->findWithNonZeroTaTotal());
        $games = $games->filter(function(Game $game) {
            return $game->getTaTotal() / $game->getGamerscoreTotal() > 5;
        });

        return $games->usort(function(Game $a, Game $b) {
            $aRatio = $a->getTaTotal() / $a->getGamerscoreTotal();
            $bRatio = $b->getTaTotal() / $b->getGamerscoreTotal();
            if (number_format($bRatio, 2) == number_format($aRatio, 2)) {
                return $a->getName() <=> $b->getName();
            } else {
                return $bRatio <=> $aRatio;
            }
        });
    }
}
