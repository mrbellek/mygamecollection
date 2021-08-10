<?php
/**
 * This CLI script will scrape your TrueAchievements game collection, and all the current prices for it.
 * It will export a .json file which can then be imported into the main mygamecollection script.
 *
 * If you get outdated info, delete the prices json cache file and try again.
 *
 * TODO:
 * . sales?
 * . write everything to database instead of exporting as .json
 * - error reporting at the end
 * - html interface?
 * - urls are www.ta.com/www.ta.com/game
 */

//define('DB_HOST', '');
//define('DB_USER', '');
//define('DB_NAME', '');
//define('DB_PASS', '');

(new XboxCalculator)->run();

class XboxCalculator
{
    private $baseurl = 'https://www.trueachievements.com/';
    private $gamecollection = 'https://www.trueachievements.com/gamer/%s/gamecollection';
    private $gamepage = 'https://www.trueachievements.com/game/%s/achievements';
    private $pricepage = 'https://www.trueachievements.com/ajaxfunctions.aspx/ContentRecord_Regional';

    private $gamecollectionFullList = 'https://www.trueachievements.com/gamer/%s/gamecollection?executeformfunction&function=AjaxList&params=oGameCollection%%7C%%26ddlSortBy%%3DTitlename%%26ddlDLCInclusionSetting%%3DDLCIOwn%%26sddGameMediaID%%3D%%20%%26ddlStartedStatus%%3D0%%26asdGamePropertyID%%3D-1%%26GameView%%3DoptListView%%26chkColTitleimage%%3DTrue%%26chkColTitlename%%3DTrue%%26chkColPlatform%%3DTrue%%26chkColSiteScore%%3DTrue%%26chkColOfficialScore%%3DTrue%%26chkColItems%%3DTrue%%26chkColCompletionpercentage%%3DTrue%%26chkColMyrating%%3DTrue%%26chkColDatestarted%%3DTrue%%26chkColDatecompleted%%3DTrue%%26chkColLastunlock%%3DTrue%%26chkColOwnershipstatus%%3DTrue%%26chkColMedia%%3DTrue%%26chkColPlaystatus%%3DTrue%%26chkColGamerscompletedpercentage%%3DTrue%%26chkColCompletionestimate%%3DTrue%%26chkColWalkthrough%%3DTrue%%26chkColSiteratio%%3DTrue%%26chkColSiterating%%3DTrue%%26chkColUnobtainables%%3DTrue%%26txtBaseComparisonGamerID%%3D71675%%26oGameCollection_Order%%3DTitlename%%26oGameCollection_Page%%3D1%%26oGameCollection_ItemsPerPage%%3D99999%%26oGameCollection_ResponsiveMode%%3DFalse%%26oGameCollection_ShowAll%%3DFalse%%26txtGamerID%%3D%s%%26txtGameRegionID%%3D%s%%26txtUseAchievementsForProgress%%3DTrue';

    //regions for ajax price request
    private $regions = [
        'US' => 1,
        'Canada' => 7,
        'Australia' => 6,
        'UK' => 2,
        'Europe' => 4,
        'Brazil' => 11,
    ];

    //some games have no boxart, meaning we can't find the game id
    private $missinggameids = [
        'Trucking 3D (Win 8)' => 5752,
    ];

    //input
    private $gamertag;
    private $region;

    //internal global vars
    private $gamerid;
    private $games;
    private $basexpath;
    private $skipcache = true;

    public function __construct()
    {
        libxml_use_internal_errors(true);

        if (php_sapi_name() == 'cli') {
            global $argv;
            if (empty($argv[1])) {
                printf('Usage: %s gamertag [region]' . PHP_EOL, $argv[0]);
                printf('- Regions: %s (default is US)' . PHP_EOL, implode(', ', array_keys($this->regions)));
                exit();
            }
            $this->gamertag = !empty($argv[1]) ? $argv[1] : 'mrbellek';
            $this->region = !empty($argv[2]) ? $argv[2] : 'US';
        } else {
            $gamertag = filter_input(INPUT_GET, 'gamertag');
            $this->gamertag = $gamertag ? $gamertag : 'mrbellek';

            $region = filter_input(INPUT_GET, 'region');
            $this->region = $region ? $region : 'US';
        }

        if (!isset($this->regions[$this->region])) {
            printf('Invalid region "%s", falling back to US' . PHP_EOL, $this->region);
            $this->region = 'US';
        }
    }

