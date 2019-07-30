<?php
namespace MyGameCollection\Lib\Import;

use MyGameCollection\Lib\Database;
use MyGameCollection\Lib\Game;

class Price
{
    public function importJsonIntoDatabase(Database $oDatabase, string $json) {

        global $oDatabase;

        try {
            $prices = json_decode($json);
        } catch (Exception $e) {
            return ['error' => 'Invalid JSON.'];
        }
        if (!$prices) {
            return ['error' => 'Invalid JSON.'];
        }

        $games_new = [];
        $games_delisted = [];
        $games_unavailabled = [];
        $games_availabled = [];
        $games_discounted = [];
        $games_undiscounted = [];
        $games_unchanged = [];
        $price_drop = [];
        $price_hike = [];
        $total = 0;
        $available = 0;
        $unavailable = 0;
        $sale = 0;
        foreach ($prices as $game) {
            $total++;
            if ($game->status == 'available') {
                $available++;
            } else {
                $unavailable++;
                $prepared_data['price'] = 0;
            }

            if ($game->saleFrom) {
                $sale++;
                //TODO: save original price somewhere?
            }

            if ($changes = Game::hasPriceChanged($oDatabase, $game)) {

                if ($changes['status']) {
                    /**
                     * scenarios:
                     * - game new in collection
                     * - game discounted
                     * - game no longer discounted
                     * - game delisted
                     * - game available again
                     * - game now unavailable
                     */
                    switch (true) {
                        case ($changes['status']['old'] === false): $games_new[] = $game; break;
                        case ($changes['status']['new'] == 'sale'): $games_discounted[] = $game; break;
                        case ($changes['status']['old'] == 'sale'): $games_undiscounted[] = $game; break;
                        case ($changes['status']['new'] == 'delisted'): $games_delisted[] = $game; break;
                        case (in_array($changes['status']['old'], ['delisted', 'unavailable', 'region-locked'])): $games_availabled[] = $game; break;
                        case (in_array($changes['status']['new'], ['unavailable', 'region-locked'])): $games_unavailabled[] = $game; break;
                    }
                }
                if ($changes['price']) {
                    /*
                     * scenarios:
                     * - price changed without sale, game still available
                     * - price dropped to 0, game delisted/unavailable (covered above)
                     */
                    if ($game->status == 'available' && $changes['status']['old'] != 'sale') {
                        if ($changes['price']['new'] > $changes['price']['old']) {
                            $price_hike[] = $game;
                        } else {
                            $price_drop[] = $game;
                        }
                    }
                }

                $oGame = new Game($oDatabase);
                $oGame->getById($game->id);
                if (!$oGame->id) {
                    $oGame->createByPriceData($game);
                } else {
                    $oGame->current_price = $game->price;
                    $oGame->regular_price = ($game->saleFrom ? $game->saleFrom : '');
                    $oGame->status = $game->status;
                    $oGame->save();
                }

            } else {
                $games_unchanged[] = $game;
            }
        }

        return [
            'games_new' => $games_new,
            'games_delisted' => $games_delisted,
            'games_unavailabled' => $games_unavailabled,
            'games_availabled' => $games_availabled,
            'games_discounted' => $games_discounted,
            'games_undiscounted' => $games_undiscounted,
            'games_unchanged' => $games_unchanged,
            'price_drop' => $price_drop,
            'price_hike' => $price_hike,

            'total' => $total,
            'available' => $available,
            'unavailable' => $unavailable,
            'sale' => $sale,
        ];
    }
}
