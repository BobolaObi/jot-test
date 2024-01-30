<?php
/**
 * Collect all requests then calls the right action
 * @package JotForm
 * @copyright Copyright (c) 2009, Interlogy LLC
 */
class RequestServer 
{
    # Type of the responses it should be application/json but it's hard to debug and browser are having some problems with it
    private $responseContentType = /* "application/x-json"; # */ "text/javascript"; # for debugging
	
    # if this is populated it will be included in the success message
    private $warning = false, $toALL = false;
    
    # definitions of internals
    public $callback, $response, $request;

    # In order to call these methods, user needs to be admin
    private $adminOnlyActions = array("phpInfo", "codeEditor", "evalCode", 
        "resetCacheFolder", "loginToAccount", "deployServers",
        "getLatestVersionNumber", "getLatestVersionNumber", "migrateToVersion",  // Ruckusing stuff
        "migrateUser", "migrateAllUsers", "migrateAllSubmissions",               // jotform_main to jotform_new migration stuff
        "activateUser", "suspendUser", "unsuspendUser", "deleteUser", "setAccountType", "setMonthlyUsage",
        "crawlUsers", "syncWithAmazonS3", "uploadToS3","userOperations",
        "getUserDetails", "getStatus", "setStatus", "recalculateUploads", "deleteUserFromScheduledDowngrade",
        "listServers", "clearFormCacheBySearch"
    );
    
    # These methods are only allowed for sibling servers
    private $serverTalk = array("clearAllCache", "clearFormCache", "clearUserCache", "removeSubmissionUpload");
    
    # These methods are only allowed for friend servers
    # TODO: Complete the friendTalk checks.
    private $friendTalk = array("checkCookie", "checkAdminCookie", "checkSupportCookie", "getEmailsJCM", "getAccountTypesJCM", "getUserPaymentGateway", "upgradeUserToProfessional");
    
    # In order to call these methods, user needs to be logged in
    private $loginNeededActions = array();

    # These methods cannot be called from requests
    private $protectedActions = array("error", "success", "runAction", "errorHandler", "php5208BugFix", "checkServerTalk", "checkOtherServers", "get");
    
    # These action does not need session varaibles
    private $noSessionActions = array("getStates", "getCities", "clearFormCache", "clearCache", 
                                      "testEmail", "getCaptchaId", "getCaptchaImg", "correctCaptcha", 
                                      "getExtGridStructure","getExtGridSubmissions", "getFormSource", 
                                      "clearAllCache", "clearFormCache", "clearUserCache", "removeSubmissionUpload", "multipleUpload");
    
    # Simulate web latency
    private $lazy;
    
    # ByPass Security checks for local calls
    private $bypass = false;
     
    # All responses from other servers
    private $responses = false;
    
    # Don't run action on this server. only on others
    private $skipSelf = false;
    
    /**
     * Constructor
     * @constructor
     * @param object $request
     */
    function __construct($request, $bypass = false){
        
        Profile::start("Request"); # Keep request time
        # If no action was sent
        if(!isset($request['action'])){
            $this->error('No action was provided. Please check your request');
        }
        
        $this->bypass = $bypass;
        
        # if this is a JSONP request than use callback function
        $this->callback = isset($request["callback"])? $request["callback"] : false;
        $this->callback = isset($request["callbackName"])? $request["callbackName"] : $this->callback;
        
        # Set request
        $this->request = $request;
        
        # Define as seconds (ie: 0.5, 2, 0.02)
        $this->lazy = 0; 
        
        if(isset($this->request['lazy'])){
            $this->lazy = $this->request['lazy'];
        }
        
        $this->action = $request['action'];
//        set_error_handler($this->errorHandler(...));

        # run the action now
        $this->runAction();
        if(!$this->bypass){
            # Just incase no response was prompted
            $this->success("Operation Completed");
        }
    }
    
    /**
     * Return the response
     * @return 
     */
    public function getResponse(){
        return json_decode($this->response);
    }
    
    /**
     * Fixes the json_encode bug for php 5.2.08
     * @param object $array
     * @link http://bugs.php.net/bug.php?id=40503
     * @return
     */
    private function php5208BugFix($array){
        if( ! Server::isPHP("5.2.11")){
            return Utils::arrayNumbersToString($array);
        }
        return $array;
    }
    
    /**
     * Safely brings data from request. No need to check isset
     * It also converts true false values to boolean
     * @param object $key
     * @return 
     */
    private function get($key){
        if(!isset($this->request[$key])){
            return NULL;
        }
        
        $val = $this->request[$key];
        
        if(strtolower($val) == "true"){
            $val = true;
        }
        
        if(strtolower($val) == "false"){
            $val = false;
        }
        
        return $val;
    }
    
    /**
     * Catches any error and responses with success:false
     * @param object $errno
     * @param object $message
     * @param object $filename
     * @param object $line
     */
    public function errorHandler($errno, $message, $filename, $line) {
        if (error_reporting() == 0) {
            return;
        }
        if ($errno & (E_ALL ^ E_NOTICE)) {
            $types = array(1 => 'error', 2 => 'warning', 4 => 'parse error', 8 => 'notice', 16 => 'core error', 32 => 'core warning', 64 => 'compile error', 128 => 'compile warning', 256 => 'user error', 512 => 'user warning', 1024 => 'user notice', 2048 => 'strict warning');
            $entry ="<div style='text-align:left;'><span><b>".@$types[$errno] ."</b></span>: $message <br><br>
            <span> <b>in</b> </span>: $filename <br>
            <span> <b>on line</b> </span>: $line </div>";

            Console::error("Error:".$message."\nFile:".$filename."\nOn Line: ".$line, "Request Server Error");
            $this->error($entry, null, 500);
        }
    }
    /**
     * Checks if the request is coming from a sibling or not
     * @return 
     */
    public function checkServerTalk(){
        # If this server is LocalHost then no need to wory about security
        if(Server::isLocalhost()){ Console::error('This was a localhost'); return true; }
        
        # Check if the request IP is a sibling IP
        if(Server::isSibling($_SERVER['REMOTE_ADDR'])){
            # Console::log('A Request ('.$this->get('action').') from:'.Server::isSibling($_SERVER['REMOTE_ADDR']));
            return true; # yes a sibling
        }else{
            Console::error($_SERVER['REMOTE_ADDR']." is not a sibling");
            return false; # no we don't know this person
        }
    }
    
    /**
     * Checks and runs the currents request on ther servers too
     * @return 
     */
    private function checkOtherServers(){
        # if toAll parameter send then run this action in all other servers
        if(isset($this->request['toAll'])){
            $siblings = Server::getSiblings(); # get the other servrs
            $request = $this->request;         # clone request
            $this->toALL = true;
            unset($request['toAll']);          # remove toAll parameter to prevent infinite loop
            
            if ( isset($request['skipSelf']) && $request['skipSelf'] === "yes" ){
                $this->skipSelf = true;
                unset($request['skipSelf']);
            }
            
            $this->responses = array();        # Collect all responses coming from other servers
            
            foreach($siblings as $sibling){    # loop through servers
                if($this->get('async') != "no"){
                    # post the same request over the other servers silently
                    Utils::suppressRequest("http://".$sibling['ip']."/server.php", $request);
                }else{
                    # Wait untill the process completed
                    $this->responses[$sibling['name']] = json_decode(
                        Utils::postRequest("http://".$sibling['ip']."/server.php", $request), true);
                }
            }
            # When finished continue the normal action the current server
        }
    }
    
    /**
     * Runs the given method
     * @param object $action This optional you cal call a different action with the same parameters
     */
    public function runAction($action = false){
        # Support for manual actions
        if(!$action){
            $action = $this->action;
        }

        # Check if the action exists on the server
        if(!method_exists($this, $action)){
            $this->error('No such action ('.$action.'). Please check your request');
        }
        
        # Check if this action protected or not
        if(Utils::in_arrayi($action, $this->protectedActions)){
            $this->error($action." is a protected action and it cannot be called externally.", null, 403);
        }
        
        if(!$this->bypass){
            # Check if this action is available only between servers
            if(Utils::in_arrayi($action, $this->serverTalk) && !$this->checkServerTalk()){
                $this->error("Authentication problem: Server only access", null, 401);
            } 
            
            # Check if this action is available only admins
            if(Utils::in_arrayi($action, $this->adminOnlyActions) && !Session::isAdmin() && !Session::isSupport()  ){
                $this->error("Authentication problem: Admin only access", null, 401);
            }
            if (Utils::in_arrayi($action, $this->adminOnlyActions) && Session::isSupport()){
                Console::logAdminOperation(Session::getSysAdmUserame() . "\n" . print_r($this->request, true));
            }
    
            # Check if this action is available to only loggedin users
            if(Utils::in_arrayi($action, $this->loginNeededActions) && !Session::isLoggedIn()){
                $this->error("Authentication problem: You need to be logged-in to do this action", null, 401);
            }            
        }
        
        # Check if this action needs a session
        if(!Utils::in_arrayi($action, $this->noSessionActions)){
            Session::rememberLogin(false);
        }

        try{ # try this action if it throws an error prompt it to user
            
            if($this->lazy && (JOTFORM_ENV !== 'PRODUCTION')){
                usleep($this->lazy * 1000000); # Speed Test for some properties
            }
            
            # Check if this request is meant to work on other servers too
            $this->checkOtherServers();
            
            if ($this->skipSelf !== true){
                # Run the provided action
                return $this->$action();
            }else{
                return $this->success("Skipped Current");
            }
        }catch (Warning $e){
        	$this->warning = $e->getMessage();
        	$this->success("Operation Completed");
        }catch(SoftException $e){
            $err = $e->getMessage();
            if(is_array($err)){
                return $this->error(array("message"=>$err[0], "errorNo"=>$err[1]), null, 200);
            }
            return $this->error($err, null, 200);
        }catch(Exception $e){ # Catch if any exception was thrown
            $err = $e->getMessage();
            if(is_array($err)){
                return $this->error(array("message"=>$err[0], "errorNo"=>$err[1]), null, 500);
            }
            return $this->error($err, null, 500);
        }
    }

