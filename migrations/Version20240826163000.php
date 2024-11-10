<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20240826163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create series setlist table 1';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `series_setlist` (
              `id` int(11) NOT NULL,
              `name` varchar(60) NOT NULL,
              `user_title` varchar(60) NOT NULL,
              `status` enum('listed','unlisted','franchise','subfranchise','crossover','community','legacy') NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
        ");
        
        $this->addSql("
            CREATE TABLE `series_setlist_games` (
              `id` int(11) NOT NULL,
              `game_id` int(11) NOT NULL,
              `name` varchar(120) NOT NULL,
              `setlist_id` int(11) NOT NULL,
              `alt_for` int(11) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
        ")

    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE series_setlist");
        $this->addSql("DROP TABLE series_setlist_games");
    }
}