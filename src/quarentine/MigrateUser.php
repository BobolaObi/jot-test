<?php



/**
 * Migrates a user from old database to new database
 * @package JotForm
 * @copyright Copyright (c) 2009, Interlogy LLC
 */


class MigrateUser{
    /**
     * Username of the migration user
     * @var string
     */
    private $username;
    /**
     * complete information of the user from database, this array also contains monthly_usage information
     * @var array
     */
    private $user;
    /**
     * Array of all forms of this user, this array contains every data about forms such as listing, submissions, question properties etc
     * @var array
     */
    private $forms;
    /**
     * List of the formIDs this user has
     * @var array
     */
    private $formIDList = array();
    /**
     * Defines if a prefix should be added to migrated user account or not
     * @var boolean
     */
    private $addPrefix;
    
    /**
     * Products used in this form
     * @var Array
     */
    private $productIDs;
    /**
     * @var \Legacy\Jot\A global value to keep current form who's emails are parsing
     */
    public $currentReplaceForm;    
    
    /**
     * If merge is specified then last migration data will be kept here
     * @var \Legacy\Jot\MySql DateTime
     */
    public $lastMigrationDate;
    
    /**
     * When we started the migration
     * @var \Legacy\Jot\Mysql DateTime
     */
    public $migrationStartDate;
    
    /**
     * This submission ids errored and must be skipped
     * @var \Legacy\Jot\submission id
     */
    public $erroredSubmission = array();
    
    /**
     * if set we will skip already migrated accounts
     * @var
     */
    public $skipMigrated;
    
    /**
     * @constructor
     * @param object $username
     * @return 
     */
    public function __construct($username, $merge = true, $addPrefix = false, $skipMigrated = false){
        //Console::log("Migration Started for: $username", "Migration");
        $this->username = $username;
        
        $this->skipMigrated = $skipMigrated;
        
        $this->migrationStartDate = date("Y-m-d H:i:s");
        
        $this->addPrefix = $addPrefix;
        
        # Get the last migration data of the user from V3 database
        if($merge){
            $this->getLastMigrationDate();
        }
        
        # Connect to old database first
        \Legacy\Jot\DB::useConnection('main');
        
        # Read user data from old database
        if(!$this->getUser()){  # If fiven Username is wrong
            throw new \Legacy\Jot\RecordNotFoundException( \Legacy\Jot\JotErrors::$MIGRATION_USER_NOT_FOUND );
        }
        
        # Collect all form information from old database
        $this->getForms();
    }
    /**
     * @destructor
     * @return 
     */
    public function __destruct(){
        
        unset($this->username);
        unset($this->user);
        unset($this->forms);
        unset($this->formIDList);
        unset($this->addPrefix);
        unset($this->productIDs);
        unset($this->currentReplaceForm);    
        unset($this->lastMigrationDate);
        unset($this->migrationStartDate);
        unset($this->erroredSubmission);
        unset($this->skipMigrated);
        
    }
    
    /**
     * Read users last migration date from new database
     * @return 
     */
    public function getLastMigrationDate(){
        # Make sure we are on the new database
        \Legacy\Jot\DB::useConnection('new');
        $username = $this->username;
        
        if($this->addPrefix){
            $username = "migrated_".$username;
        }
        
        $userResult = \Legacy\Jot\DB::read("SELECT `last_migration` FROM `users` WHERE `username` = ':username'", $username);
        
        if($userResult->rows < 1 || (isset($userResult->first['last_migration']) && \Legacy\Jot\Utils::startsWith($userResult->first['last_migration'], "0000"))){
            # User seems to be not migrated yet so ignore merge property and do regular migration
            //Console::log("User was not migrated before", "Migration");
            return;
        }
        
        if($this->skipMigrated){
            throw new \Legacy\Jot\Exception('User was migrated before');
        }
        
        $this->lastMigrationDate = $userResult->first['last_migration'];
        //Console::log("Last migation date was: ".$this->lastMigrationDate, "Migration");
    }
    
    /**
     * Set user as migrated
     * @return 
     */
    public function setLastMigrationDate(){
        # Make sure we are on the new database
        \Legacy\Jot\DB::useConnection('new');
        
        $username = $this->username;
        if($this->addPrefix){
            $username = "migrated_".$username;
        }
        
        \Legacy\Jot\DB::write("UPDATE `users` SET `last_migration`=':startDate' WHERE `username` = ':username'", $this->migrationStartDate, $username);
    }
    
    /**
     * Find user on old database
     * @return 
     */
    public function getUser(){
        \Legacy\Jot\Console::log('Get user started ' . \Legacy\Jot\Utils::bytesToHuman(memory_get_usage()));
        $res = \Legacy\Jot\DB::read("SELECT * FROM `users` WHERE `username`=':username' LIMIT 1", $this->username);
        
        if($res->rows < 1){ # IF user not found
            return false;
        }
        
        # Also collect the monthly_usage information
        $this->user = $res->first;
        $res = \Legacy\Jot\DB::read("SELECT * FROM `monthly_usage` WHERE `username`=':username' LIMIT 1", $this->username);
        $this->user['monthly_usage'] = $res->first;
        
        if($this->addPrefix){
            $this->user["username"] = "migrated_" . $this->user["username"];
        }
        unset($res);
        \Legacy\Jot\Console::log('Get user ended ' . \Legacy\Jot\Utils::bytesToHuman(memory_get_usage()));
        return true;
    }
    
