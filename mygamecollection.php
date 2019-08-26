<?php
namespace MyGameCollection;

use MyGameCollection\Lib\Database;
use MyGameCollection\Lib\Game;
use MyGameCollection\Lib\Logger;
use MyGameCollection\Lib\Request;
use MyGameCollection\Setup\Setup;
use MyGameCollection\Lib\Import\Collection as CollectionImporter;
use MyGameCollection\Lib\Import\Price as PriceImporter;

require_once('func/int2glyph.php');
require_once('func/priceformat.php');

/**
 * CMS page for managing your TrueAchievements game collection.
 * Import the .json from the price scraper, and the game collection csv from TA.
 *
 * TODO:
 * v namespaces and autoloader
 * v shortlist
 * v separate this mess from twitterbot libraries and put it on github
 * v show if dlc also completed
 * x walkthrough urls seems mostly wrong (TA bug reported)
 * - sortable columns
 * - fix crash when importing new games when there's already newly imported games (hardcoded -1 id)
 */

if (!is_readable('mygamecollection.inc.php')) {
    die(sprintf('Include file missing. Please create %s and define DB_HOST, DB_USER, DB_NAME and DB_PASS with your database details,
        and FORM_PASSWORD if you want to password-protect your edits.',
        pathinfo(__FILE__, PATHINFO_FILENAME)
    ));
}

require_once('mygamecollection.inc.php');
require_once('autoloader.php');

$oDatabase = new Database;
$oRequest = new Request;

//check if database connection is ok
if ($oDatabase->connect()) {

    //check if table exists
    if (!$oDatabase->query('SHOW TABLES LIKE "mygamecollection"')) {

        //call setup
        (new Setup($oDatabase))->run();
        exit();
    }
}

$sThisFile = pathinfo(__FILE__, PATHINFO_BASENAME);

$sSearch = $oRequest->getStr('search');
$sShow = $oRequest->getStr('show');

$iPage = max(1, $oRequest->getInt('page'));
$iPerPage = 30;
$iOffset = ($iPage - 1) * $iPerPage;

