<?
/**
 * Creates parses and Send emails associated with the form
 * Checks all conditions and stuff
 * @package JotForm
 * @copyright Copyright (c) 2009, Interlogy LLC
 */
class FormEmails{
    
    public $formID, $sid, $emails, $emailsToBeSent;
    
    /**
     * @var Submission
     */
    public $submission;
    
    public $questions, $questionNames, $options, $conditions;
    public $currentEmailHTML;
    public $tagMatch = "/\{([^\n\{\}]+)\}/mi";
    
    /**
     * @constructor
     * @param object $formID
     * @return 
     */
    function FormEmails($options){
        
        $this->submission = $options['submission'];
        $this->formID     = $this->submission->form->id;
        $this->sid        = $this->submission->sid;
        $this->emails     = $this->getEmails();
        $this->questionNames = $this->submission->questionNames;
        $this->questions  = $this->submission->questions;
        $this->options    = (object) $options;
        if(isset($options['conditions'])){
            $this->conditions = $options['conditions'];
        }
        
        if(isset($options['parser']) && $options['parser'] === true){
            return;
        }
        
        $this->parseEmails();
    }
    
    /**
     * Get saved emails of this form
     * @return 
     */
    public function getEmails(){
        $response = DB::read("SELECT * FROM `form_properties` WHERE `form_id`=#id AND `type`='emails'", $this->formID);
        if($response->rows < 1){ return false; }
        $emails = array();
        foreach($response->result as $line){
            if(!is_array(@$emails[$line['item_id']])){
                $emails[$line['item_id']] = array();
            }
            
            $emails[$line['item_id']][$line['prop']] = $line['value'];
        }
        return array_values($emails);
    }
    
    /**
     * Parses email tags from any content
     * @param object $content
     * @return 
     */
    public function parseTags($content){
        return @preg_replace_callback($this->tagMatch, array($this, "replaceTags"), $content);
    }
    
    /**
     * Parses the email content with submission data
     * @param object $data
     * @return 
     */
    public function parseEmails(){
        if(!is_array($this->emails)){ return; }
        foreach($this->emails as $i => $email){
            // If there are conditions filtered for emails
            if(is_array($this->conditions)){
                foreach($this->conditions as $cond){
                    // Check to see if any of them is for current email
                    if($cond['email'] == 'email-'.$i){
                        $email['to'] = $cond['to']; // Use to field for this email
                        $email['disabled'] = 0;     // This email is triggered by a condition
                        
                        # See Submissions::sendEmails for disable hack comment
                        if(isset($cond['disable']) && $cond['disable'] === 1){
                            $email['disabled'] = 1;
                        }
                    }
                }
            }
            $this->currentEmailHTML = $email['html'];
            if(isset($email['disabled']) && $email['disabled'] == 1){ continue; } // This email is disabled by user don't send it.
            
            # Some wrong links were placed in the emails
            $body    = @str_replace("http://yingv3.jotform.com/", "http://www.jotform.com", $email['body']);
            $body    = @str_replace("http://yangv3.jotform.com/", "http://www.jotform.com", $body);
            $body    = @str_replace("Utilshttp://v3.jotform.com/", "http://www.jotform.com/", $body);
            $body    = @str_replace("http://v3.jotform.com/", "http://www.jotform.com/", $body);
            
            $body    = $this->parseTags($body);
            $subject = $this->parseTags($email['subject']);
            
            if($email['type'] == "notification"){
                if(!strstr($email['from'], "{")){
                    $email['from'] = "none";
                }
            }
            $from  = $this->parseTags($email['from']);            
            $to    = $this->parseTags($email['to']);
            
            if(strstr($from, '|')){
                $from = explode("|", $from);
            }
            
            $this->emailsToBeSent[] = array(
                "type" => $email['type'],
                "html" => $email['html'],
                "from" => $from,
                "to"   => $to,
                "useOld" => ($email['type'] == "notification" && (!isset($email['dirty']) || $email['dirty'] != true)), 
                "subject" => $subject,
                "body" => $body
            );
        }
    }
    
