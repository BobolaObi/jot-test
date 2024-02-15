<?php

# no idea why, but this doesn't work with the name RequestServer....


class UpgradeTest extends ABTesting{
    
    public static $myName = __CLASS__;
    
    static function getClass(){
        return __CLASS__;
    }
    
    static function getGroupNames(){
        return self::$groupNames;
    }
    
    public function checkParticipant(){
        
        if($this->user->accountType == "FREE"){
            if($usage = $this->user->getMonthlyUsage()){
                if($usage['submissions'] > 70){
                    return true;
                }
            }
        }
        
        return false;
    }
    
}