    /**
     * Prompts a standard error response, all errors must prompt by this function
     * adds success:false automatically
     * @param object|string $message An error message, you can directly pass all parameters here
     * @param object $addHash[optional] contains the all error parameters will be sent as a response
     */
    private function error($message, $addHash = array(), $status = 400){
        
        Console::info($message, "Request Server Error");
        if(is_array($message)){
            $addHash = $message;
        }else{
            $addHash["error"] = $message;
        }
        
        if($this->responses){
            $addHash['other_responses'] = $this->responses;
        }
        
        $addHash["success"] = false;
        $addHash["duration"] = Profile::end("Request");
        #$addHash['server'] = Server::whoAmI();
        #$addHash['db_host'] = DB_HOST;
        
        if(!$this->bypass){
            @header("Content-Type: ".$this->responseContentType."; charset=utf-8", true, $status);
        }
        
        if($this->callback){
            $response = $this->callback."(".json_encode($this->php5208BugFix($addHash)).");";
        }else{
            $response = json_encode($this->php5208BugFix($addHash));
        }
        
        if (!$this->bypass) {
            echo $response;
            exit;
        }
        
        $this->response = $response;
        return $response;
    }

    /**
     * Prompts the request response by given hash
     * adds standard success:true message automatically
     * @param object|string $message Success message you can also pass the all parameters as an array here
     * @param object $addHash [optional] all other parameters to be sent to user as a response
     */
    private function success($message, $addHash = array(), $status = 200){
        if(is_array($message)){
            $addHash = $message;
        }else{
            $addHash["message"] = $message;
        }
        
        if($this->responses){
            $addHash['other_responses'] = $this->responses;
        }
        
        $addHash["success"] = true;
        $addHash["duration"] = Profile::end("Request");
        
        if($this->warning !== false){
        	$addHash["warning"] = $this->warning;
        }
        
        #$addHash['server'] = Server::whoAmI();
        #$addHash['db_host'] = DB_HOST;
        
        if(!$this->bypass){
            @header("Content-Type: ".$this->responseContentType."; charset=utf-8", true, $status);
        }
        
        
        if($this->callback){
            $response = $this->callback."(".json_encode($this->php5208BugFix($addHash)).");";
        }else{
            $response = json_encode($this->php5208BugFix($addHash));
        }
        if (!$this->bypass) {
            echo $response;
            exit;
        }
        $this->response = $response;
        return $response;
    }

    /**
     * Prints php info
     * @return
     */
    private function phpInfo(){
        phpinfo();
        exit; // prevent request server to send headers
    }

    /**
     * Prints a code editor on the page
     * admin only
     * @return
     */
    private function codeEditor(){
        include ROOT."/opt/codepress/editor.php";
        exit;
    }
    
    /**
     * Evaluates the given code on the server
     * Admin only function, I think this can be useful
     * admin only
     * @return
     */
    private function evalCode(){
    	error_reporting(E_ALL);
        echo eval("?>".$this->request["code"]."<?");
        exit;
    }

    ########################################################################
    # All actions will be written below this point
    #
    # REMEMBER: do not return or echo anything in the actions, only use
    #
    # $this->success or $this->error methods to prompt something otherwise
    # it will just brake the JSON response
    #
    # ALSO: You don't need to catch thrown exceptions, this class will
    # automatically catch the exceptions and prompt them to user by
    # using $this->error method, USE Try-Catch only if you need them
    #
    # VERY-IMPORTANT: Do not use $_GET, $_POST or $_REQUEST in your actions
    # get request values from $this->request
    ########################################################################
    
    /**
     * Forces the current session to log-in given account
     * @return 
     */
    private function loginToAccount(){
        $username = $this->request['username'];
        $user = User::find($username);
        if ( Session::isAdmin() || $user->accountType !== "ADMIN"){
            User::forceLogin($username);
        }
    }

    /**
     * Get the states
     */
    private function getStates(){
        $states = CountryDropdown::getStates($this->request['countryId']);
        $this->success(array(
            "states" => $states
        ));
    }

    /**
     * Get Cities
     */
    private function getCities(){
        $cities = CountryDropdown::getCities($this->request['stateId']);
        $this->success(array(
            "cities"=>$cities
        ));
    }

    /**
     * Create a new form
     */
    private function newForm(){
        $form = new FormFactory($this->request['properties']);
        $this->success(array(
            "id" => $form->save()
        ));
    }

    /**
     * Save form
     * @return float Id of the form
     */
    private function saveForm(){

        $form = new FormFactory($this->request['properties'], $this->request['source']);
        $res = $form->save();

        if($res === false){
            $this->error($res);
        }else{
            $this->success("Form Saved", array(
                "id"=>$res
            ));
        }
    }

    /**
     * Clones given form
     * @return
     */
    private function cloneForm() {
        $form = new Form($this->request["formID"]);
        $newID = $form->cloneForm();
        $this->success(array("newID" => $newID));
    }
    
    /**
     * Exports a given form to PDF.
     * @todo Must move contents to a function
     * @return
     */
    private function exportPDF() {
        $id = $this->request["formID"];
        $cacheFilePath = CACHEPATH . $id . "_facebook.html";
        
        $dataCache = CACHEPATH."".$id.'.js';
        if(!file_exists($dataCache)){
            $form = new Form($id);
            $prop = $form->getSavedProperties(false);
            touch($dataCache, 0777);
            chmod($dataCache, 0777);
            file_put_contents($dataCache, 'getSavedForm({"form":'.json_encode($prop).', "success":true})');
        }
        
        // First create source code that is compatible with wkhtmltopdf program
        chdir("opt/v8/");
        system('d8 v8_build_source_facebook.js -- ' . $id . ' v8_config ' . $debugMode . ' > ' . $cacheFilePath);
        $wkpdf = new WKPDF();
        $wkpdf->set_tmp_path($cacheFilePath);
        $wkpdf->render();
        $wkpdf->output(WKPDF::$PDF_DOWNLOAD, 'form.pdf');
        exit;
    }

    private function getSubmissionPDF(){
        $wkpdf = new WKPDF();
        $url   = HTTP_URL."pdfview/".$this->get('sid');
        $wkpdf->set_url($url);
        $wkpdf->render();
        $wkpdf->output(WKPDF::$PDF_DOWNLOAD, 'form.pdf');
        exit;
        
    }

    /**
     * Permanently deletes a form
     * @return
     */
    private function deleteForm(){
        $form = new Form($this->request["formID"]);
        $form->deleteForm();
    }

    /**
     * Empty trash can for current user
     * @return
     */
    private function emptyTrash(){
        Form::emptyTrash();
    }
    
    /**
     * Removes the user from scheduled downgrade.
     */
    private function deleteUserFromScheduledDowngrade(){
        $username = $this->request["username"];
        if ($username){
        	$res = DB::write("DELETE FROM `scheduled_downgrades` WHERE `username` = ':s'", $username);
            if ($res->success){
                $this->success(print_r($res, true));
            }
        }
        $this->error("Cannot delete user from scheduled_downgrades table.");
    }
    
    /**
     * Clears the all cache folder and if provided does this for all other servers
     * Call this action with toAll=yes parameter if you want this to work on all servers
     * @return 
     */
    private function clearAllCache(){
        system('rm -f '.CACHEPATH.'*.html');
        system('rm -f '.CACHEPATH.'*.shtml');
        system('rm -f '.CACHEPATH.'*.js');
    }
    
    /**
     * Removes the submission folder on all servers
     * @return 
     */
    private function removeSubmissionUpload(){
        $dir = UPLOAD_FOLDER . $this->request['username'] . "/" . $this->request['formID'] . "/" . $this->request['submissionID'];
        Utils::recursiveRmdir($dir);        
    }
    
    /**
     * Clears form caches by given search parameters
     * @return 
     */
    private function clearFormCacheBySearch(){
        $search = ($this->get("search"));
        
        # Shell command explanation:
        # First Part: Grep all .js files under cache which contains given keyword
        # Second part grep output only gives js files so we need .html, .shtml, .nogz.html we give grep output to AWK and simply replace .js with other extensions
        # Third part We have a full list of files to be deleted now give them to xargs and delete all with rm -f. 
        $cmd = 'grep -l "'.$search.'" '.CACHEPATH.'*.js | awk \'BEGIN{FS="."}{print $1".js "$1".html "$1".shtml "$1".nogz.html"}\' | xargs rm -f';
        
        $o = shell_exec($cmd);
        
        Console::log("Command executed: \n $cmd \nOutput: $o");
        
        return $o;
    }
    
