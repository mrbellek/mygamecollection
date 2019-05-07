<?php
namespace Mygamecollection\Lib;

use MyGameCollection\Lib\Database;
 
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

    public function __construct(Database $oDatabase)
    {
        $this->db = $oDatabase;
    }

    private function fillObject(array $gameArr) : self
    {
        foreach ($gameArr as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    public function getById(int $id) : self
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

    public function getIdByUrl(string $url) : ?int
    {
        return $this->db->query_value('
            SELECT id
            FROM mygamecollection
            WHERE game_url = :url
            LIMIT 1',
            [':url' => $url]
        );
    }

    public function getAll() : array
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

    public function save() : bool
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

    private function update() : bool
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

    private function insert() : bool
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

    public function delete() : bool
    {
        return $this->db->query('
            DELETE FROM mygamecollection
            WHERE id = :id
            LIMIT 1',
            ['id' => $this->id]
        );
    }

    private function getMaxShortlistOrder() : int
    {
        return $this->db->query_value('
            SELECT COALESCE(MAX(shortlist_order), 0)
            FROM mygamecollection'
        );
    }

    public function addToShortlist() : bool
    {
        $this->shortlist_order = $this->getMaxShortlistOrder() + 1;

        return $this->save();
    }

    public function shortlistUp() : bool
    {
        if ($this->shortlist_order == 1 || is_null($this->shortlist_order)) {
            return true;
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
            return false;
        }

        $this->shortlist_order--;

        return $this->save();
    }

    public function shortlistDown() : bool
    {
        if ($this->shortlist_order == $this->getMaxShortlistOrder() || is_null($this->shortlist_order)) {
            return true;
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
        } else {
            return false;
        }

        $this->shortlist_order++;

        return $this->save();
    }

    //wrapper for creating a game during the price import
    public function createByPriceData($game) : bool
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

        return $this->insert();
    }
}
