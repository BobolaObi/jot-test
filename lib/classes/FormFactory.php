<?php
/**
 * Saves or Updates a form from configuration
 * @package JotForm
 * @copyright Copyright (c) 2009, Interlogy LLC
 */
class FormFactory {
    
    private $newForm, $id, $rawProperties, $questionProperties=array(), $formProperties=array(), $username, $source;
    
    /**
     * Constructor
     * @param object $id [optional]
     * @return 
     */
    function FormFactory($properties, $source="<body></body>", $forceNew = false){
        # Convert all JSON properties to PHP array
        $this->rawProperties = json_decode($properties, true);
        if($this->rawProperties === NULL){ # if json_decode fails warn user about it
            throw new Exception("Form data cannot be decoded");
        }
        
        $this->source = $source; # form source sent from formBuilder
        $formPropFound = false;  # for save data validation
        
        # loop through raw properties and convert them to the way we can understand
        foreach($this->rawProperties as $key => $value){
            if(strstr($key, 'form_')){
                # If property starts with a form prefix, then put it on the form properties array
                $this->formProperties[str_replace("form_", "", $key)] = $value;
                $formPropFound = true; # yes form property found data seems to be OK
            }else{
                # Get question ID and property KEY
                list($qid, $prop) = explode("_", $key);
                
                ### Fill properties array with a new structure, group properties together
                
                # If array node is not created yet, create it now.
                if(!is_array($this->questionProperties[$qid])){ $this->questionProperties[$qid] = array(); }
                # Place the value
                $this->questionProperties[$qid][$prop] = $value;
            }
        }
        
        # If no form property was found on the save code send this error
        # an unknown bug was causing JotForm to create lot's of empty forms
        if($formPropFound === false){
            throw new Exception("There is a problem with save data.");
        }
        
        # If ID was not send with the form properties therefore this is a new form
        # create an ID and set it as a new form
        if(!isset($this->formProperties["id"])){
            $this->newForm = true;
            $this->id = ID::generate(); # If it is a new form then generate a new ID for it
        }else{
            $this->newForm = false;
            $this->id = (float) $this->formProperties["id"];
        }
        
        # Always foce it to be a new form
        if($forceNew){ $this->newForm = true; }
        
        # if ID was not set in rawProperties set tge generated one
        if(!isset($this->rawProperties['form_id'])){
            $this->rawProperties['form_id'] = $this->id;
        }
        
        # place the newly generated formID in the formsource where it's marked
        $this->source = str_replace("{formID}", $this->id, $this->source);
        
        # set current user
        $this->username = Session::$username;
    }
    
    
    /**
     * Just in case
     * @param object $property
     * @return 
     */
    public function getProperty($property){
        return $this->formProperties[$property];
    }
    