    /**
     * Clears the cache file for given form and if provided does this for all other servers
     * Call this action with toAll=yes parameter if you want this to work on all servers
     * @return 
     */
    private function clearFormCache(){

        $id = (float) $this->request['formID'];
        
        system('rm -f '.CACHEPATH.$id.'.html');
        system('rm -f '.CACHEPATH.$id.'.shtml');
        system('rm -f '.CACHEPATH.$id.'-js.html');     
        system('rm -f '.CACHEPATH.$id.'-js.shtml');
        system('rm -f '.CACHEPATH.$id.'.nogz.html');
        system('rm -f '.CACHEPATH.$id.'.js');
        
    }
    
    private function clearMaxCDNFormCache(){
        $path = $this->get('path');
        Form::clearMaxCDNCache($path);
        #Console::log('clear cache complete for: '.$path);
    }
    
    
    private function clearALLMaxCDNFormCache(){
        Form::purgeAllMaxCDNCache();
        #Console::log('clear cache complete for: '.$path);
    }
    
    
    /**
     * Clears the cache file for given form and if provided does this for all other servers
     * Call this action with toAll=yes parameter if you want this to work on all servers
     * @return 
     */
    private function clearUserCache(){

        $username = $this->request['username'];
        $res = DB::read("SELECT `id` FROM `forms` WHERE `username`=':username'", $username);
        
        foreach($res->result as $form){
            $id = (float) $form['id'];
            
            system('rm -f '.CACHEPATH.$id.'.html');
            system('rm -f '.CACHEPATH.$id.'.shtml');
            system('rm -f '.CACHEPATH.$id.'-js.html');     
            system('rm -f '.CACHEPATH.$id.'-js.shtml');
            system('rm -f '.CACHEPATH.$id.'.nogz.html');
            system('rm -f '.CACHEPATH.$id.'.js');         
        }
    }
    
    /**
     * Greps the username in sessions, then removes all matched files
     * @todo don't use ` back tick operator for system calls. Fix this to system_exec or something
     * @return 
     */
    private function clearUserSession() {
        $username = escapeshellarg($this->request['username']);
        
        $o = `grep $username /tmp/sess*`;
        if( preg_match("/Binary file (\/tmp\/sess_.*) matches/", $o, $m) ){
               `rm $m[1]`;
        }
    }
    
    /**
     * Resets the caches
     * @return 
     */
    private function clearCache(){
        // type can be: folder, database, all, 1225547(formID)
        $formID = isset($this->request['formID'])? $this->request['formID'] : false;
        Form::clearCache($this->request['type'], $formID);
    }
    
    /**
     * Marks a form as deleted
     * @see Form::markDeleted();
     * @return
     */
    private function markDeleted(){
        $form = new Form($this->request["formID"]);
        $form->markDeleted();
    }
    
    /**
     * Undeletes a form
     * @see Form::undelete()
     * @return
     */
    private function unDelete(){
        $form = new Form($this->request["formID"]);
        $form->unDelete();
    }

    /**
     * Get saved form
     */
    private function getSavedForm($public = false){
        
        $form = new Form($this->get('formID'));
        $checkAuth = true;
        if($this->get('checkPublicity')){
            if(isset($_SESSION['public']) && $_SESSION['public'] == $form->id){
                $checkAuth = false;
            }
        }
        
        if($public){
            $checkAuth = false;
        }
        
        $response = $form->getSavedProperties($checkAuth);

        if(isset($response["success"]) && $response["success"] === false){
            Utils::deleteCurrentID("form");
            $this->error($response);
        }
        
        $this->success(array(
            "form" => $response,
            "submissions" => array(
                "total" => $form->getSubmissionCount(),
                "new"   => $form->getNewSubmissionCount()
            )
        ));
    }
    
    /**
     * Returns the form properties to public
     * @return 
     */
    private function getFormProperties(){
        $this->getSavedForm(true);
    }
    
    /**
     * Parses and saves the imported form code
     * @return
     */
    private function getImportedForm(){
        $html = new ParseHTML($this->request["url"]);
        $response = $html->extractForm();
        if($response === true){ // form found on DB
            $this->success("Form Cloned");
        }else{
            $form = new FormFactory(json_encode($response), false, true);
            $this->success(array(
                "id" => $form->save()
            ));            
        }
    }

    /**
     * Test the current email
     */
    private function testEmail(){
        Utils::sendEmail(array(
            "from"    => $this->request['from'],
            "to"      => $this->request['to'],
            "subject" => "(TEST) " . $this->request['subject'],
            "body"    => $this->request['body'],
            "html"    => $this->request['html'] == 'true' 
        ));
        $this->success("E-mail Sent");
    }

    /**
     * Get a random id for captcha image
     * @return
     */
    private function getCaptchaID(){
        $num = Captcha::encode(Captcha::getRandom());
        $this->success(array(
            "num" => $num
        ));
    }
    
    /**
     * Serves a captcha image with given id
     * @return 
     */
    private function getCaptchaImg(){
        Captcha::serveImg($this->request['code']);
    }
    
    /**
     * Gives you a chance to correct mistaken captcha
     * @return 
     */
    private function correctCaptcha(){
        try{
            # Get the code, check then continue the submission or show the page again
            if(!Captcha::checkWord($this->request['captcha'], $this->request['captcha_id'])){
                Captcha::printCaptchaPage($this->request['sid']);
            }else{
                if(!Submission::continueSubmission($this->request['sid'], 'captcha')){
                    Utils::errorPage("This submission was already completed or expired.", "Error");
                }
            }
        }catch(Exception $e){
            Utils::error($e);
            exit;
        }
    }
    
    /**
     * Registers a new user
     * @return
     */
    private function registerNewUser() {
        # No server-side validation is necessary. Username is unique so
        # you cannot create more than one empty username. E-mail could be
        # checked on the client along with others.
        $message = User::registerNewUser(array('username' => $this->request['username'],
                            'password' => $this->request['password'],
                            'email' => $this->request['email'],
                            'ip' => $_SERVER['REMOTE_ADDR']
        ));
        $this->success($message);
    }

    /**
     * Updates user's account info.
     * It is SECURE because it only updates user account who is logged in.
     * @TODO Must move contents to a function, clearly to a updateUser method
     */
    private function updateUserAccount() {
        
        $user = new User(array('username' => Session::$username, 'id' => Session::$id));
        
        if (isset($this->request['email'])) {
            $_SESSION[COOKIE_KEY]->email = $user->email = $this->request['email'];
        }
        if (isset($this->request['name'])) {
            $_SESSION[COOKIE_KEY]->name = $user->name = $this->request['name'];
        }
        if (isset($this->request['website'])) {
            $_SESSION[COOKIE_KEY]->website = $user->website = $this->request['website'];
        }
        if (isset($this->request['timeZone'])) {
            $_SESSION[COOKIE_KEY]->timeZone = $user->timeZone = $this->request['timeZone'];
        }
        if (isset($this->request['password'])) {
            $_SESSION[COOKIE_KEY]->password = $user->password = User::encodePassword($this->request['password']);
        }
        
        $user->save();
    }
    
    /**
     * Check if the slug is uniq for the user or not.
     */
    private function checkSlugAvailable(){
        $form = new Form($this->request["id"]);
        $res = Form::checkSlugAvailable($form->form['username'], $this->request["slugName"]);
        if ($res){
            $this->success($res);
        }else{
            $this->error('Slug not available');
        }
    }
    /**
     * Save the slug
     * @return unknown_type
     */
    private function saveSlug(){
        if (Form::saveSlug($this->request["id"], $this->request["slugName"])){
            $this->success("Slug saved.");
        }else{
            $this->error("Cannot save slug.");
        }
    }
    /**
     * Checks if the username available or not
     * @return
     */
    private function checkUsernameAvailable() {
        
        $username = $this->get('username');
        
        if(empty($username) || preg_match("/[^A-Za-z0-9-_]/", $username)){
            $this->error("Username not acceptable");
        }
        
        if(User::find($username)){
            $this->error('Username not available');
        }else{
            $this->success('Username available');
        }
    }
    /**
     * Checks if this email available or not
     * @return 
     */
    private function checkEmailAvailable() {
        if(User::findByEmail($this->request['email'])){
            $this->error('Email not available;');
        }else{
            $this->success('Email available');
        }
    }
    
    /** 
     * Gets the list of the forms for myforms page
     * This is a login needed action
     */
    private function getFormList(){
        $list = User::getFormList(Session::$username);
        $this->success(array("forms" => $list));
    }
    
    /**
     * Get the reports by formID
     * @return 
     */
    private function getReports(){
        $reports  = Report::getAllByFormID($this->get('formID'), true);
        $listings = DataListings::getAllByFormID($this->get('formID'));
        $list = array_merge($reports, $listings);
        $this->success(array('reports' => $list));
    }
    
    /**
     * Brings the update information for myforms page
     * @TODO Must move contents to a function
     * @return 
     */
    private function updateMyForms(){
        
        if($this->get('username') != Session::$username){
            $this->error("User not authenticated");
        }
        $username = $this->get('username'); 
        $response = DB::read("SELECT * FROM `forms` WHERE `username`=':username' ORDER BY `title` ASC", $username);
        $forms = array();
        foreach($response->result as $line){
            if($line['count'] < 0){
                $line['count'] = Form::updateSubmissionCount($line["id"]);
            }
            if($line['new'] < 0){
                $line['new'] = Form::updateNewSubmissionCount($line["id"]);
            }
            
            $forms[$line['id']] = $line['new'].":".$line['count'];
        }
        
        $usage = (array) MonthlyUsage::find();
        unset($usage['user']);
        
        $this->success(array("forms" => $forms, "usage"=>$usage));
    }
    