    public function run()
    {
        //$this->skipcache = true;

        /*try {
            $this->oPDO = new PDO(sprintf('mysql:host=%s;dbname=%s', DB_HOST, DB_NAME), DB_USER, DB_PASS);
        } catch (Exception $e) {
            die('Database connection failed. ' . $e->getMessage());
        }*/

        printf('Fetching %s\'s game collection..' . PHP_EOL, $this->gamertag);
        $this->fetchGamerId();
        $this->fetchGameCollection();

        //$this->importCsvToDatabase();
        //$this->importJsonToDatabase();
        //$this->updateDatabase();
        //die('stop');

        $this->fetchPrices();
        $this->printSummary();

        //$this->printCsvImport();
    }

    private function printCsvImport()
    {
        if (!$this->games || !$this->prices) {
            die('Games/prices missing. Import went wrong?' . PHP_EOL);
        } elseif ($this->games->length != count($this->prices)) {
            die(sprintf('Games count mismatch between list view (%s) and prices (%s)!' . PHP_EOL, $this->games->length, count($this->prices)));
        }

        die(var_dump($this->prices));
    }

    private function importJsonToDatabase()
    {
        $prices = json_decode(file_get_contents('C:\Users\Merijn\Documents\Twitterbot\xboxcalculator-prices-europe.json'));
        $i = 0;
        foreach ($prices as $game) {
            printf('UPDATE mygamecollection SET current_price = %s, status = "%s", last_modified = "%s" WHERE id = %d LIMIT 1;' . PHP_EOL,
                $game->price,
                $game->status,
                $game->timestamp,
                $game->id
            );
            $i++;
        }
        printf('processed %d games' . PHP_EOL, $i);
    }

    private function importCsvToDatabase()
    {
        $csv = array_map('str_getcsv', file('C:\Users\Merijn\Downloads\My Xbox Game Collection - MyGameCollection_ 150218_100728.csv'));
        $csv[0][0] = trim($csv[0][0]);
        array_walk($csv, function(&$a) use ($csv) {
            $a = array_combine($csv[0], $a);
        });
        array_shift($csv);

        foreach ($csv as $game) {
            $completiondate = 'NULL';
            if (!empty($game['Completion Date'])) {
                $a = DateTime::createFromFormat('d/m/Y H:i:s', $game['Completion Date']);
                if ($a) {
                    $completiondate = '"' . $a->format('Y-m-d H:i:s') . '"';
                }
            }
            //printf('UPDATE mygamecollection SET platform = "%s", backcompat = %s, kinect_required = %s, peripheral_required = %s, online_multiplayer = %s, completion_perc = %d, completion_estimate = "%s", hours_played = %d, achievements_won = %d, achievements_total = %d, gamerscore_won = %d, gamerscore_total = %d, ta_score = %d, completion_date = %s, site_rating = %s, format = "%s", walkthrough_url = "%s" WHERE name = "%s" LIMIT 1;' . PHP_EOL,
            printf('UPDATE mygamecollection SET backcompat = %s, kinect_required = %s, peripheral_required = %s, online_multiplayer = %s WHERE name = "%s" LIMIT 1;' . PHP_EOL,
                ($game['BC'] == 'yes' ? 1 : ($game['BC'] == 'no' ? 0 : 'NULL')),
                ($game['Kinect?'] == 'yes' ? 1 : ($game['Kinect?'] == 'no' ? 0 : 'NULL')),
                ($game['Periph?'] == 'yes' ? 1 : ($game['Periph?'] == 'no' ? 0 : 'NULL')),
                ($game['Online?'] == 'yes' ? 1 : ($game['Online?'] == 'no' ? 0 : 'NULL')),
                /*$game['My Completion Percentage'],
                $game['Completion Estimate'],
                $game['Hours Played'],
                $game['Achievements Won (incl. DLC)'],
                $game['Max Achievements (incl. DLC)'],
                $game['GamerScore Won (incl. DLC)'],
                $game['Max Gamerscore (incl. DLC)'],
                $game['Max TrueAchievement (incl. DLC)'],
                $completiondate,
                !empty($game['Site Rating']) ? $game['Site Rating'] : 'NULL',
                $game['Format'],
                $game['Walkthrough'],*/
                $game['Game name']
            );
        }
        printf('done processing %d games' . PHP_EOL, count($csv));
    }