    /**
     * Save the form
     * @return 
     * @param object $properties
     * @param object $source
     */
    public function save(){
        
        $errors = array();                  # collect all errors here
        $defaultValues = array();           # collect default values then clean them from database
        $insertValues  = array();           # values will be inserted to DB
        $questionPropInsertArray = array(); # database insert values for question properties
        
        # If current user is guest then save guest user to database
        # @TODO save user only if it was not saved before
        if(Session::isGuest()){
            Session::commitGuestAccount();
        }
        
        # This thing happens time to time
        if(!isset($this->formProperties['title'])){
            throw new Exception("Form settings seems to be missing.");
        }
        
        # The fields that must be saved in form table but NOT in properties table are here
        # Utils::u gets the value of the given key then removes it from the array
        $formTitle  = Utils::u($this->formProperties, 'title');
        $formStatus = Utils::u($this->formProperties, 'status');
        $formHeight = Utils::u($this->formProperties, 'height');
        $currentIndexChanged = Utils::u($this->formProperties, 'currentIndexChanged');
        $formSlug   = trim(Utils::u($this->formProperties, 'slug'));
        
        # If form slug was not defined use formID as a slug
        if(empty($formSlug)){
            $formSlug = $this->id;
        }
        
        # If property was defined as default use the default title
        if($formTitle == "%%default%%"){
            $formTitle = "Untitled Form";
        }
        
        # If formstatus is not disabled then it's enabled
        # it fixes the language mix-up
        if(strtolower($formStatus) !==  "disabled" /*$formStatus == "%%default%%" || empty($formStatus)*/ ){
            $formStatus = "Enabled";
        }
        # convert to upper case for DB values
        $formStatus = strtoupper($formStatus);
        
        # If this is a new form then insert it to the table
        if($this->newForm){
            $response = DB::write(
                "INSERT INTO `forms` (`id`, `username`, `title`, `height`, `status`, `created_at`, `updated_at`, `new`, `count`, `slug`) 
                 VALUES (#id, ':username', ':title', ':height', ':status', NOW(), NOW(), 0, 0, ':slug')",
                   $this->id, $this->username, $formTitle, $formHeight, $formStatus, $formSlug
            );
            
        }else{ # If it's an old form then update it
        
            $response = DB::write("UPDATE `forms` SET `title`=':title', `height`=':height', `status`=':status', `slug`=':slug', `updated_at`=NOW() WHERE `id`=#id", 
                $formTitle,
                $formHeight,
                $formStatus,
                $formSlug,
                $this->id
            );
            
        }
        
        # Clean all old form properties first
        $response = DB::write('DELETE FROM `form_properties` WHERE `form_id`=#id', $this->id);
        
        # For each form property
        foreach($this->formProperties as $key => $value){
            
            # This can be emails, products, conditions or any other multi dimensional property
            if(is_array($value)){
                
                # ex: emails, loop for each email get their IDs
                foreach($value as $itemID => $item){
                    
                    # then loop for each email property then insert into database
                    foreach($item as $itemKey => $itemValue){
                        
                        # for more deeper values such as product options color, quantity
                        # We cannot save them as an array on the database so theys hould be json
                        if(is_array($itemValue)){ 
                            $itemValue = Utils::safeJsonEncode($itemValue);
                        }
                        # Get the parameters for database insert
                        $insertValues[] = DB::parseQuery(array("(#form_id, #item_id, ':type', ':prop', ':value')", $this->id, $itemID, $key, $itemKey, $itemValue));
                    }
                    
                }
            }else { # Write any other property
                
                # If it's an empty value then don't insert it on DB
                if(empty($value) && $value !== 0 && $value !== "0"){ continue; }
                
                # collect default values for clean up
                if($value == "%%default%%"){
                    $defaultValues[$key] = $value;
                    continue;
                }
                
                # get the parameters for database insert
                $insertValues[] = DB::parseQuery(array("(#form_id, #item_id, ':type', ':prop', ':value')", $this->id, 0, '', $key, $value ));
            }
        }
        
        # Make all inserts at once
        if(count($insertValues) > 0){
            $response = DB::write("INSERT INTO `form_properties` (`form_id`, `item_id`, `type`, `prop`, `value`)
                                   VALUES ".join(", ", $insertValues));
        }
        
        # Clean up default values from form properties
        # @deprecated but we may need it in the future
        foreach($defaultValues as $prop => $value){
            $response = DB::write("DELETE FROM `form_properties` WHERE `form_id`=#form_id AND `prop`=':prop'", $this->id, $prop);
        }
        
        # reset defaults
        $defaultValues = array();
        
        # {@TODO: run this query only if a question was deleted on the form builder.} -> this may not be necessary after all it's database cleanup
        $response = DB::write('DELETE FROM `question_properties` WHERE `form_id`=#id', $this->id);
        
        # If autoIncrement field was used in the form
        $currentIndex = false;
        
        # For each question
        foreach($this->questionProperties as $qid => $question){
            
            # For each question property
            foreach($question as $key => $value){
                
                # If it's an empty value then don't insert it on DB
                if(empty($value) && $value !== 0 && $value !== "0"){ continue; }
                
                # Collect default values
                if($value == "%%default%%"){
                    if(!is_array($defaultValues[$qid])){ $defaultValues[$qid] = array(); }
                    array_push($defaultValues[$qid], $key);
                    continue;
                }
                
                # Yes autoIncrement field was on the form
                if($key === "currentIndex"){
                    $currentIndex = $value;
                }
                
                # the value is deep then convert it into JSON then save it
                if(is_array($value)){
                    $value = json_encode($value);
                }
                
                # Get the values for database insert
                $questionPropInsertArray[] = DB::parseQuery(array("(#form_id, #question_id, ':prop', ':value')", $this->id, $qid, $key, $value));
            }
        }
        
        # If currentIndex found then save it on properties table
        if($currentIndex !== false && $currentIndexChanged === 'yes'){
            Settings::setSetting("autoIncrement", $this->id, $currentIndex);
        }
        
        # Make all inserts at once
        if(count($questionPropInsertArray) > 0){
            $response = DB::write("INSERT INTO `question_properties` (`form_id`, `question_id`, `prop`, `value`)
                                   VALUES ".join(", ", $questionPropInsertArray));
        }
        
        # Clean up default values
        # @deprecated but we may need it in the future
        foreach($defaultValues as $qid => $props){
            foreach($props as $prop){
                $response = DB::write("DELETE FROM `question_properties` WHERE `form_id`=#form_id AND `question_id`=#qid AND `prop`=':prop'", $this->id, $qid, $prop);
            }
        }
        
        # Set a cookie for this form to get remember
        Utils::setCurrentID("form", $this->id);
        
        
        # Dont create a cache for deleted form
        if($formStatus == "DELETED"){
            return $this->id;
        }
        
        ####################################################################
        ##
        ## Below this line all codes are related to create a form cache
        ##
        ####################################################################
        
		# Delete old form cache
		Form::clearCache("id", $this->id);
        
        # Check if cache path exists and writable. if not try to create it
        if(!file_exists(CACHEPATH)){
            if(!@mkdir(CACHEPATH, 0777)){
                throw new Exception('Cannot create cache folder. Please fix the permissions');
            }
        }
        
        # I don't know what this code does.
        # When I do I'll comment here and fix the new form cache issue
        # if($this->newForm){
        #     return $this->id;
        # }
        
        
        # If there is no source then there is no thing to cache
        if($this->source == ""){ return $this->id; }
        
        if(USE_REDIS_CACHE){
            $cacheDB = Utils::getRedis(CACHEDB);
            if($formStatus != "ENABLED"){
                $cacheDB->del($this->id.'.html');
                return $this->id;
            }
            
            $cacheDB->set($this->id.'.html', $this->source);
            # We can also store this as an hash instead of JSON string
            $cacheDB->set($this->id.'.js', 'getSavedForm({"form":'.json_encode($this->rawProperties).', "success":true});');
            
        }else{
            # If form is not enabled then do not create a cache for it
            if($formStatus != "ENABLED"){
                @unlink(CACHEPATH.$this->id.'.html');
                return $this->id;
            }
            
            # Conventional Save - Means save all properties of a form on files
            if (@file_put_contents(CACHEPATH.$this->id.'.html', /*stripslashes causing form source brake*/ ($this->source))) {
                if (@file_put_contents(CACHEPATH.$this->id.'.js', 'getSavedForm({"form":'.json_encode($this->rawProperties).', "success":true}); ')) {
                    return $this->id;
                } else {
                    throw new Exception("File Cannot be saved: ".CACHEPATH.$this->id.'.js');
                }
            } else {
                throw new Exception("File Cannot be saved: ".CACHEPATH.$this->id.'.html');
            }
        }
        return $this->id;
    }
}