    /**
     * Gets the ExtJS grid structure
     * @see Form::getExtGridStructure()
     * @return
     */
    private function getExtGridStructure(){
        $form = new Form($this->request['formID']);
        $form->useListingFilter($this->request["listID"]);
        $struct = $form->getExtGridStructure($this->request["type"]);

        $this->success($struct);

    }
    /**
     * Returns the index of the submission that should be selected on the submissions page.
     * This way, we will know which page to select in the grid.
     * 
     * BEWARE: Form::getSubmissions does not work for sorting columns other than created_at.
     * You cannot sort for question_id 6 for example, although the grid interface shows the
     * otherwise.
     * @TODO TERRIBLE TERRIBLE Must move contents to a function
     * @return unknown_type
     */
    private function getSubmissionIndex() {
        // Get total number of submissions for this form.
        /*
        // totalSubmissions number is not needed.
        $res =  DB::read('SELECT COUNT(`id`) FROM `submissions` WHERE `form_id` = ":formID"', $this->request['formID']);       
        $response['totalSubmissions'] = end($res->first);
         */
        // Find this submission's index number.
        // TODO: When sorting works for other fields as well, remove 1 || from the if conditional.
        if (1 || $this->request['sortField'] == "created_at") {
            $res = DB::read('select count(`id`) from `submissions` where `created_at` > ' . 
                                ' (select `created_at` from `submissions` where `id` = ":submissionID") ' . 
                                'and `form_id` = ":formID" ORDER BY `created_at` :dir', 
                            $this->request['submissionID'], $this->request['formID'], $this->request['sortDir']);
        } else {
            $compOp = ($this->request['sortDir'] == 'ASC')? '<' : '>';
            // Sort field is from answers.
            $res = DB::read('SELECT count(*) FROM `answers` WHERE `form_id` = ":formID" AND `question_id` = ":sort_field"' . 
                            ' AND `value` ' . $compOp . 
                            '(SELECT `value` FROM `answers` WHERE `submission_id` = ":submissionID" AND `question_id`=#qID)', 
                            $this->request['formID'], $this->request['sortField'], $this->request['submissionID'], $this->request['sortField']);            
        }
        $response['subIndex'] = end($res->first);
        $this->success($response);
    }
    
    /**
     * Gets ExtJS grid Submissions
     * @see Form::getExtGridSubmissions()
     * @return
     */
    private function getExtGridSubmissions(){
        $form = new Form($this->get('formID'));
        $form->useListingFilter($this->get("listID"));
        $response = $form->getExtGridSubmissions($this->get('sort'), $this->get('start'), $this->get('limit'), $this->get('dir'), $this->get('keyword'), $this->get('startDate'), $this->get('endDate'));
        $this->success($response);
    }
    
    /**
     * Mark submission as flagged
     * @return
     */
    private function setSubmissionFlag(){
        Form::setSubmissionFlag($this->request["sid"], $this->request["value"]);
    }
    
    /**
     * Mar submission as read or unread
     * @return
     */
    private function setReadStatus(){
        Form::setSubmissionReadStatus($this->request["formID"], $this->request["sid"], $this->request["value"]);
    }
    
    /**
     * Delete a submission
     * @return
     */
    private function deleteSubmission(){
        Form::deleteSubmission($this->request["sid"], $this->request["formID"]);
    }

    /**
     * Gets form submissions
     * @see Form::getSubmissions()
     * @return
     */
    private function getFormSubmissions(){

        $form = new Form($this->request['formID']);
        $submissions = $form->getSubmissions($this->request['sort'], $this->request['start'], $this->request['limit'], $this->request['dir'], $this->request["keyword"]);
        $questions = $submissions["questions"];
        unset($submissions["questions"]);
        $this->success(array(
            "questions" => $questions, 
            "submissions" =>$submissions
        ));
    }
    
    /**
     *
     * @return
     */
    private function getSubmissionResults(){
        $result = Form::getSubmissionResult($this->request["formID"]);
        $this->success(array("result" => $result));
    }
    
    private function getSavedUploadResults(){
        $this->getSavedSubmissionResults('control_fileupload');
    }
    
    /**
     * Returns the saved submissions result for population
     * @return 
     */
    private function getSavedSubmissionResults($filterType=false){
        $res = DB::read("SELECT * FROM `pending_submissions` WHERE `form_id`=#formID AND `type`=':type' AND `session_id`=':sessid'", $this->get('formID'), 'SAVED', $this->get('sessionID'));
        
        $result = array();
        if($res->rows > 0){
            $line = $res->first;
            $obj = Utils::unserialize($line['serialized_data']);
            
            
            if($obj === false){ throw Exception('Cannot unserialize submission'); }
            foreach($obj->formQuestions as $question){
                $qid = $question['qid'];
                if(!isset($obj->questions[$qid])){ continue; }    
                $value = $obj->questions[$qid];
                # Filter the question types and returns only one type of question results
                if($filterType !== false && $obj->formQuestions[$qid]['type'] != $filterType){ continue; }
                
                if(is_array($value)){
                    $fvalue = $obj->fixValue($value, $qid);
                }else{
                    $fvalue = $value;
                }
                if($obj->formQuestions[$qid]['type'] == 'control_fileupload'){
                    $fvalue = Utils::getUploadURL(Session::$username, $this->get('formID'), $obj->sid, $fvalue);
                }
                $result[$question['qid']] = array(
                    "text"   => $question["text"],
                    "value"  => $fvalue,
                    "items"  => $value,
                    "type"   => $obj->formQuestions[$qid]['type']
                );
            }
        }
        $this->success(array("result" => $result, "currentPage" => $obj->currentPage, "submissionID"=>$obj->sid));
    }
    
    /**
     * Returns the data to be used in reports
     * @return
     */
    private function getReportsData(){
        $form = new Form($this->request["formID"]);
        $result = $form->getReportsData();
        $this->success(array("data"=>$result));
    }

    /**
     * Logs user in.
     */
    private function login(){
        User::login($this->request['username'],
                    $this->request['password'],
                    ($this->request['remember'] === "true" || $this->request['remember'] === true),
                    $this->request['forceDeleted']);
        if($this->request["includeUsage"]){
            $usage = (array) MonthlyUsage::find();
        }else{
            $usage = array();
        }
        include_once ROOT."lib/includes/accountInfo.php";
        $accountHTML = ob_get_contents();
        ob_clean();
         
        $this->success("Login Successfull.", array("user" => Session::getPublicUserInformation(), "usage"=>$usage, "accountBox"=>$accountHTML));
    }

    /**
     * Send password reminder to the e-mail address. Find all accounts
     * associated with that e-mail address.
     */
    private function sendPasswordReset() {
        $message = User::sendPasswordReset($this->request['resetData']);
        
        if($this->get('showMessage')){
            Utils::successPage($message, "Successful!");
        }else{
            $this->success($message);
        }        
    }

    /**
     * Test the authorize.Net integration
     * @see AuthorizeDotNet::testIntegration()
     */
    private function testAuthnetIntegration(){

        $response = AuthorizeDotNet::testIntegration($this->request['loginId'], $this->request['transactionKey'], $this->request['paymentType']);

        if($response !== true){
            $this->error($response);
        }else{
            $this->success("Integration successfully tested.");
        }
    }

    /**
     * Tests PayPalPro integration
     * @see PayPalPro::testIntegration()
     */
    private function testPayPalProIntegration(){

        $response = PayPalPro::testIntegration($this->request['apiUsername'], $this->request['apiPassword'], $this->request['signature']);

        if($response !== true){
            $this->error($response);
        }else{
            $this->success("Integration successfully tested.");
        }
    }
    
    /**
     * get database schema and write it to the scheama file.
     */
    private function commitDatabaseSchema(){
        DBMigrate::commitDatabaseSchema();
        $this->success("Database commited to schema file successfully.");
    }
    
    /**
     * creates css sprites
     * @return unknown_type
     */
    private function createCssSprite(){
        CssSprite::convertToCssSprite();
        $this->success("CSS Sprite created successfully.");
    }

    /**
     * @see DBMigrate::updateDatabaseFromSchema()
     */
    private function getDatabaseChanges(){
        DBMigrate::getDatabaseChanges();
        $this->success(array("changes" => DBMigrate::$syncQueries));
    }

    /**
     * Syncs the database with the changes on SVN
     */
    private function syncDatabase(){
        DBMigrate::syncDatabase();

        $this->success("Database synced successfully");
    }

    /**
     * save guest account with given email
     * @see Session::setGuestEmail
     * @return
     */
    private function setGuestEmail(){
    	Session::setGuestEmail($this->request["email"]);
    	// User::findByEmail does not find guest accounts.
        $this->success(array("hasAccount" => !!User::findByEmail($this->request["email"])));

    }
    
    /**
     * commits guest account to db
     * @see Session::setGuestEmail
     * @return
     */
    private function commitGuestAccount(){
    	Session::commitGuestAccount();
    }
    
