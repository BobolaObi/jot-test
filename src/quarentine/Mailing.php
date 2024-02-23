<?php

use Legacy\Jot\UserManagement\Session;
use Legacy\Jot\Utils\Console;
use Legacy\Jot\Utils\DB;
use Legacy\Jot\Utils\Settings;
use Legacy\Jot\Utils\Utils;


class Mailing extends UserCrawler{
	
	const connectionLimit = 90;
	const loadLimit = 2; 
	
	public function setProperties(){
		$this->limit = 10;
		$this->userFrequency = 1;
		$this->executeDelay = 0;
        
        # main criteria don't send emails to paid users only the guests and free users
        $main = " AND (`account_type`='FREE' OR (`account_type`='GUEST' AND `email` IS NOT NULL)) AND `status`='ACTIVE'";
        
        $criterias = array(
            # Only to test sending emails and ETC
            "test"             => "`username` LIKE 'group\_%'",
            
            # Users who have been active in the last month, edit a form, update account info and such
            "activeUsers"      => "`updated_at` > DATE_SUB(NOW(),INTERVAL 1 MONTH)".$main,
            
            # Users who have been active in the last three months, edit a form, update account info and such
            "lessActiveUsers"  => "`updated_at` > DATE_SUB(NOW(),INTERVAL 3 MONTH)".$main,
            
            # Users who have updated their forms in the last three months
            "updatedForms"     => "`username` IN (SELECT distinct(`username`) FROM `forms`  WHERE `updated_at` > DATE_SUB(NOW(),INTERVAL 1 MONTH))".$main,
            
            # Users who have updated their forms in the last three months
            "updatedFormsMore" => "`updated_at` > DATE_SUB(NOW(),INTERVAL 7 MONTH)".$main,
            
            # All Free Users
            "allFree"          => "1 ".$main,
            
            # Get all premium users
            "allPremium"       => "`account_type`='PREMIUM'"
        );
        
        # Get all users and guest users with email addresses
        $this->setCriteria($criterias['updatedForms']);
	}
    
    /**
     * Calculates the execute delay according to EST time
     * @return 
     */
    public function calculateExecuteDelay(){
        $currentTime = (float) date("G"); // Hour between 0 => 23
        
        if($currentTime > 10 && $currentTime < 16){
            return 0.25;
        }
        
        return 0.75;
    }
    
