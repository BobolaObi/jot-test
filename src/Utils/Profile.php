<?php

/**
 * This is a simple profiler tool. Just keeps the execution time in seconds
 * @package JotForm_Utils
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\Utils;


class Profile {
    
    private static $timers = array();
    /**
     * Starts the timer for given title
     * @param  $title
     * @return 
     */
    static function start($title){
        self::$timers[$title] = microtime(true);
    }
    /**
     * Brings back the result of time spending in seconds with floating point of milli seconds
     * Title must be exact same of the start functon
     * @param  $title
     * @return 
     */
    static function end($title){
        $end = microtime(true);
        return  sprintf("%01.3f", ($end - self::$timers[$title]));
    }
}