if ($oRequest->isPost()) {
    if (defined('FORM_PASSWORD') && FORM_PASSWORD && $oRequest->postStr('password') != FORM_PASSWORD) {
        $sError = 'Invalid password.';
    } else {

        //process post
        if ($oRequest->postStr('action') == 'Save') {
            //edit game

            //fetch existing record
            $oGame = new Game($oDatabase);
            $oGame->getById($oRequest->postInt('id'));

            //set new id if needed
            if ($oRequest->postInt('newid')) {
                $oGame->newid = $oRequest->postInt('newid');
            }

            //set rest of the data
            $oGame->backcompat = $oRequest->postStr('backcompat');
            $oGame->kinect_required = $oRequest->postStr('kinect_required');
            $oGame->peripheral_required = $oRequest->postStr('peripheral_required');
            $oGame->online_multiplayer = $oRequest->postStr('online_multiplayer');
            $oGame->purchased_price = $oRequest->postStr('purchased_price');
            $oGame->current_price = $oRequest->postStr('current_price');
            $oGame->shortlist_order = $oRequest->postStr('shortlist_order');

            //save
            if ($oGame->save()) {
                $sSuccess = 'Game updated.';
            } else {
                $sError = 'Updating game failed.';
                $aData = $_POST;
            }

        } elseif ($oRequest->postStr('action') == 'Delete' && $oRequest->postInt('id')) {

            if (defined('FORM_PASSWORD') && FORM_PASSWORD && $oRequest->postStr('password') != FORM_PASSWORD) {
                $sError = 'Invalid password.';
            } else {

                //delete record
                $oGame = new Game($oDatabase);
                $oGame->getById($oRequest->postInt('id'));
                if ($oGame->delete()) {
                    $sSuccess = 'Game deleted.';
                } else {
                    $sError = 'Deleting game failed.';
                    $aData = $_POST;
                }
            }

        } elseif ($oRequest->postStr('action') == 'Import game collection CSV' && $csvFile = $oRequest->file('upload')) {

            $result = (new CollectionImporter)->importCsvIntoDatabase($oDatabase, file($csvFile[0]['tmp_name']));

            if (!isset($result['error'])) {
                $aSuccess[] = 'Import complete!';
                if ($result['new']) {
                    $aSuccess[] = '<br/>The following games were new:<ul>';
                    $aNewGames = $result['new'];
                    usort($aNewGames, function($a, $b) {
                        return strcasecmp($a['Game name'], $b['Game name']);
                    });
                    foreach ($aNewGames as $game) {
                        $aSuccess[] = sprintf('<li><a href="?id=%s">%s</a></li>',
                            $game['id'],
                            $game['Game name']
                        );
                    }
                    $aSuccess[] = '</ul>';
                }

                if ($result['updated']) {
                    $aSuccess[] = '<br/>The following games were updated:<ul>';
                    $aUpdatedGames = $result['new'];
                    usort($aUpdatedGames, function($a, $b) {
                        return strcasecmp($a['Game name'], $b['Game name']);
                    });
                    foreach ($aUpdatedGames as $game) {
                        $aSuccess[] = sprintf('<li><a href="?id=%s">%s</a> (%s)</li>',
                            $game['id'],
                            $game['Game name'],
                            $game['changes']
                        );
                    }
                    $aSuccess[] = '</ul>';
                }
                /*$aSuccess[] = '</ul>The following games were unchanged:<ul>';
                foreach ($result['notupdated'] as $game) {
                    $aSuccess[] = sprintf('<li><a href="?id=%s">%s</a></li>',
                        $game['id'],
                        $game['Game name']
                    );
                }*/
                if ($result['removed']) {
                    $aSuccess[] = '<br/>The following games have disappeared from your collection:<ul>';
                    $aRemovedGames = $result['removed'];
                    usort($aRemovedGames, function($a, $b) {
                        return strcasecmp($a['Game name'], $b['Game name']);
                    });
                    foreach ($aRemovedGames as $game) {
                        $aSuccess[] = sprintf('<li><a href="?id=%s">%s</a></li>',
                            $game['id'],
                            $game['Game name']
                        );
                    }
                    $aSuccess[] = '</ul>';
                }
                $sSuccess = implode(PHP_EOL, $aSuccess);
            } else {
                $sError = $result['error'];
            }

        } elseif ($oRequest->postStr('action') == 'Import prices JSON' && $jsonFile = $oRequest->file('upload')) {

            $result = (new PriceImporter)->importJsonIntoDatabase($oDatabase, file_get_contents($jsonFile[0]['tmp_name']));

            if (!isset($result['error'])) {

                $aSuccess[] = 'Import complete!';
                $aSuccess[] = sprintf('<br/>Out of your %d games, currently %d are available, %d are delisted or unavailable, and %d are on sale.<br>',
                    $result['total'],
                    $result['available'],
                    $result['unavailable'],
                    $result['sale']
                );
                if ($result['games_new']) {
                    $aSuccess[] = 'The following games were <b>new</b> in your collection:<ul>';
                    $aNewGames = $result['games_new'];
                    usort($aNewGames, function($a, $b) {
                        return strcasecmp($a->name, $b->name);
                    });
                    foreach ($aNewGames as $game) {
                        $aSuccess[] = sprintf('<li><a href="%s" target="_blank">%s</a></li>', $game->url, $game->name);
                    }
                    $aSuccess[] = '</ul>';
                }
                if ($result['games_delisted']) {
                    $aSuccess[] = 'The following games were <b>delisted</b>:<ul>';
                    $aDelistedGames = $result['games_delisted'];
                    usort($aDelistedGames, function($a, $b) {
                        return strcasecmp($a->name, $b->name);
                    });
                    foreach ($aDelistedGames as $game) {
                        $aSuccess[] = sprintf('<li><a href="%s" target="_blank">%s</a></li>', $game->url, $game->name);
                    }
                    $aSuccess[] = '</ul>';
                }
                if ($result['games_unavailabled']) {
                    $aSuccess[] = 'The following games are now <b>unavailable</b>:<ul>';
                    $aUnavailableGames = $result['games_unavailabled'];
                    usort($aUnavailableGames, function($a, $b) {
                        return strcasecmp($a->name, $b->name);
                    });
                    foreach ($aUnavailableGames as $game) {
                        $aSuccess[] = sprintf('<li><a href="%s" target="_blank">%s</a> (%s)</li>', $game->url, $game->name, $game->status);
                    }
                    $aSuccess[] = '</ul>';
                }
                if ($result['games_availabled']) {
                    $aSuccess[] = 'The following games are now <b>available</b> again:<ul>';
                    $aAvailableGames = $result['games_availabled'];
                    usort($aAvailableGames, function($a, $b) {
                        return strcasecmp($a->name, $b->name);
                    });
                    foreach ($aAvailableGames as $game) {
                        $aSuccess[] = sprintf('<li><a href="%s" target="_blank">%s</a></li>', $game->url, $game->name);
                    }
                    $aSuccess[] = '</ul>';
                }
                if ($result['games_discounted']) {
                    $aSuccess[] = 'The following games were <b>discounted</b>:<ul>';
                    $aDiscountedGames = $result['games_discounted'];
                    usort($aDiscountedGames, function($a, $b) {
                        return strcasecmp($a->name, $b->name);
                    });
                    foreach ($aDiscountedGames as $game) {
                        $aSuccess[] = sprintf('<li><a href="%s" target="_blank">%s</a> %s</li>', $game->url, $game->name, ($game->status != 'sale' ? '<b>(not a sale)</b>' : ''));
                    }
                    $aSuccess[] = '</ul>';
                }
                if ($result['games_undiscounted']) {
                    $aSuccess[] = 'The following games are <b>no longer discounted</b>:<ul>';
                    $aUndiscountedGames = $result['games_undiscounted'];
                    usort($aUndiscountedGames, function($a, $b) {
                        return strcasecmp($a->name, $b->name);
                    });
                    foreach ($aUndiscountedGames as $game) {
                        $aSuccess[] = sprintf('<li><a href="%s" target="_blank">%s</a></li>', $game->url, $game->name);
                    }
                    $aSuccess[] = '</ul>';
                }
                if ($result['price_drop']) {
                    $aSuccess[] = 'The following games <b>dropped in price</b> outside of a sale:<ul>';
                    $aPriceDropGames = $result['price_drop'];
                    usort($aPriceDropGames, function($a, $b) {
                        return strcasecmp($a->name, $b->name);
                    });
                    foreach ($aPriceDropGames as $game) {
                        $aSuccess[] = sprintf('<li><a href="%s" target="_blank">%s</a> (%s)</li>', $game->url, $game->name, $game->price);
                    }
                    $aSuccess[] = '</ul>';
                }
                if ($result['price_hike']) {
                    $aSuccess[] = 'The following games <b>increased in price</b> outside of a sale:<ul>';
                    $aPriceHikeGames = $result['price_hike'];
                    usort($aPriceHikeGames, function($a, $b) {
                        return strcasecmp($a->name, $b->name);
                    });
                    foreach ($aPriceHikeGames as $game) {
                        $aSuccess[] = sprintf('<li><a href="%s" target="_blank">%s</a> (%s)</li>', $game->url, $game->name, $game->price);
                    }
                    $aSuccess[] = '</ul>';
                }
                /*if ($result['games_unchanged']) {
                    $aSuccess[] = 'The following games were unchanged:<ul>';
                    foreach ($result['games_unchanged'] as $game) {
                        $aSuccess[] = sprintf('<li><a href="%s" target="_blank">%s</a></li>', $game->url, $game->name);
                    }
                    $aSuccess[] = '</ul>';
                }*/

                $sSuccess = implode(PHP_EOL, $aSuccess);
            } else {
                $sError = $result['error'];
            }
        }
    }
}

