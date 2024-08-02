<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Entity\GameCollection;
use App\Enum\Status as StatusEnum;
use DateTime;

class GameStatsService
{
    /**
     * @return array<string|int|float>
     */
    public function getStats(GameCollection $games): array
    {
        return array_merge(
            $this->getGenericStats($games),
            $this->getPurchasedStats($games),
            $this->getSpentStats($games),
        );
    }

    /**
     * @return array<float|int>
     */
    private function getGenericStats(GameCollection $games): array
    {
        return [
            'total_playtime' => $games->reduce(function (?int $sum, Game $game) {
                return $sum + intval($game->getCompletionEstimate());
            }),
            'spent_playtime' => $games->reduce(function(?float $sum, Game $game) {
                return $sum + $game->getHoursPlayed();
            }),

            'delisted' => $games->filterCount(function(Game $game) {
                return $game->getStatus() === StatusEnum::STATUS_DELISTED;
            }),
            'free' => $games->filterCount(function(Game $game) {
                return $game->getCurrentPrice() == 0;
            }),
            'purchased_free' => $games->filterCount(function(Game $game) {
                return $game->getPurchasedPrice() == 0;
            }),
        ];
    }

    /**
     * @return array<float>
     */
    private function getPurchasedStats(GameCollection $games): array
    {
        $totalPurchased = $games->reduce(function (?float $sum, Game $game) {
            return $sum + $game->getPurchasedPrice();
        });

        return [
            'total_purchased' => $totalPurchased,
            'average_purchased' => $games->count() > 0 ? $totalPurchased / $games->count() : 0,
        ];
    }

    /**
     * @return array<string|float>
     */
    private function getSpentStats(GameCollection $games): array
    {
        $gamesBoughtLastWeek = $games->filter(function (Game $game) {
            return $game->getCreated() > new DateTime('-1 week');
        });
        $gamesBoughtLastMonth = $games->filter(function (Game $game) {
            return $game->getCreated() > new DateTime('-1 month');
        });
        $gamesBoughtLastSixMonths = $games->filter(function (Game $game) {
            return $game->getCreated() > new DateTime('-6 month');
        });
        $gamesBoughtLastYear = $games->filter(function (Game $game) {
            return $game->getCreated() > new DateTime('-1 year');
        });

        return [
            'most_expensive_purchase' => $this->getMostExpensivePurchase($games),
            'spent_week' => $gamesBoughtLastWeek->reduce(function (?float $sum, Game $game) {
                return $sum + $game->getPurchasedPrice();
            }),
            'spent_month' => $gamesBoughtLastMonth->reduce(function (?float $sum, Game $game) {
                return $sum + $game->getPurchasedPrice();
            }),
            'spent_6month' => $gamesBoughtLastSixMonths->reduce(function (?float $sum, Game $game) {
                return $sum + $game->getPurchasedPrice();
            }),
            'spent_year' => $gamesBoughtLastYear->reduce(function (?float $sum, Game $game) {
                return $sum + $game->getPurchasedPrice();
            }),

            'spent_week_tooltip' => $gamesBoughtLastWeek->count() <= 10 ? $gamesBoughtLastWeek->reduce(
                function (?array $list, Game $game) {
                    $list[] = $game->getName();
                    return $list;
                }) : ['over 10 games!'],
            'spent_month_tooltip' => $gamesBoughtLastMonth->count() <= 10 ? $gamesBoughtLastMonth->reduce(
                function (?array $list, Game $game) {
                    $list[] = $game->getName();
                    return $list;
                }) : ['over 10 games!'],
            'spent_6month_tooltip' => $gamesBoughtLastSixMonths->count() <= 10 ? $gamesBoughtLastSixMonths->reduce(
                function (?array $list, Game $game) {
                    $list[] = $game->getName();
                    return $list;
                }) : ['over 10 games!'],
        ];
    }

    private function getMostExpensivePurchase(GameCollection $games): ?Game
    {
        $mostExpensiveGame = null;
        /** @var Game $game **/
        foreach ($games as $game) {
            if ($game->getPurchasedPrice() > $mostExpensiveGame?->getPurchasedPrice()) {
                $mostExpensiveGame = $game;
            }
        }

        return $mostExpensiveGame;
    }
}
