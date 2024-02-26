<?php

/**
 * User class modeling the users.
 * Columns in the DB are:
 * id, username, password, name, email, website, time_zone, folder_config, ip, account_type,
 * saved_emails, created_at, updated_at, last_seen_at.
 *
 * created_at and updated_at are populated automatically through triggers.
 * id is given automatically through an auto increment field.
 *
 * @package JotForm_User_Management
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\UserManagement;
use Legacy\Jot\Configs;
use Legacy\Jot\Exceptions\JotFormException;
use Legacy\Jot\Exceptions\RecordNotFoundException;
use Legacy\Jot\Form;
use Legacy\Jot\RequestServer;
use Legacy\Jot\Utils\Client;
use Legacy\Jot\Utils\Console;
use Legacy\Jot\Utils\DB;
use Legacy\Jot\Utils\Server;
use Legacy\Jot\Utils\Utils;

class User {
    /**
     * Keeps database fields and their types, : for a string and a # for a number.
     * Used when saving/updating a user.
     *
     * To parse each field, ':' is used for strings and '#' used for numbers.
     *
     * Example queries, with parameterized variables:
     *
     * UPDATE users SET username=':username',
     password=':password', email=':email', name=':name', website=':website'
     account_type=':account_type', friends=':friends', is_html=':is_html'
     creation_date=':creation_date', last_seen=':last_seen', time_zone=#time_zone
     WHERE username=':username'

 
     * INSERT INTO users (username, password, email,
     name, website, account_type, friends, is_html, creation_date, last_seen, time_zone)
     VALUES (':username',  ':password', ':email', ':name', ':website', ':account_type',
     ':friends', ':is_html', ':creation_date', ':last_seen', :time_zone)
     *
     * @var array
     */
    private static $dbFields = array('id'=>'#', 'username' => ':', 'password' => ':',
        'name' => ':', 'email' => ':', 'website' => ':', 'time_zone' => ':', 
        'folder_config' =>':', 'ip' => ':', 'account_type' => ':', 'status' => ':',
        'saved_emails' => ':', 'created_at' => ':', 'updated_at' => ':', 'last_seen_at' => ':', 'referer' => ':', "LDAP" => "#");

    /**
     * SQL Queries which are used.
     *
     * Note the use of "LIMIT 1" for queries which we know to return a single result. This makes the DB engine
     * run faster as it will stop after finding that single row.
     * @var
     */
    private static $selectActiveQuery = "SELECT * FROM `users` WHERE `username`=':username' AND (`status` = 'ACTIVE' OR `status` IS NULL) LIMIT 1";
    private static $selectQuery = "SELECT * FROM `users` WHERE `username`=':username' LIMIT 1";
    private static $reallyDeleteQuery = "DELETE FROM `users` WHERE `username`=':username' LIMIT 1";

    /**
     * "DELETED" is for users who delete themselves, "SUSPENDED" is for users
     * we delete ourselves as part of administration, "AUTOSUSPENDED" is for
     * automatically deleted, for example for phishing.
     */
    private static $allowedStatus = array('SUSPENDED', 'DELETED', 'AUTOSUSPENDED');
    private static $statusQuery = "UPDATE `users` SET `status` = ':status'
                                   WHERE `username`=':username' LIMIT 1";    
    public static $salt = "jotsalt_heisahbu";
    
    private $client;
    /**
     *
     * Constructor
     * @param object $userProps [optional]
     * @return
     */
    public function __construct($userProps = NULL) {
        if ($userProps == NULL) {
            return;
        }
        $this->client = new Client();
        foreach (Utils::arrayKeysToCamel($userProps) as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Finds an active (not deleted, suspended etc.) user with the given
     * username from the table. You can choose only active users (status null)
     * by using "true" value for the second variable. Otherwise the status does
     * not matter.
     *
     * @param string $username
     * @param boolean $onlyActive
     * @return found user.
     */
    public static function find($username, $onlyActive = false) {
        
        // Use the appropriate query. Active users or not.
        $resultObj = DB::read($onlyActive? self::$selectActiveQuery : self::$selectQuery, $username);

        if ($resultObj->success == true && $resultObj->rows == 1) {
            $user = new User($resultObj->result[0]);
            return $user;
        } else { // No user with the given username found.
            return false;
        }
    }

    /**
     * Finds the user by given E-mail, excludes GUEST accounts. 
     * @param object $username
     * @param object $onlyActive [optional]
     * @return
     */
    public static function findByEmail($email, $statusCond = array('ACTIVE')) {
        // $queryCond = implode(",", $statusCond);
        $queryCond = "";
        foreach($statusCond as $statusValue) {
            $queryCond .= "'$statusValue', ";    
        }
        $queryCond = substr($queryCond, 0, strlen($queryCond) - 2);
        $resultObj = DB::read("SELECT * FROM `users` WHERE `email`=':email' AND `status` in ($queryCond) AND `account_type` != 'GUEST'", $email);

        if ($resultObj->rows > 0) {
            $user = new User($resultObj->first);
            return $user;
        } else { // No user with the given username found.
            return false;
        }
    }
    /**
     * Will do a search on Users table
     * @param object $keyword
     * @return 
     */
    public static function searchUsers($keyword, $noGuests = false, $limit=false){
        

        $keyword = str_replace('*', '%', $keyword);
        $typeLimit = "";
        if($noGuests){
            $typeLimit = " AND `account_type` != 'GUEST'";
        }
        
        $query = "SELECT * FROM `users` WHERE (`username` LIKE ':keyword' OR `email` LIKE ':keyword' OR `ip` LIKE ':keyword')".$typeLimit;
        if ($limit !== false){
            $query .= " LIMIT 0, {$limit}";
        }
        $res = DB::read($query, array('keyword' => $keyword));
        
        if($res->rows < 1 && is_numeric($keyword)){ // No user found then check forms table
            $formres = DB::read("SELECT `username` FROM `forms` WHERE `id`=#keyword", $keyword);
            $res = DB::read("SELECT * FROM `users` WHERE `username` = ':username'", $formres->first['username']);
        }
        
        if (Session::isSupport()){
            Console::logAdminOperation(Session::getSysAdmUserame() . "\n" . $query);
        }
        
        $users = array();
        foreach($res->result as $user){
            # Don't display blank user
            if($user['username'] == ""){ continue; }
            unset($user['ip']);
            unset($user['password']);
            unset($user['folder_config']);
            $users[] = $user;
        }
        
        return $users;
    }
    
    /**
     * This function returns an array of object-.
     * @return array
     */
    public static function getAdminAndSupportUsers(){
        $response = DB::read("SELECT * FROM `users` WHERE `account_type` = 'SUPPORT' OR `account_type` = 'ADMIN'");
        $users = array();

        foreach ($response->result as $row){
        	$users[$row['username']] = User::find($row['username']);
        }
        # TODO: change this.
        if ( Server::isLocalhost() ){
            $server = "www.jotform.com/jcm/jcm_server.php"  ;
        }else{
            $server = Server::getHost() . "/jcm/jcm_server.php"  ;
        }
        
        $res = Utils::curlRequest($server, array(
            "action" => "getSubmissionCounts",
            "usernames" => json_encode(array_keys($users))
        ));
        
        $result = json_decode($res['content'], true);
        
        $userData = $result['result']['content'];
        
        foreach ($userData as $username => $data){
        	$users[$username]->userData = $data;
        }
        
        return $users;
    }
    
	public static function getFormList($username){
		$list = array();

        // If the username is somehow empty, do not show any forms, don't go to the DB.
        if (empty($username)) {
            throw new Exception('missing username');
//            $this->success(array(
//                "forms" => $list
//            ));
        }

        $response = DB::read("SELECT * FROM `forms` WHERE `username`=':username' ORDER BY `title` ASC", $username);

        foreach($response->result as $line){
            
            if($line['count'] < 0){
                $line['count'] = Form::updateSubmissionCount($line["id"]);
            }
            
            if($line['new'] < 0){
                $line['new'] = Form::updateNewSubmissionCount($line["id"]);
            }
            
            $line['title'] = stripslashes($line['title']);
            $line['created_date'] = date("M j, Y", strtotime($line['created_at']));
            /*
            $reports  = Report::getAllByFormID($line["id"], true);
            $listings = DataListings::getAllByFormID($line["id"]);
            
            $line["reports"] = array_merge($reports, $listings);
            */
            $list[] = $line;
        }

        #{TODO: extend form list with more information such as hasPayment}
		return $list;
	}
	
    /**
     * Returns the given users time zone
     * @param object $username
     * @return 
     */
    public static function getTimeZone($username){
        $res = DB::read("SELECT `time_zone` FROM `users` WHERE `username`=':username'", $username);
        return $res->first['time_zone'];
    }
    
    /**
     *
     * @param object $emailAddress
     * @return
     */
    public static function sendPasswordReset($resetData) {
        $query = "SELECT username, email, password FROM users WHERE ";
        if (strpos($resetData, "@")) {
            $query .= "email = ':emailAddress' AND `status` = 'ACTIVE'";
        } else {
            $query .= "username = ':username' AND `status` = 'ACTIVE'";
        }
        $query .= " AND `account_type` != 'GUEST'";
        $resultObj = DB::read($query, $resetData);
        if ($resultObj->success == true && $resultObj->rows > 0) {
            $userArr = $resultObj->result[0];
            $token = md5($userArr['password'] . date('Y-m-d'));
            // HTTP_URL was SSL_URL before. Changed until we fix the SSL issue.
            $passResetURL = HTTP_URL . "page.php?p=passwordreset&username=" . urlencode($userArr['username']) . "&token=" . $token;
            ob_start();
            $content =  ROOT. "/opt/templates/password_reset.php";
            include ROOT. "/opt/templates/email_template.html";
            $message = ob_get_contents();
            ob_end_clean();
            Utils::sendEmail(array('from' => array(NOREPLY, NOREPLY_SUPPORT),
                    'to' => array($userArr['email']), 'subject' => Configs::COMPANY_TITLE." Lost Password", 'body' => $message));
            return "Password reminder email has been sent successfully.";
        } else {
            throw new RecordNotFoundException("No user with this email address or username has been found.");
        }
    }
    
    /**
     * Updates a user object by writing the new values to the DB.
     *
     * Save and update methods are very different. The first one "INSERT"s
     * while the second "UPDATE"s the table.
     * One cannot simply differentiate between an update and an insert
     * implicitly because there's no ID field so that you can say "this
     * User has no ID so it is new and it should be saved" or "this User
     * has an ID so it should be updated". For Forms etc. this might work
     * by using only one function ie. save also doing an update.
     *
     * WRONG. DISCARD THE PARAGRAPH ABOVE. USER ID *CAN* BE USED.
     *
     * TODO: update() method always uses username as well, that is it updates the username
     * in the DB everytime. Furthermore, you cannot change the username this way, username
     * has to be given as a parameter. Fix these errors.
     *
     * @return result object from the DB class.
     */
    public function save() {
        // By default users have account_type FREE. New accounts are created 
        // with FREE, using the database default.
        $noUpdate = true;
        $queryFieldPart = $queryValuePart = NULL;
        $query = "";
        
        # Set default account type for users in APP mode
        if(APP && (!isset($this->accountType) || (isset($this->accountType) && !$this->accountType))){
            $this->accountType = Configs::DEFAULT_USER_TYPE;
        }
        
        foreach(self::$dbFields as $fieldName => $fieldType) {
            if ($fieldName == "id") {
                // We never update/save the ID field implicitly, it should
                // always be updated automatically.
                $args['id'] = isset($this->id)? $this->id : NULL;
                continue;
            }
            $camelCasedFieldName = Utils::stringToCamel($fieldName);
            if (isset($this->$camelCasedFieldName) && !empty($this->$camelCasedFieldName)) {
                $noUpdate = false;

                if (isset($this->id)) {
                    // Update query case.
                    if ($query == NULL) {
                        $query = "UPDATE users SET";
                    } else {
                        $query .= ",";
                    }
                     
                    $noUpdate = false;
                    $args[$fieldName] = $this->$camelCasedFieldName;
                    $query .= " $fieldName = '$fieldType$fieldName'";
                    if($fieldName == 'email'){
                        $this->updateALLEmails($this->email);
                    }
                } else {
                    // Insert query case.
                    if ($queryFieldPart == NULL) {
                        $queryFieldPart = '(';
                    } else {
                        $queryFieldPart .= ',';
                    }
                    if ($queryValuePart == NULL) {
                        $queryValuePart = '(';
                    } else {
                        $queryValuePart .= ',';
                    }
                    $args[$fieldName] = $this->$camelCasedFieldName;
                    $queryFieldPart .= $fieldName;
                    $queryValuePart .= "'$fieldType$fieldName'";
                }
            }
        }

        // If there's no update of any User field, throw an error.
        if ($noUpdate) {
            return false;
        }

        if (isset($this->id)) {
            $query .= " WHERE id=':id'";
        } else {
            $queryFieldPart .= ')';
            $queryValuePart .= ')';
            // Else finish the query and execute it.
            $query = "INSERT INTO users " . $queryFieldPart . " VALUES $queryValuePart";
        }
         
        // What to do with the result given?
        return DB::write($query, $args);
    }
    
    /**
     * Change all occurances of users email address to given address  
     * @param object $from
     * @param object $to
     * @return 
     */
    public function updateALLEmails($to){
        $u = User::find($this->username);
        
        // If e-mail is already the same then don't do any change
        if($to == $u->email){ return; }
        
        // If account e-mail is removed. don't remove all emails from forms
        if(empty($to)){ return; }
        
        $forms = DB::read("SELECT * FROM `forms` WHERE `username`=':username'", $this->username);
        
        foreach($forms->result as $line){
            
            $res = DB::write("UPDATE `form_properties` SET `value`=':to'
                 WHERE `form_id` = #id
                 AND `type` LIKE 'emails'
                 AND (prop = 'to' OR prop='from')
                 AND value=':from'",
                array(
                    "id" => $line['id'],
                    "to"=> $to,
                    "from" => $u->email,
                    "username" => $this->username
                )
            );
        }
        
        /*$res = DB::write("UPDATE `form_properties` SET `value`=':to'
             WHERE `form_id` IN (SELECT `id` FROM `forms` WHERE `username`=':username')
             AND `type` LIKE 'emails'
             AND (prop = 'to' OR prop='from')
             AND value=':from'",
            array(
                "to"=> $to,
                "from" => $u->email,
                "username" => $this->username
            )
        );*/        
    }
    
    /**
     * Really deletes a user from the table, it removes the user completely.
     * Also removes the related tables, ie. MonthlyUsage, Forms, ...
     * @param object $username
     * @return result object from the DB class.
     */
    public static function reallyDelete($username) { // O RLY o_O
        $resultObj = DB::write(self::$reallyDeleteQuery, array('username' => $username));
        return $resultObj;
    }
    /**
     * Sets the status of a user. It can be any status defined in self::$allowedStatus
     * static variable.
     *
     * Throws JotFormException if the status is not defined there, or
     * RecordNotFoundException if the user does not exist.
     *
     * @param object $username
     * @param object $status
     * @return
     */
    public static function setStatus($username, $status) {
        /*
        if (!in_array($status, self::$allowedStatus)) {
            throw new JotFormException('Status not Allowed');
        }
        */
        $resultObj = DB::write(self::$statusQuery, array('username' => $username, 'status' => $status));
        if ($resultObj->success == true) {
            return $resultObj;
        } else { // No user with the given username found.
            throw new RecordNotFoundException();
        }
    }
    
    public static function statusJobs($username, $status) {
        // DB::write("UPDATE `forms` SET `status` = ':status' WHERE `username` = ':username' AND `status` NOT IN ('DISABLED', 'DELETED')", $status, $username);
        User::clearCache($username);
        return User::setStatus($username, $status);
    }

    /**
     * Convenience methods using the User->setStatus method.
     */
    public static function delete($username) {
        $status = 'DELETED';
        return self::statusJobs($username, $status);
    }
    
	public function disableUser(){
	    # Control if user is overlimited here.
		self::overlimit ($this->username);
		$this->sendDisabledEmail();
	}
	
    /**
     * Convenience methods using the User->setStatus method.
     */
    public static function overlimit($username) {
        $status = 'OVERLIMIT';
        return self::statusJobs($username, $status);
    }
	
    /**
     * Recover user from being overlimited and activate it's status
     * @param object $username
     * @return 
     */
    public static function recoverFromOverLimit($username){
        self::activate($username);
        # Delete user if it's in downgrade schedule
        $res = DB::write("DELETE FROM `scheduled_downgrades` WHERE `username` = ':s'", $username);
    }
    
    
    public static function active($username){
        $status = 'ACTIVE';
        return self::statusJobs($username, $status);
    }
    
	public function downgradeUser(){
		$this->setAccountType(AccountType::find("FREE"));
		$this->sendDowngradedEmail();
	}
	/**
	 * MArk user as seen on this date
	 * @return 
	 */
    public function seen(){
        DB::write("UPDATE `users` SET `last_seen_at`=NOW() WHERE `username`=':username'", $this->username);
    }
    
	public function sendDowngradedEmail() {
        ob_start();
        $content = ROOT. "/opt/templates/downgraded.php";
        include ROOT . "/opt/templates/email_template.html";
        $message = ob_get_contents();
        ob_end_clean();
        Utils::sendEmail(array('from' => array(NOREPLY, NOREPLY_SUPPORT), 
                'to' => array($this->email), 'subject' => Configs::COMPANY_TITLE." Account Downgraded", 'body' => $message));
	}
	
	public function sendDisabledEmail() {
        ob_start();
        $content = ROOT. "/opt/templates/overlimited.php";
        include ROOT . "/opt/templates/email_template.html";
        $message = ob_get_contents();
        ob_end_clean();
        Utils::sendEmail(array('from' => array(NOREPLY, NOREPLY_SUPPORT), 
                'to' => array($this->email), 'subject' => Configs::COMPANY_TITLE." Account Disabled", 'body' => $message));
    }
    
    /**
     * Returns the ip of the client object
     */
    public function getIp(){
        return $this->client->ip;
    }
    
    public function getBrowser(){
    	$browser = new Browser($this->client->useragent);
    	if ($browser){
    		return $browser->getPlatform() . " User.php" . $browser->getBrowser() . " " . $browser->getVersion() ;  
    	}else{
    	   return false;
    	}
    }
    
    /**
     * Reverses the effects of suspend and auto-suspend.
     * @param unknown_type $username
     * @return unknown_type
     */
    public static function activate($username) {
        // Can't use statusJobs because users.status.active = forms.status.enabled
        // they have different names.
        DB::write("UPDATE `forms` SET `status` = ':status' WHERE `username` = ':username'", 'ENABLED', $username);

        return User::setStatus($username, 'ACTIVE');
    }
    
    public static function unsuspend($username){
        // Can't use statusJobs because users.status.active = forms.status.enabled
        // they have different names.
        DB::write("UPDATE `forms` SET `status` = ':status' WHERE `username` = ':username'", 'ENABLED', $username);

        // Get all the forms of the user. 
        $res = DB::read("SELECT * FROM `forms` WHERE `username`=':username'",$username);

        foreach($res->result as $line){
            DB::write("REPLACE INTO `whitelist` (`form_id`) VALUES ('".$line['id']."') ");
        }
        
        return User::setStatus($username, 'ACTIVE');
    }
    
    /**
     * Suspends the user
     * @param object $username
     * @return
     */
    public static function suspend($username) {
        $status = 'SUSPENDED';
        return self::statusJobs($username, $status);
    }
    
    /**
     * Marks user as auto suspended
     * @param object $username
     * @return
     */
    public static function autoSuspend($username) {
        $status = 'AUTOSUSPENDED';
        return self::statusJobs($username, $status);
    }
    
    /**
     * Will clear all user cache
     * @param object $username
     * @return 
     */
    public static function clearCache($username){
        $request = new RequestServer(array(
           "action" => 'clearUserCache',
           "username" => $username,
           "toAll"  => "yes"
        ), true);
    }
    
    /**
     * Return status value of the user. This value is empty if the user account
     * is in normal operation, but it can also be anything in $allowedStatus
     * such as DELETED.
     * @return
     */
    public function getStatus() {
        return $this->status;
    }
    
    /**
     * Get the monthly usage of the user
     * @return 
     */
    public function getMonthlyUsage(){
        $res = DB::read("SELECT * FROM `monthly_usage` WHERE `username` = ':username'", $this->username);
        
        if ($res->rows > 0){
            return $res->first;
        }
        
        return false;
    }
    
    /**
     * Check if user has exceed it limits.
     * 
     * @return boolean
     */
    public function isExceeded($accountType = false, $resultRow = false){
        $exceeded = false;
        
        if ( $accountType === false ) {
            $accountType = AccountType::find($this->accountType);
        }
        
        $resultRow = false;
        
        if ($resultRow === false){
            $resultRow = $this->getMonthlyUsage();
        }

        if ($resultRow){
            
            $submissions = $resultRow['submissions'];
            $sslSubmissions = $resultRow['ssl_submissions'];
            $payments = $resultRow['payments'];
            $uploads = $resultRow['uploads'];
            $tickets = $resultRow['tickets'];
            
            if ($accountType->limits['submissions']     < $submissions || 
                $accountType->limits['sslSubmissions']  < $sslSubmissions ||
                $accountType->limits['payments']        < $payments ||
                $accountType->limits['uploads']         < $uploads ||   
                $accountType->limits['tickets']         < $tickets ){
                $exceeded = true;
            }
        }
        
        unset($accountType);
        
        return $exceeded;
    }
    
    /**
     * Check if user has exceed it limits.
     * 
     * @return boolean
     */
    public function isLimitOver($percent, $limits = false){
        $exceeded = false;
        $accountType = AccountType::find($this->accountType);
        
        if($this->accountType == 'OLDPREMIUM' || $this->accountType == 'PROFESSIONAL'){
            return false; # These account types cannot upgrade or have no limit.
        }
        
        if($limits == false){
            $limits = array("submissions", "sslSubmissions", "payments", "uploads", "tickets");
        }else if(is_string($limits)){
            $limits = array($limits);
        }
        
        if ($resultRow = $this->getMonthlyUsage()){
            
            $resultRow['sslSubmissions'] = $resultRow['ssl_submissions'];
            
            foreach($limits as $limit){
                if(Utils::percent($accountType->limits[$limit], $resultRow[$limit]) >= $percent){
                    $exceeded = true;
                }
            }
        }
        
        unset($accountType);
        
        return $exceeded;
    }
    
    /**
     * Updates the user cookie
     * @return 
     */
    public function updateCookie(){
        self::setCookie($this->username, $this->password);
    }
    
    public function addToScheduledDisableList(){

        $dayLater = 1;
        
        while ($dayLater < 4){
            $timeStamp = strtotime("now + {$dayLater} day");

            $dateProps = getdate( $timeStamp );
            
            if ( $dateProps['weekday'] != "Saturday" && $dateProps['weekday'] != "Sunday" ){
                $eotDate = date( "Y-m-d 12:00:00", $timeStamp );
                break;
            }
                    
            $dayLater++;
        }
        
        $res = DB::write("  REPLACE INTO `scheduled_downgrades`
                            (`username`, `eot_time`, `gateway`, `reason` )
                            VALUES
                            (':username', ':eotTime', ':gateway', ':reason' )", 
                            $this->username, $eotDate,
                            "", "overlimit" );
        
        return $eotDate;
    }
    
    /**
     * Change user account type
     */
    public function setAccountType(AccountType $accountType){
        $res = DB::write("UPDATE `users` SET `account_type` = ':accountType' WHERE `username` = ':username'",
                $accountType->name, $this->username);
                
        $request = new RequestServer(array(
           "action" => 'clearUserSession',
           "username" => $this->username,
           "toAll"  => "yes"
        ), true);
                
        return $res->success;
    }

    /**
     * Compares username and password with that in the database users table.
     * Doesn't care about the user's status, only that the username and password
     * matches.
     *
     * @param object $username
     * @param object $password
     * @return
     */
    public static function checkLogin($username, $password) {
        
        $oldPassword = self::oldEncode($password);  # Because of the migration
        $password = self::encodePassword($password);
        
        $response = DB::read("SELECT * FROM `users`
                               WHERE (`username` = ':username' OR `email` = ':username') 
                               AND (`password` = ':password' OR `password` = ':oldPassword') 
                               AND (`account_type` != 'GUEST' OR `account_type` IS NULL)
                             ", $username, $username, $password, $oldPassword);
                             
        if ($response->rows > 0) {
            $userProp = $response->result[0];
            $user = new User($userProp);
            return $user;
        }
        return false;
    }
    
    /**
     * Check if the user is an LDAP account
     * @return 
     */
    public function isLDAP(){
        return $this->LDAP == "1"; 
    }
    
    /**
     * Logs in LDAP users and updates their account.
     * We need this because we always have to check LDAP server for password and account changes
     * @param object $username
     * @param object $password
     * @return 
     */
    public static function LDAPLogin($username, $password, $remember){
        # This users account was created from LDAP server
        # we should re fetch this user and update the 
        # information to keep up with LDAP servers
        if($u = self::checkLDAP($username, $password)){
            
            $user = self::updateUserFromLDAP($username, $password);
            if($user === false){
                throw new Exception('Account has been deleted');
            }
            
            # If this user is admin then create an admin cookie.
            if($user->accountType == "ADMIN"){
                Utils::setCookie("admin", self::getAdminHash($user->username, $user->password), "+1 Month");
            }

            # You can set cookies.
            if ($remember) {
                # Set cookie to remember login.
                self::setCookie($user->username, $user->password);
            }
            
            # Cookie for jotform content management connection
            # This cookie does not make login to remember.
            self::setCookie($user->username, $user->password, CONTENT_COOKIE_NAME);
            self::setCookie($user->username, $user->password, CONTENT_COOKIE);
            
            if ($lastForm = Utils::getCurrentID('form')) {
                Form::assignOwner($lastForm, $user->username, true);
            }
            
            # Also set the session.
            Session::setUserGlobals($user, 'login');
            Session::claimGuestAccount($user->username);
            
            return true;
        }
        
        throw new Exception('Username or password did not match.');
    }
    
    /**
     * Authenticates user and sets sessions
     * @param object $username
     * @param object $password
     * @param object $remember
     * @param object $forceDeleted
     * @return
     */
    public static function login($user, $password = false, $remember = false, $forceDeleted = false){
        
        $uname = $user;

        if(Configs::USELDAP && is_string($uname)){
            if($lu = User::find($uname)){
                if($lu->isLDAP()){
                    return self::LDAPLogin($uname, $password, $remember);
                }
            }
        }
        
        if ($user instanceof User || $user = User::checkLogin($user, $password)) {
        	# Do this controls if user is not admin.
        	if ( !Session::isAdmin() && !Session::isSupport() ){
	            # Check if the user was disabled/deleted.
	            if (!empty($user->status)) {
	                if ( $user->status == 'AUTOSUSPENDED' || $user->status == 'SUSPENDED') {
	                    # User suspended because of phishing.
	                    throw new Exception($user->status);
	                }
	            }
        	}
            
            # Deleted is a special status, different from the Suspended statuses.
            if (($user->status == "DELETED") && $forceDeleted) {
                # Force logging in of deleted accounts in place.
                # Re-Enable this user.
                $user->status = "ACTIVE";
                $user->save();
            } else if ($user->status == "DELETED") {
                # User had deleted his account before. Ask if they want to
                # re-enable it.
                throw new Exception('DELETED');
            }
            
            # If this user is admin then create an admin cookie.
            if($user->accountType == "ADMIN"){
                Utils::setCookie("admin", self::getAdminHash($user->username, $user->password), "+1 Month");
            }
            if ($user->accountType === "SUPPORT"){
                Utils::setCookie("support", self::getSupportHash($user->username, $user->password), "+1 Month");
            }

            # You can set cookies.
            if ($remember) {
                # Set cookie to remember login.
                self::setCookie($user->username, $user->password);
            }
            
            # Cookie for jotform content management connection
            # This cookie does not make login to remember.
            self::setCookie($user->username, $user->password, CONTENT_COOKIE_NAME);
            self::setCookie($user->username, $user->password, CONTENT_COOKIE);
            
            if ($lastForm = Utils::getCurrentID('form')) {
                Form::assignOwner($lastForm, $user->username, true);
            }
            
            # Also set the session.
            Session::setUserGlobals($user, 'login');
            Session::claimGuestAccount($user->username);
            
            return true;
        }
        
        if($u = self::checkLDAP($uname, $password)){            
            $LDAPUser = new User(array(
                "username" => $uname,
                "password" => self::encodePassword($password),
                "name"     => $u['cn'],
                "email"    => $u['mail'],
                "LDAP"     => 1
            ));
            $LDAPUser->save();
            User::forceLogin($uname);
            return true;
        }
        
        throw new Exception('Username or password did not match.');
    }
    
    /**
     * Go find user information on LDAP servers and update local user with this information
     * @param object $username
     * @return 
     */
    public static function updateUserFromLDAP($username, $password){
        
        $l = new LDAPInterface();
        $l->setOption('server', Configs::LDAP_SERVER);
        $l->connectAndBind(Configs::LDAP_BIND_USER, Configs::LDAP_BIND_USER_PASS);
        $r = $l->search(Configs::LDAP_SEARH_DOMAIN, "(|(sn=".$username.")(uid=".$username.")(cn=".$username.")(givenname=".$username.")(mail=".$username."))", array("*"));
                
        # Check if the user is still existed on LDAP servers
        if(empty($r)){
            # if not mark this account as DELETED to prevent further use of the account
            User::delete($username);
            return false;
        }
        
        $user = $l->normalizeSearchResult($r[0]);
        
        $LDAPUser = User::find($username);
        
        # if user is there, retrieve all information of the user and update JotForm DB
        $LDAPUser->username = $username;
        $LDAPUser->password = self::encodePassword($password);
        $LDAPUser->name     = $user['cn'];
        $LDAPUser->email    = $user['mail'];
        $LDAPUser->LDAP     = 1;
        $LDAPUser->save();
        
        return $LDAPUser;
    }
    
    /**
     * Checks the LDAP servers for username
     * @param object $username
     * @param object $password
     * @return
     */ 
    public static function checkLDAP($username, $password){
        if(!Configs::USELDAP){ return false; }
        
        $l = new LDAPInterface();
        $l->setOption('server', Configs::LDAP_SERVER);
                
        $l->connectAndBind(Configs::LDAP_BIND_USER, Configs::LDAP_BIND_USER_PASS);
        
        $search = $username;
        
        $r = $l->search(Configs::LDAP_SEARH_DOMAIN, "(|(sn=".$search.")(uid=".$search.")(cn=".$search.")(givenname=".$search.")(mail=".$search."))", array("*"));
        # User could not be found on LDAP server
        if(empty($r)){
            return false;
        }
        
        $user = $l->normalizeSearchResult($r[0]);
        Console::log($user['userpassword'] . " - " . $password);
        if($l->password_check($user['userpassword'], $password)){
            return $user;
        }
        
        return false;
    }
    
    /**
     * Forces a username to login
     * @param object $username
     * @return 
     */
    public static function forceLogin($username){
        self::logout();
        self::login(User::find($username), true);
    }
    
    /**
     * Creates a completeley un recoverable hash from admin password
     * @param object $pass
     * @return
     */
    public static function getAdminHash($username, $password, $fingerprint=false){
        $salt = "==:+&==";
        if ($fingerprint === false){
            return base64_encode($username.":".md5($salt.$password.$salt).":".Client::getFingerPrint());
        }else{
            return base64_encode($username.":".md5($salt.$password.$salt).":".$fingerprint);
        }
    }
    /**
     * Creates a completeley un recoverable hash from support password
     * @param object $pass
     * @return
     */
    public static function getSupportHash($username, $password, $fingerprint=false){
        $salt = "=gg=:+&=sd=";
        if ($fingerprint === false){
            return base64_encode($username.":".md5($salt.$password.$salt).":".Client::getFingerPrint());
        }else{
            return base64_encode($username.":".md5($salt.$password.$salt).":".$fingerprint);
        }
    }
    /**
     * checks the admin hash with the fingerprint sent from JCM.
     */
    public static function checkAdminHashWithGivenFingerPrint($hash, $fingerprint){
        @list($username, $passhash) = explode(":", base64_decode($hash));
        
        $res = DB::read("SELECT `password` FROM `users` WHERE `username`=':user'", $username);
        if($res->first["password"]){
            $newHash = self::getAdminHash($username, $res->first["password"], $fingerprint);
            if ($newHash === $hash){
                return $username;
            }else{
                return false;
            }
        }
        return false;
    }
    /**
     * checks the support hash with the fingerprint sent from JCM.
     */
    public static function checkSupportHashWithGivenFingerPrint($hash, $fingerprint){
        @list($username, $passhash) = explode(":", base64_decode($hash));
        
        $res = DB::read("SELECT `password` FROM `users` WHERE `username`=':user'", $username);
        if($res->first["password"]){
            $newHash = self::getSupportHash($username, $res->first["password"], $fingerprint);
            if ($newHash === $hash){
                return $username;
            }else{
                return false;
            }
        }
        return false;
    }
    /**
     * Checks the admin hash if it's correct
     * @param object $hash
     * @return
     */
    public static function checkAdminHash($hash){
        @list($username, $passhash) = explode(":", base64_decode($hash));
        
        DB::setNoLog(); # Do not log this query because it useless on logs
        $res = DB::read("SELECT `password` FROM `users` WHERE `username`=':user'", $username);
        DB::setLog();
        
        if($res->first["password"]){
            $newHash = self::getAdminHash($username, $res->first["password"]);
            if ($newHash === $hash){
            	return $username;
            }else{
            	return false;
            }
        }
        return false;
    }

    /**
     * Checks the support hash if it's correct
     * @param object $hash
     * @return
     */
    public static function checkSupportHash($hash){
        @list($username, $passhash) = explode(":", base64_decode($hash));
        
        $res = DB::read("SELECT `password` FROM `users` WHERE `username`=':user'", $username);
        if($res->first["password"]){
            $newHash = self::getSupportHash($username, $res->first["password"]);
            if ($newHash === $hash){
                return $username;
            }else{
                return false;
            }
        }
        return false;
    }
    /**
     * Log's user out
     * @return
     */
    public static function logout() {
        if(Session::$accountType == "ADMIN"){
            //Utils::deleteCookie('admin');
        }
        
        Utils::deleteCookie(COOKIE_KEY);
        Utils::deleteCookie(CONTENT_COOKIE_NAME);
        Utils::deleteCookie(CONTENT_COOKIE);
        Utils::deleteCookie('guest');
        Utils::deleteCookie('theme');
        Utils::deleteCurrentID('form');
        Utils::deleteCurrentID('report');
        session_unset();
    }
    /**
     * Sets the user cookie
     * @param object $username Username
     * @param object $password password
     * @param object $name [optional] Optional name for cookie
     * @return 
     */
    public static function setCookie($username, $password, $name = false) {
        
        if(!$name){
            $cname = COOKIE_KEY;
        }else{
            $cname = $name;
        }

        # Look if the user is testforum user.
        if ($cname === CONTENT_COOKIE){
            Utils::setCookie($cname, $username . ":" . md5($password), "+1 Month", "/");
        }
        else{
            Utils::setCookie($cname, $username . ":" . self::createCookieHash($password), "+1 Month", "/");
        }
        
    }
    
    /**
     * Creates a cookie hash
     * @param object $password
     * @return 
     */
    static function createCookieHash($password, $fingerprint = false){
        if($fingerprint !== false){
            return md5($password .':'. $fingerprint);
        }
        return md5($password .':'. Client::getFingerPrint());
    }
    
    /**
     * Old version of the encode password
     * @param object $password
     * @return 
     */
    public static function oldEncode($password){
        return sha1($password . User::$salt);
    }
    
    
    public static function encodePassword($password){
        
        return hash("sha256", $password); // Salt removed because IPhone application doesn have salt in passwords
        // return hash("sha256", $password . User::$salt);
        // return sha1($password . User::$salt);
    }
    
    /**
     * creates a new account for user
     * @param object $propsArray
     * @return
     */
    public static function registerNewUser($propsArray) {
        # Soft logout
        Utils::deleteCookie(COOKIE_KEY);
        Utils::deleteCookie('guest');
        
        if(isset($_SESSION[COOKIE_KEY]->referer)){
            $referer = $_SESSION[COOKIE_KEY]->referer;
        }else{
            $referer = "unknown";
        }
        
        session_unset();
        
        // Check if there are any genuine users who registered with the same 
        // username/email combination.
        if (empty($propsArray['username']) || empty($propsArray['password']) || empty($propsArray['email']) || AccountUtils::emailAlreadyRegistered($propsArray['email'])) {
            throw new JotFormException('User with this e-mail address already registered.');
        }
        
        // Salt the password and save to the DB.
        $propsArray['password'] = self::encodePassword($propsArray['password']);
        $propsArray['referer']  = $referer;
        $u = new User($propsArray);
        try {
            $result = $u->save();
        } catch(Exception $e) {
            if (stripos($e->getMessage(), "Duplicate entry") !== false) {
                throw new JotFormException("Username is not available. Please choose a new one.");    
            }
            throw new JotFormException("An error occurred. User account could not be created.");
        }
        
        if ($result->success) {
            // Set session cookie.
            if ($lastForm = Utils::getCurrentID('form')) {
                Form::assignOwner($lastForm, $u->username, true);
            }
            
            self::setCookie($u->username, $u->password);
            self::setCookie($u->username, $u->password, CONTENT_COOKIE_NAME);
            self::setCookie($u->username, $u->password, CONTENT_COOKIE);
            Session::setUserGlobals(User::find($u->username), 'register');
            Session::claimGuestAccount($u->username);
            
            ob_start();
            $content =  ROOT. "/opt/templates/register.php";
            include ROOT. "/opt/templates/email_template.html";
            $message = ob_get_contents();
            ob_end_clean();
            Utils::sendEmail(array('from' => array(NOREPLY, NOREPLY_NAME),
                    'to' => array($u->email), 'subject' => "Welcome to ".Configs::COMPANY_TITLE, 'body' => $message));
            #Funnel::setGoal('Email Entered', 'Funnel');
            #Funnel::setGoal('Sign Up', 'Funnel');
            return 'User created successfully.';
        } else {
            if (stripos($result->error, 'Duplicate entry') !== false) {
                throw new JotFormException("Username is not available.");
            } else {
                throw new JotFormException("An error occurred. User account could not be created.");
            }
        }
    }

    /**
     * Saves the MyForms page folder and sort configuration into database
     * @param object $config
     * @return
     */
    public function saveFolderConfig($config){
        $decoded = Utils::safeJsonDecode($config);

        if(!is_array($decoded)){
            throw new Exception("Configuration cannot be decoded, therefore it's not saved");
        }

        $response = DB::write("UPDATE `users` SET `folder_config` =':config' WHERE `username`=':username'", $config, $this->username);
        if(!$response->success){
            throw new Exception($response->error);
        }
        Session::$folderConfig = $config;
        $_SESSION[COOKIE_KEY]->folderConfig = $config;
    }
}