if ($oRequest->getStr('shortlistadd')) {
    //add to shortlist, at the bottom
    $oGame = new Game($oDatabase);
    $oGame->getById($oRequest->getStr('shortlistadd'));
    if ($oGame) {
        $oGame->addToShortlist();
        $oGame->save();
        $sSuccess = sprintf('"%s" added to shortlist.', $oGame->name);
    } else {
        $sError = sprintf('Game with id %s not found!', $oRequest->getStr('shortlistadd'));;
    }
} elseif ($oRequest->getStr('shortlistdel')) {
    //remove from shortlist
    $oGame = new Game($oDatabase);
    $oGame->getById($oRequest->getStr('shortlistdel'));
    if ($oGame) {
        $oGame->shortlist_order = '';
        $oGame->save();
        $sSuccess = sprintf('"%s" removed from shortlist.', $oGame->name);
    } else {
        $sError = sprintf('Game with id %s not found!', $oRequest->getStr('shortlistdel'));;
    }
} elseif ($oRequest->getStr('shortlistdown')) {
    $oGame = new Game($oDatabase);
    $oGame->getById($oRequest->getStr('shortlistdown'));
    $oGame->shortlistDown();
} elseif ($oRequest->getStr('shortlistup')) {
    $oGame = new Game($oDatabase);
    $oGame->getById($oRequest->getStr('shortlistup'));
    $oGame->shortlistUp();
}


