<?php
namespace MyGameCollection\Lib;

use \PDO;
use MyGameCollection\Lib\Logger;

/**
 * Database class, connect to database and run queries
 *
 * NOTE: do not use Logger::write here since there's a very high chance of
 * ending up in an infinite loop and stuff.
 */
class Database
{
    public function __construct()
    {
        $this->logger = new Logger;
    }

    /**
     * Connect to database (PDO)
     *
     * @throws Exception
     * @return bool
     */
    public function connect() : bool
    {
        $this->checkConfig();

        try {
            //basic dns check to prevent warnings
            if (!$this->validIp4OrIp6Hostname(DB_HOST)) {
                throw new Exception('database hostname not found: ' . DB_HOST);
            }

            //connect to database
            $this->oPDO = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch(Exception $e) {
            //do not write to logger since database connection is down lol
            $this->logger->output(sprintf('- Database connection to %s failed. (%s)', DB_HOST, $e->getMessage()));

            die('FATAL' . PHP_EOL);
        }

        return true;
    }

    /**
     * Execute a query that doesn't return rows, but is not insert/update/delete/replace
     *
     * @param string $sQuery
     * @return mixed
     */
    public function statement(string $sQuery)
    {
        if (empty($this->oPDO)) {
            $this->connect();
        }

        return $this->oPDO->query($sQuery);
    }

    /**
     * Generic query function wrapper
     *
     * @param string $sQuery - mysql query
     * @param array $aData - data for placeholders
     *
     * @return mixed
     */
    public function query($sQuery, $aData = [])
    {
        if (empty($this->oPDO)) {
            $this->connect();
        }

        try {
            //create statement
            if($sth = $this->oPDO->prepare($sQuery)) {

                foreach ($aData as $key => $value) {
                    //prefix colon
                    $key = (substr($key, 0, 1) == ':' ? $key : ':' . $key);

                    //bind as int if it looks like a number
                    //if (is_numeric($value) && !is_float($value)) {
                    //    $sth->bindValue($key, $value, PDO::PARAM_INT);
                    //} else {
                        $sth->bindValue($key, $value, PDO::PARAM_STR);
                    //}
                }

                //run query
                $sth->execute();
            }
        } catch(Exception $e) {
            $this->logger->output('FATAL: %s (query: %s with data %s', $e->getMessage(), $sQuery, var_export($aData, true));

            return false;
        }

        //what to return?
        $sQueryTemp = preg_replace('/^\s+/', '', strtolower($sQuery));
        if (strpos($sQueryTemp, 'insert') === 0) {
            //insert, return insert id
            return $this->oPDO->lastInsertId();

        } elseif (strpos($sQueryTemp, 'delete') === 0) {
            //delete, return bool
            return true;

        } elseif (strpos($sQueryTemp, 'update') === 0) {
            //update, return bool
            return true;

        } elseif (strpos($sQueryTemp, 'replace') === 0) {
            //replace into, return bool
            return true;

        } else {
            //assume select, return rows
            return $sth->fetchAll();
        }
    }

    /**
     * Execute a select query, and return only the first result row
     *
     * @param string $sQuery
     * @param array $aData
     * @return array
     */
    public function query_single(string $sQuery, array $aData = []) : array
    {
        $aResult = $this->query($sQuery, $aData);

        return $aResult ? $aResult[0] : [];
    }

    /**
     * Execute a select query, and return only the first column of the first result row
     *
     * @param string $sQuery
     * @param array $aData
     * @return mixed
     */
    public function query_value($sQuery, $aData = [])
    {
        $aResult = $this->query($sQuery, $aData);

        return $aResult ? reset($aResult[0]) : false;
    }

    private function checkConfig()
    {
        if (!defined('DB_HOST') || !defined('DB_NAME') ||
            !defined('DB_USER') || !defined('DB_PASS')) {

            $this->logger->output('- One or more of the MySQL database credentials are missing, halting.');

            return false;
        }

        return true;
    }

    private function validIp4OrIp6Hostname($host)
    {
        if ($this->gethostbyname6($host)) {
            return true;
        }

        if (gethostbyname($host) != $host) {
            return true;
        } else {
            return false;
        }
    }

    private function gethostbyname6($host)
    {
        $dns6 = dns_get_record($host, DNS_AAAA);
        $ipv6 = [];
        foreach ($dns6 as $record) {
            switch($record['type']) {
                //case 'A': $ipv4[] = $record['ip'];
                case 'AAAA': $ipv6[] = $record['ipv6'];
            }
        }

        return count($ipv6) > 0 ? $ipv6[0] : false;
    }
}
