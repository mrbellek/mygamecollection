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
    
    public function isAltVersion(): bool
    {
        return !is_null($this->altForId);
    }

    public function getAltForId(): ?int
    {
        return $this->altForId;
    }
}
