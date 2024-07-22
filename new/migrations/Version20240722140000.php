<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20240709150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update status enum with \'sold\' value.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `mygamecollection` CHANGE `status` `status` ENUM('available','delisted','region-locked','sale','sold') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;");
        $this->addSql("UPDATE `mygamecollection` SET format = 'Disc', status = 'sold' WHERE `format` = 'sold';");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `mygamecollection` CHANGE `status` `status` ENUM('available','delisted','region-locked','sale') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;");
    }
}
