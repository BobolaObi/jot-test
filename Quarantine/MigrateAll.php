<?php

namespace forms.
datalynk . ca\Quarantine;

use formsuse;
use Legacy\Jot\Utils\Console;
use Legacy\Jot\Utils\DB;
use Legacy\Jot\Utils\Utils;
use MigrateUser;

Legacy\Jot\Utils\Console;


/**
 *
 */


class MigrateAll
{
    /**
     * Number of the users will be migrated at each chunk
     * @var int
     */
    static $chunk = 100;

    /**
     * Will Users be merged or overwritten
     * @var boolean
     */
    static $merge = true;

    /**
     * Gets a pile of users from database
     * @param  $start // Where to start
     * @param  $limit // How many
     * @return
     */
    static function getChunk($start, $limit)
    {
        flush();
        Console::log('Getting the user from ' . $start . ' to ' . ($start + $limit), 'Migrate All');

        DB::useConnection('main');
        $res = DB::read("SELECT `username` FROM `users`
                  WHERE `account_type` IS NULL
                  OR `account_type` = ''
                  OR `account_type` = 'NORMAL'
                  OR `account_type` = 'PREMIUM'
                  OR `account_type` = 'ADMIN'
                  ORDER BY `username` ASC
                  LIMIT #start, #limit 
               ", $start, $limit);

        if ($res->rows < 1) {
            throw new \Exception('Completed');
        }

        return $res->result;
    }

    /**
     * Starts to migrate users
     * @return
     */
    static function migrate($currentChunk = 0)
    {
        Console::openConsole();
        ob_end_flush();
        //echo "Started.<br>";

        ini_set('memory_limit', '1000M');
        ini_set('max_execution_time', '300'); // 5 Minutes
        DB::useConnection('main');
        $countResult = DB::read("SELECT count(*) FROM `users`
                                WHERE `account_type` IS NULL
                                OR `account_type` = ''
                                OR `account_type` = 'NORMAL'
                                OR `account_type` = 'PREMIUM'
                                OR `account_type` = 'ADMIN'
                                ORDER BY `username` ASC
                               ");

        $totalCount = $countResult->first['count(*)'];

        Console::log('Total of ' . $totalCount . ' Users will be migrated', 'Migrate All');

        #while($currentChunk < $totalCount){
        //echo "Current Chunk: " . $currentChunk." => ".($currentChunk + self::$chunk)."<br>";

        $users = self::getChunk($currentChunk, self::$chunk);
        foreach ($users as $user) {
            //echo $user['username']."<br>";
            Console::log("User " . $user['username'] . " Started With: " . Utils::bytesToHuman(memory_get_usage()));
            if (strstr(strtolower($user['username']), 'anonymous')) {
                continue;
            }
            try {
                $migrate = new MigrateUser($user['username'], self::$merge);
                $migrate->moveUser();
                $migrate->moveForms();

                $migrate->__destruct();

            } catch (Exception $e) {
                Console::error($e);
            }
            unset($migrate);
            flush();
            ob_flush();
            Console::log("User " . $user['username'] . " Ended With: " . Utils::bytesToHuman(memory_get_usage()) . " Peak usage was: " . Utils::bytesToHuman(memory_get_peak_usage()));

        }
        unset($users);
        $currentChunk += self::$chunk;
        flush();
        #}
        Console::closeConsole();
    }
}
