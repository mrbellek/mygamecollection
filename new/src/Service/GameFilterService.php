<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Enum\Format as FormatEnum;
use App\Enum\Platform as PlatformEnum;
use App\Enum\Status as StatusEnum;
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
            'physical' => $this->gameRepository->findBy(['format' => [FormatEnum::FORMAT_DISC, FormatEnum::FORMAT_BOTH, 'Sold']], ['name' => 'ASC']),
            'sold' => $this->gameRepository->findBy(['format' => 'Sold'], ['name' => 'ASC']), //@TODO this is wrong
            'unavailable' => $this->gameRepository->findUnavailable(),
            'xb1' => $this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_XB1], ['name' => 'ASC']),
            '360' => $this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_360], ['name' => 'ASC']),
            'xsx' => $this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_XSX], ['name' => 'ASC']),
            'win' => $this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_WIN], ['name' => 'ASC']),
            'bc' => $this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_360, 'backwardsCompatible' => 1], ['name' => 'ASC']),
            'nonbc' => $this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_360, 'backwardsCompatible' => 0], ['name' => 'ASC']),
            'nonbckinect' => $this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_360, 'backwardsCompatible' => 0, 'kinectRequired' => 1], ['name' => 'ASC']),
            'nonbcperiph' => $this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_360, 'backwardsCompatible' => 0, 'peripheralRequired' => 1], ['name' => 'ASC']),
            'nonbconline' => $this->gameRepository->findBy(['platform' => PlatformEnum::PLATFORM_360, 'backwardsCompatible' => 0, 'onlineMultiplayer' => 1], ['name' => 'ASC']),
            'walkthrough' => $this->gameRepository->findWithWalkthrough(),
            'nowalkthrough' => $this->gameRepository->findBy(['walkthroughUrl' => ''], ['name' => 'ASC']),
            'nodlc' => $this->gameRepository->findBy(['hasDlc' => 0], ['name' => 'ASC']),
            'withdlc' => $this->gameRepository->findBy(['hasDlc' => 1], ['name' => 'ASC']),
            'dlccompleted' => $this->gameRepository->findBy(['hasDlc' => 1, 'dlcCompletionPercentage' => 100], ['name' => 'ASC']),
            'dlcnotcompleted' => $this->gameRepository->findNotCompletedDlc(),
            default => throw new InvalidArgumentException(sprintf('Error: filter "%s" is invalid.', $filter)),
        };
    }

    private function getGamesByLongest(): array
    {
        $games = $this->gameRepository->findLongest();
        usort($games, function(Game $a, Game $b) {
            if ($a->getCompletionEstimate() !== $b->getCompletionEstimate()) {
                return $a->getCompletionEstimate() <=> $b->getCompletionEstimate();
            } else {
                return $a->getName() <=> $b->getName();
            }
        });

        return $games;
    }

    private function getGamesByMostPlayed(): array
    {
        $games = $this->gameRepository->findAll();
        usort($games, function(Game $a, Game $b) {
            $hoursPlayedA = $a->getHoursPlayed();
            if ($hoursPlayedA === 0 && $a->getCompletionPercentage() === 100) {
                $hoursPlayedA = intval($a->getCompletionEstimate());
            }
            $hoursPlayedB = $b->getHoursPlayed();
            if ($hoursPlayedB === 0 && $b->getCompletionPercentage() === 100) {
                $hoursPlayedB = intval($b->getCompletionEstimate());
            }
            return $hoursPlayedB <=> $hoursPlayedA;
        });

        return $games;
    }

    private function getGamesByEasiest(): array
    {
        $games = array_filter($this->gameRepository->findWithNonZeroTaTotal(), function(Game $game) {
            return $game->getTaTotal() / $game->getGamerscoreTotal() < 2;
        });
        usort($games, function(Game $a, Game $b) {
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

    private function getGamesByHardest(): array
    {
        $games = array_filter($this->gameRepository->findWithNonZeroTaTotal(), function(Game $game) {
            return $game->getTaTotal() / $game->getGamerscoreTotal() > 5;
        });
        usort($games, function(Game $a, Game $b) {
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
