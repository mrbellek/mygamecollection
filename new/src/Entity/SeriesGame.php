<?php
declare(strict_types=1);

/**
 * @TODO:
 * - relationship to series
 * - relationship to altfor
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\SeriesGameRepository;

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
