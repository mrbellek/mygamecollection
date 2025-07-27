<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20250727161500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Nintendo Switch as a platform option';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE `mygamecollection` CHANGE `platform` `platform` 
            ENUM('Xbox 360','Xbox One','Android','Windows','Web','Xbox Series X|S','Nintendo Switch')
            CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE `mygamecollection` CHANGE `platform` `platform`
            ENUM('Xbox 360','Xbox One','Android','Windows','Web','Xbox Series X|S')
            CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;"
        );
    }
}