    /**
     * Gets the loggedin user information
     */
    private function getLoggedInUser(){
        if($this->request["includeUsage"]){
            $usage = (array) MonthlyUsage::find();
            unset($usage['user']);
        }else{
            $usage = array();
        }
        $this->success(array("user" => Session::getPublicUserInformation(), "usage"=>$usage));
    }

    /**
     * Saves the MyForms page folder and sort configuration into database
     * @return
     */
    private function saveFolderConfig(){
        $user = Session::getUser();
        $user->saveFolderConfig($this->request['config']);
        $this->success("Configuration Saved");
    }

    /**
     * Refreshes the form ID
     * @return
     */
    private function renewFormID(){
        $form = new Form($this->request["formID"]);
        $form->renewFormID();
        $this->success("ID Renewed");
    }

    /**
     * @see Settings::setSetting
     * @return
     */
    private function setSetting(){
        Settings::setSetting($this->request['identifier'], $this->request['key'], $this->request['value']);
    }

    /**
     * @see Settings::getSetting
     * @return
     */
    private function getSetting(){
        
        $value = Settings::getSetting($this->request['identifier'], $this->request['key']);
        if($value){
            $this->success(array("value"=> Utils::safeJsonDecode($value["value"])));
        }else{
            $this->error("Setting not found");
        }
    }

    /**
     * Sends any email
     * @return
     */
    private function sendEmail() {
        Utils::sendEmail(array(
            'to' => $this->request['to'], 
            'from' => array($this->request['from'], ""),
            'subject' => $this->request['subject'],
            'body' => $this->request['body']
        ));
    }

    /**
     * Saves reports on database
     * @return
     */
    private function saveReport(){

        if($this->request["reportID"] && $this->get("reportID") != 'session'){
            $report = new Report($this->get("reportID"));
            $id = $report->save($this->get("title"), $this->get("configuration"), $this->get('password'));
        }else{
            $report = new Report($this->get("formID"), true);
            $id = $report->save($this->get("title"), $this->get("configuration"), $this->get('password'));
        }

        $this->success(array('id'=> $id));
    }
    /**
     * Gets the saved Report
     * @return
     */
    private function getSavedReport(){
        $report = new Report($this->request["reportID"]);

        $this->success(array(
            "title"       => $report->title,
            "id"          => Utils::getCurrentID('report'),
            "hasPassword" => $report->hasPassword, 
            "config"      => $report->config
        ));
    }
    
    /**
     * This function checks if the cookie is set correctly
     * @return unknown_type
     */
    private function checkCookie(){
        $params = false;
        
        # Set params to request array if remote addr is sent
        # By this we are passing browser information from memberkit server to jotform server
        if ( isset($this->request['REMOTE_ADDR']) ){ # Send from memberkit server
            $params = $this->request;
        }else{
        	if (Session::$username === false){
        		$this->error("Cannot create guest account.");
        	}else{
                $this->success(Session::getPublicUserInformation());
        	}
        }
        
        if ( isset($this->request['newcheck']) ){
            $passed = Session::checkUserPasswordHashJCM( $this->request['username'], $this->request['passwordHash']);
        }else{
            $passed = Session::checkUserPasswordHash( $this->request['username'], $this->request['passwordHash'], $params );
        }
        
        
        # Return the result
        if ( $passed === true){
            $this->success("User is logged in.");
        }else{
            $this->error( "User cookie is wrong.", array( "hash"=>print_r($passed, true) ));
        }
    }
    /**
     * This function checks if the admin cookie is set correctly
     * @return unknown_type
     */
    private function checkAdminCookie(){
        if ( $this->request['REMOTE_ADDR'] ){ # Send from memberkit server
            $params = $this->request;
        }
        $username = Session::checkAdminPasswordHash( $this->request['cookie'], $params );
        if ( $username !== false ){
            $this->success(array("username" => $username));
        }else{
            $this->error();
        }
    }
    /**
     * This function checks if the admin cookie is set correctly
     * @return unknown_type
     */
    private function checkSupportCookie(){
        if ( $this->request['REMOTE_ADDR'] ){ # Send from memberkit server
            $params = $this->request;
        }
        $username = Session::checkSupportPasswordHash( $this->request['cookie'], $params );
        if ( $username !== false ){
            $this->success(array("username" => $username));
        }else{
            $this->error();
        }
    }
    /**
     * Get the usernames and return emails of users for JCM system.
     * @param usernames: Array
     * @return emails: Array
     */
    private function getEmailsJCM(){
        $usernames = Utils::safeJsonDecode($this->request['usernames']);
        # Emails addresses that will return
        $emails = array();
        foreach ($usernames as $username){
            $user = User::find($username, true);
            array_push($emails, $user->email);
        }
        $this->success(array("emails"=> $emails) );
    }
    
    /**
     * returns all status
     */
    private function getStatus(){
    	$this->success(array("status" => array("ACTIVE","SUSPENDED","DELETED","AUTOSUSPENDED","OVERLIMIT")));
    }
    
    /**
     * Returns all account types
     */
    private function getAccountTypes(){
        $this->success(array("accountTypes" => AccountType::getAllAccountTypes()));
    }
    
    private function setStatus(){
        $username = $this->request["username"];
        User::statusJobs($username, $this->request["status"]);
        $this->success("Converted successfully.");
    }
    
    /**
     * Tayfun write comments here
     * @return 
     */
    private function setAccountType(){
        $username = $this->request["username"];
        $user = User::find($username);
        $user->setAccountType (AccountType::find($this->request["accountType"]));
        $this->success("Converted successfully.");
    }
    
    /**
     * Gets username, usage type and usage number
     */
    private function setMonthlyUsage() {
        $username = $this->request["username"];
        $usageType = $this->request["usageType"];
        $usageValue = $this->request["usageValue"];
        MonthlyUsage::setUsageBy($username, $usageType, $usageValue); 
    }
    
    /**
     * 
     * Get user last payment gateway.
     */
    private function getUserPaymentGateway(){
        $username = $this->request['username'];
        $subscriptions = new JotFormSubscriptions();
        $subscriptions->setUser($username);
        $info = $subscriptions->getLastPaymentType();
        $this->success($info['gateway']);
    }
    
    /**
     * 
     * Upgrade user to premium and send email to us.
     */
    private function upgradeUserToProfessional(){
    	Utils::sendEmail(array(
            "from"    => "jotform@interlogy.com",
            "to"      => "jotformsupport@gmail.com",
            "subject" => "Upgrade request to Professional: " . $this->request['username'],
            "body"    => "Username: ". $this->request['username'] . "\n RequestedContract: " . $this->request['type'],
            "html"    => true
        ));
    	
        $user = User::find($this->request['username']);
        $user->setAccountType( AccountType::find('PROFESSIONAL'));
        
        # Save this goal for new year sale statistics
        # MailingTest::setGoal("Upgrade Completed", "MailingTest", $this->request['username']);
        
    	$this->success($this->request['username'] . " " . $this->request['type'] );
    }
    
    /**
     * Gets the account types for content manager use
     * @return 
     */
    private function getAccountTypesJCM(){
        $usernames = Utils::safeJsonDecode($this->request['usernames']);
        # Emails addresses that will return
        $accountTypes = array();
        foreach ($usernames as $username){
            $user = User::find($username, true);
            $accountType = $user->accountType;
            array_push($accountTypes, $accountType);
        }
        $this->success(array("accountTypes"=> $accountTypes) );
    }

    /**
     * Gets the form source
     * @return
     */
    private function getFormSource(){

        Form::displayForm($this->request['formID']);
        $source = ob_get_contents();
        ob_clean();
        $this->success(array("source"=> $source));
    }
    
    private function getV8Source(){
        $source = Form::createV8Source($this->get('id'), $this->get('formProperties'), $this->get('config'), $this->get('debug'));
        
        $this->success(array(
            "source" => $source
        ));
    }
    
    /**
     * This function will create the zip file and
     */
    private function getFormSourceZip(){
        $id = $this->get('id');
        if( ! ($src = $this->get('source'))){
            $src = Form::getSource($id);
        }
        
        # Create the $zipURL
        $zipURL = Form::createZip($id, $src);
        
        $url = HTTP_URL."zip/".str_replace(TRASH_FOLDER, "", $zipURL)."?t=".time();
        
        if($this->get('download') !== NULL){
            Utils::redirect($url);            
        }
        
        # After creating the zip, print with header
        $this->success(array( "zipURL" => $url));
    }
    
    /**
     * Saves a migration created on the admin page
     * @return 
     */
    private function saveMigration() {
        $name = $this->request['name'];
        $code = $this->request['code'];
        $result = RuckusingWrapper::createNewMigration($name, $code);
        if ($result['success']) {
            $this->success($result['message']);
        } else {
            $this->error($result['message']);
        }
    }
    
    /**
     * Migrates to the latest version, using the migration files in the 
     * file system.
     * @return 
     */
    private function migrateLatest() {
        $result = RuckusingWrapper::migrateLatest();
        if ($result['success']) {
            $this->success($result['message']);
        } else {
            $this->error($result['message']);
        }
    }
    
