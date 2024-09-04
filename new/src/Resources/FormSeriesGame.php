<?php
declare(strict_types=1);

namespace App\Resources;

use App\Entity\Game;
use App\Entity\SeriesGame;

class FormSeriesGame
{
    private int $id;
    private ?int $gameId;
    private int $seriesId;
    private string $name;
    private bool $isInCollection;
    private ?int $altForId = null;
    private ?Game $altFor;
    private float $completionPercentage;
    private ?string $taUrl;

    public function __construct(SeriesGame $seriesGame)
    {
        $this->id = $seriesGame->getId();
        $this->gameId = $seriesGame->getGameId();
        $this->name = $seriesGame->getName();
        $this->seriesId = $seriesGame->getSetlistId();
        $this->altForId = $seriesGame->getAltForId();
        $this->altFor = $seriesGame->getAltFor();
        $this->isInCollection = $seriesGame->isInCollection();
        $this->completionPercentage = $seriesGame->isInCollection() ? $seriesGame->getGame()->getCompletionPercentage() : 0.0;
        $this->taUrl = $seriesGame->isInCollection() ? $seriesGame->getGame()->getGameUrl() : null;

        //@TODO lookup SeriesGame object for games that are linked, but not owned.
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getGameId(): ?int
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

    public function getAltFor(): ?Game
    {
        return $this->altFor;
    }

    public function getAltForName(): ?string
    {
        return 'TODO';
    }

    public function isAltVersion(): bool
    {
        return !is_null($this->altForId);
    }

    public function isInCollection(): bool
    {
        return $this->isInCollection;
    }

    public function getCompletionPercentage(): float
    {
        return $this->completionPercentage;
    }

    public function getTaUrl(): ?string
    {
        return $this->taUrl;
    }
}