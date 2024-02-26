<?php

use Legacy\Jot\Exceptions\JotFormException;
use Legacy\Jot\Utils\DB;


class UFSController {

	/**
	 * This holds interfaces and keys true or false, decided according there are used or not.
	 * This is default value. You can change it using setInterfaces.
	 * @var String Array
	 */
	private $interfaces = array(
		UploadControllers::FileController,
        UploadControllers::AmazonS3Controller,
        UploadControllers::DropBox,
        UploadControllers::FTP
	);

	private $controllers = array();

	private $username, $formID, $submissionID, $fileName, $fileType, $fileTmpName, $size, $uploadProperties, $submission;
    
    
    
    /**
     * Create one instance for each
     * @params username
     * @params formID
     * @params sid
     * @params uploadProperties - Array with keys 'name', 'type', 'tmp_name' and 'size'
     * @params definedInterfaces - Array with keys UploadControllers ( Ex: UploadControllers::AmazonS3Controller )
     * @return 
     */
    public function __construct($username, $formID, $sid, $uploadProperties = null, $definedInterfaces = null, $submission = null){

        $this->submission = $submission;
        
        # Set the objects properties that are needed for insert delete.
        $this->username     = $username;
        $this->formID       = $formID;
        $this->submissionID = $sid;
        if ($uploadProperties){
            $this->uploadProperties = $uploadProperties;
            $this->fileName     = $uploadProperties['name'];
            $this->fileType     = $uploadProperties['type'];
            $this->fileTmpName  = $uploadProperties['tmp_name'];
            $this->size         = $uploadProperties['size'];
        }
        
        if ( !isset($definedInterfaces) ){
        	$definedInterfaces = $this->interfaces; 
        }
        
        if(defined('ENABLE_UFS') && ENABLE_UFS === false){
            $definedInterfaces = array(UploadControllers::DropBox, UploadControllers::FileController, UploadControllers::FTP);
        }
        
        
        foreach ($definedInterfaces as $className){
            # later change this to direct dropbox checker. Only disable if dropbox libraries are not found
            if((!DROPBOX_AVAILABLE || APP) && $className == 'DropBoxIntegration'){ continue; }
            $this->setController($className);
        }
    }

	/**
	 * Set the interfaces sended by the arguments
	 * Ex: $ufsc->setInterfaces(UploadControllers::AmazonS3Controller, UploadControllers::FileController);
	 */
	public function defineInterfaces(){
		$controllers = func_get_args();
		# Reset the interfaces.
		$this->controllers = array();
		# Control and set the controllers.
		foreach ($controllers as $controller){
			$this->setController($controller);
		}
	}

	private function setController($className){
		if ( @class_exists($className) ){
		    
		    
			$controller = new $className($this->username, $this->formID, $this->submissionID, $this->uploadProperties, $this->submission);
			$controller->setProperties();
			if($controller->hasIntegration()){
				array_push ($this->controllers, $controller);
			}
		}else{
			throw new Exception("Cannot create with controller name " . $className);
		}
	}

	public function uploadFile(){
	    
        $id = $this->addFileToDatabase();
        $this->operateMethod("setInsertID", array($id));
		$this->operateMethod("uploadFile");
	}
	
	public function deleteSubmissionFiles(){
		$this->operateMethod("deleteSubmissionFiles");
		$this->removeSubmissionFilesFromDatabase();
	}

	private function operateMethod($methodName, $params=array()){	    
		foreach ($this->controllers as $controller){
			if ( method_exists ($controller, $methodName) ){
				call_user_func_array(array($controller, $methodName), $params);
			}else{
				throw new JotFormException("Wrong parameter number in construct.");
			}
		}
	}

	private function addFileToDatabase(){
		$response = DB::write(" REPLACE INTO `upload_files` (`name`, `type`, `size`, `username`, `form_id`, `submission_id`, `date`)
                                VALUES (':name', ':type', #size, ':username', ':form_id', ':submission_id', NOW() )", $this->fileName,
		$this->fileType, $this->size, $this->username, $this->formID, $this->submissionID);

		if ($response->success === false){
			throw new JotFormException("Cannot add uploaded file to system.");
		}
		return $response->insert_id;
	}

	private function removeSubmissionFilesFromDatabase(){
		$response = DB::write("DELETE FROM `upload_files` WHERE `form_id`=':form_id' AND `submission_id`=':submission_id' AND `username`=':username'",
		$this->formID, $this->submissionID, $this->username);
		if ($response->success === false){
			throw new JotFormException("Cannot remove uploaded file from system.");
		}
	}
	
	/**
	 * Control if file is uploaded before.
	 * @return Boolean
	 */
	public function fileUploaded(){
		$response = DB::read( "SELECT * FROM `upload_files` WHERE `name` = ':name' AND `form_id` = ':formID' AND `submission_id` = ':sid' AND `username` = ':username'",
		                      $this->fileName, $this->formID, $this->submissionID, $this->username);
        if ($response->rows > 0){
            
        	# If file is not uploaded correctly remove it and return true.
            $upload = $response->first;
            if (!$upload['uploaded']){
            	# Remove the entry.
            	$res = DB::write("DELETE FROM `upload_files` WHERE `id` = #id LIMIT 1", $uploads['id']);
            	return false;
            }else{
                return true;
            }
        }else{
            return false;
        }
	}

	public function __destruct(){
		foreach ($this->controllers as $controller){
			unset($controller);
		}
	}
}


