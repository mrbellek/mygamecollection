<?php
declare(strict_types=1);

namespace App\Resources;

use App\Entity\SeriesGame;

class FormSeriesGame
{
    private int $id;
    private int $gameId;
    private int $seriesId;
    private string $name;
    private ?int $altForId = null;
    //private Game $altFor;

    public function __construct(SeriesGame $seriesGame)
    {
        $this->id = $seriesGame->getId();
        $this->gameId = $seriesGame->getGameId();
        $this->name = $seriesGame->getName();
        $this->seriesId = $seriesGame->getSetlistId();
        $this->altForId = $seriesGame->getAltForId();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getGameId(): int
    {
        return $this->gameId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSetlistId(): int
    {
        return $this->seriesId;
    }

    public function getAltForId(): ?int
    {
        return $this->altForId;
    }

    public function isAltVersion(): bool
    {
        return !is_null($this->altForId);
    }

    public function getCompletionPercentage(): float
    {
        return 0.0;
    }
}