    /**
     * Returns the lates migration version number, using the migration files in the HDD.
     * ie. if there's 010_migration.php, 11 will be returned.
     * @return 
     */
    private function getLatestVersionNumber() {
        $version = RuckusingWrapper::getLatestVersionNumber();
        $this->success($version);
    }
    /**
     * This method is used by the migration page, to move to a specific 
     * migration version.
     * @return 
     */
    private function migrateToVersion() {
        $version = $this->request['version'];
        $result = RuckusingWrapper::migrateToVersion($version);
        if ($result['success']) {
            $this->success($result['message']);
        } else {
            $this->error($result['message']);
        }
        
    }
    /**
     * Prompts the excel for download
     * @return 
     */
    private function getExcel(){
        $form = new Form($this->request['formID']);
        $form->getExcel($this->request['excludeList'], 'exclude', $this->request["startDate"], $this->request['endDate']);
    }
    
    /**
     * Prompts a CSV file for download
     * @return 
     */
    private function getCSV(){
        $form = new Form($this->get('formID'));
        $form->getCSV($this->get('excludeList'), 'exclude', $this->get("startDate"), $this->get('endDate'));
    }
    
    /**
     * Reset password request is issued when someone fills the reset page.
     * Login is not needed as the user does not know his password. Token
     * is our only authentication method.
     * @TODO WTF!! Must move contents to a function
     * @return 
     */
    private function resetPassword() {
        $user = User::find($this->request['username']);
        if (!$user) {
            $this->error("There was an error in your request. Error Number: 116");
        }
        $realToken = md5($user->password . date('Y-m-d'));
        if ($realToken != $this->request['token']) {
            // token has expired.
            $this->error("Password reset request has expired. Please request a reset again.");
        }
        // Else set the new password and go on with our lives.
        $user->password = User::encodePassword($this->request['password']);
        $user->save();
        // The last act, user is logged in after changing his/her password.
        // That is what they like to do right, after resetting their passwords?
        Session::setUserGlobals($user, 'resetPassword');
        $this->success('Your new password has been set.');
    }
    
    /**
     * Migrates a user from old database to new database
     * @return 
     */
    private function migrateUser(){
        $user = new MigrateUser($this->request["username"], $this->request["merge"] == "true", $this->request["addPrefix"] == "true");
        $user->moveUser();
        $user->moveForms();
    }
    
    /**
     * Migrates the users from old DB to New DB by given chunks
     * @return 
     */
    private function migrateAllUsers(){
        if(isset($this->request['overwrite'])){
            MigrateAll::$merge = false;
        }
        MigrateAll::migrate($this->request['chunk']);
    }
    
    /**
     * Make a crawl request
     * @return 
     */
    private function crawlUsers(){
        $className = $this->get('className');
        $chunk     = $this->get('chunk');
        $chunkSize = $this->get('chunkSize');
        $crawler = new $className($chunk);
        $crawler->setChunkSize($chunkSize);
        $res = $crawler->browseUsers();
        
        if($res !== true){
            $this->success(array("completed" => true, "complete_message"=>$res ));
        }else{
            $this->success(array("completed" => $crawler->isFinished() ));
        }
    }
    
    /**
     * Gets the current Crawler status
     * @return 
     */
    private function getCrawlStatus(){
        $prop = Settings::getValue("usercrawler", "status");
        // $prop = file_get_contents("/tmp/crawlStatus.json");
        if($prop){
            $obj = json_decode($prop, true);
            $this->success($obj);
        }else{
            $this->error("No Property yet");
        }
        
    }
    
    /**
     * Run crawler only for one user
     * @return 
     */
    private function userOperations(){
        $className  = $this->get('className');
        $username   = $this->get('username');
        
        if( !class_exists($className) ){
            throw new Exception($className." does not exists.");
        }
        
        $crawler = new $className();
        $crawler->setProperties();
        $crawler->execute($username);
        
        $this->success("Completed successfully.");
    }
    
    private function recalculateUploads(){
        $username = $this->get("username");
        
        $crawler = new SyncAmazonUploads();
        $crawler->setProperties();
        $crawler->execute($username);
        
        $newUsage = MonthlyUsage::calculateDiskUsage($username);
        
        $this->success(array("byte"=>Utils::bytesToHuman($newUsage)));
    }
    
    /**
     * Loop the users and move the uploads to S3.
     */
    private function uploadToS3(){
        $chunk = $this->get('chunk');
        if (!$chunk){
            $chunk = 0;
        }
        
        $chunkSize = $this->get('chunkSize');
        if (!$chunkSize){
            $chunkSize = 100;
        }
                
        $crawler = new UploadToS3($chunk);
        $crawler->setChunkSize($chunkSize);
        while ( !$crawler->isFinished() ){
            $crawler->browseUsers();
            $crawler->setNextStart();
        }
    }
    
    /**
     * Runs uploadS3 user crawler for given user
     * @return 
     */
    private function syncWithAmazonS3(){
        $username = $this->get('username');
        $u2S3 = new UploadToS3();
        $u2S3->execute($username);
    }
    
    /**
     * Migrates the submissions from old DB to New DB by given chunks
     * @return 
     */
    private function migrateAllSubmissions(){
        MigrateAllSubmissions::migrate($this->request['chunk']);
    }
    
    /**
     * Returs the question config of a form
     * @TODO Must move content to a function, this is some bulshit
     * Don't we already have a getQuestions method in Form class. Why not use that one? 
     * @return 
     */
    private function getQuestions(){
        $questions = array();
        $singleQuestion = array();
        $res = DB::read("SELECT `question_id`, `prop`, `value` from `question_properties` WHERE " . 
                 "`form_id` = :formID AND (`prop` = 'type' OR `prop` = 'qid' OR `prop` = 'text' OR `prop` = 'order') ", 
                 $this->request['formID']);
                 
        if ($res->rows < 0) {
            $this->success(array("questions" => array_values($questions)));
        }
        
        $currentID = false;
        foreach($res->result as $line) {
            if ($currentID !== $line['question_id']) {
                if ($currentID !== false) {
                    $questions[$singleQuestion['order']] = $singleQuestion;
                    $singleQuestion = array();
                }
                $currentID = $line['question_id'];
            }
            $singleQuestion[$line['prop']] = $line['value'];
        }
        // Don't forget to add the last question.
        $questions[$singleQuestion['order']] = $singleQuestion;
        ksort($questions);
        
        if($this->get('onlyDataFields')){
            $filtered =  array();
            foreach($questions as $question){
                if(Form::isDataField($question['type'])){
                    $filtered[] = $question;
                }
            }
            $questions = $filtered;
        }
        
        $this->success(array("questions" => array_values($questions)));
    }
    /**
     * Activates a user 
     * @return 
     */
    private function activateUser(){
        $result = User::activate($this->request['username']);
        $this->success( "User activated: " . $this->request['username'] );
    }
    /**
     * Suspends a user
     * @return 
     */
    private function suspendUser(){
        $result = User::suspend($this->request['username']);
        $this->success( "User suspended: " . $this->request['username'] );
    }
    /**
     * unSuspends a user
     * @return 
     */
    private function unsuspendUser(){
        $result = User::unsuspend($this->request['username']);
        $this->success( "User unsuspended: " . $this->request['username'] );
    }
    /**
     * Marks a user deleted
     * @return 
     */
    private function deleteUser(){
        $result = User::delete( $this->request['username'] );
        $this->success( "User deleted: " . $this->request['username'] );
    }
    
    /**
     * Saves a listing into database
     * @return 
     */
    private function createListing(){
        $id = DataListings::createListing($this->request['formID'], $this->request['title'], $this->request['type'], $this->request['fields'], $this->get('password'));
        $this->success(array("id"=>$id));
    }
    
    /**
     * Saves a listing into database
     * @return 
     */
    private function updateListing(){
        $id = DataListings::updateListing($this->request['listID'], $this->request['title'], $this->request['type'], $this->request['fields'], $this->get('password'));
        $this->success(array("id"=>$id));
    }
    
    /**
     * Save selected theme for user to use it on further page views
     * @return 
     */
    private function setTheme(){
        Session::setTheme($this->get('theme'));
    }
    
    /**
     * Creates a cookie. Use this for creating all cookies on php
     * @return 
     */
    private function setCookie(){
        Utils::setCookie($this->request['name'], $this->request['value'], $this->request['expire']);
    }
    
    /**
     * Delete a cookie
     * @return 
     */
    private function deleteCookie(){
        Utils::deleteCookie($this->request['name']);
    }
    /**
     * @see MonthlyUsage::calculateDiskUsage
     * @return 
     */
    private function calculateDiskUsage(){
        $res = MonthlyUsage::calculateDiskUsage($this->get('username'), $this->get('deep'));
        $this->success(array("newSize" => Utils::bytesToHuman($res)));
    }
    /**
     * Deletes a report by type
     * @TODO Must move content to a function
     * @return 
     */
    private function deleteReport(){
        if($this->get('type') == "visual"){
            DB::write("DELETE FROM `reports` WHERE `id`=#id", $this->get('id'));
        }else{
            DB::write("DELETE FROM `listings` WHERE `id`=#id", $this->get('id'));
        }
        $this->success("Report deleted successfully.");
    }
    
    /**
     * Gets the total of pending submissions by given type
     * @TODO Must move content to a function
     * @return 
     */
    private function getPendingCount(){
        $res = DB::read("SELECT count(*) as `cnt` FROM `pending_submissions` WHERE `form_id`=#formID AND `type`=':type'", $this->get('formID'), $this->get('type'));
        $this->success(array("total" => $res->first['cnt']));
    }
    
