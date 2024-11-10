<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240708140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Database tables initial setup';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `mygamecollection` (
              `id` int(11) NOT NULL,
              `name` varchar(120) NOT NULL,
              `platform` enum('Xbox 360','Xbox One','Android','Windows','Web','Xbox Series X|S') NOT NULL,
              `backcompat` int(11) DEFAULT NULL,
              `kinect_required` int(11) DEFAULT NULL,
              `peripheral_required` int(11) DEFAULT NULL,
              `online_multiplayer` int(11) DEFAULT NULL,
              `completion_perc` int(11) NOT NULL DEFAULT 0,
              `completion_estimate` varchar(20) DEFAULT NULL,
              `hours_played` int(11) NOT NULL DEFAULT 0,
              `achievements_won` int(11) NOT NULL DEFAULT 0,
              `achievements_total` int(11) NOT NULL,
              `gamerscore_won` int(11) NOT NULL DEFAULT 0,
              `gamerscore_total` int(11) NOT NULL,
              `ta_score` int(11) DEFAULT NULL,
              `ta_total` int(11) DEFAULT NULL,
              `dlc` int(11) NOT NULL DEFAULT 0,
              `dlc_completion` int(11) NOT NULL DEFAULT 0,
              `completion_date` datetime DEFAULT NULL,
              `site_rating` float DEFAULT NULL,
              `format` varchar(20) DEFAULT NULL,
              `status` enum('available','delisted','region-locked','sale') NOT NULL,
              `purchased_price` float DEFAULT NULL,
              `current_price` float DEFAULT NULL,
              `regular_price` float DEFAULT NULL,
              `shortlist_order` int(11) DEFAULT NULL,
              `walkthrough_url` varchar(120) DEFAULT NULL,
              `game_url` varchar(120) DEFAULT NULL,
              `last_modified` datetime NOT NULL,
              `date_created` datetime NOT NULL DEFAULT current_timestamp()
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
        ");
    }

    public function down(Schema $schema): void
    {
        //initial setup, no revert possible
    }
}
