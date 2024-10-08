<?php
declare(strict_types=1);

namespace App\Resources;

use App\Entity\Series;
use App\Entity\SeriesGame;

/**
 * Basically the same as the Series entity, but with two extra calculated
 * properties that we need in the template.
 */
class FormSeries
{
    private int $id;
    private string $name;
    private string $userTitle;
    private string $status;
    private int $gamesCount = 0;
    private int $ownedGamesCount = 0;
    private float $completionPercentage = 0.0;

    public function __construct(Series $series)
    {
        $this->id = $series->getId();
        $this->name = $series->getName();
        $this->userTitle = $series->getUserTitle();
        $this->status = $series->getStatus();

        $this->gamesCount = count($series->getGames());
        $totalGamesExcludingAlts = 0;
        /** @var SeriesGame $seriesGame **/
        foreach ($series->getGames() as $seriesGame) {
            if ($seriesGame->getAltForId() === null || $seriesGame->getAltForId() === 0) {
                $totalGamesExcludingAlts++;
            }
            if ($seriesGame->isInCollection()) {
                $this->ownedGamesCount++;
                $this->completionPercentage += $seriesGame->getGame()->getCompletionPercentage();
            }
        }
        $this->completionPercentage = $this->gamesCount > 0 ? $this->completionPercentage / $totalGamesExcludingAlts : 0;
    }

    public function getId(): int
    {
        return $this->id;
    }
    
    public function getName(): string
    {
        return $this->name;
    }

    public function getUserTitle(): string
    {
        return $this->userTitle;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStatusClass(): string
    {
        return match($this->status) {
            'listed' => 'green',
            'unlisted' => 'red',
            'franchise' => 'cyan',
            'subfranchise' => 'yellow',
            'crossover' => 'orange',
            'community' => 'pink',
            'legacy' => 'black',
            default => 'white',
        };
    }

    public function getGamesCount(): int
    {
        return $this->gamesCount;
    }

    public function getOwnedGamesCount(): int
    {
        return $this->ownedGamesCount;
    }

    public function getCompletionPercentage(): float
    {
        return $this->completionPercentage;
    }
}