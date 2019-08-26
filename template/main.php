<!DOCTYPE html>
<html>
    <head>
        <title>My Game Collection</title>
        <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js"></script>
        <script src="template/main.js"></script>
        <link href="template/main.css" rel="stylesheet" type="text/css" />
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

                <?php if (defined('FORM_PASSWORD') && FORM_PASSWORD) : ?>
                    <div class="form-group">
                        <label for="password" class="col-sm-2 control-label">Password</label>
                        <div class="col-sm-10">
                            <input type="password" id="password" name="password" class="form-control" />
                        </div>
                    </div>
                <?php endif; ?>

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
                        <a class="btn btn-default <?= ($sShow == 'paid' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=paid">Purchased games</a>
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
                        <?php if (defined('FORM_PASSWORD') && FORM_PASSWORD) : ?>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password">
                        <?php endif; ?>
                        <input class="btn btn-info hidden-xs" name="action" type="submit" value="Import prices JSON" />
                        <input class="btn btn-info hidden-xs" name="action" type="submit" value="Import game collection CSV" />
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
