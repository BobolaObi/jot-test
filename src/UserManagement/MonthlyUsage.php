<?php
/**
 * Class modeling the user's monthly usage data. Database columns are also 
 * determined here, when a new row is to be created.
 * 
 * @package JotForm_User_Management
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\UserManagement;

class MonthlyUsage {
    /**
     * Instance variable names and their related DB column name counterparts.
     * <code>
     * foreach($fields as $classField => $dbField)
     * ...
     * </code>
     * @var array
     */
    public static $fields = array(
                'username'          => 'username',       
                'submissions'       => 'submissions',    
                'sslSubmissions'    => 'ssl_submissions',
                'payments'          => 'payments',
                'uploads'           => 'uploads',      
                'tickets'           => 'tickets'        
    );
    
    public static $limitFields = array(
        'submissions'       => 'submissions',    
        'sslSubmissions'    => 'ssl_submissions',
        'payments'          => 'payments',
        'uploads'           => 'uploads',      
        'tickets'           => 'tickets'
    );
    
    /**
     * User associated with the usage data.
     * @var User
     */
    public $user;
    /**
     * Keeps the account type user has.
     * @var AccountType
     */
    public $accountType;
    /**
     * An array of over quota usage types.
     * @var array
     */
    private $overQuota = array();
    /**
     * An array of almost full usage types.
     * @var array
     */
    private $almostFull = array();
    /**
     * Set username only. Others will be default.
     * @var Insertion query in SQL.
     */    
    private static $insertQuery = "INSERT INTO `monthly_usage` (`username`) VALUES (':username')";
    private static $deleteQuery = "DELETE FROM `monthly_usage` WHERE `username`=':username' LIMIT 1";
    /**
     * Constructor: Creates a new monthly usage object using the $usage array.
     * 
     * @param array $usage
     * @return 
     */
    public function __construct($usage = array()) {
        foreach(self::$fields as $classField => $dbField) {
            if (isset($usage[$classField])) {
                $this->$classField = $usage[$classField];
            } else {
                // Set the usage with an empty value.
                $this->$classField = 0;
            }
        }
    }
    /**
     * Saves the new monthly usage object to the DB.
     * @return unknown_type
     */
    public function save() {
        $query = "INSERT INTO  `monthly_usage` " . 
                 "(`username`, `submissions`, `ssl_submissions`, " .
                 " `payments`, `uploads`, `tickets`) VALUES " .
                 "(':username', #submissions, #ssl_submissions, " . 
                 " #payments, #uploads, #tickets)";
        $res = DB::write($query, 
                $this->username, $this->submissions, (isset($this->ssl_submissions) ? $this->ssl_submissions : 0), 
                $this->payments, $this->uploads, $this->tickets);
        return $res->success;
    }

    /**
     * Resets the monthly usages of the given username. Each usage type
     * is reset to zero.
     * 
     * @param object $username
     * @return DBResponse
     */
    public static function resetUsageBy($username) {
        $query = "UPDATE `monthly_usage` SET ";
        $queryPart = "";
        foreach(self::$limitFields as $classField => $dbField) {
            $queryPart .= "$dbField = 0, ";
        }
        $query .= rtrim(''.$queryPart, ", ") . " WHERE username=':username' LIMIT 1";
        $response = DB::write($query, $username);
        return $response;
    }
    
    public function resetUsage() {
        MonthlyUsage::resetUsageBy($this->user->username);
        foreach(self::$limitFields as $classField => $dbField) {
            $this->$classField = 0;
        }
    }
    
    /**
     * Returns a monthlyUsage object containing the usage data of the user with
     * the given username.
     * 
     * @param object $username
     * @return MonthlyUsage
     */
    public static function find($username = false) {
        if(is_object($username)){
            $user = $username;
        }else if($username === false){
            $user = Session::getUser();
            if (!isset($user->id)) {
                $mu = new MonthlyUsage(array('username' => $user->username));
                // $mu->user = $user;
                $mu->accountType = AccountType::find('GUEST');
                return $mu;
            }
        }else{
            $user = User::find($username);
        }
        
        $username = $user->username;
        $accountType = $user->accountType;
        
        $response = DB::read("SELECT * FROM `monthly_usage` WHERE `username` = ':username' LIMIT 1", $username);
        if ($response->rows == 0) {
            // This user does not have a monthly usage row in the table. Create one now.
            $mu = new MonthlyUsage(array('username' => $username));
            $mu->save();    
        }
        else {
            // Copy the usage data from the DB.
            $mu = new MonthlyUsage(Utils::arrayKeysToCamel($response->result[0]));
        }
        // Set the user so that we can refer to him later.
        $mu->user = $user;
        
        // Get the monthly usage data.
        $mu->accountType = AccountType::find($accountType);
        return $mu;
    }
    
    public function delete() {
        MonthlyUsage::deleteBy($this->user->username);
    }
    
    public static function deleteBy($username) {
        $response = DB::write(self::$deleteQuery, $username);
        return $response;
    }
    
    /**
     * Takes a username string, the names of usage types 
     * and increments values in the DB.
     * Increments each usage type with the corresponding value in the DB. 
     * 
     * Increments the submissions count by default, if no parameter is supplied.
     * You can supply increase values as an array with a length equal to the number
     * of usage types you want to increase. Give a single number for the increase
     * value if you want all counts to be inreased the same.
     * 
     * <em>Note that this method only writes to the DB and no other instance object
     * since it is a static method.</em>
     * 
     * Can do 
     * <code>
     * MonthlyUsage::incrementUsageBy(array('payments', 'sslSubmisssions', 'submissions', 'tickets'));
     * </code>
     * to increment by 1.
     * 
     * Or give increase values such as 
     * <code>
     * MonthlyUsage::incrementUsageBy(array('payments', 'upload'), array(1, 10))
     * </code>
     * 
     * You can also provide one value for every property:
     * <code>
     * MonthlyUsage::incrementUsageBy(array('payments', 'sslSubmisssions', 1);
     * </code>
     * 
     * @param object $username
     * @param object $checkTypes [optional]
     * @param object $increaseValues [optional]
     * @return DBResponse
     */
    public static function incrementUsageBy($username, $checkTypes = "submissions", $increaseValues = 1) {
        $query = "UPDATE `monthly_usage` SET ";
        $queryPart = "";
        if (is_array($checkTypes)) {
            for ($i = 0; $i < sizeof($checkTypes); $i++) {
                $checkType = $checkTypes[$i];
                if (array_key_exists($checkType, self::$limitFields)) {
                    $dbName = self::$limitFields[$checkType];
                    $queryPart .= $dbName . " = $dbName + #$checkType, ";
                    // Add it to the parameterized argument hash.
                    $queryArgs[$checkType] = (is_array($increaseValues)? $increaseValues[$i] : $increaseValues);
                }
            }
            // Remove the last comma and the space characters.
            $queryPart = rtrim(''.$queryPart, ", ");
        } else {
            // It is a string, use it directly.
            if (array_key_exists($checkTypes, self::$limitFields)) {
                    $dbName = self::$limitFields[$checkTypes];
                    $queryPart .= $dbName . " = $dbName + #$checkTypes";
                    // Add it to the parameterized argument hash.
                    $queryArgs[$checkTypes] = $increaseValues;
            }
        }
        
        // Write back the updated values to the DB.
        $queryArgs['username'] = $username;
        $query .=  $queryPart . " WHERE username=':username'";
        $response = DB::write($query, $queryArgs);
        return $response;
    }
    
    /**
     * Convenience method for the static incrementUsageBy, which can run 
     * without needing an instance. Works very similarly, doesn't need a
     * username string as a parameter. It increments the instance variables 
     * in addition to writing these to the DB. 
     * 
     * @see MonthlyUsage::incrementUsageBy()
     * @param object $checkTypes [optional]
     * @param object $increaseValues [optional]
     * @return 
     */
    public function incrementUsage($checkTypes = "submissions", $increaseValues = 1) {
        // First update instance variables.
        if (is_array($checkTypes)) {
            for ($i = 0; $i < sizeof($checkTypes); $i++) {
                $checkType = $checkTypes[$i];
                if (array_key_exists($checkType, self::$limitFields)) {
                    $this->$checkType += is_array($increaseValues)? $increaseValues[$i] : $increaseValues;
                }
            }
        } else {
            // it is a string, use it directly.
            $this->$checkTypes += $increaseValues;
        }
        // Then write to the DB.
        return MonthlyUsage::incrementUsageBy($this->user->username, $checkTypes, $increaseValues);   
    }
    
    /**
     * Method for checking if the user has exceeded his account limits.
     * 
     * <p>Sends "almost full" and "over quota" warning e-mails. Almost full 
     * e-mails are sent once the limits reach 90%, "over quota" e-mail sent
     * once the limit has been exceeded.</p> 
     * 
     * Note that over-quota e-mails have higher precedence so if one limit is over
     * quota while another is almost full, an over quota e-mail is sent only.
     * 
     * The parameter $checkTypes can be an array of string values 
     * representing the usage type you care checking for or it could be a single
     * string if you are only checking a single usage limit (like 'tickets' 
     * etc.). It is optional and by default all the usage limits related to the 
     * form are checked (with the exception of 'tickets') ie. submissions, 
     * ssl_submissions, payments and uploads are all checked. Note that 
     * types to be checked are to be written in camel case of their corresponding
     * database field names. So, ssl_submissions in the table becomes sslSubmissions
     * in PHP. 
     * 
     * @param object $checkTypes [optional]
     * @param boolean $sendMail [optional] 
     * @return 
     */
    public function isOverQuota($checkTypes = array('submissions', 
                'sslSubmissions', 
                'payments', 
                'uploads')) { // End method parameters.
        
        // Ensure that the $checkTypes is an array with valid types.
        if (!is_array($checkTypes)) {
            // Make the string an array. Check if it is allowed first.
            if (array_key_exists($checkTypes, self::$limitFields)) {
                $checkTypes = array($checkTypes);
            } else {
                // Received an illegal usage type to check.
                $checkTypes = array();
                Console::warn("Invalid usage type is asked to be checked.", "Monthly Usage: checkLimitsBy");
            }
        } else {
            foreach ($checkTypes as $checkType) {
                if (!array_key_exists($checkType, self::$limitFields)) {
                    // Remove this type as it is not defined as a usage type.
                    unset($checkTypes[$checkType]);
                }
            }
        }
        
        foreach($checkTypes as $checkType) {
            // Usage number is over the quota limits.
            if ($this->$checkType >= $this->accountType->limits[$checkType]) {
                return true;
            }
        }

        return false;
    }
    
    public function getOverQuota($checkTypes = array('submissions', 
                'sslSubmissions', 
                'payments', 
                'uploads')) { // End method parameters.
        $returns = array();
        
        // Ensure that the $checkTypes is an array with valid types.
        if (!is_array($checkTypes)) {
            // Make the string an array. Check if it is allowed first.
            if (array_key_exists($checkTypes, self::$limitFields)) {
                $checkTypes = array($checkTypes);
            } else {
                // Received an illegal usage type to check.
                $checkTypes = array();
                Console::warn("Invalid usage type is asked to be checked.", "Monthly Usage: checkLimitsBy");
            }
        } else {
            foreach ($checkTypes as $checkType) {
                if (!array_key_exists($checkType, self::$limitFields)) {
                    // Remove this type as it is not defined as a usage type.
                    unset($checkTypes[$checkType]);
                }
            }
        }
        
        foreach($checkTypes as $checkType) {
            // Usage number is over the quota limits.
            if ($this->$checkType >= $this->accountType->limits[$checkType]) {
                $returns[$checkType] = true;
            }else{
                $returns[$checkType] = false;
            }
        }

        return $returns;
    }
    
    /**
     * This method sends e-mails according to the monthly usage of the user. 
     * 
     * There are currently two types of e-mails sent. Over quota e-mails are 
     * sent if the user is over their monthly limits. Almost full e-mails are
     * sent once the user reaches 90% of their allocated quotas.
     * 
     * $checkTypes parameter determines the usage types which should be checked
     * to see if an e-mail needs to be sent. It has submissions, sslSubmissions,
     * payments and uploads as the default list of usage types to check. Note
     * that the order is important here and if there are more than one usage 
     * type over limits or almost full, the first one will be told in the e-mail.
     * 
     * The $sendOverQuotaEmail and $sendAlmostFullEmail parameters determine 
     * whether an e-mail is to be sent if the user
     * is over quota or he is just about to cross the 90% mark, respectively.
     * 
     * @param object $checkTypes [optional]
     * @param object $sendOverQuotaEmail [optional]
     * @param object $sendAlmostFullEmail [optional]
     * @return 
     */
    public function sendEmails($checkTypes = array('submissions', 
                'sslSubmissions', 
                'payments', 
                'uploads'), 
                $sendOverQuotaEmail = true, 
                $sendAlmostFullEmail = true,
                $forceSend = false) { // End method parameters
    
        // Ensure that the $checkTypes is an array with valid types.
        if (!is_array($checkTypes)) {
            // Make the string an array. Check if it is allowed first.
            if (array_key_exists($checkTypes, self::$limitFields)) {
                $checkTypes = array($checkTypes);
            } else {
                // Received an illegal usage type to check.
                $checkTypes = array();
                Console::warn("Invalid usage type is asked to be checked.", "Monthly Usage: checkLimitsBy");
            }
        } else {
            foreach ($checkTypes as $checkType) {
                if (!array_key_exists($checkType, self::$limitFields)) {
                    // Remove this type as it is not defined as a usage type.
                    unset($checkTypes[$checkType]);
                }
            }
        }
        
        $alreadyOverQuota = $alreadyAlmostFull = false;
        
        // $checkTypes is considered an array with priority. The first usage type
        // to check which is over quota or almost full is reported in the e-mail. 
        foreach($checkTypes as $checkType) {
            
            if($this->accountType->limits[$checkType] < 0){
                Utils::sendEmail(array(
                    "to" => "serkan@interlogy.com",
                    "subject" => "Limit size problem detected.",
                    "body" => print_r($this->accountType->limits, true)."\nUsername: ".$this->user->username."\nServer:".Servers::whoAmI(),
                    "html" => false
                ));
                continue;
            }
            
            // Usage number is over the quota limits. An over-quota email must have 
            // already been sent. Do not send another one.
            if ($this->$checkType > $this->accountType->limits[$checkType]) {
                // An over-quota email has already been sent. Do not send other
                // notification e-mails of over-quota or almost-full.
                $res = DB::read("SELECT * FROM `scheduled_downgrades` WHERE `username` = ':s'", $this->user->username);
                if ( !$forceSend && $res->rows > 0){
                	$alreadyOverQuota = true;
                }else{
	                array_push($this->overQuota, $checkType);
                }
                // No need to check others.
                break;
            }
            else if ($this->$checkType == $this->accountType->limits[$checkType]) {
                // Usage number is exactly equal to the quota limits, send e-mail.
                array_push($this->overQuota, $checkType);
            }   
            else if ($this->$checkType > floor($this->accountType->limits[$checkType] * 0.9)) {
                // Usage number is less than the limit but over 90% of the it. 
                // E-mail must have been sent already.
                $alreadyAlmostFull = true;
            } 
            else if ($this->$checkType == floor($this->accountType->limits[$checkType] * 0.9)) {
                // Usage number is exactly 90% of the limit, send e-mail.
                array_push($this->almostFull, $checkType);
            }
        }
        
        // If the form is already over-quota, do not send any e-mails.
        if ( $forceSend || !$alreadyOverQuota) {
            // Send notification e-mails. "Over quota" and "almost full" e-mails 
            // will be sent once only.
            if ($sendOverQuotaEmail && sizeof($this->overQuota) > 0) {
                // Add user to schedule disable list.
                // Send an overquota mail.
                $this->sendOverquotaEmail();
                // Reset overQuota and almostFull arrays.
                $this->overQuota = $this->almostFull = array();
                return true;
            } else if ($sendAlmostFullEmail && !$alreadyAlmostFull 
                        && sizeof($this->almostFull) > 0) {
                // Send an "almost full" mail.
                $this->sendAlmostFullEmail();
                // Reset overQuota and almostFull arrays.
                $this->overQuota = $this->almostFull = array();
                return true;
            }
        }
        // Reset overQuota and almostFull arrays.
        $this->overQuota = $this->almostFull = array();
        // No e-mails sent.
        return false;
    }
    /**
     * Send e-mail stating that it is disabled.
     * @return 
     */
    public function sendOverquotaEmail() {
        # Add to schedule downgrade list.
        Console::customLog("overlimit", "username: " . $this->user->username . " reason: " . $this->overQuota[0]);
        $downgradeDate = $this->user->addToScheduledDisableList();
        $dateProps = getdate(strtotime($downgradeDate));
        # Send Email
        ob_start();
        $content = ROOT. "/opt/templates/over_quota.php";
        include ROOT . "/opt/templates/email_template.html";
        $message = ob_get_contents();
        ob_end_clean();
        Utils::sendEmail(array('from' => array(NOREPLY, NOREPLY_SUPPORT), 
                'to' => array($this->user->email), 'subject' => "JotForm Account Over Quota", 'body' => $message));
    }
    /**
     * 
     * @return 
     */
    public function sendAlmostFullEmail() {
         // Send e-mail stating that the form is nearly over quota.
        ob_start();
        $content = ROOT . "/opt/templates/almost_full.php";
        include ROOT . "/opt/templates/email_template.html";
        $message = ob_get_contents();
        
        ob_end_clean();
        Utils::sendEmail(array('from' => array(NOREPLY, NOREPLY_SUPPORT), 
                'to' => array($this->user->email), 'subject' => "JotForm Account Almost Full", 'body' => $message));
    }
    
    /**
     * Sets a usage statistic by username
     * @param object $username
     * @param object $type
     * @param object $value
     * @return 
     */
    static function setUsageBy($username, $type, $value){
        DB::write("UPDATE `monthly_usage` SET `:type`=':value' WHERE `username`=':username'", $type, $value, $username);
    }
    
    public function setUsage($type, $value) {
        self::setUsageBy($this->user->username, $type, $value);
    }
    
    /**
     * Deletes all upload files and folder without a database entry
     * @param object $username
     * @return 
     */
    static function cleanOrphanUploads($username){
        
        # Forms found on DB
        $forms = array();
        
        # Loop through all form upload folders
        foreach(glob(UPLOAD_FOLDER.$username."/*") as $formFolder){
            $folder = explode("/", $formFolder);
            $formID = array_pop($folder); # Get the formID from folder name
            
            # Check given form on the database
            $res = DB::read("SELECT `id` FROM `forms` WHERE `id`=#id", $formID);
            if($res->rows > 0){
                $forms[] = $formID;
            }else{
                # remove the orphan form folder
                Utils::recursiveRmdir($formFolder);
            }
        }
        
        # Check all submission folders and clean if necessary
        foreach($forms as $formID){
            foreach(glob(UPLOAD_FOLDER.$username."/".$formID."/*") as $submissionFolder){
                $folder = explode("/", $submissionFolder);
                $sid = array_pop($folder);
                
                $res = DB::read("SELECT `id` FROM `submissions` WHERE `id`=':id'", $sid);
                if($res->rows < 1){
                    Utils::recursiveRmdir($submissionFolder);
                }
            }
        }
    }
    /**
     * Check is status is overlimited
     * @return 
     */
    private function isOverLimited(){
        return $this->user == "OVERLIMIT";
    }
    
    /**
     * Check if user is in scheduled downgrades table or not
     * @return 
     */
    private function isScheduledDowngraded($username){
        $res = DB::read("SELECT * FROM `scheduled_downgrades` WHERE `username` = ':s'", $username);
        return $res->rows > 0;
    }
     
    /**
     * Checks the uploads folder for given user calculates the size then updates the monthly usage table
     * @param object $username Username to calculate uploads for
     * @param object $deep [optional] if provided cleans the orphan uploads too
     * @return 
     */
    static function calculateDiskUsage($username, $deep = false){
        
        if($deep){
            self::cleanOrphanUploads($username);
        }
        
        $bytes = self::getDiskUsage($username);
        
        self::setUsageBy($username, "uploads", $bytes);
        
        # Check if user is overlimited still.
        $monthlyUsage = MonthlyUsage::find($username);
                
        # Console::log(var_export($monthlyUsage->isOverLimited(), true)." - ".var_export($monthlyUsage->isScheduledDowngraded($username), true)." - ".var_export($monthlyUsage->isOverQuota(), true));
        
        if (($monthlyUsage->isOverLimited() || $monthlyUsage->isScheduledDowngraded($username)) && !$monthlyUsage->isOverQuota()){
        	# Make user active
        	User::recoverFromOverLimit($username);
        }
        return $bytes;
    }
    
    /**
     * This function returs the used bytes from the database.
     * @param String $username
     * @return integer $usedBytes
     */
    static function getDiskUsage($username){
    	if (ENABLE_UFS){
    		$res = DB::read("SELECT SUM(`size`) as total FROM `upload_files` WHERE `username` = ':s' AND `uploaded` = 1", $username);
    		if ( isset($res->result) && isset($res->result[0]) ){
    			$result = $res->result[0];
    			$bytes = $result['total'];
    		}
    	}else{
    		exec('du -sk '.UPLOAD_FOLDER . $username, $res);
	        if(!empty($res[0])){
	            list($bytes, $path) = explode("\t", $res[0]);
	            $bytes = $bytes * 1024;                        
	        }else{
	            $bytes = "0";
	        }
    	}
    	
        return isset($bytes) ? $bytes : 0;
    }
}
