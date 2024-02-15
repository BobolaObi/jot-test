<?php

namespace Legacy\Jot\Integrations;

class Integrations{
    
    public $partner, $username, $formID;
    public $newEntry = false;
    public $settings;
    /**
     * Sets the integraion config object
     * @constructor
     * @param object $partner
     * @param object $formID [optional]
     * @param object $username [optional]
     * @return 
     */
    public function Integrations($partner, $formID = null, $username = null){
        $this->partner = $partner;
        $this->username = $username? $username : Session::$username;
        $this->formID = $formID? $formID : Utils::getCurrentID('form');
        $this->getSettings();
    }
    /**
     * Gets the complete settings of integration
     * @return 
     */
    public function getSettings(){
        $res = DB::read("SELECT `key`, `value` FROM `integrations` WHERE `partner`=':partner' AND `username`=':username' AND `form_id`=#id", 
        $this->partner, $this->username, $this->formID);
        if($res->rows > 0){
            foreach($res->result as $line){
                $this->settings[$line['key']] = Utils::safeJsonDecode($line['value']);
            }
        }else{
            $this->newEntry = true;
            $this->settings = array();
        }
    }
    
    /**
     * Checks if this integration is a new entry or an update
     * @return 
     */
    public function isNew(){
        return $this->newEntry;
    }
    
    /**
     * Returns the asked value
     * @param object $key
     * @return 
     */
    public function getValue($key){
        if(isset($this->settings[$key])){
            if($key === 'password'){
                $value = OpenSSL::getInstance()->decryptData($this->settings[$key]);
            }else{
                $value = $this->settings[$key];
            }
            return $value;
        }
        return false;
    }
    
    /**
     * Sets a value on the fly
     * it should be saved to DB
     * @param object $key
     * @param object $value
     * @return 
     */
    public function setValue($key, $value){
        if($key == 'password'){
            $value = OpenSSL::getInstance()->encryptData($value);
        }
        $this->settings[$key] = $value;
    }
    /**
     * Removes a property from configuration
     * @param object $key
     * @return 
     */
    public function removeValue($key){
        unset($this->settings[$key]);
    }
    
    /**
     * Saves all config to database
     * @return 
     */
    public function save(){
        foreach($this->settings as $key => $value){
            DB::insert('integrations', array(
                "partner"  => $this->partner,
                "username" => $this->username,
                "form_id"  => $this->formID,
                "key"      => $key,
                "value"    => Utils::safeJsonEncode($value)
            ));
        }
    }
    
    /**
     * Remove all configurations from database
     * @return 
     */
    public function removeAll(){
        DB::write("DELETE FROM `integrations` WHERE `partner`=':partner' AND `username`=':username' AND `form_id`=#id", 
                   $this->partner, $this->username, $this->formID);
    }
}
