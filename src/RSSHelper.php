<?php 

/**
 * Helper class for generating RSS feeds. Uses the FeedItem, FeedWriter external classes.
 * 
 */
class RSSHelper {
    public $isIpNeeded = false,  $isCreatedAtNeeded = false, $formID = 0;
    public $maxNumberOfSubmissions = 10;    // Do not show more than 10k submissions in RSS feed.
    public $chunk = 10;       // Split answers into chunks in order to protect server from overload
    public $qidListArr = false; // Array. Fields selected to be shown in RSS.
    public $qidListStr = false; // String. Fields selected to be shown in RSS.
    public $form = false;       // We need form title to show.
    public $uploadID = false;
    public $subDetails = false;
    
    /**
     * Constructor takes form ID of the form in question and the field list array
     * which contains the questiond IDs of questions we want in the feed; plus
     * -1 for IP field and -2 for the submission date field.
     * 
     * @param $formID
     * @param $fieldList
     * @return unknown_type
     */
    public function __construct($formID, $fieldList = array()) {
        $this->form = new Form($formID);
        $this->formID = $formID;
        
        if(!is_array($fieldList)){
            $fieldList = explode(',', $fieldList);
        }
        
        // Remove IP and Submission Date fields as they are not in the answers
        // table. Use $isIpNeeded and $isCreatedAtNeeded variables instead.
        $ipKey = array_keys($fieldList, '-1');
        if (!empty($ipKey)) {
            unset($fieldList[$ipKey[0]]);
            $this->isIpNeeded = true;
        }
        $createdAtKey = array_keys($fieldList, '-2');
        if (!empty($createdAtKey)) {
            unset($fieldList[$createdAtKey[0]]);
            $this->isCreatedAtNeeded = true;
        }
        
        $this->qidListArr = $fieldList;
        $this->qidListStr = implode(', ', $fieldList);
    }
    /**
     * 
     * @return 
     */
    public function getSubmissions() {
        $feedData = array();
        $submissionsQuery = 'SELECT `id`, ' . ($this->isIpNeeded? '`ip`, ' : '') . '`created_at` ' . 
                     'FROM `submissions` WHERE `form_id` = #formID ORDER BY `created_at` DESC LIMIT #currentChunk, #chunk';
        
        $answersQuery = 'SELECT `submission_id`, `question_id`, `value` FROM `answers` WHERE (`item_name` IS NULL OR `item_name` = "") AND ' . 
                      '`submission_id` IN (:submissionIDs) AND `question_id` IN (:qidListStr)';
        
        for ($currentChunk = 0; $currentChunk < $this->maxNumberOfSubmissions; $currentChunk += $this->chunk) {
            $submissions = DB::read($submissionsQuery, $this->formID, $currentChunk, $this->chunk);
            
            if ($submissions->rows < 1) { break; } // If there are no results, break.
            
            $sIDs = "";
            foreach($submissions->result as $singleRow) {
                $sID = $singleRow['id'];
                $sIDs .= $sID . ", ";
                $singleSubmission = array();
                if ($this->isIpNeeded) {
                    $singleSubmission['ip_address'] = $singleRow['ip'];
                }
                $singleSubmission['created_at'] =  $singleRow['created_at'];
                $singleSubmission['answers'] =  array();
                $feedData[$sID] = $singleSubmission; 
            }
            $lastNumberOfSubmissions = $submissions->rows; 
            // We don't need $submissions anymore, delete it.
            unset($submissions);
            
            // Trim the extra ", " characters from the end.
            $sIDs = rtrim(''.$sIDs, ', ');
            // Get all the answers.
            $answers = DB::read($answersQuery, $sIDs, $this->qidListStr);
            foreach($answers->result as $singleRow) {
                $sID = $singleRow['submission_id'];
                $qid = $singleRow['question_id'];
                $value = $singleRow['value'];
                $feedData[$sID]['answers'][$qid] = $value;
            }
            // We don't need $answers anymore, delete it.
            unset($answers);
            
            if ($lastNumberOfSubmissions < $this->chunk) {
                // All results have been returned
                break;
            }
        }
        
        return $feedData;
    }
    
