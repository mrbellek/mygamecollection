<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20240709150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update hoursPlayed field from int to float.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `mygamecollection` CHANGE `hours_played` `hours_played` FLOAT NOT NULL DEFAULT '0';");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `mygamecollection` CHANGE `hours_played` `hours_played` INT(11) NOT NULL DEFAULT '0';");
    }
}