    /**
     * Create Default Email
     * @param object $formID
     * @return 
     */
    public function createDefaultEmail($formID, $isHTML = true){
        
        $allowed = explode(",", "control_textbox,control_textarea,control_dropdown,control_autocomp,control_datetimepicker,control_birthdate,control_radio,control_checkbox,control_checkbox,control_fileupload,control_passwordbox,control_rating");
        $props = $this->forms[$formID]["properties"];
        unset($props["form"]);
        
        foreach($props as $prop){
            $sortedProp[$prop["order"]] = $prop;
        }
        if(!is_array($sortedProp)){ $sortedProp = array(); }
        
        ksort($sortedProp);
        
        if(!$isHTML){
            $def = "\n\n";
            foreach($sortedProp as $prop){
                if(!in_array($prop["type"], $allowed)){ continue; }
                
                $def .= $prop['text']."\n";
                $def .= "{".$prop['name']."}\n\n";
            }
            return $def;
        }
        
        
        $def  = '<div style="height:100%; width:100%; background:#999; min-height:300px; display:inline-block;">';
        $def .= '<div style="margin:50px auto; border:1px solid #666; background:#fff;-moz-box-shadow:0 0 11px rgba(0,0,0,0.5);-webkit-box-shadow:0 0 11px rgba(0,0,0,0.5); width:530px">';
        $def .= '<div style="background:none repeat scroll 0 0 #454545;border-bottom:1px solid #222;font-size:14px;color:#fff; height:34px;">';
        $def .= '<a href="'.HTTP_URL.'">';
        $def .= '<img src="'.HTTP_URL.'images/logo-small.png" align="right" border="0" />';
        $def .= '</a>';
        $def .= '</div>';
        
        $def .= '<div style="list-style:none;">';
        
        foreach($sortedProp as $prop){
            
            if(!in_array($prop["type"], $allowed)){ continue; }
            
            $def .= '<li id="email-list-item-'.$prop["qid"].'" style="margin:10px;"><div style="display:inline-block; width:100%;">';
            
            $def .= '<div style="font-weight:bold;float:left;clear:left;width:180px;">'.$prop['text'].'</div>';
            
            
            $def .= '<div>{'.$prop['name'].'}</div>';
            $def .= '</div></li>';
        }
        
        $def .= '</div>';
        $def .= '<div style="background:#454545;border-top:1px solid #222;font-size:12px;padding:10px; color:#fff; text-align:right">';
        $def .= '<a href="'.HTTP_URL.'" style="color:#fff">';
        $def .= 'View this submission on JotForm';
        $def .= '</a>';
        $def .= '</div>';
        $def .= '</div></div>';
        
        return $def;
    }
    
    /**
     * Will change all tags to new ones
     * @param object $emailStr
     * @param object $formID
     * @return 
     */
    public function parseTags($emailStr, $formID) {
        $this->currentReplaceForm = $formID;
        $parsed = preg_replace_callback("/\{([^\n\{\}]+)\}/m", array($this, "replaceTags"), $emailStr);
        return $parsed;
    }
    
    /**
     * Replaces the tag with the user answer
     * @param object $tag
     * @return 
     */
    private function replaceTags($match){
        $tag = $match[1];
        if (strtolower($tag) == "qform title") {
            return "{form_title}";
        }
        
        preg_match("/q(\d+)_/", $tag, $matches);
        $qid = $matches[1];
        if(empty($qid) && $qid != "0"){ return $tag; }
        
        $formID = $this->currentReplaceForm;
        $qname = $this->forms[$formID]['properties'][$qid]['name'];

        return "{".$qname."}";
    }
    
    /**
     * Creates a question name for given question
     * @param object $qid
     * @param object $formID
     * @return 
     */
    public function createQuestionName($qid, $formID){
        # See if there's another question with the same name.
        $qLabel = \Legacy\Jot\Utils::fixUTF($this->forms[$formID]['properties'][$qid]['text']);
        $tokens = preg_split("/\s+/", $qLabel);
        
        $qName = ($tokens[1]) ? ( strtolower($tokens[0]) . ucfirst(strtolower($tokens[1])) ) : strtolower($tokens[0]);
        $qName = preg_replace("/\W/", "", $qName);
        
        if(empty($qName)){ $qName = $qid; }
        $hasSame = false;
        
        foreach($this->forms[$formID]['properties'] as $questionID => $prop){
            if(isset($prop["name"]) && ($prop["name"] == $qName)){
                $hasSame = true;
            }
        }
        if($hasSame){
            return $qName.$qid;
        }
        return $qName;
    }
    