    /**
     * Replaces the tag with the user answer
     * @param object $tag
     * @return 
     */
    private function replaceTags($tag){
        $name = $tag[1];
        $qid  = $this->questionNames[strtolower($name)];
        // If question name is not valid then check predefined custom tags
        if(!array_key_exists(strtolower($name), $this->questionNames)){
            switch($name){
            	case "ip":
            		return $this->submission->ip; //$_SERVER['REMOTE_ADDR'];
                case "form_title":
                    return $this->submission->form->form["title"];
                case "edit_link":
                    return HTTP_URL."form.php?formID=".$this->formID.'&sid='.$this->sid.'&mode=edit';
                case "edit_link_html":
                	$l = HTTP_URL."form.php?formID=".$this->formID.'&sid='.$this->sid.'&mode=edit';
                    return '<a href="'.$l.'">'.$l.'</a>';
                case "id":
                	return $this->submission->sid;
                case "DATE":
                	return date("Y-m-d H:i:s", $this->submission->sdate);
                default:
                    if(strpos($name, ":") !== false){
                        $atag = $tag;
                        $tag = explode(":", $name);
                        switch($tag[0]){
                            case "TAG": // In order to display examples on parsable contents such as {TAG:firstName} => {firstName}
                                return str_replace("TAG:", "", $atag[0]);
                            break;
                        	case "URLENCODE":
								if(isset($tag[2])){
									if(isset($this->questionNames[strtolower($tag[1])])){
	                                    return @rawurlencode($this->questions[$this->questionNames[strtolower($tag[1])]][$tag[2]]);
	                                }
								}else{
									$value = $this->questions[$this->questionNames[strtolower($tag[1])]];
	        
							        if(is_array($value)){
							            return @rawurlencode($this->submission->fixValue($value, $qid));
							        }else{
							            return @rawurlencode($this->submission->fixFlatValue($value, $qid));
							        }
								}
							break;
                            case "DATE":
                            	
                            	if($tag[1] == "Date-US"){
                            	    $format = "M j, Y";
                                }else if($tag[1] == "Date-EU"){
                                    $format = "j M, Y";
                            	}else if($tag[1] == "Time-US"){
                                    $format = "g:i:s A";
                                }else if($tag[1] == "Time-EU"){
                            	    $format = "G:i:s";
                            	}else if($tag[1] == "Full-US"){
                            	    $format = "M j, Y \\a\\t g:i:s A";
                                }else if($tag[1] == "Full-EU"){
                                    $format = "j M, Y \\a\\t G:i:s";
                            	}else{
                                	$key = array_slice($tag, 1, count($tag));
                                	$format = join(":", $key);
                            	}
                            	return date($format, $this->submission->sdate);
                            break;
                            case "IMG":
                            	$field  = $tag[1];
                                $qid  = $this->questionNames[strtolower($field)];
                                $width  = isset($tag[2])? $tag[2] : "";
                                $height = isset($tag[3])? $tag[3] : "";
                                $value  = $this->questions[$qid];
                                if(isset($this->submission->uploads[$qid])){
                                    $src    = Utils::getUploadURL($this->submission->owner->username, $this->formID, $this->sid, $value);
                                    return '<img src="'.$src.'" height="'.$height.'" width="'.$width.'" />';
                                }else{
                                    return ""; // No upload was made
                                }
                        	break;
                            default: // Custom Tag could not be found
                            
                                // Check if this is a item request such as {address:country} or {fullName:first} or {phone:area}
                                if(isset($this->questionNames[strtolower($tag[0])])){
                                    return @$this->questions[$this->questionNames[strtolower($tag[0])]][$tag[1]];
                                }
                                
                                return "";
                        }
                    }
                    return ""; // If question name is not found then return empty
            }
        }
        
        $value = $this->questions[$qid];
        
        if(is_array($value)){
            return $this->submission->fixValue($value, $qid);
        }else{
            $value = $this->submission->fixFlatValue($value, $qid);
        }
        
        if(isset($this->submission->uploads[$qid])){
            
        	# Create upload URL
        	$uploadURL = Utils::getUploadURL($this->submission->owner->username, $this->formID, $this->sid, $value);
			
            if($this->currentEmailHTML){
                $value = '<a href="'.$uploadURL.'">'.$value.'</a>';
            }else{
            	# If not HTML then only use file url
                $value = $uploadURL;
            }
        }
        
        $value = nl2br($value);
        return $value;
    }
    
