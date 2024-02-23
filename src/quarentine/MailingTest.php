<?php



class MailingTest extends ABTesting{
   
    public $myName = __CLASS__;
    
    protected $totalGroup = 4;
    protected $maxParticipient = 1000000;
    protected $resetOnLogin = false;
    static $groupNames = array('Group A', 'Group B', 'Group C', 'Group D',
                               // old groups again
                               'Subj: End of Year', 'Subj: Happy Holidays', 'Subj: Business Gift', 'Subj: Santa in Town',
                               // Old groups disabled by giving total number smaller
                               'Email-A-Server', 'Email-A-SendGrid', 'Email-B-Server', 'Email-B-SendGrid');
    
    static function getClass(){
        return __CLASS__;
    }
    
    static function getGroupNames(){
        return self::$groupNames;
    }
    
    public function checkParticipant(){
        # Participant will be selected in the crawler so we shıould allow all here
        return true;
    }
    
}