    private function updateDatabase()
    {
        //update database from current game collection (or cached version)
        print('INSERT INTO mygamecollection (id, name, completion_perc, achievements_won, achievements_total, gamerscore_won, gamerscore_total, ta_score, game_url, last_modified) VALUES' . PHP_EOL);

        foreach ($this->games as $gamegroupname) {
            list($gameid, $title, $url) = $this->parseBasicInfo($gamegroupname);

            $game = [
                'id' => $gameid,
                'name' => $title,
                'game_url' => $url,
            ];

            //NB: some games have challenges, which adds the class 'haschallenge' to the div#statistics element
            $els = $this->basexpath->query('div[contains(@class,"statistics")]/div[@class="wrapper"]/div[@class="statistic"]', $gamegroupname);

            $p = $this->basexpath->query('div/p[@class="bottomborder"]', $els[0]);
            if ($p->length) {
                //$game['ta_score_unlocked'] = $p[0]->textContent;
                $p = $this->basexpath->query('div/p[@class="small"]', $els[0]);
                $game['ta_score'] = str_replace(',', '', $p[0]->textContent);
            } else {
                $p = $this->basexpath->query('div/p', $els[0]);
                $game['ta_score'] = str_replace(',', '', $p[0]->textContent);
            }

            $p = $this->basexpath->query('div/p[@class="bottomborder"]', $els[1]);
            if ($p->length) {
                $game['gamerscore_won'] = str_replace(',', '', $p[0]->textContent);
                $p = $this->basexpath->query('div/p[@class="small"]', $els[1]);
                $game['gamerscore_total'] = str_replace(',', '', $p[0]->textContent);
            } else {
                $p = $this->basexpath->query('div/p', $els[1]);
                $game['gamerscore_won'] = 0;
                $game['gamerscore_total'] = str_replace(',', '', $p[0]->textContent);
            }

            $p = $this->basexpath->query('div/p[@class="bottomborder"]', $els[2]);
            if ($p->length) {
                $game['achievements_won'] = $p[0]->textContent;
                $p = $this->basexpath->query('div/p[@class="small"]', $els[2]);
                $game['achievements_total'] = $p[0]->textContent;
            } else {
                $p = $this->basexpath->query('div/p', $els[2]);
                $game['achievements_won'] = 0;
                $game['achievements_total'] = $p[0]->textContent;
            }

            $el = $this->basexpath->query('div[@class="statistics"]/div[@class="wrapper"]/div[@class="statistic narrow"]/p[@class="percentage"]', $gamegroupname);
            if ($el->length) {
                $game['completion_perc'] = rtrim($el[0]->textContent, '%');
            } else {
                $game['completion_perc'] = 0;
            }

            printf('(%d, "%s", %d, %d, %d, %d, %d, %d, "%s", NOW()),' . PHP_EOL,
                $game['id'],
                $game['name'],
                $game['completion_perc'],
                $game['achievements_won'],
                $game['achievements_total'],
                $game['gamerscore_won'],
                $game['gamerscore_total'],
                $game['ta_score'],
                $this->baseurl . $game['game_url']
            );
        }
    }

