<?php
/**
 * Generates a random API key and makes it validated
 * @ type VARCHAR(24)
 * @package JotForm_User_Management
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\UserManagement;
use Legacy\Jot\Utils\DB;

class APIKey {
    
    private static $salt = "::aSdsjD223::";
    /**
     * Generate an API Key
     * @param  $getLongKey  // [optional] set this for longer keys
     * @return APIKey
     */
    static function generate($getLongKey=false){
        
        $key = strtoupper(md5(APIKey . phptime() . self::$salt .$_SERVER['REMOTE_ADDR'].rand(0, 100)));
        preg_match_all("/(....)/", $key, $matches);
        $chunks = array_splice($matches[0], 0, 5);
        $long =  join("-", $matches[1]);
        $short = join("-", $chunks);
        if($getLongKey){
            return $long;
        }
        return $short;
    } 
    
    /**
     * Validates the API KEY, No DB action. just checks if the given string is an API KEY or not
     * @param  $key
     * @return boolean
     */
    static function isValid($key){
        if(strlen($key) != 24){ return false; } // Check the length first
        if(!strstr($key, "-")){ return false; } // Make sure it's dasherized
        return preg_match("/([A-Z0-9]{4}-?){5}/", $key); // check the string form
    }
    
    /**
     * Checks database for API KEY existance return boolean
     * @param  $key
     * @return boolean
     */
    static function isExists($key){
        $response = DB::read("SELECT * FROM `users` WHERE api_key=':key'", $key);
        if($response->success){
            if($response->rows > 0){
                return true;
            }
        }
        return false;
    }
    
    /**
     * Brings the associated user for this API KEY
     * @param  $key
     * @return User|false
     */
    static function getAPIUser($key){
        $response = DB::read("SELECT * FROM `users` WHERE api_key=':key'", $key);
        if($response->success){
            if($response->rows > 0){
                return new User($response->first['username']); // Return the associated user
            }
        }
        return false;
    }
}