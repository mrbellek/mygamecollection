<?php
declare(strict_types=1);

namespace App\Factory;

use App\Entity\Game;
use DateTime;

class GameFactory
{
    public function create(
        int $gameId,
        string $name,
        string $platform,
        ?bool $backwardsCompatible,
        ?bool $kinectRequired,
        ?bool $peripheralRequired,
        ?bool $onlineMultiplayer,
        int $completionPercentage,
        string $completionEstimate,
        float $hoursPlayer,
        int $achievementsWon,
        int $achievementsTotal,
        int $gamerscoreWon,
        int $gamerscoreTotal,
        int $taScore,
        int $taTotal,
        bool $hasDlc,
        int $dlcCompletionPercentage,
        ?DateTime $completionDate,
        float $siteRating,
        string $format,
        string $status,
        ?float $purchasedPrice,
        ?float $currentPrice,
        ?float $regularPrice,
        int $shortlistOrder,
        ?string $walkthroughUrl,
        string $gameUrl,
        DateTime $lastModified,
        DateTime $created,
    ): Game {
        return new Game(
            $gameId,
            $name,
            $platform,
            $backwardsCompatible,
            $kinectRequired,
            $peripheralRequired,
            $onlineMultiplayer,
            $completionPercentage,
            $completionEstimate,
            $hoursPlayer,
            $achievementsWon,
            $achievementsTotal,
            $gamerscoreWon,
            $gamerscoreTotal,
            $taScore,
            $taTotal,
            $hasDlc,
            $dlcCompletionPercentage,
            $completionDate,
            $siteRating,
            $format,
            $status,
            $purchasedPrice,
            $currentPrice,
            $regularPrice,
            $shortlistOrder,
            $walkthroughUrl,
            $gameUrl,
            $lastModified,
            $created,
        );
    }
}