    private function fetchGamerId()
    {
        //$this->gamerid = 71675;
        //return;

        //this may seem skippable but we need the request header cookies for subsequent requests
        $dom = new DOMDocument;
        $dom->loadHTML($this->getUrl(sprintf($this->gamecollection, $this->gamertag)));
        usleep(100000);

        $xpath = new DOMXPath($dom);
        $rsslink = $xpath->query('//link[@type="application/rss+xml"][2]');
        $linktarget = $rsslink[0]->getAttribute('href');
        if (preg_match('/gamerid=(.+)/', $linktarget, $matches)) {
            $this->gamerid = $matches[1];
        } else {
            die(sprintf('Unable to find gamerid for gamertag %s.' . PHP_EOL, $this->gamertag));
        }
    }

    private function fetchGameCollection()
    {
        if (!is_file('./xboxcalculator-cache.html') || $this->skipcache) {
            print('Fetching list view..' . PHP_EOL);
            $html = $this->getUrl(sprintf($this->gamecollectionFullList, $this->gamertag, $this->gamerid, $this->regions[$this->region]));
            if ($html) {
                file_put_contents('./xboxcalculator-cache.html', $html);
            }
        } else {
            printf('LOADING LIST VIEW FROM CACHE..' . PHP_EOL);
            $html = file_get_contents('./xboxcalculator-cache.html');
        }
        $dom = new DOMDocument;
        $dom->loadHTML($html);

        $this->basexpath = new DOMXPath($dom);
        $this->games = $this->basexpath->query('//tr[contains(@class, "green") or contains(@class, "even") or contains(@class, "odd")]');

        printf('Done - fetched %s games.' . PHP_EOL, $this->games->length);
    }

    private function fetchPrices()
    {
        $prices = [];
        $pricecachefile = sprintf('./xboxcalculator-prices-%s.json', strtolower($this->region));
        if (is_file($pricecachefile) && !$this->skipcache) {
            print('Loading cached prices..' . PHP_EOL);
            $prices = json_decode(file_get_contents($pricecachefile), true);
        }

        print('Fetching missing/outdated price info for games...' . PHP_EOL);
        $totalworth = 0;
        $highestpricedgame = ['price' => 0];
        $freegamescount = 0;
        $salecount = 0;
        $totalsaved = 0;
        $delistedcount = 0;
        $unavailablecount = 0;

        $i = 0;
        foreach ($this->games as $tablerow) {
            $status = 'available';
            $gameinfo = $this->parseBasicInfo($tablerow);
            list($gameid, $title, $url) = $gameinfo;

            if ($gameid > 0) {
                $skipPriceCheck = false;
                if (isset($prices[$gameid])) {
                    $lastTimestamp = $prices[$gameid]['timestamp'];
                    $price = $prices[$gameid]['price'];
                    //skip price check if price is not older than a week
                    if (new DateTime($lastTimestamp) > (new DateTime)->sub(new DateInterval('P7D')) && !$this->skipcache) {
                        $skipPriceCheck = true;
                    }
                }

                if (!$skipPriceCheck) {
                    $timestamp = (new DateTime)->format('Y-m-d H:i:s');

                    $priceInfo = $this->fetchPriceInfo($gameid, $title);
                    list($price, $status, $saleFrom) = $priceInfo;
                    if (is_numeric($price)) {
                        //check because price can also be 'Free'
                        $totalworth += $price;
                    }
                    $status = $status;

                } else {
                    //use cached data, don't modify entry
                    $price = $prices[$gameid]['price'];
                    $status = $prices[$gameid]['status'];
                    $saleFrom = $prices[$gameid]['saleFrom'];
                    $timestamp = $prices[$gameid]['timestamp'];
                    if (preg_match('/\d+[.,]\d+/', $price, $match)) {
                        $price = $match[0];
                        $totalworth += $price;
                    }
                }
            } else {
                printf('- %s: Unable to find game id!' . PHP_EOL, $title);
                $status = 'error';
                $price = null;
            }

            $gamePrice = [
                'id' => $gameid,
                'name' => $title,
                'url' => $url,
                'price' => number_format((double) $price, 2),
                'saleFrom' => ($status == 'sale' ? (double) $saleFrom : null),
                'status' => $status,
                'timestamp' => $timestamp,
            ];
            $prices[$gameid] = $gamePrice;

            if (is_numeric($price) && $price > $highestpricedgame['price']) {
                $highestpricedgame = $gamePrice;
            }

            if (strtolower(trim($price)) == 'free') {
                $freegamescount++;
            }

            switch ($status) {
                default:
                case 'available':
                    break;
                case 'sale':
                    $salecount++;
                    $totalsaved += ($saleFrom - (is_numeric($price) ? $price : 0));
                    break;
                case 'delisted':
                    $delistedcount++;
                    break;
                case 'region-locked':
                case 'unavailable':
                case 'error':
                    $unavailablecount++;
                    break;
            }

            $i++;
            if ($i > 10) {
                //break;
            }
        }

        echo PHP_EOL;
        print('Done!' . PHP_EOL);
        asort($prices);
        file_put_contents($pricecachefile, json_encode($prices, JSON_PRETTY_PRINT));
        $this->prices = $prices;

        $this->summary = [
            'totalworth' => $totalworth,
            'highestpricedgame' => $highestpricedgame,
            'freegamescount' => $freegamescount,
            'salecount' => $salecount,
            'totalsaved' => number_format((double) $totalsaved, 2),
            'delistedcount' => $delistedcount,
            'unavailablecount' => $unavailablecount,
        ];
    }

