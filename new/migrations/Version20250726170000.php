<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20250726170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a ranking column to the games table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `mygamecollection` ADD `ranking` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `shortlist_order`;");

    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `mygamecollection` DROP `ranking`;");
    }
}