            <form method="post" action="<?= $sThisFile ?>?page=<?= $iPage ?>&show=<?= $sShow ?>&search=<?= $sSearch ?>" class="form-horizontal" role="form">

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

                <?php if ($aData['platform'] == 'Xbox 360'): ?>
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
                <?php endif; ?>

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
                            <a href="<?= $sThisFile ?>?page=<?= $iPage ?>&show=<?= $sShow ?>&search=<?= $sSearch ?>" class="btn btn-default">Cancel</a>
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
                        <input type="text" id="completion_perc" name="completion_perc" disabled class="form-control" value="<?= $aData['completion_perc'] ?> %" required />
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
                    <label for="dlc" class="col-sm-2 control-label">DLC available</label>
					<div class="col-sm-10">
						<input type="text" id="dlc" name="dlc" disabled class="form-control" value="<?= $aData['dlc'] ? 'yes' : 'no' ?>" />
					</div>
                </div>

                <div class="form-group">
                    <label for="dlc_completion" class="col-sm-2 control-label">DLC completion percentage</label>
					<div class="col-sm-10">
						<input type="text" id="dlc_completion" name="dlc_completion" disabled class="form-control" value="<?= $aData['dlc_completion'] ?> %" />
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

                <div class="form-group">
                    <label for="date_created" class="col-sm-2 control-label">Date added</label>
					<div class="col-sm-10">
						<input type="text" id="date_created" name="date_created" disabled class="form-control" value="<?= (new DateTime($aData['date_created']))->format('Y-m-d') ?>" />
					</div>
                </div>

                <div class="form-group">
                    <label for="last_modified" class="col-sm-2 control-label">Date last modified</label>
					<div class="col-sm-10">
						<input type="text" id="last_modified" name="last_modified" disabled class="form-control" value="<?= (new DateTime($aData['last_modified']))->format('Y-m-d') ?>" />
					</div>
                </div>

            </form>
