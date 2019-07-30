<?php
namespace MyGameCollection\Setup;

use MyGameCollection\Lib\Database;

class Setup
{
    private $db;

    public function __construct(Database $oDatabase)
    {
        $this->db = $oDatabase;
    }

    public function run()
    {
        if (!is_readable('setup/mygamecollection.sql')) {
            throw new RuntimeException('Database is not setup, but bootstrap sql file was not found.');
        }

        $this->db->statement(file_get_contents('setup/mygamecollection.sql'));

        header('Location: mygamecollection.php');
        return true;
    }
}
