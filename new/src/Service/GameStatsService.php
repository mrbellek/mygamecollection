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
        $totalPurchased = $games->reduce(function (?float $sum, Game $game) {
            return $sum + $game->getPurchasedPrice();
        });
        $totalCurrentValue = $games->reduce(function (?float $sum, Game $game) {
            return $sum + $game->getCurrentPrice();
        });

        return [
            'on_sale' => $games->filterCount(function(Game $game) {
                return $game->getStatus() === StatusEnum::STATUS_SALE;
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
            'total_purchased' => $totalPurchased,
            'total_currentvalue' => $totalCurrentValue,
            'total_saved' => $totalCurrentValue - $totalPurchased,
            'average_purchased' => $games->count() > 0 ? $totalPurchased / $games->count() : 0,
            'average_value' => $games->count() > 0 ? $totalCurrentValue / $games->count() : 0,
            'total_playtime' => $games->reduce(function (?int $sum, Game $game) {
                return $sum + intval($game->getCompletionEstimate());
            }),
            'spent_playtime' => $games->reduce(function(?float $sum, Game $game) {
                return $sum + $game->getHoursPlayed();
            }),

            'spent_week' => $games->reduce(function (?float $sum, Game $game) {
                return $sum + ($game->getCreated() > new DateTime('-1 week') ? $game->getPurchasedPrice() : 0);
            }),
            'spent_month' => $games->reduce(function (?float $sum, Game $game) {
                return $sum + ($game->getCreated() > new DateTime('-1 month') ? $game->getPurchasedPrice() : 0);
            }),
            'spent_6month' => $games->reduce(function (?float $sum, Game $game) {
                return $sum + ($game->getCreated() > new DateTime('-6 month') ? $game->getPurchasedPrice() : 0);
            }),
            'spent_year' => $games->reduce(function (?float $sum, Game $game) {
                return $sum + ($game->getCreated() > new DateTime('-1 year') ? $game->getPurchasedPrice() : 0);
            }),
            'spent_week_tooltip' => [], //@TODO list of games
            'spent_month_tooltip' => [], //@TODO list of games
            'spent_6month_tooltip' => [], //@TODO list of games
        ];
    }
}
