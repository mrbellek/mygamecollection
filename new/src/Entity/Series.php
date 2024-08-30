<?php
declare(strict_types=1);

/**
 * @TODO:
 * - status enum
 * - relationship to games
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\SeriesRepository;

#[ORM\Entity(repositoryClass: SeriesRepository::class)]
#[ORM\Table(name: "series_setlist")]
class Series
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column]
    private string $name;

    #[ORM\Column(name: "user_title")]
    private string $userTitle;

    #[ORM\Column]
    private string $status;

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

    public function getGames(): array //@TODO GameCollection
    {
        return [];
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setUserTitle(string $userTitle): self
    {
        $this->userTitle = $userTitle;

        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}