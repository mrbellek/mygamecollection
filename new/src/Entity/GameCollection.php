<?php
declare(strict_types=1);

namespace App\Entity;

/**
 * @TODO:
 * - change filter() to filterCount() if that's the only thing I use it for
 * - add map() or mapCount() maybe
 * - extend from Doctrine Collection?
 */
use App\Entity\Game;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * A collection of games, not always the entire library (my game collection on TA)
 */
class GameCollection
{
    private array $games;

    public function __construct(iterable $games)
    {
        $this->games = iterator_to_array($games);
    }

    public function getIterator()
    {
        //@TODO this doesn't seem to work. implement IteratorAggregate?
        return new ArrayCollection($this->games);
    }

    public function toArray(): array
    {
        return $this->games;
    }

    public function getItem(int $gameId): ?Game
    {
        return $this->games[$gameId] ?? null;
    }

    public function hasItem(int $gameId): bool
    {
        return array_key_exists($gameId, $this->games);
    }

    public function count(): int
    {
        return count($this->games);
    }

    public function filter(callable $callback): GameCollection
    {
        return new self(array_filter($this->games, $callback));
    }

    public function usort(callable $callable): void
    {
        usort($this->games, $callable);
    }
}
