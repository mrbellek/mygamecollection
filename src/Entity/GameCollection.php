<?php
declare(strict_types=1);

namespace App\Entity;

use App\Entity\Game;
use Closure;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * A collection of games, not always the entire library (my game collection on TA)
 * 
 * we extend Doctrine ArrayColletion because it has a ton of useful functions,
 * like first, offsetGet, offsetExists, count, map, reduce and filter.
 */
class GameCollection extends ArrayCollection
{
    /**
     * @param array<Game> $games
     */
    public static function createAssociative(array $games): self
    {
        $elements = [];
        /** @var Game $game **/
        foreach ($games as $game) {
            $elements[$game->getId()] = $game;
        }

        return new self($elements);
    }

    public function filterCount(Closure $callable): int
    {
        return $this->filter($callable)->count();
    }
    
    public function usort(Closure $callable): GameCollection
    {
        $elements = $this->toArray();
        usort($elements, $callable);

        return new self($elements);
    }
}
