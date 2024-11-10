<?php
declare(strict_types=1);

namespace App\Factory;

/**
 * @TODO: add some validation? if the typehints aren't enouguh
 */

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
            $siteRating,
            $format,
            $status,
            $shortlistOrder,
            $walkthroughUrl,
            $gameUrl,
            $lastModified,
            $created,
            $completionDate,
            $purchasedPrice,
            $currentPrice,
            $regularPrice,
        );
    }
}