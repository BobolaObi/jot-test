<?php

/**
 * Keeps session related stuff. Also encapsulates _SESSION globals inside.
 * @package JotForm_User_Management
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\User_Management;

class Session {
    // Static variables must be declared before their use.
    public static $id, $username, $password, $email, $name, $website, $folderConfig, $timeZone,
                  $IP, $accountType, $savedEmails, $status, $referer, $theme, $LDAP, $loginFromReport;

    public static $savePath = "/tmp";

    public static $defaultTheme = 'tile-black';

    public static function renewCookie($cookieName, $username, $password){
        if($c = Utils::getCookie($cookieName)){
            list($uname, $chash) = explode(":", $c);

            if(User::createCookieHash($password) !== $chash){
               User::setCookie($username, $password, $cookieName);
            }
        }
    }

    /**
     * Will set the user properties to Session Global
     * @return
     */
    public static function setUserGlobals($user = false, $type = "") {
    	if (empty($user)) {
    		$user = $_SESSION[COOKIE_KEY];
    	} else {
	    	$_SESSION[COOKIE_KEY] = $user;
    	}

        if(isset($user->password)){

            # Renew user cookie, if exists
            self::renewCookie(COOKIE_KEY, $user->username, $user->password);
            # renew content cookie if exists
            self::renewCookie(CONTENT_COOKIE_NAME, $user->username, $user->password);
            # renew content cookie if exists
            self::renewCookie(CONTENT_COOKIE, $user->username, $user->password);
        }

        self::$id          = isset($user->id)?       $user->id : false;
        self::$username    = isset($user->username)? $user->username : false;
        self::$password    = isset($user->password)? $user->password : false;
        self::$email       = isset($user->email)?    $user->email : false;
        self::$name        = isset($user->name)?     $user->name : false;
        self::$website     = isset($user->website)?  $user->website : false;
        self::$timeZone    = isset($user->timeZone)? $user->timeZone : false;
        self::$IP          = isset($user->ip)?       $user->ip : false;
        self::$accountType = isset($user->accountType)?  $user->accountType : "FREE";
        self::$savedEmails = isset($user->savedEmails)?  $user->savedEmails : false;
        self::$status      = isset($user->status)?       $user->status : false;
        self::$folderConfig= isset($user->folderConfig)? $user->folderConfig : false;
        self::$referer     = isset($user->referer)?      $user->referer : false;
        self::$LDAP        = isset($user->LDAP)?         $user->LDAP    : 0;
        self::$theme       = isset($user->theme)?        $user->theme   : self::getThemeFromDB();
        self::$loginFromReport = isset($user->loginFrom)?  $user->loginFrom : 0;

        self::setThemeForMemberkit();

        if($type == 'login'){
            ABTestingController::dropGuest();
        }

        # Don't work on every refresh
        if($type != "remember"){
            # If user is not guest then set last seen date
            if(!self::isGuest()){
                $user->seen();
            }
        }

        if($type == 'login' || $type=='register'){
            ABTestingController::updateGuest();
        }

        self::sendXAccountManagementHeaders();
    }

    /**
     * @see https://wiki.mozilla.org/Labs/Weave/Identity/Account_Manager/Spec/Latest
     * @see https://mozillalabs.com/blog/2010/03/account-manager/
     * @return
     */
    public static function sendXAccountManagementHeaders(){
        return; # just for now
        header('X-Account-Management: '.HTTP_URL."acmd.json");
        if(self::$name){
            $name = self::$name;
        }else{
            $name = self::$username;
        }
        $status = 'active';
        if(self::isGuest()){
            $status = 'passive';
        }
        header('X-Account-Management-Status: '.$status.'; name="'.$name.'"');
    }

    /**
     * Checks the cookies and automatically logs user in if necessery
     * @return
     */
    public static function rememberLogin($looseSession = true) {

        # Don't create sessions for slugs unless we want to
        if($looseSession && isset($_GET['slug']) && !self::slugHasSession($_GET['slug'])){
            return;
        }

        # Don't crate session for ajax requests unless we want to
        if($looseSession && strstr($_SERVER['PHP_SELF'], 'server.php')){
            return;
        }

        # Don't crate session for form submits
        if($looseSession && strstr($_SERVER['PHP_SELF'], 'submit.php')){
            return;
        }

        if(!file_exists(self::$savePath)){
            mkdir(self::$savePath, 0777);
        }

	    @ini_set('session.save_handler','files');
        @session_save_path(self::$savePath);

        if(!isset($_SESSION)){
        session_start();
        }


        if (isset($_SESSION[COOKIE_KEY])) {
            self::setUserGlobals(null, 'remember');
            return true;
        }

        if ($cookie = Utils::getCookie(COOKIE_KEY)) {
            list($cUsername, $cPassword) = explode(':', $cookie);
            if ( self::checkUserPasswordHash($cUsername, $cPassword)){
            	return true;
            }
            // delete the cookie and return false if the cookie is not legit.
            Utils::deleteCookie(COOKIE_KEY);
            return false;
        }

        self::createGuestSession();

        return false;
    }

    /**
     * Check if slug url needs sessions or not
     * @param object $slug
     * @return
     */
    public static function slugHasSession($slug){

        return false;
    }

    /**
     * Cheks the password hash of a user
     * @param object $cUsername
     * @param object $cPassword
     * @param object $params [optional]
     * @return
     */
    public static function checkUserPasswordHash($cUsername, $cPassword, $params = false){
        if($user = User::find($cUsername, true)){
            $c = new Client($params);
            if (User::createCookieHash($user->password, $c->fingerPrint()) === $cPassword) {

                self::setUserGlobals($user, "cookieCheck");

                return true;
            }
        }
        return false;
    }

    public static function checkUserPasswordHashJCM($cUsername, $cPassword){
        if( $user = User::find($cUsername) ){
            if ( md5($user->password) === $cPassword ) {
                self::setUserGlobals($user, "cookieCheck");
                return true;
            }
            return  md5($user->password);
        }
        return "Cannot find user.";
    }

    /**
     * Checks the password hash of a user
     * @param object $cUsername
     * @param object $cPassword
     * @param object $params [optional]
     * @return
     */
    public static function checkAdminPasswordHash($adminCookie, $params){
        $c = new Client($params);
        return User::checkAdminHashWithGivenFingerPrint($adminCookie, $c->fingerPrint());
    }

    /**
     * Checks the password hash of a user
     * @param object $cUsername
     * @param object $cPassword
     * @param object $params [optional]
     * @return
     */
    public static function checkSupportPasswordHash($supportCookie, $params){
        $c = new Client($params);
        return User::checkSupportHashWithGivenFingerPrint($supportCookie, $c->fingerPrint());
    }

    /**
     * Creates an on the fly guest session
     * @return
     */
    public static function createGuestSession(){

        if(Utils::getCookie("guest")){
            $guestName = Utils::getCookie("guest");
            // Guest user was already created in the DB.
            if ($user = User::find($guestName)) {
            	self::setUserGlobals($user, 'createGuest');
            	return;
            }
        }else{
            $guestName = "guest_". ID::generate();
        }

        $guest = new User([
            "username"  => $guestName,
            "name"      => "Guest User",
            "accountType"   => "GUEST",
            "ip"        =>   $_SERVER['REMOTE_ADDR'],
            "referer"   => (isset($_SERVER['HTTP_REFERER'])? $_SERVER['HTTP_REFERER'] : "unknown")
        ]);
        self::setUserGlobals($guest, 'createGuest');
        Utils::setCookie("guest", $guestName, "+1 Month");
    }

    /**
     * Saves the guest account on database
     * @return
     */
    public static function commitGuestAccount(){
		$_SESSION[COOKIE_KEY]->save();
    	// If the user is newly created, since it doesn't have an ID field,
    	// SESSION should be re-created.
    	if (empty($_SESSION[COOKIE_KEY]->id)) {
    		$_SESSION[COOKIE_KEY] = User::find($_SESSION[COOKIE_KEY]->username);
    		self::setUserGlobals(null, 'commitGuest');
    	}
    }

    /**
     * Set given email to guest session and save this guest account right away
     * @param object $email
     * @return
     */
    public static function setGuestEmail($email){
         $_SESSION[COOKIE_KEY]->email = $email;
         self::commitGuestAccount();
    }

    /**
     *
     * @param object $username
     * @return
     */
    public static function claimGuestAccount($username, $deep = true){

        if(Utils::getCookie("guest")){
            $guestName = Utils::getCookie("guest");
            $res = DB::write("UPDATE `forms` SET `username`=':username' WHERE `username`=':guest'", $username, $guestName);
            User::reallyDelete($guestName);
            Utils::deleteCookie("guest");
            # Update all integration information
            $res = DB::write("UPDATE `integrations` SET `username`=':username' WHERE `username`=':guest'", $username, $guestName);
        }

        if($deep){
            # Deep search
            $user = User::find($username);
            $res = DB::read("SELECT `username` FROM `users` WHERE `email` = ':email' AND `account_type`='GUEST'", $user->email);

            foreach($res->result as $line){
                $res = DB::write("UPDATE `forms` SET `username`=':username' WHERE `username`=':guest'", $username, $line["username"]);
                User::reallyDelete($line["username"]);
            }
        }

    }


    /**
     * Includes the login form on the page
     * @return
     */
    public static function putLoginForm() {
        if (self::isLoggedIn()) {
            Utils::put('accountInfo');
        } else {
            Utils::put('loginForm');
        }
    }

    /**
     * Checks if the user is logged-in or not
     * @return
     */
    public static function isLoggedIn() {
        if (isset($_SESSION[COOKIE_KEY])) {
            if($_SESSION[COOKIE_KEY]->accountType != "GUEST"){
                return $_SESSION[COOKIE_KEY];
            }
        }
        return false;
    }

    /**
     * Checks if a user is loggedin
     * @return
     */
    public static function isGuest() {
        if (isset($_SESSION[COOKIE_KEY])) {
            if($_SESSION[COOKIE_KEY]->accountType == "GUEST"){
                return $_SESSION[COOKIE_KEY];
            }
        }
        return false;
    }

    /**
     * Returns the array of user information without the private information included
     * such as password.
     * @return
     */
    public static function getPublicUserInformation(){

        $user = (array) $_SESSION[COOKIE_KEY];
        foreach($user as $key => $value){
            if($value instanceof Client){
                unset($user[$key]);
            }
        }

        unset($user["password"]);
        unset($user["ip"]);
        if(isset($user["folderConfig"])){
            $user["folderConfig"] = Utils::safeJsonDecode($user["folderConfig"]);
        }
        return $user;
    }

    /**
     * Opens a session
     * @param object $save_path
     * @param object $session_name
     * @return
     */
    public static function open($save_path, $session_name){
        self::$savePath = $save_path;
        return(true);
    }

    /**
     * Closes the session
     * @return
     */
    public static function close(){
        return(true);
    }

    /**
     * Reads a session
     * @param object $id
     * @return
     */
    public static function read($id){
        $sess_file = self::$savePath."/sess_$id";
        return @file_get_contents($sess_file);
    }

    /**
     * Writes a session
     * @param object $id
     * @param object $sess_data
     * @return
     */
    public static function write($id, $sess_data){
        $sess_file = self::$savePath."/sess_$id";
        if ($fp = @fopen($sess_file, "w")) {
            $return = fwrite($fp, $sess_data);
            fclose($fp);
            return $return;
        } else {
            return(false);
        }
    }

    /**
     * Destroys a session
     * @param object $id
     * @return
     */
    public static function destroy($id){
        $sess_file = self::$savePath."/sess_$id";
        return(@unlink($sess_file));
    }

    /**
     * Session garbage collector
     * @param object $maxlifetime
     */
    public static function gc($maxlifetime){
        foreach (glob(self::$savePath."/sess_*") as $filename) {
            if (filemtime($filename) + $maxlifetime < time()) {
                @unlink($filename);
            }
        }
        return true;
    }
    /**
     * Serializes session data
     * @param object $data
     * @return
     */
    public static function serializeSession($data) {
        $ser = serialize($data);
        $ser = preg_replace("/^a:\d+:\{.*?\;/", "user|", $ser);
        $ser = preg_replace("/\}$/", "", $ser);

        return $ser;
    }

    /**
     * Unserializes session data
     * @param object $data
     * @return
     */
    public static function unserializeSession($data) {
        $vars=preg_split('/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff^|]*)\|/', $data, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        for($i=0; @$vars[$i]; $i++){
            $result[$vars[$i++]] = unserialize($vars[$i]);
        }
        return $result;
    }
    /**
     * Gets stored session data as an array
     * @param object $id
     * @return
     */
    public static function getRemoteSession($id){
        return self::unserializeSession(self::read($id));
    }

    /**
     * Store the session array in session save path
     * @param object $id
     * @param object $data
     * @return
     */
    public static function setRemoteSession($id, $data){
        $data = self::serializeSession($data);
        return self::write($id, $data);
    }

    /**
     * Checks if the user is admin or not
     * @return
     */
    public static function isAdmin(){
        $adminCookie = Utils::getCookie("admin");
        if(!$adminCookie){ return false; }
        if (User::checkAdminHash($adminCookie) === false){
        	return false;
        }else{
        	return true;
        }
    }

    public static function getSysAdmUserame(){
        $supportCookie = Utils::getCookie("admin");
        if ($supportCookie === false){
            $supportCookie = Utils::getCookie("support");
            if ($supportCookie  === false){
                return "CANNOT GET USERNAME";
            }
        }
        @list($username, $passhash) = explode(":", base64_decode($supportCookie));
        return $username;
    }

    /**
     * Checks if the user is SUPPORT or not
     * @return
     */
    public static function isSupport(){
        $supportCookie = Utils::getCookie("support");
        if(!$supportCookie){ return false; }
        if (User::checkSupportHash($supportCookie) === false){
            return false;
        }else{
            return true;
        }
    }

    /**
     * Gets the User object from Session
     * @return User
     */
    public static function getUser(){
        return $_SESSION[COOKIE_KEY];
    }

    /**
     * Checks the authentication of the admin pages
     * @return
     */
    public static function checkAdminPages( $allowSupports = false ){

    	if ( Session::isAdmin() || ($allowSupports === true && Session::isSupport()) ) $allowed = true;
    	else $allowed = false;

    	if( !$allowed ){
    		/*
            Utils::deleteCookie("admin");
            Utils::deleteCookie("support");
            */
            Utils::errorPage("You should be loggedin as an admin", "Authentication Error.");
        }

    }
    /**
     * Display browser poll screen for old browsers.
     * @return
     */
    public static function handleIE6(){
        if($ver = Client::getIEVersion()){
            if($ver < 7){ # if this browser is IE and version is lower than 7

                //Console::warn($_SERVER['HTTP_USER_AGENT'], "Browser blocked");

                ob_start();
                include ROOT."opt/ie6.html";
                $content = ob_get_contents();
                ob_clean();

                Utils::errorPage($content, "Unsupported Browser", $_SERVER['HTTP_USER_AGENT'], 200);
            }
        }
    }

    /**
     * Retuns the image identifier for banners
     * @return
     */
    public static function getLastDays(){
        $currentDay = date('j');
        if($currentDay == 28){ return 3; }
        if($currentDay == 29){ return 2; }
        return 1;
    }

    /**
     * Read theme settings from database
     * @return
     */
    public static function getThemeFromDB(){
        $theme = false;
        if(!self::isGuest()){
            $theme = Settings::getValue("theme", self::$username);
        }
        if(!$theme){
            $theme = self::$defaultTheme;
        }
        $_SESSION[COOKIE_KEY]->theme = $theme;
        return $theme;
    }

    /**
     * Set a theme cookie for memberkit to use
     * @return
     */
    public static function setThemeForMemberkit(){
        Utils::setCookie("theme", self::$theme, "+1 Month");
    }

    /**
     * Returns the theme by users preference
     * @return
     */
    public static function getTheme(){
        if(!empty(self::$theme)){
            return self::$theme;
        }
        return self::$defaultTheme;
    }

    /**
     * Save the selected theme
     * @param object $theme
     * @return
     */
    public static function setTheme($theme){
        $_SESSION[COOKIE_KEY]->theme = $theme;
        self::$theme = $theme;
        self::setThemeForMemberkit();
        Settings::setSetting("theme", self::$username, $theme);
    }


    /**
     * Checks if the current session should see the banners or not
     * @return
     */
    public static function isBannerFree(){

    	# DISABLED ALL BANNERS
    	# ~Aytekin / 1.1.2011
    	return true;

        # applications always banner free
        if(APP){ return true; }

        /*if(!Utils::debugOption('showBanners')){
            return true; // Everyone is banner free because debug parameters says so
        }*/

        if(self::$accountType != 'FREE' && self::$accountType != 'GUEST'){
            return true; // User is banner free because we only show banners to guest and free users
        }

        if(self::$accountType == 'GUEST' && empty(self::$email)){
            return true; // User is baner free becuase user is not yet ready to see the banners.
        }

        // Too bad this user will be suffocated in banners
        return false;
    }
}