    /**
     * Converts the emails then add to the form properties
     * @TODO create default email template and replace all tags with the new ones
     * @param object $formProperties
     * @return 
     */
    public function convertEmails($formProperties, $formID){
        
        $emails = array();
        
        # If notifications set to yes then create the default email
        if(strtolower($formProperties["email"]) == "yes"){
            $isHTML = strtolower($this->user["is_html"]) === "no"? false : true;
            
            $emails[] = array(
                "type"      => "notification",                                                  # Type of the email
                "name"      => "Notification",                                                  # Email Name
                "from"      => $this->parseTags("{".$formProperties["not_reply"]."}", $formID), # From address, In old version these field does not contain template tags, so we add them manually
                "to"        => $formProperties["email_addr"],                                   # To address
                "subject"   => $this->parseTags($formProperties["not_subj"], $formID),          # Subject of the email
                "disabled"  => false,                                                           # Set if the email is disabled or not
                "html"      => $isHTML,                                                         # Set HTML status
                "body"      => $this->createDefaultEmail($formID, $isHTML)                      # Put email body here
            );
        }
        
        # If confirmation emails is set to yes then create an autoresponder
        if(strtolower($formProperties["conf_send"]) == "yes"){
            $emails[] = array(
                "type"      => "autorespond",                                                   # Type of the email
                "name"      => "Auto Respond",                                                  # Email Name
                "from"      => $formProperties["conf_from"],                                    # From address
                "to"        => $this->parseTags("{".$formProperties["conf_to"]."}", $formID),   # To address, In old version these field does not contain template tags, so we add them manually
                "subject"   => $this->parseTags($formProperties["conf_subj"], $formID),         # Subject of the email
                "disabled"  => false,                                                           # Set if the email is disabled or not
                "html"      => false,                                                           # Set HTML status
                "body"      => $this->parseTags($formProperties["conf_body"], $formID)          # Body of the confirmation
            );
        }
        # Collect all email together
        $formProperties["emails"] = $emails;
        
        return $formProperties;
    }
    
    
    /**
     * Will convert the old properties to new ones
     * @param object $properties
     * @return 
     */
    public function convertProperties($properties, $formID){
        
        /**
         * @var Array containing deleted properties
         */
        $deletedOnes = array(
            "spamcheck",
            "conf_body",
            "conf_from",
            "conf_send",
            "conf_to",
            "conf_subj",
            "not_reply",
            "not_subj",
            "email_addr",
            "email",
            "item1",
            "period",
            "pids",
            "price",
            "setup",
            "trial",
            "2co_productId",
            "2co_lang",
            "2co_productName",
            "2co_productPrice",
            "2co_setupFee"
        );
         
        /**
         * @var \Legacy\Jot\Associated array containing conversion of style names
         */
        $styleNames = array(
            "Default" => "form",
            "BabyBlue" => "baby_blue",
            "IndustrialDark" => "industrial_dark",
            "JotTheme" => "jottheme",
            "PaperGery" => "paper_grey",
            "PostItYellow" => "post_it_yellow"
        );
        /**
         * @var \Legacy\Jot\Associated array contains conversion of the property names
         */
        $newOnes = array(
            "desc"    => "description",
            "theme"   => "styles",
            "submittext" => "text",
            "html"    => "text",
            "curr"    => "currency",
            "pType"   => "paymentType",
            "tot"     => "showTotal",
            "onebip_username" => "username",
            "onebip_itemid"   => "itemNo",
            "onebip_productname"  => "productName",
            "onebip_productprice" => "productPrice",
            "onebip_curr" => "currency",
            "cb_login"    => "login",
            "cb_itemNo"   => "itemNo",
            "cb_productName"  => "productName",
            "cb_productPrice" => "productPrice",
            "gco_merchantId"  => "merchantID",
            "wp_instId"       => "installationID",
            "2co_vendorNo"    => "vendorNumber"            
        );
        
        /**
         * @var \Legacy\Jot\These values must be converted to first letter capital, in order to make them work in v3
         */
        $ucFirstTexts = array("yes", "no", "true", "false", "none", "left", "center", "right");
        
        # New array to collect properties
        $newProp = array();
        
        foreach($properties as $key => $value){
            
            if(strtolower($key) == "pids"){
                $this->productIDs[$formID] = explode(":", $value);
            }
            
            # Skip the property if it is deleted in the new version
            if(in_array($key, $deletedOnes)){ continue; }
            
            if($key == "text" && $value == "undefined" && array_key_exists("html", $properties)){
                continue; # If value is undefined then skip this property
            }
            
            if($key == "text" && $value == "undefined"){
                $value = "";
            }
            // UPDATE `question_properties` SET `value`='Yes' WHERE `prop`='showTotal' AND value = 'True'
            if($key == "text" && empty($value) && array_key_exists("submittext", $properties)){
                continue; # text property is not used in buttons
            }
            
            if($key == "html"){
                $value = html_entity_decode($value); # HTML values in new version arent decoded
            }
            
            if($key == "maxsize" && array_key_exists("extensions", $properties)){
                $key = "maxFileSize";   # convert max size field name, it used to be the same as input fields
            }
            
            if($key == "validation" && strtolower($value) == "no"){
                $value = "None";
            }
            
            if($key == "showTotal" && strtolower($value) == "true"){
                $value = "Yes";
            }else if($key == "showTotal"){
                $value = "No";
            }
            
            # Convert the property name name if it is changed in the new version
            if(array_key_exists($key, $newOnes)){ $key = $newOnes[$key]; }
            
            if(is_string($value)){
                # Make sure these values are written in correct format 
                if(in_array(strtolower($value), $ucFirstTexts)){
                    $value = ucfirst(strtolower($value));
                }
            }
            
            # Change the option splitter
            if($key == 'options' || $key == 'items'){
                $value = str_replace("<br>", "|", $value);      # convert delimiters to new ones
                $value = preg_replace("/\|+/", "|", $value);    # remove multiple pipes
                $value = preg_replace("/^\||\|$/", "", $value); # strip pipes, remove emoty ones
            }
            
            # Convert payment type for the v3.
            if($key == "paymentType"){
                switch(strtolower($value)){
                    case "single":      # Single Product
                    	$value = "product";
                        $multiple = false;
                    break;
                    case "swm":         # Single Product With Multiple Choice
                    	$value = "product";
                        $multiple = false;
                    break;
                    case "mult":        # Multiple Product
                    	$value = "product";
                        $multiple = true;
                    break;
                    case "sinSubInfo":  # Single Subscription
                    	$value = "subscription";
                        $multiple = false;
                    break;
                    case "swms":        # Single Subscriptio With Multiple Choice
                    	$value = "subscription";
                        $multiple = false;
                    break;
                    case "multSub":     # Multiple Subscription
                    	$value = "subscription";
                        $multiple = true;
                    break;
                    case "donation":    # Donation
                    	$value = "donation";
                        $multiple = false;
                    break;
                    default:            # Best fits for new version
                        $value = "product";
                        $multiple = true;
                }
                # For v3 we have to explicitly specify if multiple choices are allowed. 
                $newProp["multiple"]  = $multiple? "Yes" : "No";
            }
            
            # Convert bridge properties.
            if ($key == "bridge") {
                parse_str(html_entity_decode($value), $bridge);
                $value = json_encode($bridge);
            }
            
            # Convert alignment properties to the correct value
            if($key == 'alignment'){
                if($value == 'centered'){
                    $value = 'Right';
                }else{
                    $value = 'Left';
                }
            }
            # Change the styles names with new ones
            if($key == 'styles'){
                if(array_key_exists($value, $styleNames)){
                    $value = $styleNames[$value];
                }else{
                    $value = "form";    # Default is form now
                }
            }
            # Place converted property into new array
            $newProp[$key] = $value;
        }
        return $newProp;
    }
    