    /**
     * Fetches each text of the questions in the field list for a form.
     * Returns an array such as ['3' => 'Your Name', '6' => 'Country', '7' => 'Message'] etc.
     * 
     * @return unknown_type
     */
    public function fetchQuestionText() {
        $query = 'SELECT `question_id`, `value` FROM `question_properties` WHERE `form_id` = #formID AND (`prop` = "text" OR (`prop` = "type" AND `value` = "control_fileupload"))';
        $res = DB::read($query, $this->formID);
        $questionNames = array();
        $uploadID = FALSE;
        foreach($res->result as $textArr) {
            if ($textArr['value'] == 'control_fileupload') {
                $this->uploadID = $textArr['question_id'];
                continue;
            }
            $questionNames[$textArr['question_id']] = $textArr['value'];
        }
        
        return $questionNames; 
    }
    
    public function show() {
        // Boilerplate code for the feed.
        $feed = new FeedWriter(RSS2);
        $feed->setTitle(Configs::COMPANY_TITLE." Submissions");
        $feed->setLink(HTTP_URL);
        $feed->setDescription('Your Form Submissions');
        //Image title and link must match with the 'title' and 'link' channel elements for RSS 2.0
        $feed->setImage(Configs::COMPANY_TITLE.' Submissions', HTTP_URL, HTTP_URL.'/images/logo.png');
        $feed->setChannelElement('language', 'en-us');
        $feed->setChannelElement('pubDate', date(DATE_RSS, time()));
        $subDetails = false;
        
        // This should never happen, but if the field list is empty, return an empty feed.
        if (empty($this->qidListArr)) {
            $feed->genarateFeed();            
            return;
        }
        
        $questionNames = $this->fetchQuestionText();
        
        $feedData = $this->getSubmissions();
        
        foreach($feedData as $sID => $singleSubmission) {
            $newItem = $feed->createNewItem();
            $newItem->setTitle($this->form->form['title'] . " - Submission (" . $singleSubmission['created_at'] . ")");
            if (IS_SECURE) {
            	$itemLink = SSL_URL;
            } else {
            	$itemLink = HTTP_URL;
            }
            
            $rssLink = $itemLink . "submission/" . $sID;
            
            $newItem->setLink($rssLink);
            $newItem->setDate($singleSubmission['created_at']);
            $desc = "\n<p>";
            
            
            $answers = $singleSubmission['answers'];
            
            // If submission date is wanted on the RSS feed data:
            if ($this->isCreatedAtNeeded) {
                $label = 'Submission Date';
                $desc .= '<b>' . $label . ' :</b> ' . $singleSubmission['created_at'] . "<br/>\n";
            }

            // If IP  is wanted
            if ($this->isIpNeeded) {
                $label = 'IP Address';
                $desc .= '<b>' . $label . ' :</b> ' . $singleSubmission['ip_address'] . "<br/>\n";
            }
            foreach($this->qidListArr as $qid){
                $label = $questionNames["$qid"];
                $singleAnswer = (isset($answers["$qid"])? $answers["$qid"] : "");
                
                if ($this->uploadID !== false && $this->uploadID == $qid) {
                    // This question is a file upload question. Create a link.
                    if ($subDetails === false && $this->subDetails === false) {
                        $subDetails = $this->subDetails = self::getSubmissionDetails($sID);
                    } else if ($subDetails === false ) {
                        $subDetails = $this->subDetails;
                    }
                    $uploadURL = Utils::getUploadURL($subDetails[0], $subDetails[1], $sID, $singleAnswer);
                    $singleAnswer = '<a href="' . $uploadURL . '">' . $singleAnswer . '</a>';
                }
                $desc .= '<b>' . $label . ' :</b> ' . $singleAnswer . "<br/>\n";
            }
            $desc .= "</p>\n";
            $newItem->addElement("guid", $rssLink);
            $newItem->addElement("description", $desc); //, array('style' => 'white-space:pre'));
            $feed->addItem($newItem);
        }
        
        $feed->genarateFeed();
    }

    /**
     * Returns the username and form ID that is related with this submission.
     */
    public static function getSubmissionDetails($sID) {
    	$result = DB::read("SELECT `form_id` FROM `submissions` WHERE `id` = :sid", $sID);
    	if ($result->rows < 1) {
    		return false;
    	}
    	$formID = $result->first["form_id"];
    	
        $result = DB::read("SELECT `username` FROM `forms` WHERE `id` = :formID", $formID);
        if ($result->rows < 1) {
            return false;
        }
        $username = $result->first["username"];
        
        $toReturn = array($username, $formID);
        return $toReturn;
    }
}

