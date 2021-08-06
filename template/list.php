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
                    <?php if (isset($aStats['most_expensize_purchased'])): ?>
                    <span class="btn btn-danger">Most expensive buy:<br/><?= $aStats['most_expensive_purchased']['name'] ?> <span class="badge"><?= priceFormat($aStats['most_expensive_purchased']['purchased_price']) ?></span></span>
                    <span class="btn btn-danger">Most expensive current:<br/><?= $aStats['most_expensive_current']['name'] ?> <span class="badge"><?= priceFormat($aStats['most_expensive_current']['current_price']) ?></span></span>
                    <?php endif; ?>
                    <?php if (!empty($aStats['spent_year'])): ?>
                    <span class="btn btn-warning">Money spent last week:<br/><span class="badge" title="<?= implode(', ', $aStats['spent_week_tooltip']) ?>"><?= priceFormat($aStats['spent_week']) ?></span></span>
                    <span class="btn btn-warning">Money spent last month:<br/><span class="badge" title="<?= implode(', ', $aStats['spent_month_tooltip']) ?>"><?= priceFormat($aStats['spent_month']) ?></span></span>
                    <span class="btn btn-warning">Money spent last 6 months:<br/><span class="badge" title="<?= implode(', ', $aStats['spent_6month_tooltip']) ?>"><?= priceFormat($aStats['spent_6month']) ?></span></span>
                    <span class="btn btn-warning">Money spent last year:<br/><span class="badge"><?= priceFormat($aStats['spent_year']) ?></span></span>
                    <?php endif; ?>
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
                        <a class="btn btn-info <?= ($sShow == 'easiest' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=easiest">Easiest</a>
                        <a class="btn btn-info <?= ($sShow == 'hardest' ? 'active' : '') ?>" href="<?= $sThisFile ?>?show=hardest">Hardest</a>
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

                <?php if (!$aGames && !$sShow && !$sSearch): ?>
                    <?php require 'firstrun.php'; ?>
                <?php elseif (!$aGames && $sSearch): ?>
                    <!-- search has no results -->
                <?php elseif (!$aGames && $sShow): ?>
                    <!-- filter has no results -->
                <?php else: ?>

                <?php if ($sShow == 'new' && $aGames): ?>
                <p>This filter shows games that were imported into your library without their corresponding TA game id. To fix this,
                delete them from here, run the price scraper, import the .json file and then import your game collection .csv again.</p>
                <?php endif; ?>

                <table class="table table-condensed table-hover">

                    <tr>
                        <th>Game name (<?= $iCount ?>)</th>
                        <th class="hidden-xs">Platform</th>
                        <th class="hidden-xs">Ratio</th>
                        <th class="text-nowrap">C<span class="hidden-xs">omp</span> %</th>
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
                        $hoursPlayed = '';
                        if ($aGame['hours_played']) {
                            $hoursPlayed = sprintf('title="%d hours played"', $aGame['hours_played']);
                        }
                        $sRatioType = 'green';
                        $dRatio = 0;
                        if ($aGame['gamerscore_total']) {
                            $dRatio = floatval($aGame['ta_total']) / floatval($aGame['gamerscore_total']);
                        }
                        switch (true) {
                            case $dRatio < 2:
                                $sRatioType = 'ratio-veryeasy';
                                break;
                            case $dRatio < 3:
                                $sRatioType = 'ratio-easy';
                                break;
                            case $dRatio < 4:
                                $sRatioType = 'ratio-medium';
                                break;
                            case $dRatio < 5:
                                $sRatioType = 'ratio-hard';
                                break;
                            default;
                                $sRatioType = 'ratio-veryhard';
                                break;
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
                            <td class="hidden-xs <?= $sRatioType ?>"><?= number_format($dRatio, 2) ?></td>
                            <td class="<?= $sGameCompStatus ?> text-center text-nowrap"><?= $aGame['completion_perc'] ?> %</td>
                            <td class="<?= $sCompletionEstimate ?>">
                                <span class="hidden-xs" <?= $hoursPlayed ?>><?= $aGame['completion_estimate'] ?></span>
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
                                        <a href="?id=<?= $aGame['id'] ?>&page=<?= $iPage ?>&show=<?= $sShow ?>&search=<?= $sSearch ?>"><span class="glyphicon glyphicon-pencil"></span></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <?php endif; ?>

                <!-- ============== PAGINATION ============== -->

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
