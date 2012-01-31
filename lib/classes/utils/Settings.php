<?php


class Settings {
    
    /**
     * Returns the array of properties of the setting
     * @param object $identifier
     * @param object $key
     * @return 
     */
    public static function getSetting($identifier, $key){
        
        if($identifier == "form"){
            $identifier = Utils::getCurrentID("form");
        }
        
        $res = DB::read("SELECT * FROM `custom_settings` WHERE `identifier`=':identifier' AND `key`=':key'", $identifier, $key);
        
        if($res->rows > 0){
            return $res->first;
        }
        
        return false;
    }
    /**
     * Returns only the value of a setting
     * @param object $identifier
     * @param object $key
     * @return 
     */
    public static function getValue($identifier, $key){
        $setting = self::getSetting($identifier, $key);
        if(isset($setting['value'])){
            return $setting['value']; 
        }
        return false;
    }
    
    /**
     * Inserts the given value to database
     * @param object $identifier
     * @param object $key
     * @param object $value
     * @return 
     */
    public static function setSetting($identifier, $key, $value){
        $respone = DB::insert("custom_settings", array(
            "identifier"=>$identifier, 
            "key"=>$key,
            "value"=>$value));
            
        return $value;
    }
    /**
     * Removes the setting from database
     * @param object $identifier
     * @param object $key
     * @return 
     */
    public static function removeSetting($identifier, $key){
        $res = DB::write("DELETE FROM `custom_settings` WHERE `identifier`=':identifier' AND `key`=':key'", $identifier, $key);
        return true;
    }
    
    /**
     * Returns all settings by identifier
     * @param object $identifier
     * @return 
     */
    public static function getByIdentifier($identifier){
        $res = DB::read("SELECT * FROM `custom_settings` WHERE `identifier`=':identifier'", $identifier);
        
        if($res->rows > 0){
            return $res->result;
        }
        return false;
    }
    
    /**
     * Removes all settings from database by identifier
     * @param object $identifier
     * @param object $key
     * @return 
     */
    public static function removeByIdentifier($identifier){
        $res = DB::write("DELETE FROM `custom_settings` WHERE `identifier`=':identifier'", $identifier);
        return true;
    }
}