    /**
     * Converts the changed questions types
     * @param object $type
     * @return 
     */
    public function convertTypeName($type){
        /**
         * @var \Legacy\Jot\Keps the type names chanded in the new version
         */
        $types = array(
            "control_datetimepicker" => "control_datetime",
            "control_html" => "control_text"
        );
        
        # Check if the value name is changed or not
        if(array_key_exists($type, $types)){
            return $types[$type];
        }
        return $type; 
    }
    
    /**
     * Converts subscription duration texts for new version
     * @param object $type
     * @param object $duration
     * @return 
     */
    public function convertSubscriptionDurations($type, $duration){
        
        switch(strtolower($type)){
            case "day":
            	if($duration < 4){   return "Daily"; }
                if($duration < 12){  return "Weekly"; }
                if($duration < 22){  return "Bi-Weekly";}
                if($duration < 45){  return "Monthly";}
                if($duration < 75){  return "Bi-Monthly";}
                if($duration < 135){ return "Quarterly";}
                if($duration < 270){ return "Semi-Yearly";}
                if($duration < 547){ return "Yearly";}
                return "Bi-Yearly";
            case "month":
                if($duration < 2){  return "Monthly";}
                if($duration < 3){  return "Bi-Monthly";}
                if($duration < 5){  return "Quarterly";}
                if($duration < 9){  return "Semi-Yearly";}
                if($duration < 18){ return "Yearly";}
                return "Bi-Yearly";
            case "year":
                if($duration < 2){ return "Yearly";}
                return "Bi-Yearly";
            default:
                return "Monthly";
        }

    }
    