    /**
     * parse some basic info from a game node on the game collection page
     */
    private function parseBasicInfo($tablerow)
    {
        /*
         * cells:
         * 0 - thumb picture
         * 1 - name + url (class smallgame)
         * 2 - platform (game id in id attribute as tdPlatform_xxxx)
         * 3 - TA unlocked/total (class score)
         * 4 - GS unlocked/total (class score)
         * 5 - achievements unlocked/total (class score)
         * 6 - completion percentage
         * 7 - my rating (class rating, nested div with class rating-[xxx]-stars)
         * 8 - started date (class date)
         * 9 - completed date (class date)
         * 10 - last unlocked date (class date)
         * 11 - ownership (class small)
         * 12 - media (class small)
         * 13 - play status (class small)
         * 14 - site completion percentage
         * 15 - completion estimate
         * 16 - walkthrough link
         * 17 - site ratio (class score)
         * 18 - site rating (class rating)
         * 19 - unobtainables
         */
        $namelink = $this->basexpath->query('td[@class="smallgame"]/a', $tablerow);
        $name = $namelink->item(0)->textContent;
        $url = $namelink->item(0)->getAttribute('href');

        $cells = $this->basexpath->query('td', $tablerow);

        //2 - gameid + platform
        $temp = $cells->item(2);
        $gameid = 0;
        if (preg_match('/_(\d+)$/', $temp->getAttribute('id'), $m)) {
            $gameid = $m[1];
        }

        //return just this since the gamecollection csv will have the rest anyway and is way easier to parse
        return [$gameid, $name, $url];

        $temp = $this->basexpath->query('img', $temp);
        $platform = $temp->item(0)->getAttribute('title');

        //3 - trueachievements score unlocked + total
        if (preg_match('/(.+) \/ (.+)/', $cells->item(3)->textContent, $m)) {
            $ta_unlocked = str_replace(',', '', $m[1]);
            $ta_total = str_replace(',', '', $m[2]);
        }
        //4 - gamerscore unlocked + total
        if (preg_match('/(.+) \/ (.+)/', $cells->item(4)->textContent, $m)) {
            $gs_unlocked = str_replace(',', '', $m[1]);
            $gs_total = str_replace(',', '', $m[2]);
        }
        //5 - achievements unlocked + total
        if (preg_match('/(.+) \/ (.+)/', $cells->item(5)->textContent, $m)) {
            $ach_unlocked = $m[1];
            $ach_total = $m[2];
        }
        //6 - completion percentage
        $completion_perc = $cells->item(6)->textContent;

        //7 - my rating
        $my_rating = 0;
        $temp = $this->basexpath->query('div', $cells->item(7));
        if (preg_match('/rating-([0-9-]+)-stars/', $temp->item(0)->getAttribute('class'), $m)) {
            $my_rating = str_replace('-', '.', $m[1]);
        }

        //TODO convert these to proper dates instead of '23 Dec 17'
        //8 - started date
        $date_started = $cells->item(8)->textContent;
        //9 - completion date
        $date_completed = $cells->item(9)->textContent;
        //10 - last unlock date
        $date_lastunlock = $cells->item(10)->textContent;
        //11 - ownership status
        $ownership = $cells->item(11)->textContent;
        //12 - media
        $media = $cells->item(12)->textContent;
        //13 - play status
        $play_status = $cells->item(13)->textContent;
        //14 - site completion percentage
        $site_completion_perc = $cells->item(14)->textContent;
        //15 - completion estimate
        $completion_estimate = $cells->item(15)->textContent;
        //16 - walkthrough url
        $temp = $this->basexpath->query('a', $cells->item(16));
        $walkthrough = $temp->item(0)->getAttribute('href');
        //17 - site ratio
        $site_ratio = $cells->item(17)->textContent;
        //18 - site rating
        $site_rating = 0;
        $temp = $this->basexpath->query('div', $cells->item(18));
        if (preg_match('/rating-([0-9-]+)-stars/', $temp->item(0)->getAttribute('class'), $m)) {
            $site_rating = str_replace('-', '.', $m[1]);
        }
        //19 - unobtainables (format?)
        $unobtainable_count = $cells->item(19)->textContent;

        $return = [
            'id' => $gameid,
            'name' => $name,
            'url' => $url,
            'platform' => $platform,
            'tascore_unlocked' => $ta_unlocked,
            'tascore_total' => $ta_total,
            'gamerscore_unlocked' => $gs_unlocked,
            'gamerscore_total' => $gs_total,
            'achievements_unlocked' => $ach_unlocked,
            'achievements_total' => $ach_total,
            'completion_percentage' => $completion_perc,
            'my_rating' => $my_rating,
            'date_started' => $date_started,
            'date_completed' => $date_completed,
            'date_lastunlock' => $date_lastunlock,
            'ownership' => $ownership,
            'media' => $media,
            'play_status' => $play_status,
            'site_completion_perc' => $site_completion_perc,
            'completion_estimate' => $completion_estimate,
            'walkthrough' => $walkthrough,
            'site_ratio' => $site_ratio,
            'site_rating' => $site_rating,
            'unobtainable_count' => $unobtainable_count,
        ];
        var_dump($return);
        die('stop');

        /*extract title and url from item
        $h3 = $this->basexpath->query('h3/a', $gamegroupname);
        $title = $h3->item(0)->textContent;
        $url = ltrim($h3->item(0)->getAttribute('href'), '/');

        //use boxart img to get game id
        $img = $this->basexpath->query('div/img[@class="boxart"]', $gamegroupname);
        $src = $img->item(0)->getAttribute('src');
        $gameid = 0;
        if (preg_match('/Game_(.+?)\.png/', $src, $match)) {
            $gameid = $match[1];
        } else {
            //check hardcoded game ids
            if (isset($this->missinggameids[$title])) {
                $gameid = $this->missinggameids[$title];
            }
        }*/

        return [
            $gameid,
            $title,
            $url,
        ];
    }

