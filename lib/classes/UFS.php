<?php

/**
 * This class is an abract definition for upload classes such as FileController and AmazonS3Controller.
 * The object created by UFS is controlled by UFSController class.
 *
 */

abstract class UFS{
    
    protected $insertID;
    protected $fileName;
    protected $fileType;
    protected $fileTmpName;
    protected $size;
    protected $username;
    protected $formID;
    protected $submissionID;
    protected $submission;
    protected $filePath;
    
    abstract function uploadFile();
    abstract function deleteSubmissionFiles();
    
    function __construct($username=null, $formID=null, $sid=null, $uploadProperties=null, $submission = null){
        
        # Set the objects properties.
        $this->username     = $username;
        $this->submission   = $submission;
        $this->formID       = $formID;
        $this->submissionID = $sid;
        if ($uploadProperties){
            $this->fileName     = $uploadProperties['name'];
            $this->fileType     = $uploadProperties['type'];
            $this->fileTmpName  = $uploadProperties['tmp_name'];
            $this->size         = $uploadProperties['size'];
        }
        
        # This path is the path that file is holded by temp. (This will converted to PHP temp folder later.)
        $this->filePath = $this->fileTmpName;        
    }
    
    public function setProperties(){
        
    }
    
    
    /**
     * Creates a folder name from given preferences
     * @param string     $field
     * @param Submission $submission
     * @return 
     */
    public function createFolderName($field, Submission $submission){
        if($field == "none"){
            $folder = $submission->sid;
        }else if($field == "nofolder"){
            $folder = "/"; # Skip the folder name part
        }else{
            if(isset($submission->questions[$field])){
                $folder = $submission->questions[$field];
                if(is_array($folder)){
                    $folder = $submission->fixValue($folder, $field);
                }
                if(empty($folder)){
                    $folder = "Not Answered - ".$submission->sid;
                }
            }else{
                $folder = $submission->sid;
            }
        }
        
        return $folder;
    }
    
    public function hasIntegration(){
        return true;
    }
    
    public function completeUpload(){
        DB::write("UPDATE `upload_files` SET `uploaded` = '1' WHERE `id` = #insertID LIMIT 1", $this->insertID);
    }
    
    public function disableEntry(){
        DB::write("UPDATE `upload_files` SET `uploaded` = '2' WHERE `id` = #insertID LIMIT 1", $this->insertID);
    }
    
    public function setInsertID($id){
    	$this->insertID = $id;
    }
} 

