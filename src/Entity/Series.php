<?php
declare(strict_types=1);

/**
 * @TODO:
 * - status enum
 */

namespace App\Entity;

use App\Entity\SeriesGame;
use App\Repository\SeriesRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

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

    #[ORM\OneToMany(targetEntity: SeriesGame::class, mappedBy: 'series')]
    private Collection $seriesGames;

    public function __construct()
    {
        $this->seriesGames = new ArrayCollection();
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

    public function getGames(): Collection
    {
        return $this->seriesGames;
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