if ($id = $oRequest->getInt('id')) {
    //show single game
    $oGame = new Game($oDatabase);
    $oGame->getById($id);
    $aData = get_object_vars($oGame);
    unset($aData['db']);

} elseif (!empty($sShow)) {
    //show a specific subset of games
    $sQuery = 'WHERE 1=1 ';
    $aPrepared = [];

    //add in the search query if present
    if ($sSearch) {
        $sQuery .= 'AND name LIKE :search ';
        $aPrepared['search'] = '%' . $sSearch . '%';
    }

    switch($sShow) {
        case 'completed':
            $sQuery .= 'AND completion_perc = 100 ORDER BY name'; break;
        case 'incomplete':
            $sQuery .= 'AND completion_perc < 100 AND completion_perc > 0 ORDER BY completion_perc DESC, name'; break;
        case 'new':
            $sQuery .= 'AND id < 0 ORDER BY name'; break;
        case 'notstarted':
            $sQuery .= 'AND completion_perc = 0 ORDER BY name'; break;
        case 'bestrating':
            $sQuery .= 'AND site_rating > 4 ORDER BY site_rating DESC, name'; break;
        case 'notstartedrating':
            $sQuery .= 'AND completion_perc = 0 AND site_rating > 4 ORDER BY site_rating DESC, name'; break;
        case 'shortest':
            $sQuery .= 'AND completion_estimate != "" AND (0 + completion_estimate) < 12 ORDER BY (0 + completion_estimate) ASC, name'; break;
        case 'notstartedshort':
            $sQuery .= 'AND completion_perc = 0 AND completion_estimate != "" AND (0 + completion_estimate) < 12 ORDER BY (0 + completion_estimate) ASC, name'; break;
        case 'longest':
            $sQuery .= 'AND (0 + completion_estimate) >= 100 AND completion_estimate != "" ORDER BY (0 + completion_estimate) DESC, name'; break;
        case 'paid':
            $sQuery .= 'AND purchased_price > 0 ORDER BY purchased_price DESC'; break;
        case 'free':
            $sQuery .= 'AND purchased_price = 0 ORDER BY name'; break;
        case 'sale':
            $sQuery .= 'AND status = "sale" ORDER BY name'; break;
        case 'physical':
            $sQuery .= 'AND format IN ("Disc", "Disc & Digital", "Sold") ORDER BY name'; break;
        case 'sold':
            $sQuery .= 'AND format = "Sold" ORDER BY name'; break;
        case 'unavailable':
            $sQuery .= 'AND status IN ("delisted", "region-locked") ORDER BY name'; break;
        case 'xb1':
            $sQuery .= 'AND platform = "Xbox One" ORDER BY name'; break;
        case '360':
            $sQuery .= 'AND platform = "Xbox 360" ORDER BY name'; break;
        case 'win':
            $sQuery .= 'AND platform LIKE "Win%" ORDER BY name'; break;
        case 'bc':
            $sQuery .= 'AND platform = "Xbox 360" AND backcompat != 0 ORDER BY name'; break;
        case 'nonbc':
            $sQuery .= 'AND platform = "Xbox 360" AND backcompat = 0 ORDER BY name'; break;
        case 'nonbckinect':
            $sQuery .= 'AND platform = "Xbox 360" AND backcompat = 0 AND kinect_required = 1 ORDER BY name'; break;
        case 'nonbcperiph':
            $sQuery .= 'AND platform = "Xbox 360" AND backcompat = 0 AND peripheral_required = 1 ORDER BY name'; break;
        case 'nonbconline':
            $sQuery .= 'AND platform = "Xbox 360" AND backcompat = 0 AND online_multiplayer = 1 ORDER BY name'; break;
        case 'walkthrough':
            $sQuery .= 'AND walkthrough_url != "" ORDER BY name'; break;
        case 'nowalkthrough':
            $sQuery .= 'AND walkthrough_url = "" ORDER BY name'; break;
        case 'withdlc':
            $sQuery .= 'AND dlc = 1'; break;
        case 'nodlc':
            $sQuery .= 'AND dlc = 0'; break;
        case 'dlccompleted':
            $sQuery .= 'AND dlc = 1 AND dlc_completion = 100 ORDER BY name'; break;
        case 'dlcnotcompleted':
            $sQuery .= 'AND dlc = 1 AND dlc_completion < 100 ORDER BY dlc_completion DESC'; break;
        case 'mostplayed':
            $sQuery .= 'ORDER BY hours_played DESC'; break;
        case 'shortlist':
            $sQuery .= 'AND shortlist_order > 0 ORDER BY shortlist_order ASC'; break;
        case 'recent':
            $sQuery .= 'AND date_created > DATE_SUB(NOW(), INTERVAL 14 DAY) ORDER BY date_created DESC'; break;
        default:
            $sQuery .= 'ORDER BY name'; break;
    }

    //execute the query
    if ($sQuery) {
        $aGames = $oDatabase->query('
            SELECT SQL_CALC_FOUND_ROWS *
            FROM mygamecollection '
            . $sQuery,
            $aPrepared
        );
    }

} elseif (!empty($sSearch)) {
    //show search results
    $aGames = $oDatabase->query('
        SELECT SQL_CALC_FOUND_ROWS *
        FROM mygamecollection
        WHERE name LIKE :name',
        [
            'name' => '%' . $sSearch . '%',
        ]
    );

}