    /**
     * Converts trial duration texts to new version
     * @param object $type
     * @param object $duration
     * @return 
     */
    public function convertTrialDurations($type, $duration){
        if($duration < 1 || $duration == NULL){
            return "None";
        }
        
        /*
         * @TODO We have checked the database and it turns out that no body is using this feature
         * all trial durations are 0 or NULL in the database so we decided to not to waste time on this.
         * However, if needed this feature should be completed
         */
        return "None"; # Just in case, when completing remove this line
        
        switch(strtolower($type)){
            case "day":
                if($duration < 2){   return "One Day";}
                if($duration < 4){   return "Three Days";}
                if($duration < 8){   return "Five Days";}
                if($duration < 12){  return "10 Days";}
                if($duration < 22){  return "15 Days";}
                if($duration < 45){  return "30 Days";}
                if($duration < 270){ return "60 Days";}
                if($duration < 547){ return "6 Months";}
                if($duration < 547){ return "1 Year";}
                return "None";
            case "month":
                if($duration < 4){   return "One Day";  }
                if($duration < 12){  return "Three Days"; }
                if($duration < 22){  return "Five Days";}
                if($duration < 45){  return "10 Days";  }
                if($duration < 75){  return "15 Days";  }
                if($duration < 135){ return "30 Days";  }
                if($duration < 270){ return "60 Days";  }
                if($duration < 547){ return "6 Months"; }
                if($duration < 547){ return "1 Year";   }
                return "None";
            case "year":
                if($duration < 2){ return "1 Year";}
                return "None";
            default:
                return "None";
        }
    }
    
    
    /**
     * Collects all forms of the user
     * @return 
     */
    public function getForms(){
        \Legacy\Jot\Console::log('Get Forms Started ' . \Legacy\Jot\Utils::bytesToHuman(memory_get_usage()));
        # Get all forms
        $res = \Legacy\Jot\DB::read("SELECT * FROM `forms` WHERE `username`=':username'", $this->username);
        
        // Console::log("User has: ". $res->rows. " Form", "Migration");
        
        foreach($res->result as $form){
            $id = $form['id'];
            //Console::log("Check form: ".$id, "Migration");
            
            # Get form data
            $this->forms[$id] = $form;
            $this->formIDList[] = $id;
            
            
            # Get listings
            if($this->lastMigrationDate){
                $listings = \Legacy\Jot\DB::read("SELECT * FROM `listings` WHERE `form_id`=#id AND `updated_at` > ':lastMigration'", $id, $this->lastMigrationDate);
            }else{
                $listings = \Legacy\Jot\DB::read("SELECT * FROM `listings` WHERE `form_id`=#id", $id);
            }
            $this->forms[$id]['listings'] = $listings->result;
            
            
            $this->forms[$id]['submissions'] = array();
            
            \Legacy\Jot\Console::log('Getting submissions');
            
            # Get submissions and don't move the deleted submissions
            if($this->lastMigrationDate){
                $submissions = \Legacy\Jot\DB::read("SELECT * FROM `submissions` WHERE `form_id`=#id AND (`status` is NULL OR `status` != 'DELETED') AND (`date_time` > ':lastMigration')", $id, $this->lastMigrationDate);
            }else{
                $submissions = \Legacy\Jot\DB::read("SELECT * FROM `submissions` WHERE `form_id`=#id AND (`status` is NULL OR `status` != 'DELETED')", $id);
            }
            
            \Legacy\Jot\Console::log('Getting answers');
            
            \Legacy\Jot\Console::log('Processing submissions');
            # Submissions should be processed
            foreach($submissions->result as $submission){
                # Place submissions in forms array
                $this->forms[$id]['submissions'][$submission['id']] = $submission;
                # Get the answers for this submission
                
                $answers = \Legacy\Jot\DB::read("SELECT * FROM `answers` WHERE `submission_id`=':sid'", $submission['id']);
                $this->forms[$id]['submissions'][$submission['id']]['answers'] = $answers->result;
            }
            
            
            \Legacy\Jot\Console::log('Done');
            
            
            //Console::log($this->forms[$id]['submissions']);
            # Check migration date and if this form was not changed then do not convert properties
            # And skip the form to prevent an insert
            if($this->lastMigrationDate){
                $form_updated = strtotime($form["updated_at"]);
                $last_migrate = strtotime($this->lastMigrationDate);
                if($form_updated < $last_migrate){
                    //Console::log("Form (".$form['id'].") was not changed, so skipped", "Migration");
                    $this->forms[$id]['skipForm'] = true;
                    continue;
                }
            }
            
            
            # Get the default properties from javascript using V8
            $defaultRawProperties = \Legacy\Jot\Form::getDefaultProperties();

            $defaultProperties = array();
            /*
             * When we retrieve properties from javascript they are useless multidimensional objects 
             * we should process them to be used in these kind of actions.
             * Convert them to a one dimensional array
             */
            foreach($defaultRawProperties as $key => $rawProperty){
                $defaultProperties[$key] = array();
                foreach($rawProperty as $prop => $rawValue){
                    if(empty($rawValue['value'])){ continue; }
                    # Prevent Sub Header to place "Click to edit" message on the form.
                    # Since these are old forms users are not avare of the subHeader
                    if($prop === "subHeader"){ $rawValue['value'] = ''; }
                    $defaultProperties[$key][$prop] = $rawValue['value'];
                }
            }
            
            # Collect properties
            $properties = array("form"=>array());
            $questions = \Legacy\Jot\DB::read("SELECT * FROM `questions` WHERE `form_id`=#id", $id);
            foreach($questions->result as $question){
                $properties[$question['id']] = array(
                    "qid"   => $question['id'],
                    "type"  => $this->convertTypeName($question['type']), # Conevrt the question type name to new version names
                    "order" => $question['order']
                );
            }
            
            # Collect question properties
            $questionProperties = \Legacy\Jot\DB::read('SELECT * FROM `question_properties` WHERE `form_id`=#id', $id);
            foreach($questionProperties->result as $question){
                $qid = $question['question_id'];
                if($qid == '999'){ $qid = 'form'; } # Convert 999 to "form" because 999 is the form properties
                $properties[$qid][$question['prop']] = $question['value'];
            }
            
            # Place them in the form properties
            $this->forms[$id]['properties'] = $properties;
            
            # Create all question names
            foreach($this->forms[$id]['properties'] as $qid => $prop){
                if($qid === "form"){ continue; }
                if(!isset($prop["name"])){
                    $this->forms[$id]['properties'][$qid]["name"] = $this->createQuestionName($qid, $id);
                }
            }
            
            # New array for collection default properties and form properties together
            $mergedProperties = array();
            foreach($properties as $qid => $property){
                if($qid === 'form'){
                    $property = $this->convertEmails($property, $id);    # Convert Emails
                    
                    if(!empty($property['thankurl'])){
                        $property['activeRedirect'] = 'thankurl';
                    }
                    
                    if(!empty($property['thanktext'])){
                        $property['activeRedirect'] = 'thanktext';
                    }
                }
                $property = $this->convertProperties($property, $id);    # Convert prop name
                $defKey = $this->convertTypeName(($qid !== "form")? $property['type'] : "form");    # Type name
                if(!is_array($defaultProperties[$defKey])){
                    \Legacy\Jot\Console::error($defKey." was not in defaults array");
                    unset($mergedProperties[$qid]);
                    continue;
                }
                # Add default properties where they are missing
                $mergedProperties[$qid] = array_merge($defaultProperties[$defKey], $property);
            }
            
            # Place them in the form properties
            $this->forms[$id]['properties'] = $mergedProperties;
            
            # We should change the default line spacing to old versions line spacing for migrated forms
            # Because old form may not fit the old iframes
            $this->forms[$id]['properties']['form']['lineSpacing'] = '5';
            # Change the form with for old forms because new width is too wide for them
            $this->forms[$id]['properties']['form']['formWidth']   = '520';
            
            $products = array();
            # Get Products for this form
            $productsRes = \Legacy\Jot\DB::read("SELECT * FROM `products` WHERE `form_id`=#id", $id);
            $pid = 100;
            foreach($productsRes->result as $product){
                
                if(!is_array($this->productIDs[$id]) || !in_array($product["product_id"], $this->productIDs[$id])){
                    continue; // This product is deleted earlier. So we should not include it.
                }
                
                $products[] = array(
                    "pid"      => $pid++,           # Product id's for new version
                    "name"     => $product['name'],
                    "price"    => $product['price'],
                    "period"   => $this->convertSubscriptionDurations($product["subs_type"], $product["subs_duration"]),
                    "setupfee" => $product['price'] + $product['setup_fee'],
                    "trial"    => $this->convertTrialDurations($product["trial_type"], $product["trial_duration"]),
                    "icon"     => ""
                );
            }
            # Place products in the forms properties
            $this->forms[$id]['properties']["form"]["products"] = $products;
        }
        \Legacy\Jot\Console::log('Get Forms Ended ' . \Legacy\Jot\Utils::bytesToHuman(memory_get_usage()));
    }
    