    /**
     * forge an ajax request to fetch the price info on a game
     * normally this information is fetched on the game page in a box
     * on the right-hand side of the page
     */
    private function fetchPriceInfo($gameid, $title)
    {
        //use game id to craft fake ajax request to get price info
        $json = ['PurchaseOptionsRequest' => [
            'GameID' => $gameid,
            'RegionID' => $this->regions[$this->region],
            'PageType' => 'Game',
            'GamePropertyID' => 0,
            'MobileVersion' => false,
            'ResponsiveVersion' => true,
        ]];

        $curl = curl_init($this->pricepage);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($json),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=UTF-8',
                'Content-Length: ' . strlen(json_encode($json)),
            ],
            //we fetched the session cookie after the first curl request
            CURLOPT_COOKIE => implode(' ', $this->cookies),
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 Safari/537.36 OPR/51.0.2830.55',
        ]);
        $result = curl_exec($curl);
        usleep(100000);

        $price = false;
        $saleFrom = false;
        if ($result) {
            //extract payload
            $result = json_decode($result);
            if (!empty($result->d)) {

                //extract html from payload
                $priceinfo = $result->d;
                $dom = new DOMDocument;
                $dom->loadHTML($priceinfo);

                //extract price from html
                $xpath = new DOMXPath($dom);
                $priceNode = $xpath->query('//div[contains(@class, "price")]/span');
                if ($priceNode->length) {
                    //multiple prices may be listed - get the lowest one
                    foreach ($priceNode as $item) {
                        //extract numerical value from price
                        if (preg_match('/\d+[.,]\d+/', $item->textContent, $match)) {
                            $price = (!$price || $match[0] < $price) ? $match[0] : $price;
                        } elseif (strtolower($item->textContent) == 'free') {
                            $price = 'Free';
                            break;
                        }
                    }

                    //check for 'strikethrough' price, meaning the item is on sale
                    $salePriceNode = $xpath->query('//div[contains(@class, "price")]/span[contains(@class, "strk")]');
                    if ($salePriceNode->length) {
                        foreach ($salePriceNode as $item) {
                            if (preg_match('/\d+[.,]\d+/', $item->textContent, $match)) {
                                $saleFrom = (!$saleFrom || $match[0] < $saleFrom) ? $match[0] : $saleFrom;
                            }
                        }
                        $status = 'sale';
                        printf('- %s: %s (on sale from %s)' . PHP_EOL, $title, $price, $saleFrom);
                    } else {
                        $status = 'available';
                        printf('- %s: %s' . PHP_EOL, $title, $price);
                    }

                } else {
                    //this usually means 'no pricing available for your region'
                    $status = 'region-locked';
                    printf('- %s: %s' . PHP_EOL, $title, '(No pricing available in this region)');
                }
            } else {
                //either free or delisted
                $status = 'delisted';
                printf('- %s: %s' . PHP_EOL, $title, '(Delisted)');
            }
        } else {
            $status = 'unavailable';
            printf('- %s: ERROR - query response blank' . PHP_EOL, $title);
        }

        return [
            $price,
            $status,
            $saleFrom,
        ];
    }

    private function printSummary()
    {
        printf('Done! Your game collection is currently worth %s in %s' . PHP_EOL, $this->summary['totalworth'], $this->region);

        printf('- Your most expensive game is %s costing %s' . PHP_EOL,
            $this->summary['highestpricedgame']['name'],
            $this->summary['highestpricedgame']['price']
        );
        printf('- %d of your games are currently on sale' . PHP_EOL, $this->summary['salecount']);
        printf('- %d of your games are currently free' . PHP_EOL, $this->summary['freegamescount']);
        printf('- %d of your games have been delisted, %d are unavailable in the Xbox store' . PHP_EOL,
            $this->summary['delistedcount'],
            $this->summary['unavailablecount']
        );
        if ($this->summary['totalsaved'] > 0) {
            printf('- If you re-bought all your games at current prices, you would save %s due to current sales', $this->summary['totalsaved']);
        }
    }

    private function getUrl($url)
    {
        if (empty($this->curl)) {
            $this->curl = curl_init($url);
        } else {
            curl_setopt($this->curl, CURLOPT_URL, $url);
        }

        curl_setopt_array($this->curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            //CURLOPT_HEADER => true,
            //CURLOPT_HTTPHEADER = > [],
            //CURLOPT_COOKIE => '?',
            //CURLOPT_USERAGENT => '?',
        ]);

        if (empty($this->cookies)) {
            $cookies = [];
            curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, function ($ch, $line) use (&$cookies) {
                if (preg_match('/^Set-Cookie:\s*([^;]*)/mi', $line, $cookie)) {
                    printf('Got session cookie with id %s' . PHP_EOL, $cookie[1]);
                    $cookies[] = $cookie[1];
                }
                return strlen($line);
            });
        }

        $result = curl_exec($this->curl);

        if (empty($this->cookies)) {
            $this->cookies = $cookies;
        }

        return $result;
    }
}