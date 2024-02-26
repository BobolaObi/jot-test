<?php
/**
 * Collects and hadles the submissions
 * @package JotForm
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot;

use forms\DropBoxIntegration;
use Legacy\Jot\Integrations\FTPIntegration;
use Legacy\Jot\UserManagement\MonthlyUsage;
use Legacy\Jot\Utils\Captcha;
use Legacy\Jot\Utils\Console;
use Legacy\Jot\Utils\DB;
use Legacy\Jot\Utils\Server;
use Legacy\Jot\Utils\Settings;
use Legacy\Jot\Utils\Utils;

class Submission {
    
    private $slowDownForms = array(/*"2855730853", "11035037214",*/ "11090501232"),
            $noRedirect;
    
    static  $isSlowDownForm = false;
    
    public  $questions,
            $questionNames, 
            $request, 
            $files, 
            $formID, 
            $form, 
            $owner, 
            $sid,
            $spcCheck=false, 
            $checkSpam = "", 
            $uploadTarget,
            $uploads, 
            $getQuestions, 
            $paymentField, 
            $productsToBuy,
            $paymentTotal,
            $monthlyUsage,
            $paypro,
            $productSelected = false,
            $payment,
            $isEdit,
            $isInline,
            $formProperties,
            $formQuestions,
            $isOldForm = false,
            $currentPage = 0,
            $sessionID = false,
            $shouldStop = false,
            $goBackMessage = "<br><br>Please <a href=\"javascript:window.history.back(-1)\">go back</a> and fix this",
            $additional,
            $multipleUploadsPath = false,
            $ip,
            $sdate;
    
    /**
     * These file types cannot be uploaded on our servers
      * @var //array
     */        
    public static $neverAllow = array('php', 'pl', 'cgi', 'rb', 'asp', 'aspx', 'exe', 'scr', 'dll', 'msi', 'vbs', 'bat', 'com', 'pif', 'cmd', 'vxd', 'cpl');
    
    
    /**
     * Collects all data from request and converts it for further use
     * @constructor
     * @return 
     */
    function __construct($request = false){
    	
    	// set ip of the user
    	$this->ip = $_SERVER['REMOTE_ADDR'];
        
        $post = $_POST? $_POST : $_GET;
        $this->request = $request? $request : $post;    # Allow get just for now
        $this->files = $_FILES;
        
        $this->formID = $this->request['formID'];
        unset($this->request['formID']);
        
        # get Simple spam check veraibles
        if(isset($this->request['simple_spc'])){
            $this->checkSpam = $this->request['simple_spc'];
            $this->spcCheck = true;
        }else if(isset($this->request['website'])){
            $this->checkSpam = $this->request['website'];
            unset($this->request['website']);
        }else if(isset($this->request['spc'])){
            $this->checkSpam = $this->request['spc'];
            $this->isOldForm = true;
        }else{
            $this->checkSpam = false;
            $this->isOldForm = true;
        }
        
        $this->sdate = time();                               # take the exact time of submission
        
        $this->sid = ID::generateSubmissionID($this->ip);    # Generate submission ID
        
        # For multipage forms and save operations
        if(isset($this->request['session_id'])){
            $this->currentPage = $this->request['current_page'];
            $this->sessionID   = $this->request['session_id'];
            if(isset($this->request['submission_id'])){
                $this->sid     = $this->request['submission_id'];
            }
            $this->shouldStop  = isset($this->request['hidden_submission']);
        }
        
        # IF the form is in slowdown forms list then push submission into the redis queue
        if(Configs::USE_REDIS_SUBMSN && in_array($this->formID, $this->slowDownForms)){
            self::$isSlowDownForm = $this->formID;
            $redis = Utils::getRedis();
            # Check if the thank you page for this submission was cached
            if($redis !== false && $redirectArgs = $redis->get($this->formID.'-thankyou')){
                # Save submission into redis
                if($this->serializeAndSaveForSlowDown() !== false){
                    $args = Utils::unserialize($redirectArgs);
                    # Get cached thank you page and show user the cached version of the thank you 
                    # Without saving submission into database
                    call_user_func_array(array('self', 'doRedirect'), $args);
                    die(); # stop script here
                }
            }
        }
        
        # Continue database related operations here
        $this->fetchAndParseProperties();
        
        # Server side validations disabled because of the hidden fields and ETC.
        # $this->validateSubmission();
    }
    
    /**
     * Serializes and saves the submission for later use.
     * @return 
     */
    private function serializeAndSaveForSlowDown(){
        Console::setOneLine(true);
        $serialized = $this->serialize();
        
        $redis = Utils::getRedis();
        if($redis !== false){
            $redis->lpush("submissions", $serialized);
            $length = $redis->llen("submissions");
            Console::customLog('redis', 'Pushed new submission, Length for the list: '.$length, $this->formID);
            return $serialized;
        }
        
        return false;
    }
    
    /**
     * Gets the REDIS submission queue and completes each submission one by one
     * This script called automatically by an outside script 
     * @return 
     */
    static function completeSlowSubmissions(){
        Console::setOneLine(true);
        # Get the aloowed memory limit for this script
        $memLimit = (((float) ini_get('memory_limit')*1024*1024) / 100) * 50;
        
        $redis = Utils::getRedis();
        if($redis === false){ throw new \Exception('Redis is not connected'); }
        
        Console::customLog("redis", "Script Started, current length of submissions:". $redis->llen("submissions"));
        $i = 0;
        # Loop through infinetelly until the the memory is under limits
        while(memory_get_usage() < $memLimit){
            
            try{
                
                # Wait for every then submission to relax mysql
                if(($i++ % 10) == 0){
                   usleep(0.1*SEC); 
                }

                $res = $redis->lpop("submissions");
                # If there is a submission in the queue
                if($res !== NULL){
                    Console::customLog('redis', 'Fetced new submission. trying to push');
                    $submission = Utils::unserialize($res);
                    $submission->fetchAndParseProperties(true);
                    $submission->submit(); # unserialize and complete the submission here
                    # Print the current status of the script here
                    Console::customLog('redis', 'completed. '.$redis->llen("submissions").' Left, Current Memory: '.Utils::bytesToHuman(memory_get_usage()).' Limit: '.Utils::bytesToHuman($memLimit));
                    unset($submission);
                }else{
                    usleep(0.1*SEC); # If there is no submission found wait a bit before checking again
                }
                # Flush buffers
                flush();
                ob_flush();
            }catch(Exception $e){
                # If this operations throw an exception put these submissions 
                # into another list so we can try again
                Console::customLog("redis", $e->getMessage(), "Error");
                $redis->lpush('errored-submissions', $res);
            }
        }
    }
    
    /**
     * Fetch Form and submission properties form database 
     * and parse the request with these properties
     * @return 
     */
    private function fetchAndParseProperties($noRedirect = false){
        $this->noRedirect = $noRedirect;
        
        /* Here starts queries */
        $this->form = new Form($this->formID);      # Get associated Form
        
        $this->formQuestions = $this->form->getQuestions(); # Questions of the form
        $this->owner = $this->form->getOwner();     # Get owner information
        # A monthly usage object seems to be necessary because it is being 
        # used at least two times during submissions: once while checking 
        # limits to send e-mails and once while incrementing usage counts. 
        $this->monthlyUsage = MonthlyUsage::find($this->owner);
        
        # /www/uploads/_username_/_formID_/_submissionID_/
        $this->uploadTarget = UPLOAD_FOLDER."".$this->owner->username."/".$this->formID."/".$this->sid."/";
        
        $this->parseRequest();
        
    }
    
    /**
     * Add aditional data to use in further operations
     * @param  $data
     * @return 
     */
    public function addAdditional($data){
        $this->additional = $data;
    }
    
    /**
     * Cleans the non data fields from form questions array
     * @depricated Causes captcas not to work
     * @return 
     */
    public function cleanupQuestionsArray(){
        $newQuestions = array();
        foreach($this->formQuestions as $qid => $prop){
            if(Form::isDataField($prop['type'])){
                $newQuestions[$qid] = $prop;
            }
        }
        
        unset($this->formQuestions);
        $this->formQuestions = $newQuestions;
    }
    
    /**
     * Deletes the submissions by a username
     * @param  $username
     * @return 
     */
    public static function deleteBy($username) {
        $response = DB::write("DELETE FROM submissions WHERE `username`=':username'", $username);
        return $response;
    }
    
    /**
     * Serialize this object
     * @return string serialized object
     */
    public function serialize(){
        return Utils::serialize($this);
    }
    
    /**
     * Returns the form desired form property and caches the results
     * @param  $prop
     * @return 
     */
    public function getFormProperty($prop){
        if(!$this->formProperties){
            $this->formProperties = $this->form->getSavedProperties(false);            
        }
        
        return isset($this->formProperties["form_".$prop])? $this->formProperties["form_".$prop] : "";
    }
    
    
    /**
     * Stores serialized submission on the database
     * @param  $token  // [optional] token for identifying paypal pro submissions
     * @return 
     */
    private function stopSubmission($type, $token = "", $sessionID = ""){
        $res = DB::read("SELECT * FROM `pending_submissions` WHERE `submission_id`=':sid' AND `type`=':type' AND `form_id`=':formID' AND `token`=':token' AND `session_id`=':sessid'",
            $this->sid,
            $type, 
            $this->formID,
            $token,
            $sessionID
        );
        
        if($res->rows > 0){
        #if(0){ 
            $response = DB::write("UPDATE `pending_submissions` SET `serialized_data`=':data' WHERE `submission_id`=':sid' AND `type`=':type' AND `form_id`=':formID' AND `token`=':token' AND `session_id`=':sessid'",
                $this->serialize(),
                $this->sid,
                $type, 
                $this->formID,
                $token,
                $sessionID
            );
        }else{
            $response = DB::write("INSERT IGNORE INTO `pending_submissions` (`submission_id`, `type`, `form_id`, `token`, `serialized_data`, `session_id`) VALUES(':sid', ':type', ':formID', ':token', ':data', ':sessid')",
                $this->sid,
                $type, 
                $this->formID,
                $token, 
                $this->serialize(),
                $sessionID
            );
        }
    }
    
    /**
     * Contiues the stopped submission
     * @param  $id
     * @param  $type  // [optional]
     * @param  $continuous  // [optional]
     * @return 
     */
    public static function continueSubmission($id, $type = '', $continuous = false, $additionalInfo = false){
        if(empty($id)){ return; }
        
        $response = DB::read("SELECT `serialized_data` FROM `pending_submissions` WHERE `submission_id` = ':sid' OR `token` = ':sid'", $id, $id);
        if($response->rows > 0){
            $line = $response->result[0];
            /**
             * Unserialized submission object
              * @var //Submission
             */
            $submit = Utils::unserialize($line['serialized_data']);
            
            # if it's not an object that means it cannot be unserilized
            if($submit === false){ throw new \Exception("Cannot unserialize pendining submission"); }
            
            # @TODO this should be run after submission saved on the database. Do it later
            DB::write("DELETE FROM `pending_submissions` WHERE `submission_id` = ':sid' OR `token` = ':sid' LIMIT 1", $id, $id);
            
            if(method_exists($submit, "addAdditional") && $additionalInfo){
                $submit->addAdditional($additionalInfo);
            }
            
            if($type == 'captcha'){
                return $submit->captchaContinue();
            }else{
                return $submit->complete($continuous);
            }
        }
        return false;
    }
    
    /**
     * Completes all pending submissions of a form
     * @param  $formID
     * @return 
     */
    public static function continueAllSubmissions($formID){
        $response = DB::read('SELECT `submission_id` FROM `pending_submissions` WHERE `form_id`=#id', $formID);
        foreach($response->result as $line){
            self::continueSubmission($line['submission_id'], '', true);
            echo "Completed: ".$line['submission_id']."<br>";
        }
    }
    
    /**
     * Server side validation for the form
     * @TODO causes problems with conditions, so we disabled it..
     * @TODO We have to find a way to avoid validating hidden fields
     * @TODO Error messages must be translated
     * @return 
     */
    public function validateSubmission(){
        $errors = array();
        foreach($this->formQuestions as $qid => $prop){
            if ($prop['type'] == 'control_captcha') {
                continue;
            }
            # Get hte field value
            if(isset($this->questions[$qid])){
                $value = $this->questions[$qid];
            }else{
                $value = "";
            }
            
            if(is_array($value)){
                $value = join("", $value);
            }
            
            # Check required
            if(isset($prop['required']) && $prop['required'] == 'Yes'){
                if($value !== "0" && empty($value)){
                    $errors[] = $prop['text']." <b>is required</b>";
                }
            }
            
            # Check email
            if(isset($prop['validation']) && $prop['validation'] != 'None'){
                
                if($value !== "0" && empty($value)){
                    continue; # if field is not required and is empty then skip the validations
                }
                
                switch(strtolower($prop['validation'])){
                    case "email":
                        if(!Utils::checkEmail($value)){
                            $errors[] = $prop['text']." <b>field can only contain a valid email address:</b> \"".$value."\" given";
                        }
                    break;
                    case "numeric":
                    	if(!preg_match("/^(\d+[\.\,]?)+$/", $value)){
                            $errors[] = $prop['text']." <b>field can only contain numeric values:</b> \"".$value."\" given";
                        }
                    break;
                    case "alphanumeric":
                    	if(!preg_match("/^[a-zA-Z0-9]+$/", $value)){
                            $errors[] = $prop['text']." <b>field can only contain letters and numbers:</b> \"".$value."\" given";
                        }
                    break;
                    case "alphabetic":
                    	if(!preg_match("/^[a-zA-Z\s]+$/", $value)){
                            $errors[] = $prop['text']." <b>field can only contain letters:</b> \"".$value."\" given";
                        }
                    break;
                }
            }
        }
        
        if(!empty($errors)){
            $errorText  = '<div style="text-align:left;">';
            $errorText .= "There are incomplete fields in your submission:";
            $errorText .= '<ul style="text-align:left;padding: 0px; list-style-position: inside;">';
            $errorText .= "<li>".join("</li><li>", $errors)."</li>";
            $errorText .= "</ul>";
            $errorText .= preg_replace("/\<br\>/", "", $this->goBackMessage);
            $errorText .= "</div>";
            Utils::errorPage($errorText, "Incomplete Values", "", 200);
        }
    }
    
    /**
     * Parses the POST Request splits questionIDs questionNames and values
     * @return 
     */
    private function parseRequest() {
        
        $this->questions = array();        # All questions on the form with user entered values, array KEYs are question IDs
        $this->questionNames = array();    # Names of these questions
        
        # Check if the submission is in edit mode
        if($this->isEdit = isset($this->request["editSubmission"])){
            $this->sid = $this->request["editSubmission"];
            unset($this->request["editSubmission"]);
        }
        
        # Check if the edit form is an inline editor or what
        if($this->isInline = isset($this->request["inlineEdit"])){
            unset($this->request["inlineEdit"]);
        }
        # Check if the form contains an ajax multiple upload field
        # and made any uploads
        if(isset($this->request['temp_upload_folder']) && file_exists(TMP_UPLOAD_FOLDER.$this->request['temp_upload_folder'])){
            $this->multipleUploadsPath = Utils::path(TMP_UPLOAD_FOLDER.$this->request['temp_upload_folder']);
        }
        
        # split all question ids and question names
        # Names will be used in the emails
        foreach($this->request as $key => $value){
            preg_match('/q(?P<id>\d+)_(?P<name>.*)/', $key, $matches);
            if(isset($matches['id'])){
                $this->questions[$matches['id']] = $value;
                $this->questionNames[strtolower($matches['name'])] = $matches['id'];
            }
        }
        
        # If form has an autoIncrement field then do what's necessary
        if(($auQid = $this->formHas("autoincrement")) !== false && !$this->isEdit){
            # get old AU value from DB
            $auNum = Settings::getValue("autoIncrement", $this->formID);
            if($auNum !== false){ #
                $auNum = (float) $auNum;    # Fix user's mistake
                $auNumValue = ++$auNum;     # increate the number and put it on DB
            }else{
                $auNumValue = $auNum = 1;   # If not found turn back to 1
            }
            
            # question properties
            $qprop = $this->formQuestions[$auQid];
            
            # Give number padding such as: 00001
            if(isset($qprop['idPadding']) && $qprop['idPadding'] > 0){
                $auNumValue = str_pad($auNumValue, $qprop['idPadding'], "0", STR_PAD_LEFT);
            }
            # add prefix here WD-15 or WD-000015
            if(isset($qprop['idPrefix']) && $qprop['idPrefix'] !== ""){
                $auNumValue = ($qprop['idPrefix'] . $auNumValue);
            }
            
            # place value for DB insertion
            $this->questions[$auQid] = $auNumValue;
            # update the value on settings
            Settings::setSetting("autoIncrement", $this->formID, $auNum);
        }
        
        # if the form has uploads then parse the $_FILES array too
        if($this->formHasUpload()){
            # Check if multiple ajax file upload was provided
            if($this->multipleUploadsPath){
                # Check all directories under temp_upload_path
                foreach(scandir($this->multipleUploadsPath) as $field){
                    if($field === "." || $field === ".."){ continue; } # skip upper directories
                    # default files array
                    $uploaded = array("name" => array(),"type" => array(),"tmp_name" => array(),"error" => array(),"size" => array());
                    # Loop all upload files for thsi field
                    foreach(glob($this->multipleUploadsPath.$field."/*.*") as $file){
                        $filename = basename($file); # get file name
                        # Pupulate files array like it's a real upload
                        $uploaded['name'][] = $filename;
                        $uploaded['type'][] = Utils::getMimeType($file);
                        $uploaded['tmp_name'][] = $file;
                        $uploaded['error'][] = "0";
                        $uploaded['size'][] = filesize($file);
                    }
                    # Put properties in files array
                    $this->files[$field] = $uploaded;
                }
                # This comes with files array and it's empty so delete it.            
                unset($this->files['file']);
            }
            
            # Loop through all files and set them for upload process
            foreach($this->files as $key => $value){
                preg_match('/q(?P<id>\d+)_(?P<name>.*)/', $key, $matches);
                
                if($value['name']){
                    $this->questions[$matches['id']] = Utils::fixUploadName($value["name"]);
                    $this->uploads[$matches['id']]   = $value;
                    $this->questionNames[strtolower($matches['name'])] = $matches['id'];
                } else {
                    if(isset($matches['id'])){
                        # If old value was sent from edit mode then use it instead
                        if(isset($this->request['input_'.$matches['id'].'_old'])){
                            $this->questions[$matches['id']] = $this->request['input_'.$matches['id'].'_old'];
                            unset($this->uploads[$matches['id']]);
                        }else{
                            # Pretend this upload doesn't exist
                            unset($this->questions[$matches['id']]);
                            unset($this->uploads[$matches['id']]);
                            unset($this->questionNames[strtolower($matches['name'])]);
                        }
                    }
                }
            }
        }
        
        # If the form has payment then collect all payment information together
        # Get all selected options such as color, size, quantity
        # Merge the product information with the original from database
        if($this->formHasPayment()){
            
            # If this is an old form source and has a payment question
            # we should redirect this submission to old version
            # user will lost the submission data but save their money
            if($this->isOldForm && !APP){
                $this->submitTov2();
            }
            
            $this->paymentField = $this->getPaymentField();                     # Payment field on the form
            
            $selectedProducts = @$this->questions[$this->paymentField['qid']];  # Get the selected payment fields from request
            $allProducts = $this->form->getProducts();                          # Products saved on the form
            if(empty($allProducts)){ $allProducts = array();  }
            
            if($this->paymentField['paymentType'] == 'donation' && is_array($selectedProducts)){
               $selectedProducts = $selectedProducts['price'];
            }
            
            # If there are any products on the form then check if they are selected or not 
            if(is_array($selectedProducts)){
                foreach($selectedProducts as $k => $item){
                    if(isset($item['id'])){
                        $this->productSelected = true;                           # Yes. A product was selected
                    }else{
                        if(is_array($item)){
                            unset($this->questions[$this->paymentField['qid']][$k]);  # No. This product was on the request but user didn't select it, so just ignore it
                        }
                    }
                }               
            }else{
                
                if($this->paymentField['paymentType'] == 'donation' && $selectedProducts > 0){                    
                    $this->productsToBuy = $this->paymentField;
                    $this->productsToBuy["price"] = $selectedProducts;
                    $this->productSelected = true;
                }
            }
            
            # If any of the products are selected?
            if($this->productSelected && is_array($selectedProducts)){
                $productsToOriginal = array();
                # Loop through selected products
                foreach($selectedProducts as $product){
                    # Loop through all products of the form
                    foreach($allProducts as $originalProduct){
                        # If current selected product matches the original product
                        if(isset($product['id']) && $product['id'] == $originalProduct['pid']){ # then
                            # Loop through the submission data for this product, in order to get options
                            foreach($this->request["q".$this->paymentField['qid']."_".$this->paymentField['name']] as $itemKey => $val){
                                # if item key has the "special" then this is an option value
                                if($itemKey == "special_".$originalProduct['pid']){
                                    # Loop through the options to get what value was selected by user
                                    foreach($val as $valKey => $valValue){
                                        if($valKey == "id"){ continue; } # Ignore this key
                                        list($none, $optID) = explode("_", $valKey); # Split and get the option ID
                                        $originalProduct["options"][$optID]["selected"] = $valValue; # Put selected value in the option
                                        unset($originalProduct["options"][$optID]["properties"]);    # This is unnecessary, just for form builder
                                    }
                                }
                            }
                            # These are form builder values and we don't need them here
                            unset($originalProduct["icon"]);
                            unset($originalProduct["hasQuantity"]);
                            # Place this product into new a array of manuplated original products
                            $productsToOriginal[] = $originalProduct; 
                        }
                    }
                }
                # All selected products are filled with user selected values and ready for payment process
                $this->productsToBuy = $productsToOriginal;
                
                # Insert products as a submission into questions array to be able to save them in submissions table
                if(!is_array($this->questions[$this->paymentField['qid']])){
                    $this->questions[$this->paymentField['qid']] = array();
                }
                $total = 0;
                foreach($productsToOriginal as $p){
                    $total += $p['price'];
                    $p["currency"] = $this->paymentField["currency"];
                    $p["gateway"]  = str_replace("control_", "", $this->paymentField["type"]);
                    $p["paymentType"] = $this->paymentField["paymentType"];
                    
                    $this->questions[$this->paymentField['qid']][] = $p;
                }
                
                if($total <= 0){
                    # If total is smaller than 0 than no need to go to payment gateway
                    $this->productSelected = -1;
                }
            }
        }
        
        if($this->isEdit){
            if($this->formHas('matrix') !== false){
                $this->fixMatrixCheckboxOnEdit();
            }
        }
        
        # If this is an old form then we should recreate the question names
        # because old form sources doesn't contain the question names so we
        # cannot populate the emails
        if($this->isOldForm){
            $res = DB::read("SELECT * FROM `question_properties` WHERE `prop` IN ('name', 'text') AND `form_id`=#id", $this->formID);
            $this->questionNames = array();
            
            foreach($res->result as $line){
                $this->questionNames[ strtolower($this->createQuestionName($line['value'])) ] = $line['question_id'];
                if($line['prop'] == 'name' && !empty($line['value'])){
                    $this->questionNames[ strtolower($line['value']) ] = $line['question_id'];
                }
            }
            //Utils::print_r($this->questionNames);
        }
    }
    
    /**
     * Apply a fix for matrix checkboxes, because if you unselect and entire row it will not be saved on database.
     * We have to fullfill a complete array before we submit it to database otherwise old data will remain the same
     * @return 
     */
    public function fixMatrixCheckboxOnEdit(){
        foreach($this->formQuestions as $qid => $prop){
            if($prop['type'] == "control_matrix" && $prop['inputType'] == 'Check Box'){
                foreach(explode("|", $prop['mrows']) as $i => $row){
                    if(!isset($this->questions[$qid][$i])){
                        $this->questions[$qid][$i] = array();
                    }
                }
            }
        }
    }
    
    /**
     * Submit request directly to old source
     * @return 
     */
    public function submitTov2(){
        Utils::postRedirect("http://v2.jotform.com/payment_submit.php", $_POST); 
        exit;
    }
    
    /**
     * Creates a question name for given question
     * @param  $qid
     * @param  $formID
     * @return 
     */
    public function createQuestionName($text){
        # See if there's another question with the same name.
        $qLabel = Utils::fixUTF($text);
        $tokens = preg_split("/\s+/", $qLabel);
        
        $qName = (isset($tokens[1])) ? ( strtolower($tokens[0]) . ucfirst(strtolower($tokens[1])) ) : strtolower($tokens[0]);
        $qName = preg_replace("/\W/", "", $qName);
        
        return $qName;
    }
    /**
     * To see detailed information about the submission
     * @return 
     */
    private function debug(){
        
        echo "Submission Details for Form: ". $this->formID. " on: ".Server::whoAmI();
        echo "<hr><br> Request:<br>";         Utils::print_r($this->request);
        if($this->formHasPayment()){
            echo "<h3>Form has a payment</h3>";
            echo "<hr><br> Payment text:";     echo $this->makeProductText($this->questions[$this->paymentField['qid']]);
            echo "<hr><br> Payment Field:<br>"; Utils::print_r($this->paymentField);
            echo "<hr><br> Products To Buy:<br>";      Utils::print_r($this->productsToBuy);
            echo "<hr><br> Products:<br>";      Utils::print_r($this->form->getProducts());
        }
        echo "<hr><br> Questions:<br>";       Utils::print_r($this->questions);        
        echo "<hr><br> Question Names:<br>";  Utils::print_r($this->questionNames);
        if($this->formHasUpload()){
            echo "<h3>Form has upload</h3>";
            echo "<hr><br> Files:<br>";       Utils::print_r($this->files);
            echo "<hr><br> Uploads:<br>";     Utils::print_r($this->uploads);
        }
        
        $emails = new FormEmails(array(
            "conditions"    => array(),
            "submission"    => $this
        ));
        
        echo "<hr><br> Emails To Be Sent (Conditions are not calculated):<br>"; Utils::print_r($emails->emailsToBeSent);
        echo "<hr><br> Old Email Generated:<br>"; Utils::print_r($emails->createOldEmail());
        echo "<hr><br> Form:<br>";            Utils::print_r($this->form);
        echo "<hr><br> Form Questions:<br>";  Utils::print_r($this->formQuestions);
        echo "<hr><br> Owner:<br>";           Utils::print_r($this->owner);
        echo "<hr><br> Conditions:<br>";      Utils::print_r($this->getConditions());
        unset($this->formProperties["form_emails"]);
        echo "<hr><br> Form Properties:<br>"; Utils::print_r($this->formProperties); 
        exit;
    }
    
    /**
     * Check if the form has a captcha tool
     * @return boolean
     */
    private function formHasCaptcha(){
        $ret = false;
        foreach($this->formQuestions as $qid => $prop){
            if($prop['type'] == "control_captcha"){
                $ret = true;
            }
        }
        return $ret;
    }
    
    /**
     * Check if the form has a payment
     * @return boolean
     */
    private function formHasPayment(){
        $ret = false;
        foreach($this->formQuestions as $qid => $prop){
            if(in_array($prop['type'], Form::$paymentFields)){
                $ret = true;
            }
        }
        return $ret;
    }
    
    /**
     * Check if the form has a payment
     * @return boolean
     */
    private function getPaymentField(){
        $ret = false;
        foreach($this->formQuestions as $qid => $prop){
            if(in_array($prop['type'], Form::$paymentFields)){
                $ret = $prop;
            }
        }
        return $ret;
    }
    
    /**
     * Check if the form has an upload field
     * @return boolean
     */
    private function formHasUpload(){
        $ret = false;
        foreach($this->formQuestions as $qid => $prop){
            if($prop['type'] == "control_fileupload"){
                $ret = true;
            }
        }
        return $ret;
    }
    
    /**
     * Check if the form has given type of field
     * @return boolean
     */
    private function formHas($type){
        $ret = false;
        foreach($this->formQuestions as $qid => $prop){
            if($prop['type'] == "control_".$type){
                $ret = $qid;
            }
        }
        return $ret;
    }
    
    /**
     * Inserts the data into the database
     * @return 
     */
    private function insertData(){
        $status = 'ACTIVE';
        # If over quota, set the status value of a submission.
        if ($this->monthlyUsage->isOverQuota()) {
            $status = "OVERQUOTA";
        }
        
        # Insert data into submissions and answers table
        $response = DB::write("INSERT INTO `submissions` (`id`, `form_id`, `ip`, `created_at`, `status`, `new`) VALUES(':id', #form_id, ':ip', NOW(), ':STATUS', 1)",
            $this->sid,
            $this->form->id,
            $this->ip,
            $status
        );
        
        if($response->success){
            foreach($this->questions as $qid => $pvalue){
                
                # If this is a deep value such as Address or Matrix
                if(is_array($pvalue)){
                    
                    $value = $this->fixValue($pvalue, $qid);
                    
                    foreach($pvalue as $item_name => $ival){
                        
                        if(strpos($item_name, "cc_") === 0){
                            continue; # don't write any credit card information database
                        }
                        
                        $response = DB::write("INSERT INTO `answers` (`form_id`, `submission_id`, `question_id`, `item_name`, `value`) VALUES(#form_id, ':sid', #qid, ':item_name', ':value')",
                            $this->form->id,
                            $this->sid,
                            $qid,
                            $item_name,
                            Utils::safeJsonEncode($ival) # This value can also be an array. Sad but true :(
                        );
                    }
                }else{
                    # $value = $this->fixFlatValue($pvalue, $qid);
                    $value = $pvalue;
                }
                
                $response = DB::write("INSERT INTO `answers` (`form_id`, `submission_id`, `question_id`, `value`) VALUES(#form_id, ':sid', #qid, ':value')",
                    $this->form->id,
                    $this->sid,
                    $qid,
                    $value
                );
            }
        }
    }
    
    /**
     * Convert the submitted value into a readable value for reports
     * 
     * !!IMPORTANT!! Only use this for emails because it breaks the 
     * submitted value for some fields so it should only be used for
     * display purposes not for DB actions
     * 
     * @param  $pvalue
     * @param  $qid
     * @return 
     */
    public function fixFlatValue($pvalue, $qid){
        
        $props = Utils::getArrayValue("qid", $qid, $this->formQuestions);
        $value = "";
        
        switch($props["type"]){
            case "control_rating":
            	if($pvalue === ""){ return $pvalue; }
                $length = $props['stars'];
            	$value = $pvalue . "/". $length;
                
                # Above line prints [ * * * - -] like indicator, it's complicated we do'nt need that
                # $value = "( ".$pvalue." ) - [ ".str_pad(str_repeat("* ", $pvalue), $length*2, "- ", STR_PAD_RIGHT)."]";
            break;
            case "control_slider":
            	if($pvalue === ""){ return $pvalue; }
            	# Max value                	
            	$max = $props['maxValue'];
                
                # Join all together
                $value = $pvalue."/".$max;
            break;
            default:
                $value = $pvalue;
        }
        
        return $value;
    }
    
    /**
     * Fix the array value for display and saving
     * @param  $pvalue
     * @param  $qid
     * @return 
     */
    public function fixValue($pvalue, $qid){
        
        if(($this->productSelected || $this->productSelected == -1) && $this->paymentField['qid'] == $qid){
            $value = $this->makeProductText($pvalue);
        }else{
            $props = Utils::getArrayValue("qid", $qid, $this->formQuestions);
            
            # Somehow cc information accidently ends up here
            # We should never store any cc information by any means
            foreach($pvalue as $key => $value){
                if(strpos($key, 'cc_') === 0){
                    unset($pvalue[$key]);
                }
            }
            
            switch($props["type"]){
                case "control_dropdown":
                case "control_checkbox":
                    $value = join("<br>", $pvalue);
            	break;
                case "control_matrix":
                    $cols  = explode("|", $props['mcolumns']);
                	$html  = '<table summary="" cellpadding="4" cellspacing="0" border="0" style="font-size:10px;border-collapse:collapse;"><tr>';
                    $html .= '<th style="border:none">&nbsp;</th>';
                    $colWidth = ( 100 / count($cols) + 2 )."%";
                    foreach($cols as $col){
                        $html .=  '<th style="background:#eee;border:1px solid #ccc;text-align:center;width:'.$colWidth.'">' . $col . '</th>';
                    }
                    $html .= '</tr>';
                    
                    $rows = explode("|", $props['mrows']);
                    $items = $pvalue;
                    foreach($rows as $ri => $row){
                        $html .= '<tr>';
                        $html .= '<th style="background:#eee;border:1px solid #ccc;text-align:left;" nowrap="nowrap">'.$row."</th>";
                        foreach($cols as $ci => $col){
                            $input = "-";
                            if(!isset($items[$ri])){ $items[$ri] = ''; }
                            switch($props['inputType']){
                                case "Radio Button":
                                    $input = ($items[$ri] == $col)? '<img src="'.HTTP_URL.'images/tick.png" height="16" width="16" alt="X" align="top" />' : "-";
                                break;
                                case "Check Box":
                                    $input = (in_array($col, $items[$ri]) !== false)? '<img src="'.HTTP_URL.'images/tick.png" height="16" width="16" alt="X" align="top" />' : "-";
                                break;
                                case "Text Box":
                                    $input = isset($items[$ri][$ci])? $items[$ri][$ci] : "-";
                                break;
                                case "Drop Down":
                                    $input = isset($items[$ri][$ci])? $items[$ri][$ci] : "-";
                                break;
                            }
                            $html .= '<td align="center" style="background:#ffffff;border:1px solid #ccc;" >'.$input."</td>";
                        }
                        $html .= "</tr>";
                    }
                    $html .= "</table>";
                    $value = $html;
                break;
                case "control_phone":
                	$value = "(".$pvalue['area'].")-".$pvalue['phone'];
            	break;
                case "control_range":
                	$value = "From: ".$pvalue['from'].", To: ".$pvalue['to']; //.". Difference: ".($pvalue['to'] - $pvalue['from']);
                break;
                case "control_fullname":
                	$name = "";
                    if(isset($pvalue['prefix']) && !empty($pvalue['prefix'])){
                        $name .= ($pvalue['prefix'].". ");
                    }
                    if(isset($pvalue['first']) && !empty($pvalue['first'])){
                        $name .= ($pvalue['first']." ");
                    }
                    if(isset($pvalue['middle']) && !empty($pvalue['middle'])){
                        $name .= ($pvalue['middle']." ");
                    }
                    if(isset($pvalue['last']) && !empty($pvalue['last'])){
                        $name .= $pvalue['last'];
                    }
                    if(isset($pvalue['suffix']) && !empty($pvalue['suffix'])){
                        $name .= (", ".$pvalue['suffix'].".");
                    }
                    $value = $name;
                break;
                case "control_datetime":
                    $date = "";
                    $time = "";
                    
                    if($props['format'] == "mmddyyyy"){
                        $date = $pvalue["month"]."-".$pvalue["day"]."-".$pvalue["year"];
                    }else{
                        $date = $pvalue["day"]."-".$pvalue["month"]."-".$pvalue["year"];
                    }
                    
                    if(isset($pvalue["hour"])){
                        $time = " ".$pvalue["hour"].":".$pvalue["min"];
                    }
                    
                    if(isset($pvalue['ampm'])){
                        $time .= " ".$pvalue["ampm"];
                    } 
                    
                    $value = $date.$time;
            	break;
                case "control_grading":
                    $texts = explode("|", $props['options']);
                    $sum   = 0;
                    $value = "";
                    foreach($texts as $i => $text){
                        $value .= $text." = ".$pvalue[$i]."<br>";
                        $sum += $pvalue[$i];
                    }
                    
                    $value .= "Total: ".$sum;
                break;
                case "control_fileupload":
                	$values = array();
                    foreach($pvalue as $val){
                        $fullURL = Utils::getUploadURL($this->owner->username, $this->formID, $this->sid, $val);
                        $values[] = '<a href="'.$fullURL.'" target="_blank">'.$val.'</a>'; 
                    }
                    $value = join("<br>", $values);
                break;
                case "control_address":
                	$sublabels = json_decode($props['sublabels'], true);
                    $value = "";
                    foreach($pvalue as $key => $val){
                        if(!empty($val)){
                            $value .= ($sublabels[$key].": ".$val."<br>");
                        }
                    }
                break;
                default:
                    $value = join(" ", $pvalue);
            }
            
        }
        
        return $value;
    }
    
    
    /**
     * Creates a representative text for the products selected. their real values are kept on the database too.
     * @param  $selectedProducts
     * @return 
     */
    public function makeProductText($selectedProducts){
        
        $text = "";
        $total = 0;
        if($this->paymentField['paymentType'] == 'donation'){
            $donation = $this->paymentField;
            $price = $this->questions[$this->paymentField['qid']];
            if(is_array($price)){ $price = $price['price']; }
            $donation['price'] = $price;
            $donation['name'] = $donation['donationText'];
            $selectedProducts = array($donation);
        }
        $type = "";
        foreach($selectedProducts as $product){
            if(!is_array($product) || !isset($product['paymentType'])){ continue; }
            
            $quantity = 1;
            $options = array();
            $hasQuantity = false;
            $type = $product['paymentType'];
            
            if($type == "subscription"){
                
                $text .= $product['name']."<br>";
                
            }else{
                if(!empty($product['options'])){
                    foreach($product['options'] as $option){
                        if($option['type'] == 'quantity'){
                            $quantity = $option['selected'];
                            $hasQuantity = true;
                        }
                        $options[] = $option['name'].": ".$option['selected'];
                    }
                    $total += $quantity * $product['price'];
                }else{
                    $total += $product['price'];
                }
                
                $text .= $product['name'];
                if(!empty($options)){
                    $text .= ' (' . join(', ', $options) . ')';
                }
                $text.="<br>";                
            }
            
        }
        
        if($type != 'subscription'){
            if($product['currency'] == 'USD'){
                $text .= "Total: \$". $total . "<br>";
            }else{
                $text .= "Total: ". $total . " " . $product['currency'] . "<br>";
            }            
        }
        
        $table  = '<table cellpadding="0" cellspacing="0" border="0"><tr><td>';
        $table .= $text;
        
        if($this->additional){
            $table .= "</td></tr><tr><td><br />\n";
            if($this->additional['gateway'] == 'paypalpro' || $this->additional['gateway'] == 'authnet'){
                
                $table .= '<table cellpadding="0" cellspacing="0" border="0" style="font-size:10px;">';
                $table .= '<tr><td colspan="2"><b style="color:#777; font-size:12px;">==Payer Info==</b></td></tr>';
                $table .= '<tr><td width="70"><b style="color:#777">First Name</b> </td><td>'.$this->additional['FIRSTNAME'].'</td></tr>';
                $table .= '<tr><td width="70"><b style="color:#777">Last Name</b> </td><td>'.$this->additional['LASTNAME'].'</td></tr>';
                if(!empty($this->additional['EMAIL'])){
                    $table .= '<tr><td width="70"><b style="color:#777">E-Mail</b> </td><td>'.$this->additional['EMAIL'].'</td></tr>';
                }
                if(!empty($this->additional['SHIPTOSTREET'])){
	                $table .= '<tr><td colspan="2"><b style="color:#777; font-size:12px;"><br>==Address==</b></td></tr>';
	                if(isset($this->additional['SHIPTONAME'])){
	                    $table .= '<tr><td><b style="color:#777">Name</b> </td><td>'.$this->additional['SHIPTONAME'].'</td></tr>';
	                }
	                $table .= '<tr><td><b style="color:#777">Street</b> </td><td>'.$this->additional['SHIPTOSTREET'].'</td></tr>';
	                $table .= '<tr><td><b style="color:#777">City</b> </td><td>'.$this->additional['SHIPTOCITY'].'</td></tr>';
	                $table .= '<tr><td><b style="color:#777">State</b> </td><td>'.$this->additional['SHIPTOSTATE'].'</td></tr>';
	                $table .= '<tr><td><b style="color:#777">Zip</b> </td><td>'.$this->additional['SHIPTOZIP'].'</td></tr>';
	                $table .= '<tr><td><b style="color:#777">Country</b> </td><td>'.$this->additional['SHIPTOCOUNTRYNAME'].'</td></tr>';
                }
                $table .= '</table>';
                
            }else if($this->additional['gateway'] == 'paypal'){
                $table .= '<table cellpadding="0" cellspacing="0" border="0" style="font-size:10px;">';
                $table .= '<tr><td colspan="2"><b style="color:#777; font-size:12px;">==Payer Info==</b></td></tr>';
                $table .= '<tr><td width="70"><b style="color:#777">First Name</b> </td><td>'.$this->additional['first_name'].'</td></tr>';
                $table .= '<tr><td><b style="color:#777">Last Name</b> </td><td>'.$this->additional['last_name'].'</td></tr>';
                $table .= '<tr><td><b style="color:#777">E-Mail</b> </td><td>'.$this->additional['payer_email'].'</td></tr>';
                
                if(!empty($this->additional['address_street'])){
	                $table .= '<tr><td colspan="2"><b style="color:#777; font-size:12px;"><br>==Address==</b></td></tr>';
	                $table .= '<tr><td><b style="color:#777">Name</b> </td><td>'.$this->additional['address_name'].'</td></tr>';
	                $table .= '<tr><td><b style="color:#777">Street</b> </td><td>'.$this->additional['address_street'].'</td></tr>';
	                $table .= '<tr><td><b style="color:#777">City</b> </td><td>'.$this->additional['address_city'].'</td></tr>';
	                $table .= '<tr><td><b style="color:#777">State</b> </td><td>'.$this->additional['address_state'].'</td></tr>';
	                $table .= '<tr><td><b style="color:#777">Zip</b> </td><td>'.$this->additional['address_zip'].'</td></tr>';
	                $table .= '<tr><td><b style="color:#777">Country</b> </td><td>'.$this->additional['address_country'].'</td></tr>';
                }
                $table .= '</table>';
                          
            } else if($this->additional['gateway'] == 'googleco'){
                
                $table .= '<table cellpadding="0" cellspacing="0" border="0" style="font-size:10px;">';
                $table .= '<tr><td colspan="2"><b style="color:#777; font-size:12px;">==Payer Info==</b></td></tr>';
                $table .= '<tr><td width="70"><b style="color:#777">Buyer Name</b> </td><td>'.$this->additional['buyer-billing-address_contact-name'].'</td></tr>';
                $table .= '<tr><td><b style="color:#777">E-Mail</b> </td><td>'.$this->additional['buyer-shipping-address_email'].'</td></tr>';
                
                if(!empty($this->additional['buyer-shipping-address_address1'])){
	                $table .= '<tr><td colspan="2"><b style="color:#777; font-size:12px;"><br>==Address==</b></td></tr>';
	                $table .= '<tr><td><b style="color:#777">Name</b> </td><td>'.$this->additional['buyer-shipping-address_contact-name'].'</td></tr>';
	                $table .= '<tr><td><b style="color:#777">Street</b> </td><td>'.$this->additional['buyer-shipping-address_address1'].' '.$this->additional['buyer-shipping-address_address2'].'</td></tr>';
	                $table .= '<tr><td><b style="color:#777">City</b> </td><td>'.$this->additional['buyer-shipping-address_city'].'</td></tr>';
	                $table .= '<tr><td><b style="color:#777">State</b> </td><td>'.$this->additional['buyer-shipping-address_region'].'</td></tr>';
	                $table .= '<tr><td><b style="color:#777">Zip</b> </td><td>'.$this->additional['buyer-shipping-address_postal-code'].'</td></tr>';
	                $table .= '<tr><td><b style="color:#777">Country Code</b> </td><td>'.$this->additional['buyer-shipping-address_country-code'].'</td></tr>';
                }
                $table .= '</table>';
            } else if($this->additional['gateway'] == 'worldpay'){
                
                $table .= '<table cellpadding="0" cellspacing="0" border="0" style="font-size:10px;">';
                $table .= '<tr><td colspan="2"><b style="color:#777; font-size:12px;">==Payer Info==</b></td></tr>';
                $table .= '<tr><td width="70"><b style="color:#777">Buyer Name</b> </td><td>'.$this->additional['name'].'</td></tr>';
                if(!empty($this->additional['compName'])){
                    $table .= '<tr><td width="70"><b style="color:#777">Company</b> </td><td>'.$this->additional['compName'].'</td></tr>';
                }
                $table .= '<tr><td><b style="color:#777">E-Mail</b> </td><td>'.$this->additional['email'].'</td></tr>';
                $table .= '<tr><td><b style="color:#777">Address</b> </td><td>'.$this->additional['address'].'</td></tr>';
                $table .= '<tr><td><b style="color:#777">Zip</b> </td><td>'.$this->additional['postcode'].'</td></tr>';
                $table .= '<tr><td><b style="color:#777">Country Code</b> </td><td>'.$this->additional['countryString'].'</td></tr>';
	            $table .= '</table>';
            }
        }
        
        $table .= '</td></tr></table>';
         
        return $table;
    }
    
	/**
	 * Checks if the given answer is exist on database or not
	 * @param  $qid
	 * @param  $item_name  // [optional]
	 * @return 
	 */
	public function checkAnswerExists($qid, $item_name = ''){
		$res = DB::read("SELECT * FROM `answers` WHERE `form_id`=#formID AND `submission_id`=':sid' AND `question_id`=#qid AND `item_name`=':itemname'", $this->formID, $this->sid, $qid, $item_name);
		return ($res->rows > 0);
	}
	
    
    /**
     * Updates the given entry
     * @return 
     */
    private function updateData(){
        
        # Insert data into submissions and answers table
        $response = DB::write("UPDATE `submissions` SET `updated_at`=NOW(), `new`=2 WHERE `id`=:id", $this->sid);
        if($response->success){
            foreach($this->questions as $qid => $pvalue){
                
                # If this is a deep value such as Address or Matrix
                if(is_array($pvalue)){
                    
                    $value = $this->fixValue($pvalue, $qid);
                    
                    foreach($pvalue as $item_name => $ival){
                    	if($this->checkAnswerExists($qid, $item_name)){
	                    	$response = DB::write("UPDATE `answers` SET `value`=':value' WHERE `form_id`=#form_id AND `submission_id`=':sid' AND `question_id`=#qid AND `item_name`=':item_name'",
	                            Utils::safeJsonEncode($ival), # This value can also be an array. Sad but true :(
	                            $this->form->id,
	                            $this->sid,
	                            $qid,
	                            $item_name
	                        );
                    	}else{
	                        $response = DB::write("REPLACE INTO `answers` (`form_id`, `submission_id`, `question_id`, `item_name`, `value`) VALUES (#form_id, ':sid', #qid, ':item_name', ':value')",
	                            $this->form->id,
	                            $this->sid,
	                            $qid,
	                            $item_name,
	                            Utils::safeJsonEncode($ival) # This value can also be an array. Sad but true :(
	                        );
                    	}
                    }
                }else{
                    
                    # $value = $this->fixFlatValue($pvalue, $qid);
                    $value = $pvalue;
                }
                
				if($this->checkAnswerExists($qid)){
					$response = DB::write("UPDATE `answers` SET `value`=':value' WHERE `form_id`=#form_id AND `submission_id`=':sid' AND `question_id`=#qid AND `item_name`=''",
	                    $value,
	                    $this->form->id,
	                    $this->sid,
	                    $qid
	                );
				}else{
	                $response = DB::write("REPLACE INTO `answers` (`form_id`, `submission_id`, `question_id`, `value`) VALUES (#form_id, ':sid', #qid, ':value')",
	                    $this->form->id,
	                    $this->sid,
	                    $qid,
	                    $value
	                );					
				}
            }
        }
    }
    
    /**
     * Checks the simple spam check value
     * @return 
     */
    private function simpleSpamCheck(){ 
        
        if($this->checkSpam === false){
            return true;
        }
        
        if($this->spcCheck){
            
            if($this->checkSpam == $this->formID . "-" . $this->formID){
                return true;
            }else{
                return false;
            }
        }else if($this->isOldForm){
            if($this->checkSpam == md5($this->formID)){
                return true;
            }
        }else{
            if(empty($this->checkSpam)){
                return true;
            }
        }
        return false;
    }
    
    /**
     * Checks the submissions on the form for uniqueness
     * @return 
     */
    private function handleUniqueSubmissions(){
        
        if($this->isEdit){
            return; # does not check uniqueness on edit mode.
        }
        
        $check = $this->form->getProperty('unique');
        $errorMessage = 'Multiple submissions are disabled for this form.';
        $error = 'Sorry! Only one entry is allowed.';
        
        if($check == 'Loose'){
            if(Utils::getCookie('unique') == md5($this->formID.":securitySalt:")){
                Utils::errorPage($errorMessage, $error, 'Loose Fail');
            }
            Utils::setCookie('unique', md5($this->formID.":securitySalt:"), '+1 Year');
        }
        
        if($check == 'Strict'){
            $res = DB::read('SELECT * FROM `submissions` WHERE `form_id`=#id AND `ip`=":ip"', $this->formID, $this->ip);
            if($res->rows > 0){
                Utils::errorPage($errorMessage, $error, 'Strict Fail');
            }
        }
        
        return;
    }
    
    /**
     * Handles the uploads on the form
     * @return 
     */
    private function handleUploads(){
        
        # if form has no upload field on the form then return
        if(!$this->formHasUpload() || empty($this->uploads)){ return; }
        
        # Check if the form has file upload field
        if($uploads = $this->form->getQuestions('control_fileupload')){
            
            foreach($this->uploads as $qid => $uploadProp){
                $questionProp = $uploads[$qid];
                $props = array();
                if(is_array($uploadProp['name'])){
                    foreach($uploadProp as $key => $value){
                        foreach($value as $i => $v){
                            $props[$i][$key] = $v;
                        }
                    }                    
                }else{
                    $props = array($uploadProp);
                }
                
                foreach($props as $uploadProp){
                    # empty upload field? then go ahead and move to the next one
                    if(strlen($uploadProp["name"]) <= 3 || $uploadProp["size"] <= 0){ continue; }
                     
                    $this->doUpload($uploadProp, $questionProp);
                }
            }
        }
        
        if($this->multipleUploadsPath){
            Utils::recursiveRmdir($this->multipleUploadsPath);
        }
    }
    
    /**
     * Gets an upload propery and completes the upload
     * @param  $uploadProp
     * @param  $questionProp
     * @return 
     */
    private function doUpload($uploadProp, $questionProp){
        
        $fileSize = round($uploadProp["size"]/1024, 2); # Find how much kilobytes file is.
        
        # get the allowed max size
        $mSize = $questionProp["maxFileSize"];
        if(empty($mSize)){
            $mSize = "10241"; // If not provided limit it with 10MB
        }
        
        $maxSize = round(str_replace(",", "", $mSize), 2);
        
        # Check max-size                 
        if($fileSize > $maxSize){
            Utils::errorPage("<b>".$uploadProp["name"]."</b> is <u>".Utils::bytesToHuman($fileSize*1024)."</u> but it cannot be bigger than <u>".Utils::bytesToHuman($maxSize*1024)."</u> ".
            $this->goBackMessage, "Upload Error");
        }
        # Get allowed extensions
        $allowedExts = preg_split("/\s*,\s*/", $questionProp["extensions"]);
        $fileExt = Utils::getFileExtension($uploadProp["name"]);
        
        # Check if this is an harmful file
        if(in_array($fileExt, self::$neverAllow)){
            Utils::errorPage("<b>".$fileExt."</b> file extension is considered harmful and cannot be uploaded on our servers".
            $this->goBackMessage, "Upload Error", "Harmful extension");
        }
        
        # Check file extension is allowed
        if(trim(''.$questionProp["extensions"]) != "*" && !in_array($fileExt, $allowedExts) && !in_array(strtolower($fileExt), $allowedExts)){
            Utils::errorPage("<b>".$fileExt."</b> file extension is not allowed. You can only upload <b>".$questionProp["extensions"]."</b> files.".
            $this->goBackMessage, "Upload Error");
        }
        
        
        # Get allowed extensions
        $allowedExts = preg_split("/\s*,\s*/", $questionProp["extensions"]);
        $fileExt = Utils::getFileExtension($uploadProp["name"]);
        
        # Check if this is an harmful file
        if(in_array($fileExt, self::$neverAllow)){
            Utils::errorPage("<b>".$fileExt."</b> file extension is considered harmful and cannot be uploaded on our servers".
            $this->goBackMessage, "Upload Error", "Harmful extension");
        }
        
        # Check file extension is allowed
        if(trim(''.$questionProp["extensions"]) != "*" && !in_array($fileExt, $allowedExts) && !in_array(strtolower($fileExt), $allowedExts)){
            Utils::errorPage("<b>".$fileExt."</b> file extension is not allowed. You can only upload <b>".$questionProp["extensions"]."</b> files.".
            $this->goBackMessage, "Upload Error");
        }
         
        #------------------------------------------------------------------------------------------------
        # UFSCONTROLLER ---------------------------------------------------------------------------------
        #------------------------------------------------------------------------------------------------
        try{
            # Create UFSController.
            $ufsc = new UFSController($this->owner->username, $this->formID, $this->sid, $uploadProp, null, $this);
            $ufsc->uploadFile();
            if(!$this->isEdit){
               $this->monthlyUsage->incrementUsage('uploads', $uploadProp["size"]);
            }
        }catch (\Exception $e){
            Utils::errorPage("File could not be uploaded for some reason.".$this->goBackMessage, "Upload Error", print_r(error_get_last(), true));
        }
    }
    
    
    /**
     * Handle the payments
     * @return 
     */
    private function handlePayments(){
        
        if($this->isEdit){ return; } # Do not allow users to edit payments
        
        # if user didn't select a product, then return
        if(!$this->productSelected || $this->productSelected < 0){ return; }
        
        # Data user submitted
        $answer = $this->questions[$this->paymentField["qid"]];
        $thankyou = $this->form->getThankyouPage();
        
        $returnURL = HTTP_URL."complete.php?sid=".$this->sid;
        
        # Special cases for each payment  gateway
        switch($this->paymentField['type']){
            case "control_paypal":
	            # Make redirects pending and complete after payment is done
                $this->handleRedirects(true);
                
                $this->payment = new PayPal(array( # Initiate the paypal object
                    "formID"      => $this->formID,
                    "sid"         => $this->sid,
                    "business"    => $this->paymentField["account"],
                    "currency"    => $this->paymentField["currency"],
                    "paymentType" => $this->paymentField["paymentType"],
                    "payerAddress" => $this->paymentField["payeraddress"],
                    "returnURL"    => $returnURL // Done
                ));
                
                # Set products to buy
                $this->payment->setProducts($this->productsToBuy);
                # stop the submission here
                $this->stopSubmission('PAYMENT');
                
                # Make transaction
                $this->payment->transact();
                
            break;
            case "control_authnet":
            	$this->payment = new AuthorizeDotNet(array(
                    "formID"         => $this->formID,
                    "sid"            => $this->sid,
                    "loginID"        => $this->paymentField["apiLoginId"],
                    "transactionKey" => $this->paymentField["transactionKey"],
                    "paymentType"    => $this->paymentField["paymentType"],
                    "currency"       => $this->paymentField["currency"],
                    "returnURL"      => $returnURL // Doen't seem to be working and we seem to no need it.
                ));
                $this->payment->setProducts($this->productsToBuy);
                $this->payment->setCreditCard($answer["cc_number"], $answer["cc_exp_month"], $answer["cc_exp_year"], $answer["cc_ccv"]);
                
                # Search all questions to match an email address then adds it into the billing info
                # So that form owner can see this address in the transaction details
                $emailField = false;
                foreach($this->formQuestions as $question){
                    if(preg_match("/email|mail|e\-mail/i", $question['text'])){
                        # If there is en email on this location then stop searching
                        if($emailField = $this->questions[$question['qid']]){ 
                            break; 
                        }
                    }
                }
                
                $this->addAdditional(array(
                    "gateway"       => "authnet",
                    "FIRSTNAME"     => $answer["cc_firstName"],
                    "LASTNAME"      => $answer["cc_lastName"],
                    "EMAIL"         => $emailField,
                    // "SHIPTONAME"    => "", # We don't have this name so it should not be included in the address
                    "SHIPTOSTREET"  => $answer["addr_line1"]." ".$answer["addr_line2"],
                    "SHIPTOCITY"    => $answer["city"],
                    "SHIPTOSTATE"   => $answer["state"],
                    "SHIPTOZIP"     => $answer["postal"],
                    "SHIPTOCOUNTRYNAME" => $answer["country"]
                ));
                
                $this->payment->setBillingInfo($answer["cc_firstName"], $answer["cc_lastName"], $answer["addr_line1"]." ".$answer["addr_line2"], $answer["city"], $answer["state"], $answer["country"], $answer["postal"]);
                $this->payment->transact();
            break;
            case "control_paypalpro":
            	
            	$this->payment = new PayPalPro(array(
                    "formID"      => $this->formID,
                    "sid"         => $this->sid,
                    "apiUsername" => $this->paymentField["username"],
                    "apiPassword" => $this->paymentField["password"],
                    "signature"   => $this->paymentField["signature"],
                    "paymentMethod" => $answer["paymentType"],
                    "paymentType"   => $this->paymentField["paymentType"],
                    "currency"      => $this->paymentField["currency"]
                ));
                
                $this->payment->setProducts($this->productsToBuy);
                
                # Search all questions to match an email address then adds it into the billing info
                # So that form owner can see this address in the transaction details
                $emailField = false;
                foreach($this->formQuestions as $question){
                    if(preg_match("/email|mail|e\-mail/i", $question['text'])){
                        # If there is en email on this location then stop searching
                        if($emailField = $this->questions[$question['qid']]){ 
                            break; 
                        }
                    }
                }
                
                if($answer["paymentType"] == "express"){
                    $token = $this->payment->setExpressCheckout();
                    $this->stopSubmission('PAYMENT', $token);
                    # => may be a we can add email here but on instant payments email already shown to the merchant
                    $this->payment->goExpressCheckout($token);
                }else{
                    $this->payment->setCreditCard($answer["cc_number"], $answer["cc_exp_month"], $answer["cc_exp_year"], $answer["cc_ccv"]);
                    $this->addAdditional(array(
                        "gateway"       => "paypalpro",
                        "FIRSTNAME"     => $answer["cc_firstName"],
                        "LASTNAME"      => $answer["cc_lastName"],
                        "EMAIL"         => $emailField,
                        // "SHIPTONAME"    => "", # We don't have this name so it should not be included in the address
                        "SHIPTOSTREET"  => $answer["addr_line1"]." ".$answer["addr_line2"],
                        "SHIPTOCITY"    => $answer["city"],
                        "SHIPTOSTATE"   => $answer["state"],
                        "SHIPTOZIP"     => $answer["postal"],
                        "SHIPTOCOUNTRYNAME" => $answer["country"]
                    ));
                    
                    $this->payment->setBillingInfo($answer["cc_firstName"], $answer["cc_lastName"], $answer["addr_line1"]." ".$answer["addr_line2"], $answer["city"], $answer["state"], $answer["country"], $answer["postal"], $emailField);
                    $this->payment->transact();
                }
            break;
            case "control_googleco":
                # Make redirects pending and complete after payment is done
                $this->handleRedirects(true);
                
                $this->payment = new GoogleCheckout(array(
                    "formID"        => $this->formID,
                    "sid"           => $this->sid,
                    "merchantID"    => $this->paymentField["merchantID"],
                    "paymentMethod" => $answer["paymentType"],
                    "paymentType"   => $this->paymentField["paymentType"],
                    "currency"      => $this->paymentField["currency"],
                    "returnURL"     => $returnURL // Done
                ));
                
                $this->payment->setProducts($this->productsToBuy);
                $this->stopSubmission('PAYMENT');
                $this->payment->transact();
                
            break;
            case "control_worldpay":
            	$this->payment = new WorldPay(array(
                    "formID"        => $this->formID,
                    "sid"           => $this->sid,
                    "installationID" => $this->paymentField["installationID"],
                    "paymentMethod"  => $answer["paymentType"],
                    "paymentType"    => $this->paymentField["paymentType"],
                    "currency"       => $this->paymentField["currency"],
                    "returnURL"      => $returnURL // There seems to be no way of setting this URL, so I'm leaveing as it is.
                ));
                $this->payment->setProducts($this->productsToBuy);
                $this->stopSubmission('PAYMENT');
                $this->payment->transact();                
            break;
            case "control_2co":
            	# Make redirects pending and complete after payment is done
                $this->handleRedirects(true);
                
            	$this->payment = new TwoCheckOut(array(
                    "formID"        => $this->formID,
                    "sid"           => $this->sid,
                    "vendorNumber"  => $this->paymentField["vendorNumber"],
                    "paymentType"   => $this->paymentField["paymentType"],
                    "currency"      => $this->paymentField["currency"],                    
                    "returnURL"     => $returnURL 
                ));
                
                $this->payment->setProducts($this->productsToBuy);
                # $this->complete(true); # No redirect
                $this->stopSubmission('PAYMENT');
                $this->payment->transact();
            break;
            case "control_clickbank":
            	$this->payment = new ClickBank(array(
                    "formID"        => $this->formID,
                    "sid"           => $this->sid,
                    "login"         => $this->paymentField["login"],
                    "itemNumber"    => $this->paymentField["itemNo"],
                    "productName"   => $this->paymentField["productName"],
                    "productPrice"  => $this->paymentField["productPrice"],
                    "paymentType"   => $this->paymentField["paymentType"],
                    "currency"      => $this->paymentField["currency"],
                ));
                
                $this->stopSubmission('PAYMENT');
                $this->payment->transact();
            break;
            case "control_onebip":
            	# Make redirects pending and complete after payment is done
                $this->handleRedirects(true);
                
            	$this->payment = new OneBip(array(
                    "formID"        => $this->formID,
                    "sid"           => $this->sid,
                    "username"      => $this->paymentField["username"],
                    "itemNumber"    => $this->paymentField["itemNo"],
                    "productName"   => $this->paymentField["productName"],
                    "productPrice"  => $this->paymentField["productPrice"],
                    "paymentType"   => $this->paymentField["paymentType"],
                    "currency"      => $this->paymentField["currency"],
                    "returnURL"     => $returnURL // Done.
                ));
                
                $this->complete(true); # No redirect
                $this->payment->transact();
            break;
        }
    }
    
    /**
     * Handle Emails, send them according to user settings, such as conditions
     * @return 
     */
    private function sendEmails(){
        $conditions = $this->getConditions('email');
        
        $conds = array();
        if($conditions){
            $condMatchedEmails = array();
            foreach($conditions as $cond){
                if($this->checkCondition($cond)){
                    $conds[] = $cond['action'];
                    $condMatchedEmails[$cond['action']['email']] = "";
                    unset($cond['action']['disable']);
                }else{
                    // Since we have disabled the disable email button on the wizard
                    // This is a mendetory and ugly hack to make email conditions work
                    if(!isset($condMatchedEmails[$cond['action']['email']])){
                        $cond['action']['disable'] = 1;
                        $conds[] = $cond['action'];
                    }
                }
            }
        }
        
        $emails = new FormEmails(array(
            "conditions"    => $conds,
            "submission"    => $this
        ));
        
	if( $this->isEdit ){
		$emails->sendEmails( true );
	} else {
		$emails->sendEmails();
	}
    }
    
    /**
     * Updates the forms table for submission counts
     * @return null
     */
    private function updateFormStats(){
        if($this->form->form['count'] < 0){
            Form::updateSubmissionCount($this->formID);
            
            if($this->form->form['new'] < 0){
                Form::updateNewSubmissionCount($this->formID);
            }
            
            $response = DB::write("UPDATE `forms` SET `new`=`new`+1 WHERE `id`=#id", $this->formID);
        }else{
            $response = DB::write("UPDATE `forms` SET `count`=`count`+1, `new`=`new`+1 WHERE `id`=#id", $this->formID);
        }
    }
    
    /**
     * Generates a simulative post data
     * @return 
     */
    private function createPostData(){
        $postData = array(
            "submission_id"=>$this->sid,
            "formID" => $this->formID,
            "ip" => $this->ip
        );
        
        foreach($this->questionNames as $name => $qid){
            if(isset($this->paymentField['qid']) && $this->paymentField['qid'] == $qid){
                $postData[$name] = $this->productsToBuy;
            }else{
                $postData[$name] = $this->questions[$qid];
            }
        }
        
        return $postData;
    }
    
    /**
     * Get the conditions for this form by type
     * @param array|string $type [optional] give condition type or array of types. if not provided than 
     * @return 
     */
    public function getConditions($type = false){
        $conditions = $this->getFormProperty('conditions');
        if(!is_array($conditions)){return array(); }
        $result = array();
        foreach($conditions as $condition){
            
            if(!$type && in_array($condition['type'], array('email', 'url', 'message'))){
                $result[] = $condition;
                continue;
            }
            
            if(is_array($type)){
                if(in_array($condition['type'], $type)){
                    $result[] = $condition;
                    continue;
                }                
            }
            
            if($type == $condition['type']){
                $result[] = $condition;                
            }
        }
        
        return $result;
    }
    
    /**
     * Checks the rule according to given operator
     * @param  $operator
     * @param  $field
     * @param  $value
     * @return 
     */
    public function checkTerm($operator, $id, $value){
        # If field is not send then we cannot check for conditions
        if(!isset($this->questions[$id])){ 
            $this->questions[$id] = "";
        }
        $value = html_entity_decode(trim(''.$value), ENT_COMPAT, 'UTF-8');
        
        if(is_string($this->questions[$id])){
            $field = html_entity_decode(trim(''.$this->questions[$id]), ENT_COMPAT, 'UTF-8');
        }else{
            $field = $this->questions[$id];
        }
                
        switch ($operator) {
            case "equals":
            	if(is_array($field)){ return in_array($value, $field); }
                return $field == $value;
            case "notEquals":
            	if(is_array($field)){ return !in_array($value, $field); }
                return $field != $value;
            case "endsWith":
                return Utils::endsWith($field, $value);
            case "startsWith":
                return Utils::startsWith($field, $value);
            case "contains":
                return strstr($field, $value);
            case "notContains":
                return !strstr($field, $value);
            case "greaterThan":
                return ((float) $field) > ((float) $value);
            case "lassThan":
                return ((float) $field) < ((float) $value);
            case "isEmpty":
                if(is_array($field)){
                    $field = join("", array_values($field));
                    return empty($field);
                }
                return empty($field);
            case "isFilled":
                if(is_array($field)){
                    $field = join("", array_values($field));
                    return !empty($field);
                }
                return !empty($field);
            case "before":
            	$v = strtotime($value);
            	$f = strtotime($field['year']."-".$field['month']."-".$field['day']);
            	return $v > $f;
            case "after":
            	$v = strtotime($value);
                $f = strtotime($field['year']."-".$field['month']."-".$field['day']);
                return $v < $f;
        }
    }
    
    /**
     * Checks the condition as a whole, checks all terms the checks their link
     * @param  $cond
     * @return 
     */
    public function checkCondition($cond){
        
        $any = false;
        $all = true;
        
        foreach($cond['terms'] as $term){
            if($this->checkTerm($term['operator'], $term['field'], $term['value'])){
                $any = true;
            }else{
                $all = false;
            }
        }
        
        if((strtolower($cond['link']) == 'any' && $any) || (strtolower($cond['link']) == 'all' && $all)){
            return true;
        }
        return false;
    }
    
    
    /**
     * Redirects the user after submission to a specified place.
     * @param  $store  // [optional] if true stores redirect parameters instead of applying
     * @return 
     */
    private function handleRedirects($store = false){
        
        if($this->isEdit && $this->isInline){
            Utils::redirect(HTTP_URL."opt/editComplete.php", array(
                "sid" => $this->sid
            ));
            return;
        }
        
        if($store){
            $store = $this;
        }
        
        $thankyou = $this->form->getThankyouPage();
        $conditions = $this->getConditions(array('url', 'message'));
        
        if($conditions){
            foreach($conditions as $cond){
                if($this->checkCondition($cond)){
                    if($cond['type'] == 'message'){
                        $thankyou['activeRedirect'] = 'thanktext';
                        $thankyou['thanktext'] = $cond['action']['message'];
                    }else if($cond['type'] == 'url'){
                        $thankyou['activeRedirect'] = 'thankurl';
                        $thankyou['thankurl'] = $cond['action']['redirect'];
                    }
                }
            }
        }
        
        switch($thankyou['activeRedirect']){
            case "thankurl":
            	$url = Utils::fixHTTP($thankyou['thankurl']);
            	if($thankyou['sendpostdata'] == 'Yes' && stristr($url, '.htm') === FALSE){
            	    self::doRedirect('POSTREDIRECT', $url, $this->createPostData(), $store);
            	}else{
            	    self::doRedirect('REDIRECT', $url, false, $store);
            	}
            break;
            case "thanktext":
            	ob_clean();
                $text = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"><html><head>
                        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                        <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" />
                        <meta name="HandheldFriendly" content="true" /><title>Thank You</title>'.
                        '<style>body{ font-size:12px; font-family:Verdana; }</style></head><body>';
                
                $parser = new FormEmails(array(
                    "submission"=> $this,
                    "parser"    => true
                ));
                
                # Fix stupid v2 bug
                # Convert all nbsp to regular spaces
                $ttext = str_replace("&nbsp;", " ", $thankyou['thanktext']);
                # Strip All slashes from escaped content
                $ttext = stripslashes($ttext);
                # Convert all decoded HTML codes
                $ttext = html_entity_decode($ttext, ENT_COMPAT, 'UTF-8');
                # Do it again because in old code it was done twice.
                $ttext = html_entity_decode($ttext, ENT_COMPAT, 'UTF-8');
                
                # Parse all email tags on the thank you page
                $text .= $parser->parseTags($ttext);
                
                $text .= '</body></html>';
                self::doRedirect("TEXT", $text, false, $store);
        	break;
            default:
                self::doRedirect("DEFAULT", false, false, $store);
        }
    }
    
    /**
     * Completes the redirect action
     * @param  $type  // Type of the redirect action
     * @param  $value  // value of the redirect such as URL or text
     * @param  $params  // [optional] paramaters for URL
     * @param  $store  // [optional] if provided redirect action will be saved in pending table 
     * @return 
     */
    public static function doRedirect($type, $value="", $params = array(), $store = false){
        
        if(self::$isSlowDownForm !== false){
            $serializedArgs = Utils::serialize(func_get_args());
            $redis = Utils::getRedis();
            if($redis !== false){
                $redis->set(self::$isSlowDownForm.'-thankyou', $serializedArgs);
            }
        }
        
        # save this redirect to database
        if($store){
            # if there are parameters convert it to JSON then save options
            if($params){
                $value = json_encode(array("url" => $value, "parameters" => $params));
            }
            # Put redirect options on databas
            DB::insert('pending_redirects', array(
                "form_id" => $store->formID,
                "submission_id" => $store->sid,
                "type" => $type,
                "value" => $value
            ));
            return; # Return after saving redirects
        }
        
        # Do the regular redirects
        switch($type){
            case "POSTREDIRECT":
                Utils::postRedirect($value, $params);
            break;
            case "REDIRECT":
            	Utils::redirect($value, false, true); # Bust Frames
            break;
            case "TEXT":
            	echo $value;
        	break;
            default:
                include ROOT."thankyou.html";
        }
        exit;
    }
    
    /**
     * Completes the pending redirect action
     * @param  $submissionID
     * @return 
     */
    public static function completeRedirect($submissionID){
        $res = DB::read("SELECT * FROM `pending_redirects` WHERE `submission_id`=':id'", $submissionID);
        if($res->rows > 0){
            # Decided not to delete this for one day
            # DB::write("DELETE FROM `pending_redirects` WHERE `submission_id`=':id'", $submissionID);
            $params = false;
            $value = $res->first['value'];
            
            if($res->first['type'] == 'POSTREDIRECT'){
                $dbval = json_decode($value);
                $value = $dbval->url;
                $params = $dbval->parameters;
            }
            self::doRedirect($res->first['type'], $value, $params);
        }else{
            self::doRedirect("DEFAULT", "");
        }
    } 
    
    /**
     * Collection of all functions, also increments usages.
     * @return 
     */
    public function submit(){
        # display every detail of a submission
        if(Utils::debugOption('stopSubmission')){
            $this->debug();
        }
        
        # Check form status
        if(!$this->form->checkStatus() && !$this->isEdit){
            Utils::errorPage("This form has been disabled", "Form Disabled", "Form did not pass status check");
        }
        
        if ($this->owner->status == 'OVERLIMIT') {
            Utils::errorPage("This form has passed its allocated quota <br>and cannot be used at the moment. <br><br>Try contacting the owner of this form.", "Form over quota");
        }
        
        # Check if the form has unique submission setting
        $this->handleUniqueSubmissions();
        
        # Upload files
        $this->handleUploads();
        
        # If sesssion ID was sent, then save this form as a pending submission
        if($this->sessionID !== false){
            if($this->shouldStop){
                $this->stopSubmission('SAVED', "", $this->sessionID);
                die("Completed: ".$this->sid);
            }else{
                DB::write("DELETE FROM `pending_submissions` WHERE `form_id`=#formID AND `submission_id`=':sid' AND `session_id`=':sessid'", $this->formID, $this->sid, $this->sessionID);
            }
        }
        
        # Check simple spam-check  
        if( ! $this->simpleSpamCheck()){
            //Utils::errorPage("Spam check has failed, Make sure you have Javascript enabled.".$this->goBackMessage, "Submission Error", "No Javascript or Spam bot");
            $this->stopSubmission('SPAMCHECK');
            Captcha::printCaptchaPage($this->sid);
        }
        
        # Check captcha and do server side validations        
        if($this->formHasCaptcha()){
            
            if(isset($this->request["recaptcha_challenge_field"])){
                $challenge = isset($this->request["anum"])? $this->request["anum"] : $this->request["recaptcha_challenge_field"];
                $response  = isset($this->request["qCap"])? $this->request["qCap"] : $this->request["recaptcha_response_field"];
                if(!Captcha::checkReCaptcha($challenge, $response)){
                    $this->stopSubmission('CAPTCHA');
                    Captcha::printCaptchaPage($this->sid);
                }
            }else{
                if(!isset($this->request['captcha']) || !Captcha::checkWord($this->request['captcha'], $this->request['captcha_id'])){
                    $this->stopSubmission('CAPTCHA');
                    Captcha::printCaptchaPage($this->sid);
                }
            }
            
        }
        
        # continue submission after validating captcha
        $this->captchaContinue();
    }
    
    /**
     * 
     * @return 
     */
    public function captchaContinue(){
        # Handle payments
        $this->handlePayments();
        
        # Complete submission
        $this->complete();
    }
    
    /**
     * Sets the no redirect value
     */
    public function setNoRedirect($value = true){
      $this->noRedirect = $value;
    }
    
    
    /**
     * Completes the rest of the submission
     * @param  $noRedirect  // [optional] If set to true then skips the redirect process, only saves the submission and sends the emails
     * @return 
     */
    public function complete($noRedirect = false){
        
    	$this->monthlyUsage->incrementUsage();
        
        if(isset($this->payment) && isset($this->payment->paymentMethod) && $this->payment->paymentMethod == "express"){
            $details = $this->payment->getExpressCheckoutDetails(); # First of all get the details of this payment to be used in the submissions
            $this->addAdditional($details);
            $this->payment->doExpressCheckoutPayment();
        }
        
        if(isset($this->payment)){
            $this->monthlyUsage->incrementUsage('payments');
        }

        if($this->isEdit){
            $this->updateData();
        }else{
            # Insert submission data on database
            $this->insertData();
        }
        
        $this->sendPDFToIntegrations();
        
        # Send e-mails related with monthly usage quotas.
        if(!$this->isEdit){
            $this->monthlyUsage->sendEmails();
        }
        
        # Increment submission or SSL submission
        if(IS_SECURE){
            $this->monthlyUsage->incrementUsage('sslSubmissions');
        }
        
        # Increse the submission count and new count on the form table
        if(!$this->isEdit){
            $this->updateFormStats();
        }
        
        # Send notification and auto-response emails 
	    # People want to receive the notification emails after form edit, 
        # so this is enabled back.
        #if(!$this->isEdit){
            $this->sendEmails();
        #}
        
        # Send iphone notification
        if(!$this->isEdit){
        	$this->sendIPhoneNotification();
        }
        
        # Return before redirect
        if($noRedirect || $this->noRedirect){ return; }
        # Redirect and complete submit
        $this->handleRedirects();
    }
    
    /**
     * Check if PDF was generated before
     * if true sends the current PDF path
     * if false generates a PDF then sends the path
     * @return 
     */
    private function getPDF(){
        $localFile = "/tmp/".$this->sid.".pdf";
        if(!file_exists($localFile)){
            $server = str_replace("https:", "http:", HTTP_URL); # make sure you PDF always requested from http site
            
            if(Server::isHost(array('yang', '10.202.1.216'))){
                $server = "http://monk.interlogy.com";
            }
                        
            $pdf = file_get_contents($server."/server.php?action=getSubmissionPDF&sid=".$this->sid);
            file_put_contents($localFile, $pdf);
        }
        return $localFile;
    }
    
    /**
     * Gets all integrations for this form
     * @return 
     */
    private function getIntegrations(){
        $res = DB::read("SELECT DISTINCT `partner` FROM `integrations` WHERE `username`=':username' AND `form_id`=#formID", $this->owner->username, $this->formID);
        $integrations = array();
        if($res->rows > 0){
            foreach($res->result as $line){
                $integrations[] = $line['partner'];
            }            
        }
        return $integrations;
    }
    
    /**
     * Converts current submission to PDf and sends it to all integrated services
     * @return 
     */
    private function sendPDFToIntegrations(){
        try{
            $integrations = $this->getIntegrations();
            # if user has dropbox integration upload PDF version of this submission to dropbox
            if(DROPBOX_AVAILABLE && in_array("dropbox", $integrations)){
                $dropbox = new DropBoxIntegration($this->owner->username, $this->formID); 
                if($dropbox->hasIntegration()){
                    # generate PDF file and get the local path
                    $localFile = $this->getPDF();
                    # This section should exactly be the same as Dropboxintegrations.
                    $field  = $dropbox->config->getValue("folder_field");
                    # get the folder name for upload
                    $folder = $dropbox->createFolderName($field, $this);
                    # Create the PDF name from folder name
                    $pdfName = Utils::fixUploadName($folder);
                    # if folder is not found then use SID
                    if($folder == "/"){
                        $pdfName = $this->sid."-"."submission";
                    }
                    # create remote path 
                    $remoteFile = "JotForm/".$this->form->getTitle()."/".$folder."/".$pdfName.".pdf";
                    # upload file
                    $dropbox->sendFile($remoteFile, $localFile);
                }
            }
            
            if(in_array("FTP", $integrations)){
                $ftp = new FTPIntegration($this->owner->username, $this->formID); 
                if($ftp->hasIntegration()){
                    # generate PDF file and get the local path
                    $localFile = $this->getPDF();
                    # This section should exactly be the same as Dropboxintegrations.
                    $field  = $ftp->config->getValue("folder_field");
                    # get the folder name for upload
                    $folder = $ftp->createFolderName($field, $this);
                    # Create the PDF name from folder name
                    $pdfName = Utils::fixUploadName($folder);
                    # if folder is not found then use SID
                    if($folder == "/"){
                        $pdfName = $this->sid."-"."submission";
                    }
                    # create remote path 
                    $remoteFile = Utils::path($ftp->config->getValue('path')."/".$this->form->getTitle()."/".$folder."/".$pdfName.".pdf", true);
                    # upload file
                    $ftp->sendFile($remoteFile, $localFile);
                }
            }
        }catch(Exception $e){
            Console::error($e->getMessage());
        }
    }
    
    /**
     * Sends apush notification for IPhones
     * @return 
     */
    private function sendIPhoneNotification(){
        if(APP){ return; }
        # send iphone notification---------------------------------------
        require_once(ROOT . "/api/iphone/api_functions.php");
        send_iphone_notification($this->owner->username, $this->form->form['title'], $this->sid);
        # ---------------------------------------------------------------
    }
}