    /**
     * Moves the monthly usage seperately
     * @return 
     */
    public function moveMonthlyUsage(){
        
        # Insert Monthly Usage
        \Legacy\Jot\DB::write("REPLACE INTO `monthly_usage` (`username`, `submissions`, `ssl_submissions`, `payments`, `uploads`, `tickets`) 
                   VALUES(':username', #submissions, #ssl_submissions, #payments, #uploads, #tickets)", array(
                        'username'        => $this->user['username'], 
                        'submissions'     => $this->user['monthly_usage']["submissions"],
                        'ssl_submissions' => $this->user['monthly_usage']["ssl_submissions"],
                        'payments'        => $this->user['monthly_usage']["payments"],
                        'uploads'         => $this->user['monthly_usage']["uploads"],
                        'tickets'         => $this->user['monthly_usage']["tickets"]
                   ));
    }
    
    /**
     * Moves the user to new database
     * @return 
     */
    public function moveUser(){
        \Legacy\Jot\Console::log('Move User Started ' . \Legacy\Jot\Utils::bytesToHuman(memory_get_usage()));
        \Legacy\Jot\DB::useConnection('new');
        
        if($this->lastMigrationDate){
            $user_updated = strtotime($this->user["updated_at"]);
            $last_migrate = strtotime($this->lastMigrationDate);
            if($user_updated < $last_migrate){
                //Console::log("User (".$this->username.") was not changed, so skipped", "Migration");
                $this->moveMonthlyUsage(); # move the monthly usage anyway.
                
                return; # This user havent changed since last migration so don't update anything
            }
        }
        
        
        $createTrigger = "CREATE TRIGGER insert_user BEFORE INSERT ON users FOR EACH ROW SET NEW.updated_at = NOW(), NEW.last_seen_at = NOW()";
        
        \Legacy\Jot\DB::write("DROP TRIGGER `insert_user`");
        \Legacy\Jot\DB::beginTransaction(); # Start Transaction
        
        try{
            
            $values = array(
                'username'   => $this->user["username"],
                'password'   => \Legacy\Jot\User::encodePassword($this->user["password"]), # Encode password
                'name'       => $this->user["name"],
                'email'      => $this->user["email"],
                'website'    => $this->user["url"],
                'time_zone'  => "UTC", # $this->user["time_zone"], # They are all blank so place the correct values
                'ip'         => $this->user["ip"],
                'accountType'=> empty($this->user["account_type"])? 'FREE' : $this->user["account_type"],
                'status'     => 'ACTIVE',
                'friends'    => $this->user["friends"],
                'createdate' => $this->user["creation_date"],
                'last_seen'  => $this->user["last_seen"]
            );
            
            # @TODO if user exists then check the emails
            # if they are equal then merge the accounts
            # else add _beta suffix to the V3 username then send and email to the account
            
            if($this->isUserExist($this->user["username"])){
                # update user
                \Legacy\Jot\DB::write("UPDATE `users`
                           SET  
                              `password`  = ':password',
                              `name`      = ':name',
                              `email`     = ':email',
                              `website`   = ':website',
                              `time_zone` = ':time_zone',
                              `ip`        = ':ip',
                              `account_type` = ':accountType',
                              `status`       = ':status',
                              `saved_emails` = ':friends',
                              `created_at`   = ':createdate',
                              `last_seen_at` = ':last_seen'
                          WHERE
                              `username`     = ':username'
                          ", $values);
            }else{
                # Insert User
                \Legacy\Jot\DB::write("INSERT INTO `users` (`username`, `password`, `name`, `email`, `website`, `time_zone`, `ip`, `account_type`, `status`, `saved_emails`, `created_at`, `last_seen_at`)
                                       VALUES(':username', ':password', ':name', ':email', ':website', ':time_zone', ':ip', ':accountType', ':status', ':friends', ':createdate', ':last_seen')", $values);
            }
            
            # Move monthly usage of the user
            $this->moveMonthlyUsage();
            
        }catch(\Legacy\Jot\Exception $e){
            # Create last seen trigger back
            \Legacy\Jot\DB::rollbackTransaction();
            \Legacy\Jot\DB::write($createTrigger);
            throw $e;
        }
        \Legacy\Jot\Console::log('Move User Ended ' . \Legacy\Jot\Utils::bytesToHuman(memory_get_usage()));
        # Create last seen trigger back
        \Legacy\Jot\DB::commitTransaction();
        \Legacy\Jot\DB::write($createTrigger);
    }
    
    /**
     * Will count the submissions for given form
     * @param object $id
     * @return 
     */
    public function countSubmissions($id){
        return count($this->forms[$id]["submissions"]);
    }
    /**
     * Checks if the form exists or not
     * @param object $id
     * @return 
     */
    public function isFormExist($id){
        $res = \Legacy\Jot\DB::read('SELECT `id` FROM `forms` WHERE `id`=#id', $id);
        return !($res->rows < 1);
    }
    /**
     * Checks if the user exists or not
     * @param object $username
     * @return 
     */
    public function isUserExist($username){
        $res = \Legacy\Jot\DB::read("SELECT `username` FROM `users` WHERE `username`=':username'", $username);
        return !($res->rows < 1);
    }
    
    /**
     * Moves the forms of the user
     * @return 
     */
    public function moveForms(){
        \Legacy\Jot\Console::log('Move Forms Started ' . \Legacy\Jot\Utils::bytesToHuman(memory_get_usage()));
        # Change the DB to new one
        \Legacy\Jot\DB::useConnection('new');
        
        # If no form found then skip
        if(empty($this->forms)){
            $this->setLastMigrationDate();
            return;
        }
        
        # Start Transaction
        \Legacy\Jot\DB::beginTransaction();
        try{
            # For every form
            foreach($this->forms as $form){
                
                if(empty($form['skipForm'])){
                    
                    $values = array(
                        "id"         => $form["id"],
                        "username"   => $this->user["username"],
                        "title"      => $form['title'],
                        "slug"       => $form["id"],
                        "height"     => $form["height"] + 100,   # Make it a bit bigger just in case,
                        "status"     => (empty($form['status']) || $form['status'] != 'Disabled')? 'ENABLED' : 'DISABLED', 
                        "created_at" => date("Y-m-d H:i:s"), # NOW()
                        "count"      => -1, # $this->countSubmissions($form["id"]), # Count the form submission for cacheing
                        "new"        => 0   # there is no new submissions
                    );
                    
                    if($this->isFormExist($form["id"])){
                        # update the converted form
                        \Legacy\Jot\DB::write("UPDATE `forms` 
                                   SET 
                                      `username` = ':username',
                                      `title`    = ':title',
                                      `slug`     = ':slug',
                                      `height`   = ':height',
                                      `status`   = ':status',
                                      `created_at` = ':created_at',
                                      `count`      = ':count'
                                   WHERE
                                      `id` = #id
                                  ", $values);
                    }else{
                        # Save the converted form 
                        \Legacy\Jot\DB::insert("forms", $values, true);
                    }
                    
                    
                    # Seperate form properties from question properties
                    $formProperties = $form["properties"]["form"];
                    unset($form["properties"]["form"]);
                    
                    # First clean all old form properties
                    \Legacy\Jot\DB::write('DELETE FROM `form_properties` WHERE `form_id`=#id', $form['id']);
                    
                    # Form properties will be saved in a different table
                    foreach($formProperties as $prop => $value){
                        # If value is an array, such as emails, conditions etc
                        if(is_array($value)){
                            # Loop through the value
                            foreach($value as $item_id => $typeValues){
                                # Loop through every item of this property
                                # If this is an email there can be more than one email
                                foreach($typeValues as $typeProp => $typeValue){
                                    \Legacy\Jot\DB::insert("form_properties", array(
                                        "form_id" => $form["id"],
                                        "item_id" => $item_id,
                                        "type"    => $prop,
                                        "prop"    => $typeProp,
                                        "value"   => $typeValue
                                    ));
                                }
                            }
                        }else{
                            # Save property regularly
                            \Legacy\Jot\DB::insert("form_properties", array(
                                "form_id" => $form["id"],
                                "prop"    => $prop,
                                "value"   => $value
                            ));
                        }
                    }
                    
                    # First clean all old question properties
                    \Legacy\Jot\DB::write('DELETE FROM `question_properties` WHERE `form_id`=#id', $form['id']);
                    
                    # Save the question properties
                    foreach($form["properties"] as $qid => $properties){
                        foreach($properties as $prop => $value){
                            # Regularly insert every question property
                            \Legacy\Jot\DB::insert("question_properties", array(
                                "form_id"     => $form["id"],
                                "question_id" => $qid,
                                "prop"        => $prop,
                                "value"       => $value
                            ));
                        }
                    }
                }
                
                # Loop through every Submission
                # Submissions also contains their answers
                foreach($form["submissions"] as $sid => $submission){
                    # Insert submission
                    \Legacy\Jot\DB::insert("submissions", array(
                        "id"         => $submission["id"],
                        "form_id"    => $form["id"],
                        "ip"         => $submission["ip"],
                        "created_at" => $submission["date_time"],
                        "status"     => $submission["status"],
                        "new"        => 0   # Mark it as read because default value is unread
                    ));
                    
                    # Loop through the all answers of this submission
                    foreach($submission["answers"] as $answer){
                        if(in_array($answer["form_id"].":".$answer["submission_id"], $this->erroredSubmission)){
                            continue; # this submission throwed error before and must be skipped
                        }
                        try{
                            # Insert the answer first
                            \Legacy\Jot\DB::insert("answers", array(
                                "form_id"       => $answer["form_id"],
                                "submission_id" => $answer["submission_id"],
                                "question_id"   => $answer["question_id"],
                                "value"         => $answer["value"]
                            ));
                            
                            # if inserted answer was a splittable value such as Check box
                            # then insert it as splitted also
                            if(strstr($answer["value"], "|")){
                                # Explode values into array
                                $options = explode("|", $answer["value"]);
                                # For each value
                                foreach($options as $itemID => $optValue){
                                    \Legacy\Jot\DB::insert("answers", array(
                                        "form_id"       => $answer["form_id"],
                                        "submission_id" => $answer["submission_id"],
                                        "question_id"   => $answer["question_id"],
                                        "item_name"     => $itemID,
                                        "value"         => $optValue
                                    ));                                
                                }
                            }
                        }catch(\Legacy\Jot\Exception $e){
                            $this->erroredSubmission[] = $answer["form_id"].":".$answer["submission_id"];
                            \Legacy\Jot\Console::error('answers submission error. This submission is skipped');
                        }
                    }
                }
                # if this form was skipped and have a submission
                # we should update the submission count
                if(!empty($form['skipForm']) && $this->countSubmissions($form["id"]) > 0){
                    \Legacy\Jot\DB::write('UPDATE `forms` SET `count`=`count`+#newCount WHERE `id`=#id', $this->countSubmissions($form["id"]), $form["id"]);
                }
                
                # Move listings
                foreach($form['listings'] as $listing){
                    \Legacy\Jot\DB::insert('listings', array(
                        "id"        => $listing["id"],
                        "form_id"   => $listing["form_id"],
                        "title"     => $listing["title"],
                        "fields"    => $listing["fields"],
                        "list_type" => $listing["list_type"],
                        "status"    => ($listing["status"] != 'DELETED')? 'ENABLED' : 'DELETED'
                    ));
                }
            }
        }catch(\Legacy\Jot\Exception $e){
            # If an array occures during migration rollback every change
            \Legacy\Jot\DB::rollbackTransaction();
            \Legacy\Jot\Console::error('Was not able to migrate this user:'.$this->user['username']);
            return;
        }
        $this->setLastMigrationDate();
        \Legacy\Jot\Console::log('Move Forms Ended ' . \Legacy\Jot\Utils::bytesToHuman(memory_get_usage()));
        # Everything was successfull  then commit these changes to database
        \Legacy\Jot\DB::commitTransaction();
        //Console::log('Migration successfully completed for:'.$this->user['username']);        
    }
}