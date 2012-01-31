<?php
/**
 * Loops the upload of the user and corrects all mistakes about sync.
 * @author seyhunsariyildiz
 */
class SyncAmazonUploads extends UserCrawler{
	
	const connectionLimit = 90;
	private $as3c;
	private $usersSubmissions = array();
	
	public function setProperties(){
		$this->limit = 10;
		$this->userFrequency = 1;
		$this->executeDelay = 0;

		# Initialize amazon
		$this->as3c = new AmazonS3Controller();
		$this->as3c->setProperties();
	}

	/**
	 * This function is executed after each user.
	 */
	public function execute($username = false){
		# Get username
		# If username is send from parameters, work for that user.
		if ($username === false){
            $username = $this->userDetails['username'];
		}
		
		if ( !trim($username) ){
			return;
		}
		
		# Get the uploads
	    $res = DB::read("SELECT * FROM `upload_files` WHERE `username` = ':s' AND `uploaded` = 1", $username);
            
        # Loop uploads
        foreach ($res->result as $row){
        	usleep(0.2*1000000);
            # Upload file
            $this->completeUpload($row);
		}
		
		Console::log(print_r($this->usersSubmissions, true));
	}
	
	public function completeUpload($row){
        Console::log("Uploading entry with id: " . $row['id']);
		
		# File path on server
        $filePath = UPLOAD_FOLDER . "/" . $row['username'] . "/" . $row['form_id'] . "/" . $row['submission_id'] . "/"
                    . Utils::fixUploadName($row['name']);
        # base path for amazon
        $baseName = $row['username'] . "/" . $row['form_id'] . "/" . $row['submission_id'] . "/"
                    . Utils::fixUploadName($row['name']);
        
        # Entry id
        $entryID = $row['id'];
        
        # Set id of the file for completeUpload
        $this->as3c->setInsertID($entryID);
        
        # in Amazon
        $inAmazon = $this->as3c->fileExists("jufs", $baseName);
        # in submissions
        $res = DB::read("SELECT * FROM `submissions` WHERE `id` = ':sid'", $row['submission_id']);
        $inSubmission = $res->rows > 0 ? true : false;

        if ($row['size'] == 0){
        	Console::log("Size of entry is 0. Passing it.");
            $this->as3c->disableEntry();
        }else if (!$inSubmission && $inAmazon){
        	Console::log("Entry does not exists in submissions, but exists in Amazon.");
			# delete from  amazon and db
        	$this->as3c->suppressDelete($baseName);
			$this->as3c->disableEntry();
			Console::log("Entry deleted from amazon and removed from upload_files.");
        }else if (!$inSubmission && !$inAmazon){
            Console::log("Entry does not exists in submissions and Amazon");
            $this->as3c->disableEntry();
            Console::log("Entry removed from upload_files.");
        }else if ($inSubmission && $inAmazon){
            Console::log("Entry exists in submissions and Amazon");
			$this->addToSubmissions($row['form_id'], $row['submission_id']);
            $this->as3c->completeUpload();
            Console::log("Entry updated at upload_files.");
        }else if ($inSubmission && !$inAmazon){
            Console::log("Entry exists in submissions, but does not exists in Amazon");
			$this->addToSubmissions($row['form_id'], $row['submission_id']);
            # if file does not exists, look to the other servers
	        if (!file_exists($filePath)){
	            Console::log("File is not uploaded in this server. Looking to other servers..");
	
	            $request = new RequestServer(array(
	                "action" => "sendFileToAmazonS3",
	                "filePath" => $filePath,
	                "baseName" => $baseName,
	                "formID" => $formID,
	                "toAll" => "yes",
	                "async" => "no",
	                "skipSelf" => "yes"
	            ), true);
	
	            $responses = $request->getResponse()->other_responses;
	            $found = false;
	            foreach ($responses as $server => $response){
	                if ($response->success){
	                    $found = true;
	                    break;
	                }
	            }
	            if ($found === false){
	                Console::log("Cannot find file in other servers: " . print_r($responses, true));
	                $this->as3c->disableEntry();
	            }else{
	                Console::log("File founded in other servers.");
	            }
	            
	        }else{
	            Console::log("Entry Exists in this server. Uploading..");
	            # Upload the file finally
	            Console::log("File path: {$filePath}, Base name: {$baseName}");
	            if ($this->as3c->suppressUpload($filePath, $baseName)){
	                Console::log("Upload completed successfully.");
	            }else{
	                Console::log("Error in upload file.");
	            }
	        }
        }
	}
	
	private function addToSubmissions($formId, $submissionId){
		if ( !isset($this->usersSubmissions[$formId]) ){
			$this->usersSubmissions[$formId] = array();
		}
		array_push($this->usersSubmissions[$formId], $submissionId);
	}
}