    /**
     * Return the current cache ID of the minified files
     * @return 
     */
    private function getCacheIdOfMinGroup(){
        Console::log("Request has been received.");
        $groupName = $this->request['g'];
        $_GET[VERSION] = "";
        require_once( DROOT . DIRECTORY_SEPARATOR .  "min/generateMinUrl.php");
        $cacheId = generateMinUrl(); # Generate cache ID.
        # Console::log("Calculated cache id is: " . $cacheId);
        $this->success(array("cacheId"=>$cacheId));
    }
    
    /**
     * Returns the prop of all pending submissions
     * @TODO Must move content to a function
     * @return 
     */
    private function getPendingSubmissions(){
        $res = DB::read("SELECT * FROM `pending_submissions` WHERE `form_id`=#formID AND `type`=':type'", $this->get('formID'), $this->get('type'));
        $submissions = array();
        foreach($res->result as $line){
            $obj = Utils::unserialize($line['serialized_data']);
            $submissions[$line['submission_id']] = array("date" => $line['created_at'], "questions"=>array());
            if($obj === false){ continue; }
            foreach($obj->formQuestions as $question){
                $qid = $question['qid'];
                if(!isset($obj->questions[$qid])){ continue; }    
                $value = $obj->questions[$qid];
                
                if(is_array($value)){
                    $value = $obj->fixValue($value, $qid);
                }
                
                $submissions[$line['submission_id']]["questions"][$question['qid']] = array(
                    "text"   => $question["text"],
                    "answer" => $value
                );
            }
        }
        $this->success(array("submissions"=> $submissions));
    }
    
    /**
     * Completes the given submission
     * @return 
     */
    private function completePending(){
        Submission::continueSubmission($this->get('id'), 'PAYMENT', true);
    }
    
    /**
     * Deletes the given submission
     * @TODO Must move content to a function
     * @return 
     */
    private function deletePending(){
        DB::write("DELETE FROM `pending_submissions` WHERE `submission_id`=':id'", $this->get('id'));
    }
    
    /**
     * Uploads given file to S3 servers
     * @return 
     */
    private function sendFileToAmazonS3(){
        $filePath = $this->request['filePath'];
        $baseName = $this->request['baseName'];
        $formID = $this->request['formID'];
        if (file_exists($filePath)){
	        $as3c = new AmazonS3Controller();
	        $as3c->setProperties();
	        $as3c->setInsertID($formID);
	        $as3c->suppressUpload($filePath, $baseName);
        }else{
            $this->error("Cannot find file in this server: " . $filePath, null, 200);
        }
    }
    
    /**
     * Syncs all sybling with each other
     * @return 
     */
    private function sendFileToSiblings(){
        $filePath = $this->request['filePath'];
        $formID = $this->request['formID'];
        $as3c = new FileController();
        $as3c->setProperties();
        $as3c->setInsertID($formID);
        $as3c->suppressUpload($filePath);
    }
    
    /**
     * Goes an deletes file from S3 servers
     * @return 
     */
    private function deleteSubmissionFromAmazonS3(){
        $filePath = $this->request['filePath'];
        $as3c = new AmazonS3Controller();
        $as3c->setProperties();
        $as3c->suppressDelete($filePath);
    }
    
    /**
     * Ads the given server to the server list
     * then deploys new jotform version
     * @return 
     */
    private function addServer(){
        Server::addServer($this->get('name'), $this->get('publicIP'), $this->get('localIP'));
        Server::deployServers();
    }
    
    /**
     * Removes the server from list
     * @return 
     */
    private function removeServer(){
        Server::removeServer($this->get('name'));
    }
    
    /**
     * returns a list of active servers
     */
    private function listServers(){
        $list = Server::getServerList();
        $this->success(array('serverList' => $list));
    }
    
    /**
     * Deploy jotform to all servers
     * @return 
     */
    private function deployServers(){
        $response = Server::deployServers();
        $this->success("Build request has been sent. It will take a while", array("response" => $response));
    }
    
    /**
     * Calls the file upload options of dropbox integration
     * @return 
     */
    private function sendFileToDropBox(){
        $d = new DropBoxIntegration($this->get('username'), $this->get('formID'));
        $d->sendFile($this->get('basePath'), $this->get('filePath'));
    }
    
    private function sendFileToFTP(){
        $d = new FTPIntegration($this->get('username'), $this->get('formID'));
        $d->sendFile($this->get('basePath'), $this->get('filePath'));
    }
    
    /**
     * Get an integration information
     * @return 
     */
    private function getIntegration(){
        // If someone else is trying to get this page block them
        if(Session::$username !== $this->get('username')){
            $this->error("You are not allowed to make this request", false, 401);
        }
        
        $in = new Integrations($this->get('type'), $this->get('formID'), $this->get('username'));
        if($in->isNew()){
            $this->error("No integration found", null, 200);
        }else{
            $keys = explode(",", $this->get('keys'));
            $values = array();
            foreach($keys as $key){
                $values[$key] = $in->getValue($key);
            }
            $this->success(array("values" => $values));
        }
    }
    
    /**
     * Sets a property of integration
     * @return 
     */
    private function setIntegrationProperty(){
        $in = new Integrations($this->get('type'), $this->get('formID'), $this->get('username'));
        $in->setValue($this->get('key'), $this->get('value'));
        $in->save();
    }
    
    /**
     * Sets a set of properties of an integration
     * @return 
     */
    private function setIntegrationProperties(){
        $in = new Integrations($this->get('type'), $this->get('formID'), $this->get('username'));
        $props = json_decode($this->get('props'), true);
        foreach($props as $key => $value){
            $in->setValue($key, $value);
        }
        $in->save();
    }
    
    /**
     * Removes the integration of given service
     * @return 
     */
    private function removeIntegration(){
        switch($this->get('type')){
            case "dropbox":
                $Db = new DropBoxIntegration($this->get('username'), $this->get('formID'));
                $Db->removeIntegration();
        	break;
            case "FTP":
            	$ftp = new FTPIntegration($this->get('username'), $this->get('formID'));
                $ftp->removeIntegration();
        	break;
        }
    }
    
    /**
     * Calculates the stats and saves on the database
     * @return 
     */
    private function getStats(){
        $stats = new Stats();
        $stats->writeStats();
        $this->success($stats->totals);
    }
    
    /**
     * Sets a goal for given test
     * @return 
     */
    private function setGoal(){
        $name = $this->get('name');
        $className = $this->get('testName');
        $username = $this->get('username');
        if($username){
            $instance = ABTesting::getInstance($className);
            $user = $instance->getTestParticipant($username);
            if($user['test_name'] == $className){
                call_user_func(array($className, "setGoal"), $name, $className, $username);
            }
        }else{
            if(isset($_SESSION[ABTesting::SESSION]) ){
                if($_SESSION[ABTesting::SESSION]['test_name'] == $className){
                    call_user_func(array($className, "setGoal"), $name, $className);
                }
            }
        }
        
    }
    
    /**
     * Retuns the basic information of all tests
     * @return 
     */
    private function getAllTestInformation(){
        $res = ABTestingController::getAllTestsInfo();
        $this->success(array("tests" => $res));
    }
    
    /**
     * Sets a 
     * @return 
     */
    private function getGoalInfoByDate(){
        $res = ABTestingController::getGoalsDataByDate($this->get('test'), $this->get('goals'), $this->get('group'), $this->get('start'), $this->get('end'));
        $this->success($res);
    }
    
    /**
     * Set or remove the public password for submissions page
     * @return 
     */
    private function submissionPublicPassword(){
        if($this->get('type') == "add"){
            Settings::setSetting("public_submission_password", $this->get('formID'), md5(":jotform:".$this->get('password')));
        }else{
            Settings::removeSetting("public_submission_password", $this->get('formID'));
        }
    }
    
    /**
     * Create a downloadable application package
     * @todo move contents to a function
     * @return 
     */
    private function getAppPackage(){
        $zipName = "jotapp-".SVNREV.".zip";
        $tmpFolder = 'getapp';
        if(JOTFORM_ENV == 'DEVELOPMENT'){
            $url = HTTP_URL;
            $svnbase = "/www/cleanCO";
            $output  = "/tmp/";
        }else{
            $url = "http://".Server::whoAmI().".interlogy.com";
            $svnbase = "/home/jotform/jotform3";
            $output  = "/tmp/";
        }
        
        if(!file_exists(ROOT.$tmpFolder)){
            Utils::recursiveMkdir(ROOT.$tmpFolder);
        }
        
        if(file_exists(ROOT.$tmpFolder."/".$zipName)){
            $this->success(array("url" => $url."/".$tmpFolder."/".$zipName, "scriptResult" => "No change was found"));
        }
        
        $result = shell_exec(ROOT."opt/installer/makeapp.sh ".$svnbase." ".$output);
        $result = nl2br(Console::clearColors($result));
        
        if(file_exists($output."jotapp.zip")){
            rename($output."jotapp.zip", ROOT.$tmpFolder."/".$zipName);
            $this->success(array("url" => $url."/".$tmpFolder."/".$zipName, "scriptResult"=> $result));
        }else{
            Console::error($result);
            $this->error("ZIP Cannot be created.", array("details" => $result));
        }
    }
    
