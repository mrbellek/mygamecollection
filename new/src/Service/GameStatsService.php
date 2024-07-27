<?php
declare(strict_types=1);

namespace App\Service;

/**
 * @TODO:
 * - split up into functions for generic, completion, playtime, money spent etc
 * - finish tooltips for money spent
 */
use App\Entity\Game;
use App\Entity\GameCollection;
use App\Enum\Status as StatusEnum;
use DateTime;

class GameStatsService
{
    public function getStats(GameCollection $games): array
    {
        $totalPurchased = array_sum(array_map(function(Game $game) {
            return $game->getPurchasedPrice();
        }, $games->toArray()));
        $totalCurrentValue = array_sum(array_map(function(Game $game) {
            return $game->getCurrentPrice();
        }, $games->toArray()));

        return [
            'on_sale' => $games->filter(function(Game $game) {
                return $game->getStatus() === StatusEnum::STATUS_SALE;
            })->count(),
            'delisted' => $games->filter(function(Game $game) {
                return $game->getStatus() === StatusEnum::STATUS_DELISTED;
            })->count(),
            'free' => $games->filter(function(Game $game) {
                return $game->getCurrentPrice() == 0;
            })->count(),
            'purchased_free' => $games->filter(function(Game $game) {
                return $game->getPurchasedPrice() == 0;
            })->count(),
            'total_purchased' => $totalPurchased,
            'total_currentvalue' => $totalCurrentValue,
            'total_saved' => $totalCurrentValue - $totalPurchased,
            'average_purchased' => $games->count() > 0 ? $totalPurchased / $games->count() : 0,
            'average_value' => $games->count() > 0 ? $totalCurrentValue / $games->count() : 0,
            'total_playtime' => array_sum(array_map(function(Game $game) {
                return $game->getCompletionEstimate();
            }, $games->toArray())),
            'spent_playtime' => array_sum(array_map(function(Game $game) {
                return $game->getHoursPlayed();
            }, $games->toArray())),

            'spent_week' => array_sum(array_map(function (Game $game) {
                return $game->getCreated() > new DateTime('-1 week') ? $game->getPurchasedPrice() : 0;
            }, $games->toArray())),
            'spent_month' => array_sum(array_map(function (Game $game) {
                return $game->getCreated() > new DateTime('-1 month') ? $game->getPurchasedPrice() : 0;
            }, $games->toArray())),
            'spent_6month' => array_sum(array_map(function (Game $game) {
                return $game->getCreated() > new DateTime('-6 month') ? $game->getPurchasedPrice() : 0;
            }, $games->toArray())),
            'spent_year' => array_sum(array_map(function (Game $game) {
                return $game->getCreated() > new DateTime('-1 year') ? $game->getPurchasedPrice() : 0;
            }, $games->toArray())),
            'spent_week_tooltip' => [], //@TODO list of games
            'spent_month_tooltip' => [], //@TODO list of games
            'spent_6month_tooltip' => [], //@TODO list of games
        ];
    }
}
