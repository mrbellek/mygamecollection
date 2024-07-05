<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use DateTime;

class GameStatsService
{
    // @TODO split up into functions for generic, completion, playtime, money spent etc
    public function getStats(array $games): array
    {
        $totalPurchased = array_sum(array_map(function(Game $game) {
            return $game->getPurchasedPrice();
        }, $games));
        $totalCurrentValue = array_sum(array_map(function(Game $game) {
            return $game->getCurrentPrice();
        }, $games));

        return [
            'on_sale' => count(array_filter($games, function(Game $game) {
                return $game->getStatus() === Game::STATUS_SALE;
            })),
            'delisted' => count(array_filter($games, function(Game $game) {
                return $game->getStatus() === Game::STATUS_DELISTED;
            })),
            'free' => count(array_filter($games, function(Game $game) {
                return $game->getCurrentPrice() == 0;
            })),
            'purchased_free' => count(array_filter($games, function(Game $game) {
                return $game->getPurchasedPrice() == 0;
            })),
            'total_purchased' => array_sum(array_map(function(Game $game) {
                return $game->getPurchasedPrice();
            }, $games)),
            'total_currentvalue' => array_sum(array_map(function(Game $game) {
                return $game->getCurrentPrice();
            }, $games)),
            'total_saved' => $totalCurrentValue - $totalPurchased,
            'average_purchased' => $totalPurchased / count($games),
            'average_value' => $totalCurrentValue / count($games),
            'total_playtime' => array_sum(array_map(function(Game $game) {
                return $game->getCompletionEstimate();
            }, $games)),
            'spent_playtime' => array_sum(array_map(function(Game $game) {
                return $game->getHoursPlayed();
            }, $games)),

            'spent_week' => array_sum(array_map(function (Game $game) {
                return $game->getCreated() > new DateTime('-1 week') ? $game->getPurchasedPrice() : 0;
            }, $games)),
            'spent_month' => array_sum(array_map(function (Game $game) {
                return $game->getCreated() > new DateTime('-1 month') ? $game->getPurchasedPrice() : 0;
            }, $games)),
            'spent_6month' => array_sum(array_map(function (Game $game) {
                return $game->getCreated() > new DateTime('-6 month') ? $game->getPurchasedPrice() : 0;
            }, $games)),
            'spent_year' => array_sum(array_map(function (Game $game) {
                return $game->getCreated() > new DateTime('-1 year') ? $game->getPurchasedPrice() : 0;
            }, $games)),
            'spent_week_tooltip' => [], //@TODO list of games
            'spent_month_tooltip' => [], //@TODO list of games
            'spent_6month_tooltip' => [], //@TODO list of games
        ];
    }
}
