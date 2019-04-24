<?php
/**
 * CMS page for managing your TrueAchievements game collection.
 * Import the csv from TA, and the .json from the price scraper.
 *
 * TODO:
 * v shortlist
 * v separate this mess from twitterbot libraries and put it on github
 * v show if dlc also completed
 * x walkthrough urls seems mostly wrong (TA bug reported)
 * - sortable columns
 * - fix crash when importing new games when there's already newly imported games (hardcoded -1 id)
 * - namespaces and autoloader
 */

if (!is_readable('mygamecollection.inc.php')) {
    die(sprintf('Include file missing. Please create %s and define DB_HOST, DB_USER, DB_NAME and DB_PASS with your database details,
        and FORM_PASSWORD if you want to password-protect your edits.',
        pathinfo(__FILE__, PATHINFO_FILENAME)
    ));
}

require_once('mygamecollection.inc.php');
require_once('lib/logger.php');
require_once('lib/database.php');

$oDatabase = new Database;

//check if database connection is ok
if ($oDatabase->connect()) {

    //check if table exists
    if (!$oDatabase->query('SHOW TABLES LIKE "mygamecollection"')) {

        //call setup
        require_once('setup/setup.php');
        (new Setup($oDatabase))->run();
        exit();
    }
}

$sThisFile = pathinfo(__FILE__, PATHINFO_BASENAME);

$oRequest = new Request;

$sSearch = $oRequest->getStr('search');
$sShow = $oRequest->getStr('show');

$iPage = max(1, $oRequest->getInt('page'));
$iPerPage = 30;
$iOffset = ($iPage - 1) * $iPerPage;

