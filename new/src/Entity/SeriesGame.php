<?php
declare(strict_types=1);

/**
 * @TODO:
 * - better workaround for doctrine lazy loading
 */

namespace App\Entity;

use App\Entity\Game;
use App\Entity\Series;
use App\Repository\SeriesGameRepository;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SeriesGameRepository::class)]
#[ORM\Table(name: "series_setlist_games")]
class SeriesGame
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(name: "game_id")]
    private int $gameId;

    #[ORM\Column]
    private string $name;

    #[ORM\Column(name: "setlist_id")]
    private int $setlistId;

    #[ORM\Column(name: "alt_for", nullable: true)]
    private ?int $altForId = null;

    #[ORM\ManyToOne(targetEntity: SeriesGame::class)]
    #[ORM\JoinColumn(name: 'alt_for', referencedColumnName: 'id')]
    private ?SeriesGame $altFor = null;

    #[ORM\ManyToOne(targetEntity: Series::class, inversedBy: 'seriesGames')]
    #[ORM\JoinColumn(name: 'setlist_id', referencedColumnName: 'id')]
    private Series $series;

    #[ORM\OneToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(name: 'game_id', referencedColumnName: 'id', nullable: true)]
    private ?Game $game = null;

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
        return $this->setlistId;
    }
    
    public function getAltForId(): ?int
    {
        return $this->altForId;
    }

    public function getSeries(): Series
    {
        return $this->series;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getAltFor(): ?SeriesGame
    {
        return $this->altFor;
    }

    /**
     * This is quick and dirty and not at all a permanent solution to
     * Doctrine's lazy loading. This relationship can be NULL!
     */
    public function isInCollection(): bool
    {
        try {
            $this->game->getName();
            return true;
        } catch (EntityNotFoundException) {
            return false;
        }
    }

    public function setGameId(int $id): self
    {
        $this->gameId = $id;

        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setSetlistId(int $setlistId): self
    {
        $this->setlistId = $setlistId;

        return $this;
    }

    public function setAltForId(?int $id): self
    {
        $this->altForId = $id;

        return $this;
    }
}