    /**
     * Creates a mail according to given group
     * @return 
     * @param object $type
     */
    public function createMail($type){
        $mail = "";
        $temp = ROOT."/opt/templates/announcement_mails/";
        switch($type){
            case "A":
                $mail = file_get_contents($temp."new_year_sale_group_a.html");
            break;
            case "B":
                $mail = file_get_contents($temp."new_year_sale_group_b.html");
            break;
            case "premium":
                $mail = file_get_contents($temp."premiums.html");
            break;
            case "3":
            	$mail = file_get_contents($temp."new_year_sale_group_last_days3.html");
            break;
            case "2":
                $mail = file_get_contents($temp."new_year_sale_group_last_days2.html");
            break;
            case "1":
                $mail = file_get_contents($temp."new_year_sale_group_last_day.html");
            break;
        }
        
        $url = "http://www.jotform.com/"; // HTTP_URL;
        
        $mail = str_replace("{username}", $this->userDetails['username'], $mail);
        $mail = str_replace("{email}", $this->userDetails['email'], $mail);
        $mail = str_replace("{FULL_URL}", $url, $mail);
        $mail = str_replace("/images", $url."images", $mail);
        return $mail;
    }
    /**
     * Check if the user is in test list of not
     * @return 
     */
    public function isParticipant(){
        $username = $this->userDetails['username'];
        
        $res = DB::read("SELECT * FROM `test_participants` WHERE `username`=':username' AND `test_name` = 'MailingTest'", $username);
        if($res->rows > 0){
            return $res->first;
        }
        return false;
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
     * Remove user from test
     * @return 
     */
    public function removeFromTest(){
        DB::write("DELETE * FROM `test_participants` WHERE `username`=':username' AND `test_name` = 'MailingTest'", $this->userDetails['username']);
    }
    
    /**
     * Checks if this premium user has a monthly subscription or not
     * @param object $username
     * @author Seyhun Sarıyıldız
     * @return boolean
     */
    public function isMonthly(){
        
        $subscriptions = new JotFormSubscriptions();
        $subscriptions->setUser($this->userDetails['username']);
        $info = $subscriptions->getLastPaymentType();
        
        return (strpos(strtolower($info['period']), "month") === false) ? false : true;
    }
    
	/**
	 * This function is executed after each user.
	 */
	public function execute(){
	    
        unset($_SESSION[ABTesting::SESSION]);
        
        $username = $this->userDetails['username'];
        
        # Checks if user is in block list
        if($this->isBlocked()){
            return false;
        }
        
        if(Settings::getSetting("new-year-sale", $username)){
            // This user received an email already
            return false;
        }
        
        
        if($this->isParticipant() !== false){
            # User was already send out an email
            $group = 0;
            Settings::setSetting("new-year-sale", $username, 'yes');
        }else{
            # Assign user to a group
            $group = MailingTest::assignUser("MailingTest", $username);            
        }
        
        $currentTime = (float) date("G");
        if($currentTime > 16){
            return 'break';
        }
        
        /*
        # Check if the user is monthly or not
        if( ! $this->isMonthly() ){
            return false; # don't send this email to users already have yearly subscriptions
        }
        */
        
        if(Session::getLastDays() == 3){
            $subject = "JotForm End of Year Sale - LAST 3 DAYS!";
        }else if(Session::getLastDays() == 3){
            $subject = "JotForm End of Year Sale - LAST 2 DAYS!";
        }else if(Session::getLastDays() == 1){
            $subject = "JotForm End of Year Sale - LAST DAY!";
        }
        
        $from    = array("noreply@jotform.com", "JotForm Form Builder");
        $mail    = Session::getLastDays();
        
        usleep($this->calculateExecuteDelay() * 1000000);
        
        $g = MailingTest::$groupNames[$group];
        if(preg_match("/yahoo|btinternet|ymail|rocketmail|rogers/i", $this->userDetails['email'])){
            $g = 'Group B';
        }
        
        try{
            switch($g){
                case 'Group A':
                	# Send email A from our server
                	$content = $this->createMail($mail);
                	Utils::sendEmail(array(
                        "from" => $from,
                        "subject" => $subject,
                        "to"   => $this->userDetails['email'],
                        "html" => true,
                        "body" => $content,
                    ));
                break;
                case 'Group B':
                	# Send email A using SendGrid
                	$content = $this->createMail($mail);
                	Utils::sendGrid(array(
                        "from" => $from,
                        "subject" => $subject,
                        "to"   => $this->userDetails['email'],
                        "html" => true,
                        "body" => $content,
                    ));
                break;
                case 'Group C':
                	# Send email B from our server
                	$content = $this->createMail($mail);
                	Utils::sendEmail(array(
                        "from" => $from,
                        "subject" => $subject,
                        "to"   => $this->userDetails['email'],
                        "html" => true,
                        "body" => $content,
                    ));
                break;
                case 'Group D':
                	# Send email B using SendGrid
                	$content = $this->createMail($mail);
                	Utils::sendGrid(array(
                        "from" => $from,
                        "subject" => $subject,
                        "to"   => $this->userDetails['email'],
                        "html" => true,
                        "body" => $content,
                    ));
                break;
                default:
                    Console::error("failed in default:".$group);
                	# Possibly a problem occured don't give up send user the email anyways
                	$content = $this->createMail($mail);
                	Utils::sendEmail(array(
                        "from" => $from,
                        "subject" => $subject,
                        "to"   => $this->userDetails['email'],
                        "html" => true,
                        "body" => $content,
                    ));
            }
        }catch(Exception $e){
            $this->removeFromTest();
            Console::error($e, "Email could not be sent to user: ".$username." Email:".$this->userDetails['email']);
        }
	}
}