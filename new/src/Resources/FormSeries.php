<?php
declare(strict_types=1);

namespace App\Resources;

use App\Entity\Game;
use App\Entity\Series;

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
    private int $completionPercentage = 0;

    public function __construct(Series $series)
    {
        $this->id = $series->getId();
        $this->name = $series->getName();
        $this->userTitle = $series->getUserTitle();
        $this->status = $series->getStatus();

        $this->gamesCount = count($series->getGames());
        /** @var Game $game **/
        foreach ($series->getGames() as $game) {
            $this->completionPercentage += $game->getCompletionPercentage();
        }
        $this->completionPercentage = $this->gamesCount > 0 ? $this->completionPercentage / $this->gamesCount : 0;
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

    public function getGamesCount(): int
    {
        return $this->gamesCount;
    }

    public function getCompletionPercentage(): float
    {
        return $this->completionPercentage;
    }
}