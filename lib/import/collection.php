<?php
namespace MyGameCollection\Lib\Import;

use MyGameCollection\Lib\Database;
use MyGameCollection\Lib\Game;

class Collection
{
    public function importCsvIntoDatabase(Database $oDatabase, array $data) {

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
                $line['changes'] = '';
                if ($changes = $this->hasGameChanged($oDatabase, $line)) {
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
                $oGame->dlc                 = ($line['Max Gamerscore (incl. DLC)'] != $line['Max GamerScore (No DLC)']);
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

    private function hasGameChanged(Database $oDatabase, array $game)
    {
        if (!$game['id']) {
            die('hasGameChanged: no game id.' . var_export($game));
        }

        $oOldGame = new Game($oDatabase);
        $oOldGame->getById($game['id']);

        if ($oOldGame->format == '') {
            //this is a good indication that the game was freshly imported from the prices .json
            //return '<b>new game!</b>';
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
        //@TODO: if a completed game gets new dlc and you complete that, the comp. percentage
        //still goes from 100 to 100 and won't show as 'completed'
        if ($oOldGame->completion_perc != $newgame['completion_perc']) {
            if ($newgame['completion_perc'] == 100) {
                $aChanges[] = '<b>game completed</b>';
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

    //backup import function for when custom data is wiped out again
    public function importStuff(Database $db)
    {
        echo '<pre>';
        $lines = file('export.sql');
        $line = false;
        while (strpos($line, '(') !== 0) {
            $line = array_shift($lines);
        }
        foreach ($lines as $line) {
            $parts = explode(',', $line);
            if (isset($parts[2]) && $parts[2] == " 'Xbox 360'") {
                //var_dump($parts);
                $id = trim($parts[0], ' (');
                $bc = trim($parts[3]);
                $kr = trim($parts[4]);
                $pr = trim($parts[5]);
                $om = trim($parts[6]);
                if ($bc != 'NULL' || $kr != 'NULL' || $pr != 'NULL' || $om != 'NULL') {
                    //printf('needs updating: %s<br>', trim($parts[1]));
                    printf('UPDATE mygamecollection SET backcompat=%s, kinect_required=%s, peripheral_required=%s, online_multiplayer=%s WHERE id=%d LIMIT 1;<br>',
                        $bc,
                        $kr,
                        $pr,
                        $om,
                        $id
                    );
                } else {
                    //printf('skip: %s<br>', trim($parts[1]));
                }
            }
        }
        die();
    }
}