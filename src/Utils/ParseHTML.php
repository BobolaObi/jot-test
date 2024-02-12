<?php
@include_once "../init.php";

# Make sure file is included
include_once ROOT."opt/simple_html_dom.php";
/**
 * Wrapper for HTML parser function
 * @link http://sourceforge.net/projects/simplehtmldom/
 * @link http://simplehtmldom.sourceforge.net/manual.htm for manual
 * @package JotForm_Utils
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\Utils;
class ParseHTML{
    
    public $dom, $origin, $form, $includeID;
    /**
     * Gets the file source (HTML or URL) and parses the HTML
     * @param string $source Source of the form either a URL or an HTML code
     * @param boolean $isFile [optional] specifies either this is an HTML code or a file path default to true
     */
    public function __construct($source, $isFile = true){
        
        if($isFile){
            if(strstr($source, "facebook.com")){
                $this->handleFacebook($source);
            }else{
                $this->dom = file_get_html($source);
            }
        }else{
            $this->dom = str_get_html($source);
        }
        
        if( ! $this->origin){
            $this->origin = $this->identifyFormOrigin();
        }
    }
    
    /**
     * Get facebook page source and try to find a jotform form
     * @param object $source
     * @return 
     */
    public function handleFacebook($source){
        $s = Utils::curlRequest($source);
        if(strstr($s['content'], "jotform-form") !== false){
            if(preg_match('/name\=\\\"formID\\\"\svalue\=\\\"(\d+)\\\"/', $s['content'], $m)){
                if($m[1]){
                    $this->origin    = "fbForm";
                    $this->includeID = $m[1]; 
                }else{
                    throw new Exception("Form could not be identified"); 
                }
            }
        }else{
            throw new Exception("Form could not be found");
        }
        # throw new Exception("Importing forms from Facebook.com is currently disabled.");
    }
    
    /**
     * Tries to find the Element Label, If it cannot find then uses the element name by default
     * @param object $el
     * @param object $tryObvious [optional] tries only the obvious label if it's not found returns false
     * @return 
     */
    private function getElementLabel($el, $tryObvious = false){
        
        # If element has an ID then it may have a label assigned to it
        if($el->id){
            # Obvious solution
            if($label = $this->dom->find('label[for="'.$el->id.'"]', 0)){
                return trim(''.preg_replace("/\s+/", " ", $label->plaintext));
            }
        }
        if($tryObvious){ return false; } // Try only obvious label
        
        # Check parents parent for text nodes
        
        # Assume first label found in the parent is the label for this item
        if($l = trim(''.$el->parent()->find('label', 0)->plaintext)){
            return $l;
        }
        
        # Assume first label found in the parent's parent is the label for this item
        if($l = trim(''.$el->parent()->parent()->find('label', 0)->plaintext)){
            return $l;
        }
        
		# Assume first strong found in the parent's parent is the label for this item
        if($l = trim(''.$el->parent()->parent()->find('strong', 0)->plaintext)){
            return $l;
        }
		
        # No luck with labels then try to get text nodes from the parent
        $l = trim(''.$el->parent()->parent()->plaintext);
        if(strlen($l) <! 3 && strlen($l) >! 40){    // If the label smaller than 3 char or larger than 40 it may not be a label
            return $l;
        }
        
        # Fail, no label or equivalent found, return question name
        return $el->name;
    }
    
    /**
     * Gets the JotForm specific question names by question types
     * @param object $input
     * @return 
     */
    private function getControlNameByType($input){
         switch($input->tag){
                case "input":
                	switch($input->type){
                	    case "password": return "control_passwordbox";
                        case "hidden":   return "control_hidden";
                        case "checkbox": return "control_checkbox";
                        case "radio":    return "control_radio";
                        case "file":     return "control_fileupload";
                        case "submit":
                        case "button":   return "control_button";
                        default:         return "control_textbox";
                	}
            	break;
                case "select":   return "control_dropdown";
                case "textarea": return "control_textarea";
                case "button":   return "control_button";
                default:         return "control_textbox";
            }
    }
    
    /**
     * Itentifies the form origin for our parser, we can do specific thing for specific forms
     * Gather more informarmation as much as you can
     * @return string returns the service name or generic for unknown forms
     */
    private function identifyFormOrigin(){
        # try to get form from HTML code 
        $forms =  $this->dom->find("form");
		$selectedForms = array();
        foreach($forms as $form){
        	# it maybe a search form
        	if(strpos($form->action, "search") === false && strpos($form->action, "find") === false && 
			   strpos($form->id, "search")     === false && strpos($form->name, "search") === false){
        		$selectedForms[] = $form;
        	}
        }
		$this->form = $selectedForms[0];
		
        if(!$this->form){ 
            
			#Try to match iframes or jsincludes
		    foreach($this->dom->find('script') as $script){
		    	if(preg_match(Utils::$urlMatch, $script->src, $matches)){
		    		$this->includeID = $matches[4];
					return "jsform";
		    	}
		    }
			
			foreach($this->dom->find('iframe') as $iframe){
                if(preg_match(Utils::$urlMatch, $iframe->src, $matches)){
                    $this->includeID = $matches[4];
					return "iframe";
                }
            }
		    
            # if not found a form on the page then warn user
            throw new Exception("No form found on the page. Please check the source code you provided");
        }
        
        switch(true){
            case $this->dom->find(".wufoo") && $this->dom->find('input[id="idstamp"]'):
                return "wufoo";
            case $this->dom->find(".fsSubmitButton") && $this->dom->find('input[name="form"]'):
                return "formSpring";
            case $this->dom->find(".form-all") && $this->dom->find('input[name="formID"]'):
            	return "jotForm";
            case $this->dom->find('.tbmain') && $this->dom->find('input[name="formID"]'):
            	return "jotFormV2";
            case $this->dom->find('.wFormContainer') &&  $this->dom->find('input[name="tfa_dbCounters"]'):
            	return "formAssembly";
            case ($this->dom->find('.form_container') || $this->dom->find('.appnitro')) && $this->dom->find('input[name="form_id"]'):
            	return "phpForm";
            case $this->dom->find('#FSForm') && ($this->dom->find('input[name="FormId"]') && $this->dom->find('input[name="FormNbr"]')):
            	return "formSite";
            # Since DRUPAL forms are very much like JotForm forms we can handle them pretty well so they can be generic
            default:  return "generic";
        }
        
    }
    
    /**
     * Recursively searches for a specific parent
     * @param object $element
     * @param object $tagname
     * @return 
     */
    private function findParent($element, $tagname){
        if($element->tag == $tagname){
            return $element;
        }
        if($element->tag == "body"){ return false; }
        if(!$element->parent()){ return false; }
        
        return $this->findParent($element->parent(), $tagname);
    }
    
    /**
     * Creates a jotFormSpecific questionname from the question label
     * @param object $text
     * @return 
     */
    private function makeQuestionName($text){
        # See if there's another question with the same name.
        $qLabel = Utils::fixUTF($text);
        $tokens = preg_split("/\s+/", $qLabel);
        
        $qName = (isset($tokens[1])) ? ( strtolower($tokens[0]) . ucfirst(strtolower($tokens[1])) ) : strtolower($tokens[0]);
        $qName = preg_replace("/\W/", "", $qName);
        
        return $qName;
    }
    
    /**
     * Checks if the given name exists in given array
     * @param object $name
     * @param object $array
     * @return 
     */
    private function checkNameExist($name, $array){
        foreach($array as $line){
            if(isset($line['name']) && $line['name'] === $name){
                return true;
            }
        }
        return false;
    }
    
    /**
     * Extracts the form from HTML code, seperates all inputs 
     * collect all information Join together and create a 
     * configuration array for JotForm, then we can save it on our database
     * @todo find a way to parse special inputs such as DateDime pickers, or Joined fiels lije Full Name and Phone Number 
     * @return 
     */
    public function extractForm(){
        
        if($this->origin == "jotForm"){   // Try cloning the form
            try{
                $formIDInputs = $this->dom->find('input[name="formID"]');
                $formID = $formIDInputs[0]->value;
                $form = new Form($formID);
                $newID = $form->cloneForm(Session::$username);
                Utils::setCurrentID('form', $newID);
                return true;
            }catch(Exception $e){ /* Form not found on database, just continue; */ }
        }
        
		if($this->origin == "jsform" || $this->origin == "iframe" || $this->origin == "fbForm"){
			try{
				$form = new Form($this->includeID);
                $newID = $form->cloneForm(Session::$username);
                Utils::setCurrentID('form', $newID);
                return true;				
			}catch(Exception $e){
				throw new SoftException("Form cannot be imported");
			}
		}
		
        # Collect all types of inputs from the form
        $inputs = $this->form->find("input, textarea, select, button");
        $questions = array();   # All collected question on the form
        $qid = 0;               # questionID
        $questionsToSkip = array(); # if you want a question to be skipped just push it in this array
        $temp_id = 0;
        
        # Loop through all inputs
        foreach($inputs as $input){
            
            # Skip know unusable questions
            if($this->origin == "wufoo" && in_array($input->id, array("comment", "currentPage", "idstamp"))){ continue; }
            if($this->origin == "jotForm" && in_array($input->name, array("formID", "check_spm"))){ continue; } // New source
            if($this->origin == "jotFormV2" && in_array($input->name, array("formID", "spc"))){ continue; }     // Old source
            if($this->origin == "formSpring" && in_array($input->name, array("form","viewkey","unique_key","password","hidden_fields","fspublicsession","incomplete","referrer","referrer_type","_submit"))){ continue; }
            if($this->origin == "formAssembly" && $input->type == "hidden"){ continue; }
            if($this->origin == "formSite" && $input->type == "hidden"){ continue; }
            if($this->origin == "phpForm" && in_array($input->name, array("form_id"))){ continue; }     // Old source
            if($input->type  == "hidden"){ continue; } // Ignore all hidden fields
            
            
            $qid++; # increase ID
            $label = $this->getElementLabel($input);      # get the label of the input
            $required = false;
            if(strstr($label, "*")){
                $label = trim(''.str_replace("*", "", $label));
                $required = "Yes";
            }
            
            $type  = $this->getControlNameByType($input); # get the JotForm type of the input
            //echo $type."\n";
            $name  = $this->makeQuestionName($label);     # create a question name for this field
            
            if($this->checkNameExist($name, $questions)){
                $name = $name.$qid;
            }
            
            # skip address line 2 for sorm spring
            if($this->origin == "formSpring" && strstr($input->id, "-address2")){continue;}
            # add a text area for address instead of two lines
            if($this->origin == "formSpring" && strstr($input->id, "-address")){ 
                $questions[$qid] = array(
                    "type" => "control_textarea",
                    "name" => "address".$qid,
                    "labelAlign" => "Auto",
                    "text"  => "Address",
                    "required" => $required,
                    "order" => $qid,
                    "qid"   => $qid,
                    # Put defaults for missing textarea dimensions
                    "cols" => "40",
                    "rows" => "6"  
                );
                continue; 
            }
            
            # Specific jobs for each type of input
            switch($input->tag){
                case "input":
                	# INPUT tag has many type of itself
                	switch($input->type){
                	    case "button":    
                        case "submit":    
                        	$questions[$qid] = array(
                                "type" => $type,
                                "name" => $name,
                                "buttonAlign" => "Center",
                                "order" => $qid,
                                "qid"   => $qid,
                                "text"  => trim(''.$input->value),
                            );
                        break;
                        /**
                         * Check boxes and radio buttons are the most problematic elements to parse
                         * Since they are all individual inputs it's really hard group them together
                         * If we know the source of the form then we know how to parse these elements
                         * so we have our special cases for each origin
                         */
                        case "checkbox":
                        case "radio":
                        	# If the form is wufoo then 
                        	if($this->origin != "generic"){
                        	    
                        	    # find a way to place question in the right place
                        	    if($this->origin == "formSpring" || $this->origin == "jotFormV2"){
                        	        $parentElement = $this->findParent($input, "td"); # we know all lines are TD
                        	    }else if($this->origin == "jotForm" || $this->origin == "wufoo"){
                        	        $parentElement = $this->findParent($input, "li"); # we know all lines are LI
                        	    }else{
                        	        $parentElement = $this->findParent($input, "div"); # the most common parent is div
                        	    }
                        	    
                                if(in_array($parentElement->id, $questionsToSkip)){ continue; } # if this inputs parent scanned before then skip for the next time
                                if(!$parentElement->id){ $parentElement->id = "temp_".($temp_id++); }
                                
                                $questionsToSkip[] = $parentElement->id; # add this parent to skip list
                                $anyChecks = $parentElement->find('input[type="checkbox"], input[type="radio"]');
                                
                                if(count($anyChecks) > 1){    # if a line contains checkbox or radioubutton
                                
                                    $qlabel = $parentElement->find("label"); # First label in the line contains the question label
                                    $qlabel = trim(''.$qlabel[0]->plaintext);
                                    
                                    $qtype  = "control_".$anyChecks[0]->type;    # get question line
                                    $qname  = $this->makeQuestionName($qlabel);  # create a name from this label
                                    if(strstr($qlabel, "*")){
                                        $qlabel = trim(''.str_replace("*", "", $qlabel));
                                        $required = "Yes";
                                    }
                                    $opts = array(); # collect all values from inputs
                                    foreach($anyChecks as $checks){
                                        if($valueLabel = $this->getElementLabel($checks, true)){
                                            $opts[] = $valueLabel;
                                        }else{
                                            $opts[] = $checks->value;
                                        }
                                    }
                                    
                                    # add collected information into questions array
                                    $questions[$qid] = array(
                                        "type" => $qtype,
                                        "name" => $qname,
                                        "labelAlign" => "Auto",
                                        "required" => $required,
                                        "order"   => $qid,
                                        "qid"     => $qid,
                                        "text"    => $qlabel,
                                        "options" => $opts
                                    );
                                    continue; # If knwon source then skip the deault
                                } else if($input->type == 'radio'){
                                    $otherRadios = $this->dom->find('input[name="'.$input->name.'"]');
                                    if(count($otherRadios) > 1){
                                        foreach($otherRadios as $checks){
                                            if($valueLabel = $this->getElementLabel($checks, true)){
                                                $opts[] = $valueLabel;
                                            }else{
                                                $opts[] = $checks->value;
                                            }
                                        }
                                        
                                        # add collected information into questions array
                                        $questions[$qid] = array(
                                            "type" => $qtype,
                                            "name" => $qname,
                                            "labelAlign" => "Auto",
                                            "required" => $required,
                                            "order"   => $qid,
                                            "qid"     => $qid,
                                            "text"    => $qlabel,
                                            "options" => $opts
                                        );
                                        continue; # If knwon source then skip the deault
                                    }
                                }
                        	}
                            
                            # Default is simple just add all checkbox or radiobuttons as a new input
                            $questions[$qid] = array(
                                "type" => $type,
                                "name" => $name,
                                "labelAlign" => "Auto",
                                "order" => $qid,
                                "required" => $required,
                                "qid"   => $qid,
                                "options" => trim(''.$input->value),
                                "text"  => $label,
                            );
                        break;
                        case "hidden":
                        	$questions[$qid] = array(
                                "type" => $type,
                                "name" => $input->name,
                                "labelAlign" => "Auto",
                                "text"  => $input->name,
                                "order" => $qid,
                                "qid"  => $qid
                            );
                        break;
                        default:
                            $questions[$qid] = array(
                                "type" => $type,
                                "name" => $name,
                                "labelAlign" => "Auto",
                                "text"  => $label,
                                "required" => $required,
                                "order" => $qid,
                                "qid"  => $qid,
                                "size" => empty($input->size)? false : $input->size  # may not have a size
                            );
                	}
                    
            	break;
                case "select":
                	$options = $input->find("option"); # get all options of this field
                    $questions[$qid] = array(
                        "type" => $type,
                        "name" => $name,
                        "labelAlign" => "Auto",
                        "required" => $required,
                        "order" => $qid,
                        "qid"   => $qid,
                        "text"  => $label
                    );
                    # collect all options of the select
                    foreach($options as $op){
                        $questions[$qid]["options"][] = trim(''.$op->plaintext);
                    }
                    
            	break;
                case "textarea":
                	$questions[$qid] = array(
                        "type" => $type,
                        "name" => $name,
                        "labelAlign" => "Auto",
                        "text"  => $label,
                        "required" => $required,
                        "order" => $qid,
                        "qid"   => $qid,
                        # Put defaults for missing textarea dimensions
                        "cols" => empty($input->cols)? "40" : $input->cols,
                        "rows" => empty($input->rows)? "6" : $input->rows,  
                    );
                    
            	break;
                case "button":
                	$questions[$qid] = array(
                        "type" => $type,
                        "name" => $name,
                        "buttonAlign" => "Center",
                        "order" => $qid,
                        "qid"   => $qid,
                        "text"  => trim(''.$input->plaintext)
                    );
            	break;
            }
        }
        
         
        # Create a new ID for question 
        $id = ID::generate();
        # Create a shorthand for form
        $hash = ID::encodeID($id);
        # Some primitive form properties
        $properties = array(
            "form_id" => $id,
            "form_hash" => $hash,
            "form_title" => "[Imported Form] ".$this->dom->find('title', 0)->plaintext
        );
        
        # Loop through all collected properties and create saveable array for JotForm
        foreach($questions as $qid => $props){
            foreach($props as $key => $value){
                if(empty($value)){ continue; } # if no value is given then skip this prop, so JotForm can use default value
                if(is_array($value)){ $value = join("|", $value); }  # join all array values for one dimensional array
                
                $properties[$qid."_".$key] = $value;
            }
        }
        
         /*Utils::print_r($properties, true); // */ return $properties;
        
    }
}

 
/*
$html = new ParseHTML(ROOT."opt/parseForm.html");
$response = $html->extractForm();

// */