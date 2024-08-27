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
}