    /**
     * Adds a banner to emails according to user status or preferences
     * @param object $body
     * @return 
     */
    public function addBanner($body, $options){
        
        # campaign is over
        return $body;
        
        if(APP){ return $body; }
        
        # Add banners for only GUEST and FREE users
        if($this->submission->owner->accountType != 'GUEST' && $this->submission->owner->accountType != 'FREE'){
            return $body;
        }
        $username = $this->submission->owner->username;
        
        $res = DB::read("SELECT * FROM `block_email_banners` WHERE `username`=':username'", $username);
        # If user specifically wanted to block banners don't show them
        if($res->rows > 0){
            return $body;
        }
        
        if($options['html'] !== "1" && $options['html'] !== true){
            
            if(Session::getLastDays() == 3){
                $lastDays = "Last 3 Days!\n";
            }else if(Session::getLastDays() == 2){
                $lastDays = "Last 2 Days!\n";
            }else{
                $lastDays = "Last Day!!\n";
            }
            
            $banner = "\n\n\n------------------------------------\n".$lastDays."Upgrade Until December 31st and become a \nJotForm Premium Member for only $45 per year!";
            return $body.$banner;
        }
        
        $img = "notification-email-".Session::getLastDays().".png";
        
        $body = str_ireplace("<br /><br /><p></p></body></html><pre>", "", $body);
        $body = str_ireplace("<p><br /><br /></p>", "", $body);
        $banner = '<div style="white-space:normal;text-align:center;">
                    <a href="'.HTTP_URL.'pricing/?banner=email">
                        <img border="0" src="'.HTTP_URL.'images/banners/last_days/'.$img.'">
                    </a>
                    <br>
                    <a href="'.HTTP_URL.'hidebanner/'.$username.'" style="font-size:10px; color:#888888;">
                        Don\'t show this banner anymore.
                    </a>
                </div>';
        
        return $body.$banner;
    }
    
    /**
     * Send emails by checking
     * @return 
     */
    public function sendEmails($edit_emails = false){
        if(!is_array($this->emailsToBeSent)){return;}
        
        foreach($this->emailsToBeSent as $email){
            
    		# if this is a submission edit:
    		if($edit_emails){
    			if($email['type'] == "notification"){
    				$email['subject'] = "EDIT: ".$email['subject'];
    			}else{
    				#only send notification emails
    				continue;
    			}
    		}

            if($email['useOld']){
                $body = $this->createOldEmail();
                $to   = $email['to'];
                
                if(is_array($email['from'])){
                    $from = $email['from'][0];
                    if(!Utils::checkEmail($from)){
                        $from = NOREPLY_NAME."<".NOREPLY.">";
                    }else if($email["from"][1] != "none" && $email["from"][1] != "default"){
                        $from = $email["from"][1]."<".$from.">";
                    }
                }else{
                    $from = $email['from'];
                    if(!Utils::checkEmail($from)){
                        $from = NOREPLY_NAME."<".NOREPLY.">";
                    }
                }
                
                $subject = $email['subject'];
                
                if($email['type'] != 'autorespond'){
                    $body = $this->addBanner($body, $email);
                }
                
                Utils::sendOldMail($to, $subject, $body, true, $from, "", null, "X-Related-FormID:". $this->formID);
            }else{
                try {
                    if($email['type'] != 'autorespond'){
                        $email['body'] = $this->addBanner($email['body'], $email);
                    }
                    $email['customHeader'] = "X-Related-FormID:". $this->formID;
                    Utils::sendEmail($email);
                } catch (Exception $e) {
                    Console::error($e);
                }
            } 
        }

    }
    