if ($oRequest->isPost()) {
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
        //delete record
        $oGame = new Game($oDatabase);
        $oGame->getById($oRequest->postInt('id'));
        if ($oGame->delete()) {
            $sSuccess = 'Game deleted.';
        } else {
            $sError = 'Deleting game failed.';
            $aData = $_POST;
        }

    } elseif ($oRequest->postStr('action') == 'Import game collection CSV' && $csvFile = $oRequest->file('upload')) {

        $result = importCsvIntoDatabase(file($csvFile[0]['tmp_name']));

        if (!isset($result['error'])) {
            $aSuccess[] = 'Import complete!';
            if ($result['new']) {
                $aSuccess[] = '<br/>The following games were new:<ul>';
                foreach ($result['new'] as $game) {
                    $aSuccess[] = sprintf('<li><a href="?id=%s">%s</a></li>',
                        $game['id'],
                        $game['Game name']
                    );
                }
                $aSuccess[] = '</ul>';
            }

            if ($result['updated']) {
                $aSuccess[] = '<br/>The following games were updated:<ul>';
                foreach ($result['updated'] as $game) {
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
                foreach ($result['removed'] as $game) {
                    $aSuccess[] = sprintf('<li><a href="?id=%s">%s</a></li>',
                        $game->id,
                        $game->name
                    );
                }
                $aSuccess[] = '</ul>';
            }
            $sSuccess = implode(PHP_EOL, $aSuccess);
        } else {
            $sError = $result['error'];
        }

    } elseif ($oRequest->postStr('action') == 'Import prices JSON' && $jsonFile = $oRequest->file('upload')) {

        $result = importJsonIntoDatabase(file_get_contents($jsonFile[0]['tmp_name']));

        if (!isset($result['error'])) {

            $aSuccess[] = 'Import complete!';
            $aSuccess[] = sprintf('<br/>Out of %d games, currently %d are available, %d are delisted/unavailable and %d are on sale.',
                $result['total'],
                $result['available'],
                $result['unavailable'],
                $result['sale']
            );
            if ($result['games_delisted']) {
                $aSuccess[] = 'The following games were delisted:<ul>';
                foreach ($result['games_delisted'] as $game) {
                    $aSuccess[] = sprintf('<li><a href="%s" target="_blank">%s</a></li>', $game->url, $game->name);
                }
                $aSuccess[] = '</ul>';
            }
            if ($result['games_unavailabled']) {
                $aSuccess[] = 'The following games are now unavailable:<ul>';
                foreach ($result['games_unavailabled'] as $game) {
                    $aSuccess[] = sprintf('<li><a href="%s" target="_blank">%s</a> (%s)</li>', $game->url, $game->name, $game->status);
                }
                $aSuccess[] = '</ul>';
            }
            if ($result['games_availabled']) {
                $aSuccess[] = 'The following games are now available again:<ul>';
                foreach ($result['games_availabled'] as $game) {
                    $aSuccess[] = sprintf('<li><a href="%s" target="_blank">%s</a></li>', $game->url, $game->name);
                }
                $aSuccess[] = '</ul>';
            }
            if ($result['games_discounted']) {
                $aSuccess[] = 'The following games were discounted:<ul>';
                foreach ($result['games_discounted'] as $game) {
                    $aSuccess[] = sprintf('<li><a href="%s" target="_blank">%s</a> %s</li>', $game->url, $game->name, ($game->status != 'sale' ? '<b>(not a sale)</b>' : ''));
                }
                $aSuccess[] = '</ul>';
            }
            if ($result['games_undiscounted']) {
                $aSuccess[] = 'The following games are no longer discounted:<ul>';
                foreach ($result['games_undiscounted'] as $game) {
                    $aSuccess[] = sprintf('<li><a href="%s" target="_blank">%s</a></li>', $game->url, $game->name);
                }
                $aSuccess[] = '</ul>';
            }
            if ($result['price_drop']) {
                $aSuccess[] = 'The following games dropped in price outside of a sale:<ul>';
                foreach ($result['price_drop'] as $game) {
                    $aSuccess[] = sprintf('<li><a href="%s" target="_blank">%s</a> (%s)</li>', $game->url, $game->name, $game->price);
                }
                $aSuccess[] = '</ul>';
            }
            if ($result['price_hike']) {
                $aSuccess[] = 'The following games increased in price outside of a sale:<ul>';
                foreach ($result['price_hike'] as $game) {
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
            $sQuery .= 'AND dlc = 1 AND dlc_completion = 100'; break;
        case 'dlcnotcompleted':
            $sQuery .= 'AND dlc = 1 AND dlc_completion < 100'; break;
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

?><!DOCTYPE html>
<html>
    <head>
        <title>My Game Collection</title>
        <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js"></script>

        <script type="text/javascript">
            $(function() {
                $('.confirm').on('click', function(e) {

                    if (!confirm('Are you sure you want to delete this record?')) {
                        e.preventDefault();
                        return false;
                    }
                });
			});
		</script>
        <style>
            span.btn { cursor: default; }
            .table tbody tr > td.xb1 { background-color: #777 !important; color: white; }
            .table tbody tr > td.x36 { background-color: #5cb85c !important; }
            .table tbody tr > td.win { background-color: #f0ad4e !important; }
            .table tbody tr > td.mob { background-color: #5bc0de !important; }
            .table-hover tbody tr:hover > td.xb1 { background-color: #777 !important; color: white; }
            .table-hover tbody tr:hover > td.x36 { background-color: #5cb85c !important; }
            .table-hover tbody tr:hover > td.win { background-color: #f0ad4e !important; }
            .table-hover tbody tr:hover > td.mob { background-color: #5bc0de !important; }
        </style>
    </head>
    <body>
        <div class="container">
        <h1><a href="https://www.trueachievements.com/" target="_blank">My Game Collection</a>
            <a href="#content"><span class="glyphicon glyphicon-arrow-down"></span></a>
        </h1>

            <?php if (!empty($id)): ?>
            <form method="post" action="<?= $sThisFile ?>?page=<?= $iPage ?>&show=<?= $sShow ?>" class="form-horizontal" role="form">

                <input type="hidden" name="id" value="<?= $id ?>" />

                <?php if ($id < 0): ?>
                    <div class="form-group">
                        <label for="newid" class="col-sm-2 control-label">ID</label>
                        <div class="col-sm-10">
                            <input type="text" id="newid" name="newid" class="form-control" style="background-color: #f2dede;" value="<?= $aData['id'] ?>" required />
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="name" class="col-sm-2 control-label">Name</label>
					<div class="col-sm-10">
						<input type="text" id="name" name="name" class="form-control" disabled value="<?= @utf8_encode($aData['name']) ?>" required />
					</div>
                </div>

                <div class="form-group">
                    <label for="platform" class="col-sm-2 control-label">Platform</label>
					<div class="col-sm-10">
                        <select name="platform" id="platform" disabled class="form-control">
                            <option value="Xbox 360" <?= $aData['platform'] == 'Xbox 360' ? 'selected' : '' ?>>Xbox 360</option>
                            <option value="Xbox One" <?= $aData['platform'] == 'Xbox One' ? 'selected' : '' ?>>Xbox One</option>
                            <option value="Android" <?= $aData['platform'] == 'Android' ? 'selected' : '' ?>>Android</option>
                            <option value="Windows" <?= $aData['platform'] == 'Windows' ? 'selected' : '' ?>>Windows</option>
                            <option value="Web" <?= $aData['platform'] == 'Web' ? 'selected' : '' ?>>Web</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="backcompat1" class="col-sm-2 control-label">Backwards compatible</label>
					<div class="col-sm-10">
                        <label class="radio-inline">
                            <input type="radio" id="backcompat1" name="backcompat" value="1" <?= $aData['backcompat'] === '1' ? 'checked' : '' ?> ?><b>Yes</b>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" id="backcompat2" name="backcompat" value="0" <?= $aData['backcompat'] === '0' ? 'checked' : '' ?> ?><b>No</b>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" id="backcompat3" name="backcompat" value="" <?= is_null($aData['backcompat']) ? 'checked' : '' ?> ?>N/A
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="kinect1" class="col-sm-2 control-label">Kinect required</label>
					<div class="col-sm-10">
                        <label class="radio-inline">
                            <input type="radio" id="kinect1" name="kinect_required" value="1" <?= $aData['kinect_required'] === '1' ? 'checked' : '' ?> ?><b>Yes</b>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" id="kinect2" name="kinect_required" value="0" <?= $aData['kinect_required'] === '0' ? 'checked' : '' ?> ?><b>No</b>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" id="kinect3" name="kinect_required" value="" <?= is_null($aData['kinect_required']) ? 'checked' : '' ?> ?>N/A
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="periph1" class="col-sm-2 control-label">Peripheral required</label>
                    <div class="col-sm-10">
                        <label class="radio-inline">
                            <input type="radio" id="peripheral1" name="peripheral_required" value="1" <?= $aData['peripheral_required'] === '1' ? 'checked' : '' ?> ?><b>Yes</b>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" id="peripheral2" name="peripheral_required" value="0" <?= $aData['peripheral_required'] === '0' ? 'checked' : '' ?> ?><b>No</b>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" id="peripheral3" name="peripheral_required" value="" <?= is_null($aData['peripheral_required']) ? 'checked' : '' ?> ?>N/A
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="online1" class="col-sm-2 control-label">Online multiplayer achievements</label>
					<div class="col-sm-10">
                        <label class="radio-inline">
                            <input type="radio" id="online1" name="online_multiplayer" value="1" <?= $aData['online_multiplayer'] === '1' ? 'checked' : '' ?> ?><b>Yes</b>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" id="online2" name="online_multiplayer" value="0" <?= $aData['online_multiplayer'] === '0' ? 'checked' : '' ?> ?><b>No</b>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" id="online3" name="online_multiplayer" value="" <?= is_null($aData['online_multiplayer']) ? 'checked' : '' ?> ?>N/A
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="purchased_price" class="col-sm-2 control-label">Purchased price</label>
					<div class="col-sm-10">
                        <div class="input-group">
                            <div class="input-group-addon">&euro;</div>
                            <input type="text" id="purchased_price" name="purchased_price" class="form-control" value="<?= $aData['purchased_price'] ?>" />
                        </div>
					</div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label">&nbsp;</label>
					<div class="col-sm-10">
                        <input type="submit" name="action" value="Save" class="btn btn-primary" />
						<?php if (!empty($id)) : ?>
                            <a href="<?= $sThisFile ?>?page=<?= $iPage ?>&show=<?= $sShow ?>" class="btn btn-default">Cancel</a>
							<input type="submit" name="action" value="Delete" class="btn btn-danger confirm" />
						<?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <div class="col-sm-10">
                            <hr class="col-sm-10" />
                            <h3 class="form-control-static"><b>TrueAchievement-provided data</b></h3>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="completion_perc" class="col-sm-2 control-label">Completion percentage</label>
					<div class="col-sm-10">
						<input type="text" id="completion_perc" name="completion_perc" disabled class="form-control" value="<?= $aData['completion_perc'] ?>" required />
					</div>
                </div>

                <div class="form-group">
                    <label for="completion_estimate" class="col-sm-2 control-label">Completion estimate</label>
					<div class="col-sm-10">
                        <select name="completion_estimate" id="completion_estimate" disabled class="form-control">
                            <option value=""></option>
                            <option value="0-1 hour" <?= $aData['completion_estimate'] == '0-1 hour' ? 'selected' : '' ?>>0-1 hour</option>
                            <option value="1-2 hours" <?= $aData['completion_estimate'] == '1-2 hours' ? 'selected' : '' ?>>1-2 hours</option>
                            <option value="2-3 hours" <?= $aData['completion_estimate'] == '2-3 hours' ? 'selected' : '' ?>>2-3 hours</option>
                            <option value="3-4 hours" <?= $aData['completion_estimate'] == '3-4 hours' ? 'selected' : '' ?>>3-4 hours</option>
                            <option value="4-5 hours" <?= $aData['completion_estimate'] == '4-5 hours' ? 'selected' : '' ?>>4-5 hours</option>
                            <option value="5-6 hours" <?= $aData['completion_estimate'] == '5-6 hours' ? 'selected' : '' ?>>5-6 hours</option>
                            <option value="6-8 hours" <?= $aData['completion_estimate'] == '6-8 hours' ? 'selected' : '' ?>>6-8 hours</option>
                            <option value="8-10 hours" <?= $aData['completion_estimate'] == '8-10 hours' ? 'selected' : '' ?>>8-10 hours</option>
                            <option value="10-12 hours" <?= $aData['completion_estimate'] == '10-12 hours' ? 'selected' : '' ?>>10-12 hours</option>
                            <option value="12-15 hours" <?= $aData['completion_estimate'] == '12-15 hours' ? 'selected' : '' ?>>12-15 hours</option>
                            <option value="15-20 hours" <?= $aData['completion_estimate'] == '15-20 hours' ? 'selected' : '' ?>>15-20 hours</option>
                            <option value="20-25 hours" <?= $aData['completion_estimate'] == '20-25 hours' ? 'selected' : '' ?>>20-25 hours</option>
                            <option value="25-30 hours" <?= $aData['completion_estimate'] == '25-30 hours' ? 'selected' : '' ?>>25-30 hours</option>
                            <option value="30-35 hours" <?= $aData['completion_estimate'] == '30-35 hours' ? 'selected' : '' ?>>30-35 hours</option>
                            <option value="35-40 hours" <?= $aData['completion_estimate'] == '35-40 hours' ? 'selected' : '' ?>>35-40 hours</option>
                            <option value="40-50 hours" <?= $aData['completion_estimate'] == '40-50 hours' ? 'selected' : '' ?>>40-50 hours</option>
                            <option value="50-60 hours" <?= $aData['completion_estimate'] == '50-60 hours' ? 'selected' : '' ?>>50-60 hours</option>
                            <option value="60-80 hours" <?= $aData['completion_estimate'] == '60-80 hours' ? 'selected' : '' ?>>60-80 hours</option>
                            <option value="80-100 hours" <?= $aData['completion_estimate'] == '80-100 hours' ? 'selected' : '' ?>>80-100 hours</option>
                            <option value="100-120 hours" <?= $aData['completion_estimate'] == '100-120 hours' ? 'selected' : '' ?>>100-120 hours</option>
                            <option value="120-150 hours" <?= $aData['completion_estimate'] == '120-150 hours' ? 'selected' : '' ?>>120-150 hours</option>
                            <option value="150-200 hours" <?= $aData['completion_estimate'] == '150-200 hours' ? 'selected' : '' ?>>150-200 hours</option>
                            <option value="200+ hours" <?= $aData['completion_estimate'] == '200+ hours' ? 'selected' : '' ?>>200+ hours</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="hours_played" class="col-sm-2 control-label">Hours played</label>
					<div class="col-sm-10">
						<input type="text" id="hours_played" name="hours_played" disabled class="form-control" value="<?= $aData['hours_played'] ?>" />
					</div>
                </div>

                <div class="form-group">
                    <label for="achievements_won" class="col-sm-2 control-label">Achievements won</label>
					<div class="col-sm-10">
						<input type="text" id="achievements_won" name="achievements_won" disabled class="form-control" value="<?= $aData['achievements_won'] ?>" />
					</div>
                </div>

                <div class="form-group">
                    <label for="achievements_total" class="col-sm-2 control-label">Achievements total</label>
					<div class="col-sm-10">
						<input type="text" id="achievements_total" name="achievements_total" disabled class="form-control" value="<?= $aData['achievements_total'] ?>" />
					</div>
                </div>

                <div class="form-group">
                    <label for="gamerscore_won" class="col-sm-2 control-label">Gamerscore won</label>
					<div class="col-sm-10">
						<input type="text" id="gamerscore_won" name="gamerscore_won" disabled class="form-control" value="<?= $aData['gamerscore_won'] ?>" />
					</div>
                </div>

                <div class="form-group">
                    <label for="gamerscore_total" class="col-sm-2 control-label">Gamerscore total</label>
					<div class="col-sm-10">
						<input type="text" id="gamerscore_total" name="gamerscore_total" disabled class="form-control" value="<?= $aData['gamerscore_total'] ?>" />
					</div>
                </div>

                <div class="form-group">
                    <label for="ta_score" class="col-sm-2 control-label">TrueAchievement score</label>
					<div class="col-sm-10">
						<input type="text" id="ta_score" name="ta_score" class="form-control" disabled value="<?= $aData['ta_score'] ?>" />
					</div>
                </div>

                <div class="form-group">
                    <label for="completion_date" class="col-sm-2 control-label">Completion date</label>
					<div class="col-sm-10">
						<input type="text" id="completion_date" name="completion_date" disabled class="form-control" value="<?= $aData['completion_date'] ?>" />
					</div>
                </div>

                <div class="form-group">
                    <label for="site_rating" class="col-sm-2 control-label">Site rating</label>
					<div class="col-sm-10">
						<input type="text" id="site_rating" name="site_rating" disabled class="form-control" value="<?= $aData['site_rating'] ?>" />
					</div>
                </div>

                <div class="form-group">
                    <label for="format" class="col-sm-2 control-label">Media</label>
					<div class="col-sm-10">
                        <select name="format" id="format" disabled class="form-control">
                            <option value=""></option>
                            <option value="Digital" <?= $aData['format'] == 'Digital' ? 'selected' : '' ?>>Digital</option>
                            <option value="Disc" <?= $aData['format'] == 'Disc' ? 'selected' : '' ?>>Disc</option>
                            <option value="Digital & Disc" <?= $aData['format'] == 'Digital & Disc' ? 'selected' : '' ?>>Digital &amp; Disc</option>
                            <option value="Sold" <?= $aData['format'] == 'Sold' ? 'selected' : '' ?>>Sold</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="status" class="col-sm-2 control-label">Status</label>
					<div class="col-sm-10">
                        <select name="status" id="status" disabled class="form-control">
                            <option value=""></option>
                            <option value="available" <?= $aData['status'] == 'available' ? 'selected' : '' ?>>Available</option>
                            <option value="delisted" <?= $aData['status'] == 'delisted' ? 'selected' : '' ?>>Delisted</option>
                            <option value="region-locked" <?= $aData['status'] == 'region-locked' ? 'selected' : '' ?>>Region locked</option>
                            <option value="sale" <?= $aData['status'] == 'sale' ? 'selected' : '' ?>>On sale</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="current_price" class="col-sm-2 control-label">Current price</label>
                    <div class="col-sm-10">
                        <div class="input-group">
                            <div class="input-group-addon">&euro;</div>
                            <input type="text" id="current_price" name="current_price" disabled class="form-control" value="<?= priceFormat($aData['current_price'], false) ?>" />
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="regular_price" class="col-sm-2 control-label">Regular price</label>
                    <div class="col-sm-10">
                        <div class="input-group">
                            <div class="input-group-addon">&euro;</div>
                            <input type="text" id="regular_price" name="regular_price" disabled class="form-control" value="<?= priceFormat($aData['regular_price'], false)?>" />
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="walkthrough_url" class="col-sm-2 control-label"><a href="<?= $aData['walkthrough_url'] ?>" target="_blank">Walkthrough URL</a></label>
					<div class="col-sm-10">
						<input type="text" id="walkthrough_url" name="walkthrough_url" disabled class="form-control" value="<?= $aData['walkthrough_url'] ?>" />
					</div>
                </div>

                <div class="form-group">
                    <label for="game_url" class="col-sm-2 control-label"><a href="<?= $aData['game_url'] ?>" target="_blank">Game page URL</a></label>
					<div class="col-sm-10">
						<input type="text" id="game_url" name="game_url" disabled class="form-control" value="<?= $aData['game_url'] ?>" />
					</div>
                </div>

            </form>

            <?php else: ?>
                
                <div class="form-group text-center" style="line-height: 5em;">
                    <span class="btn btn-info">Total games<br/><span class="badge"><?= $iCount ?></span></span>
                    <span class="btn btn-warning">Currently on sale<br/><span class="badge"><?= $aStats['on_sale'] ?></span></span>
                    <span class="btn btn-success">Free games<br/><span class="badge"><?= $aStats['free'] ?></span></span>
                    <span class="btn btn-success">Purchased for free<br/><span class="badge"><?= $aStats['purchased_free'] ?></span></span>
                    <span class="btn btn-danger">Delisted games<br/><span class="badge"><?= $aStats['delisted'] ?></span></span>
                    <span class="btn btn-info">Total spent<br/><span class="badge"><?= priceFormat($aStats['total_purchased']) ?></span></span>
                    <span class="btn btn-info">Total market value<br/><span class="badge"><?= priceFormat($aStats['total_currentvalue']) ?></span></span>
                    <span class="btn btn-success">Total saved:<br/><span class="badge"><?= priceFormat($aStats['total_saved']) ?></span></span>
                    <span class="btn btn-info">Average cost<br/><span class="badge"><?= priceFormat($aStats['average_purchased']) ?></span></span>
                    <span class="btn btn-info">Average current value<br/><span class="badge"><?= priceFormat($aStats['average_value']) ?></span></span>
                    <span class="btn btn-info">Total estimated playtime<br/><span class="badge"><?= $aStats['total_playtime'] ?> hours</span></span>
                    <span class="btn btn-info">Spent playtime<br/><span class="badge"><?= $aStats['spent_playtime'] ?> hours</span></span>
                    <span class="btn btn-danger">Most expensive buy:<br/><?= $aStats['most_expensive_purchased']['name'] ?> <span class="badge"><?= priceFormat($aStats['most_expensive_purchased']['purchased_price']) ?></span></span>
                    <span class="btn btn-danger">Most expensive current:<br/><?= $aStats['most_expensive_current']['name'] ?> <span class="badge"><?= priceFormat($aStats['most_expensive_current']['current_price']) ?></span></span>
                </div>
            
                <div class="form-group" style="line-height: 3em;">
                    <form action="<?= $sThisFile ?>" method="get" class="form-inline">
                                <a class="btn btn-success" href="<?= $sThisFile ?>">All games</a>
                                <a class="btn btn-success <?= ($sShow == 'completed' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=completed">Completed games</a>
                                <a class="btn btn-success <?= ($sShow == 'shortlist' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=shortlist">Shortlist</a>
                                <a class="btn btn-info <?= ($sShow == 'incomplete' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=incomplete">Started games</a>
                                <a class="btn btn-info <?= ($sShow == 'notstarted' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=notstarted">Not started games</a>
                                <a class="btn btn-info <?= ($sShow == 'bestrating' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=bestrating">Best games</a>
                                <a class="btn btn-info <?= ($sShow == 'notstartedrating' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=notstartedrating">Best not started games</a>
                                <a class="btn btn-info <?= ($sShow == 'notstartedshort' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=notstartedshort">Shortest not started games</a>
                                <a class="btn btn-info <?= ($sShow == 'shortest' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=shortest">Shortest games</a>
                                <a class="btn btn-info <?= ($sShow == 'longest' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=longest">Longest games</a>
                                <a class="btn btn-info <?= ($sShow == 'mostplayed' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=mostplayed">Most played</a>
                                <a class="btn btn-info <?= ($sShow == 'recent' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=recent">Recent</a>
                                <a class="btn btn-default <?= ($sShow == 'free' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=free">Free games</a>
                                <a class="btn btn-default <?= ($sShow == 'sale' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=sale">On sale</a>
                                <a class="btn btn-default <?= ($sShow == 'physical' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=physical">Physical games</a>
                                <a class="btn btn-default <?= ($sShow == 'sold' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=sold">Sold games</a>
                                <a class="btn btn-default <?= ($sShow == 'unavailable' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=unavailable">Unavailable games</a>
                                <a class="btn btn-warning <?= ($sShow == 'xb1' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=xb1">Xbox One games</a>
                                <a class="btn btn-warning <?= ($sShow == '360' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=360">Xbox 360 games</a>
                                <a class="btn btn-warning <?= ($sShow == 'win' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=win">Windows games</a>
                                <a class="btn btn-warning <?= ($sShow == 'bc' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=bc">Backwards compatible games</a>
                                <a class="btn btn-warning <?= ($sShow == 'nonbc' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=nonbc">Not backwards compatible games</a>
                                <a class="btn btn-warning <?= ($sShow == 'nonbckinect' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=nonbckinect">Non-BC games with Kinect</a>
                                <a class="btn btn-warning <?= ($sShow == 'nonbcperiph' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=nonbcperiph">Non-BC games with peripheral</a>
                                <a class="btn btn-danger <?= ($sShow == 'nonbconline' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=nonbconline">Non-BC games with online multiplayer</a>
                                <a class="btn btn-success <?= ($sShow == 'walkthrough' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=walkthrough">With walkthrough</a>
                                <a class="btn btn-warning <?= ($sShow == 'nowalkthrough' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=nowalkthrough">Without walkthrough</a>
                                <a class="btn btn-success <?= ($sShow == 'nodlc' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=nodlc">Without DLC</a>
                                <a class="btn btn-warning <?= ($sShow == 'withdlc' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=withdlc">With DLC</a>
                                <a class="btn btn-success <?= ($sShow == 'dlccompleted' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=dlccompleted">DLC completed</a>
                                <a class="btn btn-warning <?= ($sShow == 'dlcnotcompleted' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=dlcnotcompleted">DLC not completed</a>
                        <?php if ($iNewAdded): ?>
                            <a class="btn btn-danger <?= ($sShow == 'new' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=new">Without TA game id <span class="badge"><?= $iNewAdded ?></span></a><br/>
                        <?php endif; ?>
                        <input type="hidden" name="page" value="<?= $iPage ?>" />
                        <input type="hidden" name="show" value="<?= $sShow ?>" />
                        <span class="text-nowrap">
                            <input type="text" name="search" id="search" class="form-control" style="width: auto; display: inline;" value="<?= $sSearch ?>" autofocus />
                            <input class="btn btn-info" name="submit" type="submit" value="Search" />
                            <a href="<?= $sThisFile ?>" class="btn btn-default">Clear</a>
                        </span>
                    </form>
                    <form method="post" enctype="multipart/form-data" class="form-inline">
                        <input type="file" name="upload" style="width: auto; display: inline;" id="upload" class="form-control hidden-xs" />
                        <input class="btn btn-info hidden-xs" name="action" type="submit" value="Import game collection CSV" />
                        <input class="btn btn-info hidden-xs" name="action" type="submit" value="Import prices JSON" />
                    </form>
                </div>

                <a name="content"></a>

                <?php if (!empty($sSuccess)): ?>
                    <div role="alert" class="alert alert-success"><?= $sSuccess ?></div>
                <?php endif; ?>
                <?php if (!empty($sError)): ?>
                    <div role="alert" class="alert alert-danger"><?= $sError ?></div>
                <?php endif; ?>

                <?php if ($iCount > $iPerPage): ?>
                <nav style="text-align: center;">
                    <ul class="pagination">
                    <?php for ($i = 1; $i <= ceil($iCount / $iPerPage); $i++) : ?>
                        <?php if ($i == $iPage): ?>
                        <li class="active"><span><?= $i ?></span></li>
                        <?php else: ?>
                        <li><a href="<?= $sThisFile ?>?page=<?= $i ?><?= (@$sSearch ? "&search=" . $sSearch : '') ?><?= (@$sShow ? "&show=" . $sShow : '') ?>"><?= $i ?></a></li>
                        <?php endif; ?>
                    <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>

                <?php if (!$aGames): ?>
                    <p>Your game collection seems empty! You can fill it by doing the following things:</p>
                    <ol>
                        <li>Run the price scraper (<span style="font-family: Courier New;">php xboxcalculator.php [gamertag] [region]</span>
                            from the command line) and import the resulting .json file with price info.</li>
                        <li>Go to your <a href="https://www.trueachievements.com/">TrueAchievements</a> game collection</a></li>
                        <li>Click the 'View and filter' button, then click the down arrow to download your game collection as a .csv file.</li>
                        <li>Back here, click 'Choose file', pick the .csv and hit 'Import game collection CSV'.</li>
                    </ol>
                    <p>To get the full power of this page, there's a few more optional things you can do:</p>
                    <ul>
                        <li>Manually enter prices you paid for your games. Use your
                            <a href="https://account.microsoft.com/billing/orders">Transaction history on Xbox.com</a></li>
                        <li>Manually enter additional game details, like BC, kinect/peripherals required etc.</li>
                    </ul>
                    <p>Finally, if you want to keep your collection up to date, you should periodically:</p>
                    <ol>
                        <li>Import new prices json.</li>
                        <li>Import your game collection csv.</li>
                        <li>Manually update new games with price and game details, like above.</li>
                    </ol>
                    <p>Feedback and improvements are welcome, the GitHub page is at <a href="https://github.com/mrbellek/mygamecollection"
                    target="_blank">github.com/mrbellek/mygamecollection</a></p>
                <?php else: ?>

                <?php if ($sShow == 'new' && $aGames): ?>
                <p>This filter shows games that were imported into your library without their corresponding TA game id. To fix this,
                delete them from here, run the price scraper, import the .json file and then import your game collection .csv again.</p>
                <?php endif; ?>

                <table class="table table-condensed table-hover">

                    <tr>
                        <th>Game name (<?= $iCount ?>)</th>
                        <th class="hidden-xs">Platform</th>
                        <th class="text-nowrap">C<span class="hidden-xs">ompletion</span> %</th>
                        <th class="text-nowrap">C<span class="hidden-xs">omp</span> est.</th>
                        <th><abbr title="Downloadable content">DLC</abbr></th>
                        <th><abbr title="Backwards compatible">BC</abbr></th>
                        <th><abbr title="Kinect required">K</abbr></th>
                        <th><abbr title="Peripheral required">P</abbr></th>
                        <th><abbr title="No online multiplayer">O</abbr></th>
                        <th class="hidden-xs">Media</th>
                        <th class="hidden-xs">Paid</th>
                        <th class="hidden-xs">Price</th>
                        <th></th>
                    </tr>
                    <?php foreach ($aGames as $aGame): ?>
                        <?php //show entire row green if completed, show cell yellow if started
                        if ($aGame['completion_perc'] == 100) {
                            $sGameStatus = "success";
                            $sGameCompStatus = '';
                        } elseif ($aGame['completion_perc'] == 0) {
                            $sGameStatus = '';
                            $sGameCompStatus = '';
                        } else {
                            $sGameStatus = '';
                            $sGameCompStatus = 'warning';
                        }
                        //show cell colour based on platform
                        if ($aGame['platform'] == 'Xbox One') {
                            $sPlatform = 'xb1';
                        } elseif ($aGame['platform'] == 'Xbox 360') {
                            $sPlatform = 'x36';
                        } elseif ($aGame['platform'] == 'Windows') {
                            $sPlatform = 'win';
                        } else {
                            $sPlatform = 'mob';
                        }
                        //show yellow or red for long completions
                        $sCompletionEstimate = '';
                        if (in_array($aGame['completion_estimate'], [
                            '100-120 hours',
                            '120-150 hours',
                            '150-200 hours',
                            '200+ hours',
                        ])) {
                            $sCompletionEstimate = 'danger';
                        } elseif (in_array($aGame['completion_estimate'], [
                            '40-50 hours',
                            '50-60 hours',
                            '60-80 hours',
                            '80-100 hours',
                        ])) {
                            $sCompletionEstimate = 'warning';
                        }
                        //show 360 games with kinect/peripheral as yellow and non-BC with online achievements red
                        $sBackcompatStatus = '';
                        if ($aGame['platform'] == 'Xbox 360' && ($aGame['kinect_required'] || $aGame['peripheral_required']) && !$aGame['online_multiplayer']) {
                            $aBackcompatStatus = 'warning';
                        } elseif ($aGame['platform'] == 'Xbox 360' && $aGame['backcompat'] !== '1' && $aGame['online_multiplayer']) {
                            $sBackcompatStatus = 'danger';
                        } elseif ($aGame['platform'] == 'Xbox 360' && $aGame['backcompat']) {
                            $sBackcompatStatus = 'success';
                        }
                        //show physical games as yellow, sold as red
                        $aGameFormat = '';
                        if ($aGame['format'] == 'Sold') {
                            $aGameFormat = 'danger';
                        } elseif ($aGame['format'] == 'Disc') {
                            $aGameFormat = 'warning';
                        }
                        //show dlc icon if present, red if not complete, orange if partial, green if completed
                        $sDlcStatus = '';
                        if ($aGame['dlc']) {
                            if ($aGame['dlc_completion'] == 100) {
                                $sDlcStatus = 'green';
                                $sDlcCompletion = '100%';
                            } else {
                                $sDlcStatus = ($aGame['dlc_completion'] == 0 ? 'red' : 'orange');
                                $sDlcCompletion = $aGame['dlc_completion'] . '%';
                            }
                        }
                        ?>
                        <tr class="table-striped <?= $sGameStatus ?>">
                        <td>
                            <a href="<?= $aGame['game_url'] ?>" target="_blank"><?= $aGame['name'] ?></a>
                            <?php if ($aGame['walkthrough_url']): ?>
                                <a href="<?= $aGame['walkthrough_url'] ?>" target="_blank" title="Walkthrough available"><span class="glyphicon glyphicon-book"></span></a>
                            <?php endif; ?>
                            </td>
                            <td class="<?= $sPlatform ?> hidden-xs"><?= $aGame['platform'] ?></td>
                            <td class="<?= $sGameCompStatus ?> text-center text-nowrap"><?= $aGame['completion_perc'] ?> %</td>
                            <td class="<?= $sCompletionEstimate ?>">
                                <span class="hidden-xs"><?= $aGame['completion_estimate'] ?></span>
                                <span class="visible-xs text-nowrap"><?= str_replace(' hours', ' h', $aGame['completion_estimate']) ?></span>
                            </td>
                            <td>
                            <?php if ($aGame['dlc']): ?>
                                <span style="color: <?= $sDlcStatus ?>;" title="<?= $sDlcCompletion ?>" class="glyphicon glyphicon-plus"></span></td>
                            <?php endif; ?>
                            </td>
                            <?php if ($aGame['platform'] == 'Xbox 360'): ?>
                                <td class="<?= $sBackcompatStatus ?>"><?= int2glyph($aGame['backcompat'], 'glyphicon-refresh') ?></td>
                                <td class="<?= $sBackcompatStatus ?>"><?= int2glyph($aGame['kinect_required'], 'glyphicon-eye-open') ?></td>
                                <td class="<?= $sBackcompatStatus ?>"><?= int2glyph($aGame['peripheral_required'], 'glyphicon-music') ?></td>
                                <td class="<?= $sBackcompatStatus ?>"><?= int2glyph(abs($aGame['online_multiplayer'] - 1), 'glyphicon-cloud-upload') ?></td>
                            <?php else: ?>
                                <td /> <td /> <td /> <td />
                            <?php endif; ?>
                            <td class="<?= $aGameFormat ?> hidden-xs"><?= $aGame['format'] ?></td>
                            <td class="hidden-xs">
                                <?php if ($aGame['purchased_price'] > 0): ?>
                                    <?= priceFormat($aGame['purchased_price']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="hidden-xs">
                                <?php if ($aGame['status'] == 'delisted'): ?>
                                    <i title="<?= $aGame['status'] ?>" class="glyphicon glyphicon-ban-circle text-danger"></i>
                                <?php elseif ($aGame['status'] == 'region-locked'): ?>
                                    <i title="<?= $aGame['status'] ?>" class="glyphicon glyphicon glyphicon-globe text-danger"></i>
                                <?php else: ?>
                                    <?php if ($aGame['current_price'] > 0): ?>
                                        <?= priceFormat($aGame['current_price']) ?>
                                    <?php else: ?>
                                        <span class="text-success">free</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($sShow == 'shortlist'): ?>
                                    <a href="?shortlistup=<?= $aGame['id'] ?>&page=<?= $iPage ?>&show=<?= $sShow ?>"><span class="glyphicon glyphicon-arrow-up"></span></a>
                                    <a href="?shortlistdown=<?= $aGame['id'] ?>&page=<?= $iPage ?>&show=<?= $sShow ?>"><span class="glyphicon glyphicon-arrow-down"></span></a>
                                <?php else: ?>
                                    <?php if ($aGame['shortlist_order'] > 0): ?>
                                        <a href="?shortlistdel=<?= $aGame['id'] ?>&page=<?= $iPage ?>&show=<?= $sShow ?>"><span class="glyphicon glyphicon-star"></span></a>
                                    <?php else: ?>
                                        <a href="?shortlistadd=<?= $aGame['id'] ?>&page=<?= $iPage ?>&show=<?= $sShow ?>"><span class="glyphicon glyphicon-star-empty"></span></a>
                                    <?php endif; ?>
                                    <a href="?id=<?= $aGame['id'] ?>&page=<?= $iPage ?>&show=<?= $sShow ?>"><span class="glyphicon glyphicon-pencil"></span></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <?php endif; ?>

                <?php if ($iCount > $iPerPage): ?>
                <nav style="text-align: center;">
                    <ul class="pagination">
                    <?php for ($i = 1; $i <= ceil($iCount / $iPerPage); $i++) : ?>
                        <?php if ($i == $iPage): ?>
                        <li class="active"><span><?= $i ?></span></li>
                        <?php else: ?>
                        <li><a href="<?= $sThisFile ?>?page=<?= $i ?><?= (@$sSearch ? "&search=" . $sSearch : '') ?><?= (@$sShow ? "&show=" . $sShow : '') ?>"><?= $i ?></a></li>
                        <?php endif; ?>
                    <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </body>
</html>

<?php
function int2glyph($int, $glyph) {
    if ($int == '1') {
        return sprintf('<span class="glyphicon %s" title="Yes" style="color: green;"></span>', $glyph);
    } elseif ($int == '0') {
        return sprintf('<span class="glyphicon %s" title="No" style="color: red;""></span>', $glyph);
    } else {
        return '';
    }
}

function priceFormat($float, $euro = true) {
    return ($euro ? '&euro; ' : '') . number_format($float, 2);
}

function hasGameChanged($game)
{
    global $oDatabase;

    if (!$game['id']) {
        die('hasGameChanged: no game id.' . var_export($game));
    }

    $oOldGame = new Game($oDatabase);
    $oOldGame->getById($game['id']);

    if ($oOldGame->format == '') {
        return 'format was empty';
    }

    $newgame = [
        'id'                    => $game['id'],
        'completion_perc'       => $game['My Completion Percentage'],
        'completion_estimate'   => $game['Completion Estimate'],
        'hours_played'          => $game['Hours Played'],
        'achievements_won'      => $game['Achievements Won (incl. DLC)'],
        'achievements_total'    => $game['Max Achievements (incl. DLC)'],
        'walkthrough_url'       => $game['Walkthrough'],
        'game_url'              => $game['Game URL'],
    ];

    $aChanges = [];
    if ($oOldGame->completion_perc != $newgame['completion_perc']) {
        if ($newgame['completion_perc'] == 100) {
            $aChanges[] = 'game completed';
        } elseif ($oOldGame->completion_perc == 0) {
            $aChanges[] = 'game started';
        } else {
            $aChanges[] = 'completion percentage change';
        }
    }

    if ($oOldGame->completion_estimate != $newgame['completion_estimate']) $aChanges[] = 'completion estimate change';
    if ($oOldGame->hours_played != $newgame['hours_played']) $aChanges[] = 'more hours played';
    if ($oOldGame->achievements_won != $newgame['achievements_won']) $aChanges[] = 'more achievements unlocked';
    if ($oOldGame->achievements_total != $newgame['achievements_total']) $aChanges[] = 'new dlc appeared';
    if ($oOldGame->game_url != $newgame['game_url']) $aChanges[] = 'game url changed';

    if ($oOldGame->walkthrough_url != $newgame['walkthrough_url']) {
        if ($oOldGame->walkthrough_url == '') {
            $aChanges[] = 'walkthrough added';
        } else {
            $aChanges[] = 'walkthrough url changed';
        }
    }

    return $aChanges ? implode(' - ', $aChanges) : false;
}

function importCsvIntoDatabase($data) {

    global $oDatabase;

    try {
        $csv = array_map('str_getcsv', $data);

        //trim game name column since it has a leading space for some reason
        $csv[0][0] = trim($csv[0][0]);
        if ($csv[0][0] != 'Game name') {
            return ['error' => 'Invalid CSV.'];
        }

        //convert csv into associative array with column headers (row 1) as keys
        array_walk($csv, function(&$a) use ($csv) {
            $a = array_combine($csv[0], $a);
        });
        array_shift($csv);
    } catch(Exception $e) {
        return ['error' => 'Invalid CSV.'];
    }
    if (!$csv || empty($csv[0]['Game name'])) {
        return ['error' => 'Invalid CSV.'];
    }

    $id = -1; //@TODO: get lowest id from database and substract 1 instead of hardcoding -1
    $csvgames = [];
    $newgames = [];
    $updatedgames = [];
    $notupdatedgames = [];
    $removedgames = [];
    foreach ($csv as $line) {

        //hacks
        $line['Game name'] = str_replace('–', '-', $line['Game name']); // mdash in BATMAN telltale games
        $line['Game name'] = str_replace('²', '2', $line['Game name']); // geometry wars evolved '2'
        $line['Game name'] = str_replace('├â┬ø', 'U', $line['Game name']); // ABZU ^ @TODO this doesn't work
        if ($line['Ownership Status'] == 'No longer have') {
            //count sold games as such, combining ownership status into format
            $line['Format'] == 'Sold';
        }
        if ($line['Completion Date']) {
            //convert 11/11/2008 16:48:57 to proper date
            $date = date_create_from_format('d/m/Y H:i:s', $line['Completion Date']);
            $line['Completion Date'] = $date->format('Y-m-d H:i:s');
        }
        if ($line['Format'] == '') {
            //default format is 'digital'
            $line['Format'] = 'Digital';
        }

        $oGame = new Game($oDatabase);
        //try to look up game id
        if ($gameid = $oGame->getIdByUrl($line['Game URL'])) {
            $oGame->id = $gameid;
            $line['id'] = $gameid;
            if ($changes = hasGameChanged($line)) {
                $line['changes'] = $changes;
                $updatedgames[] = $line;

                //update existing game
                $oGame->id                  = $gameid;
                $oGame->name                = $line['Game name'];
                $oGame->platform            = $line['Platform'];
                $oGame->completion_perc     = $line['My Completion Percentage'];
                $oGame->completion_estimate = $line['Completion Estimate'];
                $oGame->hours_played        = $line['Hours Played'];
                $oGame->achievements_won    = $line['Achievements Won (incl. DLC)'];
                $oGame->achievements_total  = $line['Max Achievements (incl. DLC)'];
                $oGame->gamerscore_won      = $line['GamerScore Won (incl. DLC)'];
                $oGame->gamerscore_total    = $line['Max Gamerscore (incl. DLC)'];
                $oGame->ta_score            = $line['TrueAchievement Won (incl. DLC)'];
                $oGame->ta_total            = $line['Max TrueAchievement (incl. DLC)'];
                $oGame->dlc                 = ($line['Max Gamerscore (incl. DLC)'] != $line['Max GamerScore (No DLC)'] ? 1 : 0);
                if ($oGame->dlc) {
                    $ta_dlc_won = $line['TrueAchievement Won (incl. DLC)'] - $line['TrueAchievement Won (No DLC)'];
                    $ta_dlc_total = $line['Max TrueAchievement (incl. DLC)'] - $line['Max TrueAchievement (No DLC)'];
                    $oGame->dlc_completion  = intval($ta_dlc_won / $ta_dlc_total * 100);
                } else {
                    $oGame->dlc_completion  = 0;
                }
                $oGame->completion_date     = $line['Completion Date'];
                $oGame->site_rating         = $line['Site Rating'];
                $oGame->format              = $line['Format'];
                $oGame->walkthrough_url     = $line['Walkthrough'];
                $oGame->game_url            = $line['Game URL'];
                $oGame->save();
            } else {
                $notupdatedgames[] = $line;
            }
        } else {
            //insert as a new game, use negative game id
            $oGame->id                  = $id;
            $oGame->name                = $line['Game name'];
            $oGame->platform            = $line['Platform'];
            $oGame->completion_perc     = $line['My Completion Percentage'];
            $oGame->completion_estimate = $line['Completion Estimate'];
            $oGame->hours_played        = $line['Hours Played'];
            $oGame->achievements_won    = $line['Achievements Won (incl. DLC)'];
            $oGame->achievements_total  = $line['Max Achievements (incl. DLC)'];
            $oGame->gamerscore_won      = $line['GamerScore Won (incl. DLC)'];
            $oGame->gamerscore_total    = $line['Max Gamerscore (incl. DLC)'];
            $oGame->ta_score            = $line['TrueAchievement Won (incl. DLC)'];
            $oGame->ta_total            = $line['Max TrueAchievement (incl. DLC)'];
            $oGame->dlc                 = ($line['Max Gamerscore (incl. DLC)'] == $line['Max GamerScore (No DLC)']);
            if ($oGame->dlc) {
                $ta_dlc_won = $line['TrueAchievement Won (incl. DLC)'] - $line['TrueAchievement Won (No DLC)'];
                $ta_dlc_total = $line['Max TrueAchievement Won (incl. DLC)'] - $line['Max TrueAchievement (No DLC)'];
                $oGame->dlc_completion  = intval($ta_dlc_won / $ta_dlc_total * 100);
            } else {
                $oGame->dlc_completion  = 0;
            }
            $oGame->completion_date     = $line['Completion Date'];
            $oGame->site_rating         = $line['Site Rating'];
            $oGame->format              = $line['Format'];
            $oGame->walkthrough_url     = $line['Walkthrough'];
            $oGame->game_url            = $line['Game URL'];
            $oGame->status              = 'available';
            $oGame->save();

            $line['id'] = $oGame->id;
            $newgames[] = $line;
            $id--;
        }
        $csvgames[$line['id']] = $line['Game name'];
    }

    $dbgames = $oGame->getAll();
    foreach ($dbgames as $oGame) {
        if (!in_array($oGame->id, array_keys($csvgames))) {
            $removedgames[] = $oGame;
        }
    }

    return [
        'new' => $newgames,
        'updated' => $updatedgames,
        'notupdated' => $notupdatedgames,
        'removed' => $removedgames,
    ];
}

function importJsonIntoDatabase($json) {

    global $oDatabase;

    try {
        $prices = json_decode($json);
    } catch (Exception $e) {
        return ['error' => 'Invalid JSON.'];
    }
    if (!$prices) {
        return ['error' => 'Invalid JSON.'];
    }

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

        if ($changes = hasPriceChanged($game)) {

            if ($changes['status']) {
                /**
                 * scenarios:
                 * - game discounted
                 * - game no longer discounted
                 * - game delisted
                 * - game available again
                 * - game now unavailable
                 */
                switch (true) {
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

function hasPriceChanged($game)
{
    global $oDatabase;

    $oGame = new Game($oDatabase);
    $oGame->getById($game->id);

    $return = ['status' => false, 'price' => false];

    if (!$oGame->id) {
        //game isn't in database yet
        $return['status'] = ['old' => false, 'new' => $game->status];
        $return['price'] = ['old' => false, 'new' => $game->price];

        return $return;
    }

    if ($game->status != $oGame->status) {
        $return['status'] = ['old' => $oGame->status, 'new' => $game->status];
    }
    if ($game->price != $oGame->current_price) {
        $return['price'] = ['old' => $oGame->current_price, 'new' => $game->price];
    }

    return ($return['status'] || $return['price'] ? $return : false);
}

class Game
{
    public $id;
    public $newid;
    public $name;
    public $platform;
    public $backcompat;
    public $kinect_required;
    public $peripheral_required;
    public $online_multiplayer;
    public $completion_perc;
    public $completion_estimate;
    public $hours_played;
    public $achievements_won;
    public $achievements_total;
    public $gamerscore_won;
    public $gamerscore_total;
    public $ta_score;
    public $ta_total;
    public $dlc;
    public $dlc_completion;
    public $completion_date;
    public $site_rating;
    public $format;
    public $status;
    public $purchased_price;
    public $current_price;
    public $regular_price;
    public $shortlist_order;
    public $walkthrough_url;
    public $game_url;
    public $last_modified;
    public $date_created;

    public function __construct($oDatabase)
    {
        $this->db = $oDatabase;
    }

    private function fillObject($gameArr)
    {
        foreach ($gameArr as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    public function getById($id)
    {
        $game = $this->db->query_single('
            SELECT *
            FROM mygamecollection
            WHERE id = :id
            LIMIT 1',
            ['id' => $id]
        );

        return $this->fillObject($game);
    }

    public function getIdByUrl($url)
    {
        return $this->db->query_value('
            SELECT id
            FROM mygamecollection
            WHERE game_url = :url
            LIMIT 1',
            [':url' => $url]
        );
    }

    public function getAll()
    {
        $games = $this->db->query('
            SELECT *
            FROM mygamecollection
            ORDER BY id'
        );
        foreach ($games as $key => $game) {
            $games[$key] = $this->fillObject($game);
        }

        return $games;
    }

    public function save()
    {
        /*
         * scenarios:
         * - update a game normally: id > 0 and newid blank
         * - insert a new game, id < 0 and newid blank
         * - update a new game, id < 0 and newid > 0
         */
        if ($this->id < 0 && empty($this->newid)) {
            return $this->insert();
        } else {
            return $this->update();
        }
    }

    private function update()
    {
        $data = get_object_vars($this);
        unset($data['db']);
        unset($data['last_modified']);
        unset($data['date_created']);

        if (empty($data['newid'])) {
            $data['newid'] = $data['id'];
        }

        return $this->db->query('
            UPDATE mygamecollection
            SET id = :newid,
                name = :name,
                platform = :platform,
                backcompat = NULLIF(:backcompat, ""),
                kinect_required = NULLIF(:kinect_required, ""),
                peripheral_required = NULLIF(:peripheral_required, ""),
                online_multiplayer = NULLIF(:online_multiplayer, ""),
                completion_perc = :completion_perc,
                completion_estimate = :completion_estimate,
                hours_played = :hours_played,
                achievements_won = :achievements_won,
                achievements_total = :achievements_total,
                gamerscore_won = :gamerscore_won,
                gamerscore_total = :gamerscore_total,
                ta_score = :ta_score,
                ta_total = :ta_total,
                dlc = :dlc,
                dlc_completion = :dlc_completion,
                completion_date = NULLIF(:completion_date, ""),
                site_rating = :site_rating,
                format = COALESCE(NULLIF(:format, ""), format),
                status = COALESCE(NULLIF(:status, ""), status),
                purchased_price = COALESCE(NULLIF(:purchased_price, ""), purchased_price),
                current_price = COALESCE(NULLIF(:current_price, ""), current_price),
                regular_price = COALESCE(NULLIF(:regular_price, ""), regular_price),
                shortlist_order = :shortlist_order,
                walkthrough_url = :walkthrough_url,
                game_url = :game_url,
                last_modified = NOW()
            WHERE id = :id
            LIMIT 1',
            $data
        );
    }

    private function insert()
    {
        $data = get_object_vars($this);
        unset($data['db']);
        unset($data['last_modified']);
        unset($data['date_created']);
        unset($data['newid']);

        return $this->db->query('
            INSERT INTO mygamecollection
            SET id = :id,
                name = :name,
                platform = :platform,
                backcompat = NULLIF(:backcompat, ""),
                kinect_required = NULLIF(:kinect_required, ""),
                peripheral_required = NULLIF(:peripheral_required, ""),
                online_multiplayer = NULLIF(:online_multiplayer, ""),
                completion_perc = :completion_perc,
                completion_estimate = :completion_estimate,
                hours_played = :hours_played,
                achievements_won = :achievements_won,
                achievements_total = :achievements_total,
                gamerscore_won = :gamerscore_won,
                gamerscore_total = :gamerscore_total,
                ta_score = :ta_score,
                ta_total = :ta_total,
                dlc = :dlc,
                dlc_completion = :dlc_completion,
                completion_date = NULLIF(:completion_date, ""),
                site_rating = :site_rating,
                format = :format,
                status = :status,
                purchased_price = NULLIF(:purchased_price, ""),
                current_price = NULLIF(:current_price, ""),
                regular_price = NULLIF(:regular_price, ""),
                shortlist_order = NULLIF(:shortlist_order, ""),
                walkthrough_url = :walkthrough_url,
                game_url = :game_url,
                last_modified = NOW(),
                date_created = NOW()',
            $data
        );
    }

    public function delete()
    {
        return $this->db->query('
            DELETE FROM mygamecollection
            WHERE id = :id
            LIMIT 1',
            ['id' => $this->id]
        );
    }

    private function getMaxShortlistOrder()
    {
        return $this->db->query_value('
            SELECT COALESCE(MAX(shortlist_order), 0)
            FROM mygamecollection'
        );
    }

    public function addToShortlist()
    {
        $this->shortlist_order = $this->getMaxShortlistOrder() + 1;

        return $this->save();
    }

    public function shortlistUp()
    {
        if ($this->shortlist_order == 1 || is_null($this->shortlist_order)) {
            return;
        }

        $switchId = $this->db->query_value('
            SELECT id
            FROM mygamecollection
            WHERE shortlist_order = :order
            LIMIT 1',
            [':order' => $this->shortlist_order - 1]
        );

        if ($switchId) {
            $this->db->query('
                UPDATE mygamecollection
                SET shortlist_order = shortlist_order + 1
                WHERE id = :id
                LIMIT 1',
                [':id' => $switchId]
            );
        } else {
        }

        $this->shortlist_order--;
        $this->save();
    }

    public function shortlistDown()
    {
        if ($this->shortlist_order == $this->getMaxShortlistOrder() || is_null($this->shortlist_order)) {
            return;
        }

        $switchId = $this->db->query_value('
            SELECT id
            FROM mygamecollection
            WHERE shortlist_order = :order
            LIMIT 1',
            [':order' => $this->shortlist_order + 1]
        );

        if ($switchId) {
            $this->db->query('
                UPDATE mygamecollection
                SET shortlist_order = shortlist_order - 1
                WHERE id = :id
                LIMIT 1',
                [':id' => $switchId]
            );
        }

        $this->shortlist_order++;
        $this->save();
    }

    //wrapper for creating a game during the price import
    public function createByPriceData($game)
    {
        $this->id = $game->id;
        $this->name = $game->name;
        $this->game_url = $game->url;
        $this->current_price = $game->price;
        $this->regular_price = $game->saleFrom;
        $this->status = $game->status;
        $this->date_created = $game->timestamp;
        $this->last_modified = $game->timestamp;

        $this->completion_perc = 0;
        $this->platform = '';
        $this->gamerscore_total = 0;
        $this->hours_played = 0;
        $this->achievements_won = 0;
        $this->achievements_total = 0;
        $this->gamerscore_won = 0;
        $this->gamerscore_total = 0;
        $this->dlc = 0;
        $this->dlc_completion = 0;

        $this->insert();
    }
}

class Request
{
    private $queryVars = [];
    private $postVars = [];
    private $cookies = [];
    private $files = [];

    public function __construct()
    {
        $this->getQueryVars();
        $this->getPostVars();
        $this->getCookies();
        $this->getFiles();
    }

    private function getQueryVars()
    {
        foreach ($_GET as $key => $value) {
            $this->queryVars[$key] = $value;
        }
    }

    private function getPostVars()
    {
        foreach ($_POST as $key => $value) {
            $this->postVars[$key] = $value;
        }

        $this->postBody = $_POST;
    }

    private function getCookies()
    {
        foreach ($_COOKIE as $key => $value) {
            $this->cookies[$key] = $value;
        }
    }

    private function getFiles()
    {
        foreach ($_FILES as $field => $file) {
            if (is_array($file['tmp_name'])) {
                //multiple files
                $this->files[$field] = [];
                for ($i = 0; $i < count($file['tmp_name']); $i++) {
                    $file = [
                        'name' => $file['name'][$i],
                        'type' => $file['type'][$i],
                        'size' => $file['size'][$i],
                        'tmp_name' => $file['tmp_name'][$i],
                        'error' => $file['error'][$i],
                    ];
                    $this->files[$field][] = $file;
                }
            } else {
                //single file
                $this->files[$field] = [$file];
            }
        }
    }

    public function isGet()
    {
        return filter_input(INPUT_SERVER, 'REQUEST_METHOD') == 'GET';
    }

    public function isPost()
    {
        return filter_input(INPUT_SERVER, 'REQUEST_METHOD') == 'POST';
    }

    public function getInt($var)
    {
        return (isset($this->queryVars[$var]) ? filter_var($this->queryVars[$var], FILTER_SANITIZE_NUMBER_INT) : 0);
    }

    public function getStr($var)
    {
        return (isset($this->queryVars[$var]) ? filter_var($this->queryVars[$var], FILTER_SANITIZE_STRING) : '');
    }

    public function postInt($var)
    {
        return (isset($this->postVars[$var]) ? filter_var($this->postVars[$var], FILTER_SANITIZE_NUMBER_INT) : 0);
    }

    public function postStr($var)
    {
        return (isset($this->postVars[$var]) ? filter_var($this->postVars[$var], FILTER_SANITIZE_STRING) : '');
    }

    public function cookie($name)
    {
        return (isset($this->cookies[$name]) ? $this->cookies[$name] : '');
    }

    public function file($field)
    {
        return (isset($this->files[$field]) ? $this->files[$field] : []);
    }
}