if (empty($aGames)) {
    //initial page load, so show all games
    if (empty($sSearch) && empty($sShow)) {
        $aGames = $oDatabase->query('
            SELECT SQL_CALC_FOUND_ROWS *
            FROM mygamecollection
            ORDER BY name'
        );
    } else {
        if ($sSearch) {
            $sSuccess = sprintf('Search for "%s" yielded no results.', $sSearch);
        } elseif ($sShow) {
            $sSuccess = 'Current filter yielded no games.';
        } else {
            $sSuccess = 'Search or filter yielded no games.';
        }
    }
}

$aCount = $oDatabase->query('SELECT FOUND_ROWS()', []);
$iCount = $aCount[0]['FOUND_ROWS()'];

//calculate worth of current selection (total purchased value, total current value, total saved, most expensive (current/purchased), 
//average price (current/purchased), amount of free/delisted/on sale...)
if (empty($id)) {

    //newly added games count to show in button badge
    $iNewAdded = $oDatabase->query_value('
        SELECT COUNT(id)
        FROM mygamecollection
        where id < 0'
    );

    $aStats = [
        'free' => 0,
        'delisted' => 0,
        'on_sale' => 0,
        'purchased_free' => 0,
        'total_purchased' => 0.0,
        'total_currentvalue' => 0.0,
        'total_saved' => 0.0,
        'most_expensive_purchased' => false,
        'most_expensive_current' => false,
        'total_playtime' => 0,
        'spent_playtime' => 0,
    ];
    if (!empty($sShow)) {
        $aAllGames = $aGames;
    } else {
        $aAllGames = $oDatabase->query('
            SELECT *
            FROM mygamecollection'
        );
    }
    foreach ($aAllGames as $aGame) {
        switch ($aGame['status']) {
            case 'delisted': $aStats['delisted']++; break;
            case 'sale': $aStats['on_sale']++; break;
            case 'available': $aStats['total_saved'] += ($aGame['current_price'] - $aGame['purchased_price']);
        }
        if ($aGame['current_price'] == 0) {
            $aStats['free']++;
        }
        if ($aGame['purchased_price'] == 0) {
            $aStats['purchased_free']++;
        }

        $aStats['total_currentvalue'] += $aGame['current_price'];
        $aStats['total_purchased'] += $aGame['purchased_price'];

        if (!$aStats['most_expensive_purchased'] || $aStats['most_expensive_purchased']['purchased_price'] < $aGame['purchased_price']) {
            $aStats['most_expensive_purchased'] = $aGame;
        }
        if (!$aStats['most_expensive_current'] || $aStats['most_expensive_current']['current_price'] < $aGame['current_price']) {
            $aStats['most_expensive_current'] = $aGame;
        }

        if ($aGame['completion_estimate']) {
            $aStats['total_playtime'] += (int) $aGame['completion_estimate'];
            if ($aGame['hours_played']) {
                $aStats['spent_playtime'] += $aGame['hours_played'];
            } elseif ($aGame['completion_perc'] == 100 && !empty($aGame['completion_estimate'])) {
                $aStats['spent_playtime'] += intval($aGame['completion_estimate']);
            }
        }
    }
    $aStats['average_purchased'] = 0;
    $aStats['average_value'] = 0;
    if ($aGames) {
        $aStats['average_purchased'] = round($aStats['total_purchased'] / count($aGames), 2);
        $aStats['average_value'] = round($aStats['total_currentvalue'] / count($aGames), 2);
    }

    //select current page
    $aGames = array_slice($aGames, $iOffset, $iPerPage);
}

require_once('template/main.php');