    /**
     * Creates the olf email exactly
     * @return 
     */
    function createOldEmail(){
        $url=HTTP_URL;
        
        $questions = $this->submission->questions;
        
        $titles = array();
        $res = DB::read("SELECT `question_id`, `value` FROM `question_properties` WHERE `form_id`=#id AND `prop`='text' AND `question_id` IN ('".join("', '", array_keys($questions))."')", $this->submission->formID);
        foreach($res->result as $line){
            $titles[$line['question_id']] = $line['value'];
        }
        
        $contents = '<html><body bgcolor="#f7f9fc" class="Created on Submission">
            <table bgcolor="#f7f9fc" width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td height="30">&nbsp;</td>
            </tr>
            <tr>
              <td align="center"><table width="600" border="0" cellspacing="0" cellpadding="0" bgcolor="#eeeeee" >
                <tr>
                  <td width="13" height="30" background="'.$url.'images/win2_title_left.gif"></td>
                  <td align="left" background="'.$url.'images/win2_title.gif" valign="bottom">
                      <img style="float:left" src="'.$url.'images/win2_title_logo.gif" width="63" height="26" alt="JotForm.com"  />
                  </td>
                  <td width="14" background="'.$url.'images/win2_title_right.gif"></td>
                </tr>
              </table>
              <table width="600" border="0" cellspacing="0" cellpadding="0" bgcolor="#eeeeee" >
                <tr>
                  <td width="4" background="'.$url.'images/win2_left.gif"></td>
                  <td align="center" bgcolor="#FFFFFF">
                  <table width="100%" border="0" cellspacing="0" cellpadding="5">
                  <tr>
                      <td bgcolor="#f9f9f9" width="170" style="text-decoration:underline; padding:5px !important;"><b>Question</b></td>
                      <td bgcolor="#f9f9f9" style="text-decoration:underline; padding:5px !important;"><b>Answer</b></td>
                  </tr>';
                  
                  $x = 0;
                  foreach($questions as $qid => $answer){
                       
                       if(is_array($answer)){
                           $answer = $this->submission->fixValue($answer, $qid);
                       }else{
                           $answer = $this->submission->fixFlatValue($answer, $qid);
                       }
                       
                       if(isset($this->submission->uploads[$qid])){
                            $answer = '<a href="'. Utils::getUploadURL($this->submission->owner->username, $this->formID, $this->sid, $answer) . '">'.$answer.'</a>';
                       }
                       
                       $alt = ($x%2 != 0)? "#f9f9f9" : "white";
                       $contents .= "<tr>
                       <td bgcolor='".$alt."' style='padding:5px !important;' width=170>".@$titles[$qid]."</td>
                       <td bgcolor='".$alt."' style='padding:5px !important;'>".nl2br(stripslashes($answer))."</td>
                       </tr>";
                       $x++;
                  }
                  
                  
                  $contents .= '
                  </table>
                  </td>
                  <td width="4" background="'.$url.'images/win2_right.gif"></td>
                </tr>
                <tr>
                  <td height="4" background="'.$url.'images/win2_foot_left.gif"></td>
                  <td background="'.$url.'images/win2_foot.gif"></td>
                  <td background="'.$url.'images/win2_foot_right.gif"></td>
                </tr>
              </table></td>
            </tr>
            <tr>
              <td height="30">&nbsp;</td>
            </tr>
          </table>';
        return $contents;
    }
}