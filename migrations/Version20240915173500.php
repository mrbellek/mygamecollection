<?php 

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20240915173500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add `active` column to setlist table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `series_setlist` ADD `active` INT NOT NULL DEFAULT '1' AFTER `status`;")

    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `series_setlist` DROP `active`;");
    }
}