    /**
     * Prints the inline style of the form
     * @todo move contents to a function
     * @return 
     */
    private function getInlineFormStyle(){
        ob_start();
        Form::displayForm($this->get('formID'));
        $formsource = ob_get_contents();
        ob_clean();
        $style = Utils::substringBetween($formsource, '<style type="text/css">', '</style>');
        $style = preg_replace('/^\s{4}/m', '', $style);
        if($this->get('css') !== null){
            header('Content-Type: text/css; charset=utf-8');
            // Replace body, html to.form-container for facebook
            $style = preg_replace("/body\,\s+html/", ".form-container", $style);
            //echo $style;
			$styles=explode("\n}\n",$style);
			$allform=explode(";",$styles[count($styles)-2]);
			$customback="";
			$custom="";
			for($i=3;$i<count($allform);$i++){
				if($i==count($allform)-1)
				{
					$custom=$custom.$allform[$i];
				if($i != 3)
					$customback=$customback.$allform[$i];
				}
				else
				{
					$custom=$custom.$allform[$i].";";
				if($i != 3)
					$customback=$customback.$allform[$i].";";	
				}
				
			}
			$i=0;
			for(;$i<count($styles)-2;$i++){
				$styles[$i] = $styles[$i].$custom."\n    font-weight:normal;\n}\n";
			}
			$styles[$i] = $styles[$i]."\n}\n";
						
			$last=count($styles)-1;
			$styles[$last]=".form-subHeader{".$customback."\n}\n";
			$last++;
			$styles[$last]=".form-header{".$customback."\n}\n";
			$last++;
			$styles[$last]=".form-label-top{".$custom."\n    font-weight:normal;\n}\n";
			$last++;
			$styles[$last]="label{".$custom."\n    font-weight:normal;\n}\n";
			
			$sum="";
			for($i=0;$i<= $last;$i++){
				$sum = $sum.$styles[$i];
			}
			echo $sum;			
			exit;
        }else{
            $this->success($style);            
        }
    }
    
    /**
     * Mark all submissions of given form as read
     * @todo move contents to a function
     * @return 
     */
    private function markAllRead(){
        DB::write("UPDATE `submissions` SET `new`=0 WHERE `form_id`=#id", $this->get('formID'));
        DB::write("UPDATE `forms` SET `new`=0 WHERE `id`=#id", $this->get('formID'));
    }
    
    /**
     * Will delete all submissions of given form.
     * This action requires password
     * @return 
     */
    private function deleteAllSubmissions(){
        if(!$this->get('formID')){
            $this->error("There is an error. Please try again later");
        }
        
        $form = new Form($this->get('formID'));
        $user = $form->getOwner();
        $pass = $this->get('password');
        if($user->password != User::encodePassword($pass)){
            $this->error("Wrong Password. Operation Aborted");
        }
        
        $form->deleteAllSubmissions();
    }
    
    /**
     * Search given address in the maillog
     * @return 
     */
    private function grepMailLog(){
        $line = (float) $this->get('lines');
        $line = !$line? 1 : $line;
        
        $onlyError = '';
        if($this->get('onlyErrors')){
            $onlyError = '| grep -i -v "sent"';
        }
        
        $cmd = 'grep '. escapeshellarg($this->get('email')) .' /var/log/mail.log '.$onlyError.' | tail -n '.$line;
        $output = shell_exec($cmd);
        
        $this->success(array("output" => $output, "command"=> $cmd));
    }
    
    /**
     * Saves users e-mail address on dropbox form and guest account
     * sets integration folder field ETC
     * @return 
     */
    private function completeDropboxIntegration(){
        $integration = new DropBoxIntegration();
        $res = $integration->dropbox->getAccountInfo();
        $email = $res['email'];
        
        $integration->config->setValue("folder_field", "3");
        $integration->config->save();
        
        $res = DB::write("UPDATE `form_properties` SET `value`=':to'
                          WHERE `form_id` = #id
                          AND `type` LIKE 'emails'
                          AND prop = 'to'",
                          array(
                              "id" => Utils::getCurrentID('form'),
                              "to"=> $email
                          )    
                      );   
        if(Session::isGuest()){
            Session::setGuestEmail($email);
        }    
        $this->success(array("id"=>Utils::getCurrentID('form'), "email" => $email));
    }    
    
    /**
     * Creates a form for dropbox integration
     * @return 
     */
    private function createDropboxForm(){
        $form = new FormFactory($this->request['form']);
        $id = $form->save();
        Form::clearCache('id', $id); 
        $this->success(array(
            "id" => $id
        ));  
    }
    
    /**
     * Moves files to temp folder for multiple uploads
     * @return 
     */
    private function multipleUpload(){
        
        if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
            ob_end_clean();
            header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
            header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
            header("Access-Control-Allow-Headers: ".$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
            header("Access-Control-Max-Age: 1728000");
            header("Content-Length: 0");
            header("Content-Type: text/plain");
            exit(0);
        }
        header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
        include_once "file-uploader.php";

        // list of valid extensions, ex. array("jpeg", "xml", "bmp")
        $allowedExtensions = array();
        // max file size in bytes
        $sizeLimit = 1000 * MB;
        $notAllowedExtensions = Submission::$neverAllow;
        
        $uploader = new qqFileUploader($allowedExtensions, $sizeLimit, $notAllowedExtensions);
        
        $path = TMP_UPLOAD_FOLDER.$this->get('folder')."/".$this->get('field')."/";
        
        Utils::recursiveMkdir($path, 0777);
        
        $result = $uploader->handleUpload($path);
        
        $this->responseContentType = 'text/html';
        
        // to pass data through iframe you will need to encode all html tags
        if($result['success'] === true){
            $this->success($result);
        }else{
            $this->error($result['error'], null, 200);
        }
    }
    
    /**
     * Deletes the uploaded file
     * @return 
     */
    private function removeTempUpload(){
        $path = TMP_UPLOAD_FOLDER.$this->get('tempFolder')."/".$this->get('field')."/".$this->get('fileName');
        if( file_exists($path) ){
            if(!unlink($path)){
                $this->error('Cannot delete file');
            }
        }else{
            $this->error($path." File missing");
        }
    }
    
    /**
     * Makes a connection test on FTP integration
     * @return 
     */
    private function testFTPConnection(){
        $ftp = new FTPLib($this->get('host'), $this->get('username'), $this->get('password'));
        $ftp->connect();
    }
    
    /**
     * Retreive a list of folder on FTP integration
     * @return 
     */
    private function getFTPFolders(){
        // If someone else is trying to get this page block them
        if(Session::$username !== $this->get('username')){
            $this->error("You are not allowed to make this request", false, 401);
        }
        
        $ftpi = new FTPIntegration($this->get('username'), $this->get('formID'));
        $files = $ftpi->getDir($this->get('folder'));
        $this->success(array(
            "dir" => $files
        ));
    }
    
    private function geckoPanel(){
        $prop = Settings::getValue("usercrawler", "status");
        // $prop = file_get_contents("/tmp/crawlStatus.json");
        if($prop){
            $obj = json_decode($prop, true);
            $item = array(
                "item" => $obj['chunkStart']+$obj['index'],
                "max"  => array(
                    "text" => "Total",
                    "value" =>  $obj['totalUsers']
                ),
                "min"  => array(
                    "text" => "start",
                    "value" =>  0
                ),
            );
            $this->success($item);
        }else{
            $this->error("No Property yet");
        }
    }
    
    private function getUserStats(){
        DB::setConnection('stats', "jotform_stats", DB_USER, DB_PASS, DB_HOST);
        DB::useConnection("stats");
        $res = DB::read("SELECT * FROM `table_stats` WHERE `date` > DATE_SUB(NOW(),INTERVAL 3 MONTH)");
        $users = array();
        $dates = array();
        $last = false;
        
        foreach($res->result as $line){
            $d = date('D', strtotime($line['date']));
            $l = $line['premium'];
            if($last !== false){
                $dates[] = $d;
                $users[] = $l-$last;
            }    
            $last = $l;
        }    
        $item = array(
            "item"=> $users,
            "settings" => array(
                "axisx" => $dates,
                "axisy" => array(min($users),  round((max($users)+min($users))/2),  max($users)),
                "colour" => "ff9900"
            )    
        );   

        $this->success($item);
    }
    
    private function getGraph(){
        $stats = new Stats();
        $stats->setDiffMode(true);
        $result = $stats->getGraphData($this->get('column'), $this->get('duration'), $this->get('interval'));
        $res = array_values($result);
        $item = array(
            "item"=> $res,
            "settings" => array(
                "axisx" => array_keys($result),
                "axisy" => array(min($res),  round((max($res)+min($res))/2),  max($res)),
                "colour" => "ff9900"
            )
        );   

        $this->success($item);
    }
    
    /**
     * Move submissions to CouchDB
     * @return unknown_type
     */
    private function migrateToCouchDB(){
    	$id = $this->get('formID');
    	$form = new Form($id);
    	$res = $form->getSubmissions();
    	unset($res['total']);
    	unset($res['questions']);
    	foreach($res as $sid => $submission){
    	   $client = new couchClient(Configs::COUCH_DB_HOST, Configs::COUCH_DATABASE);
    	   $doc = new couchDocument($client);
    	   $submission['_id'] = "".$sid;
    	   $doc->set($submission);
    	}
    }
}
