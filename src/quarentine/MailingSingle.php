<?php

use Legacy\Jot\Utils\Console;
use Legacy\Jot\Utils\DB;
use Legacy\Jot\Utils\Settings;
use Legacy\Jot\Utils\Utils;


class MailingSingle extends UserCrawler{
	
	const connectionLimit = 90;
	const loadLimit = 2; 
	
    /**
     * Configure user crawler script
     * @return 
     */
	public function setProperties(){
		
        $this->limit = 10;
		$this->userFrequency = 1;
		$this->executeDelay = 0;
        
        # Select only active users, discard = deleted, suspended or guests without email address
        $main = " AND (`account_type`='FREE' OR (`account_type`='GUEST' AND `email` IS NOT NULL)) AND `status`='ACTIVE'";
        
        # All users who have updated their account in the last 3 months
        $this->setCriteria("`updated_at` > DATE_SUB(NOW(),INTERVAL 3 MONTH)".$main);
        
        # All user who updated their account in last one month
        #$this->setCriteria("`updated_at` > DATE_SUB(NOW(),INTERVAL 1 MONTH)".$main);
        
        # All Users who have uploads
        #$this->setCriteria("`username` IN (SELECT distinct(`username`) FROM `monthly_usage`  WHERE `uploads` != 0)".$main);
        
        # Test groups on our DB
        #$this->setCriteria("`username` LIKE 'group\_t%'");
	}

    /**
     * Creates a mail according to given group
     * @return 
     * @param  $type
     */
    public function createMail($type){
        $mail = "";
        $temp = ROOT."/opt/templates/announcement_mails/";
        
        $mail = file_get_contents($temp."uploads.html");
        
        $url = "http://www.jotform.com/"; // HTTP_URL;
        
        $mail = str_replace("{username}", $this->userDetails['username'], $mail);
        $mail = str_replace("{email}", $this->userDetails['email'], $mail);
        $mail = str_replace("{FULL_URL}", $url, $mail);
        $mail = str_replace("/images", $url."images", $mail);
        return $mail;
    }
    
    /**
     * Get currently active users from chartbeat
     * @return 
     */
    private function checkChartbeatLoad(){
        $time = Utils::cacheGet("chartbeat_time", 0);
        
        if((time() - $time) > 30){
            $url = "http://api.chartbeat.com/quickstats/";
            $json = Utils::postRequest($url, array(
                "host"   => "jotform.com",
                "apikey" => "fbed942d171ce4c5dec04b757866b276"
            ));
            $result = json_decode($json);
            Utils::cacheStore("chartbeat_time", time());
            Utils::cacheStore("chartbeat_visitors", $result->visits);
        }
        
        return Utils::cacheGet("chartbeat_visitors");
    }
    
    /**
     * Checks if the email is in blocked emails list or not
     * @return 
     */
    public function isBlocked(){
        $res = DB::read("SELECT * FROM `block_list` WHERE `email`=':email'", $this->userDetails['email']);
        if($res->rows > 0){
            return true;
        }
        return false;
    }
    
    /**
     * Save user on DB
     * @return 
     */
    public function markCrawled(){
        Settings::setSetting("UploadEmail", $this->userDetails['username'], time());
    }
    
    /**
     * Returns false if it's not found on DB
     * @return 
     */
    public function isCrawled(){
        $res = Settings::getSetting("UploadEmail", $this->userDetails['username']);
        return $res;
    }
    
	/**
	 * This function is executed after each user.
	 */
	public function execute(){
        
        # will it be using sendMail or sendGrid
        $useSendGrid = false;
        
        # shorthand
        $username = $this->userDetails['username'];
        
        # this user was crawled before
        if($this->isCrawled() !== false){ return true; }
        
        # Checks if user is in block list
        if($this->isBlocked()){ return false; }
        
        # If there are more than 250 users on the site reduce mail script frequency
        if($this->checkChartbeatLoad() > 250){
            usleep(1 * SEC);
        }else{
            usleep(0.25 * SEC);
        }
        
        # Subject and from settings
        $subject = "7 Great New Reasons to Use JotForm for your Upload Forms";
        $from    = array("noreply@jotform.com", "JotForm Form Builder");
        
        # We put TRY/CATCH because mail script may throw and exception and we want this script to continue
        try{
            # Get e-mail content, parsed and populated
        	$content = $this->createMail($mail);
            
            # create e-mail configuration
            $conf = array(
                "from" => $from,
                "subject" => $subject,
                "to"   => $this->userDetails['email'],
                "html" => true,
                "body" => $content
            );
            
            # Check preference and send e-mail
            if($useSendGrid){
            	Utils::sendGrid($conf);
            }else{
            	Utils::sendEmail($conf);
            }
            
            # Mark this user as crawled and never get back to it
            $this->markCrawled();
            
        }catch(Exception $e){
            Console::error($e, "Email could not be sent to user: ".$username." Email:".$this->userDetails['email']);
        }
	}
}