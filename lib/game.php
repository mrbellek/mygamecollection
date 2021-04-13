<?php
namespace MyGameCollection\Lib;

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

    /**
     * Database $oDatabase
     */
    public function __construct(Database $oDatabase)
    {
        $this->db = $oDatabase;
    }

    /**
     * Fill this object from an array of properties
     *
     * @param array $gameArr - properties
     * @return self
     */
    private function fillObject(array $gameArr) : self
    {
        foreach ($gameArr as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    /**
     * Get a single game record, by its id
     *
     * @param int $id
     * @return self
     */
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

    /**
     * Get a single game record, by its page url
     * @TODO sometimes these change? e.g. ' (Xbox 360)' gets added
     *
     * @param string $url
     * @return self
     */
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

    /**
     * Get all games from database, as objects
     *
     * @param Database $oDatabase
     * @return array
     */
    static public function getAll(Database $oDatabase) : array
    {
        $dbgames = $oDatabase->query('
            SELECT *
            FROM mygamecollection
            ORDER BY id'
        );
        $games = [];
        foreach ($dbgames as $game) {
            $games[$game['id']] = (new Game($oDatabase))->fillObject($game);
        }

        return $games;
    }

    /**
     * Save this game to the database
     *
     * @return bool
     */
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

    /**
     * Update an existing game record
     *
     * @return bool
     */
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

    /**
     * Insert a new game into the database
     *
     * @return bool
     */
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

    /**
     * Delete the current game from the database
     *
     * @return bool
     */
    public function delete() : bool
    {
        return $this->db->query('
            DELETE FROM mygamecollection
            WHERE id = :id
            LIMIT 1',
            ['id' => $this->id]
        );
    }

    /**
     * Get the next available shortlist order
     *
     * @return int
     */
    private function getMaxShortlistOrder() : int
    {
        return $this->db->query_value('
            SELECT COALESCE(MAX(shortlist_order), 0)
            FROM mygamecollection'
        );
    }

    /**
     * Add this game to the shortlist
     *
     * @return bool
     */
    public function addToShortlist() : bool
    {
        $this->shortlist_order = $this->getMaxShortlistOrder() + 1;

        return $this->save();
    }

    /**
     * Move this game up one spot on the shortlist
     *
     * @return bool
     */
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

    /**
     * Move this game down one spot on the shortlist
     *
     * @return bool
     */
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

    public function getGenres() : array
    {
    }

    public function setGenres(array $genres) : self
    {
    }

    /**
     * wrapper for creating a game during the price import
     *
     * @param Game $game
     * @return bool
     */
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

    /**
     * Check if a game has changed in status or price
     *
     * @param Database $oDatabase
     * @param Game $game - the newly imported game
     * @return bool
     */
    static public function hasPriceChanged(Database $oDatabase, $game)
    {
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
}
