<?php
# require_once ('./lib/classes/UFS.php');

namespace Legacy\Jot\Integrations;

class FTPIntegration extends UFS {
    
    public $config, $ftp;
    
    public function __construct($username=null, $formID=null, $sid = null, $uploadProperties = null, $submission = null){
        
        #Parents constructor
        parent::__construct($username, $formID, $sid, $uploadProperties);
        $this->submission = $submission;
        
        $this->config = new Integrations('FTP', $this->formID, $this->username);
        if($formID){
            $this->form = new Form($formID);
        }
    }
    
    /**
     * Check if the user set an integration with dropbox
     * @return 
     */
    public function hasIntegration(){
        return !$this->config->isNew();
    }
    
    /**
     * Initiates file transfer. makes upload in the background
     * @return 
     */
    public function uploadFile(){
        $serverAddr = Server::getLocalIP();
        $transferFile = UPLOAD_FOLDER . "/" . $this->username . "/" . $this->formID . "/" . $this->submissionID . "/" . Utils::fixUploadName($this->fileName);
        $field = $this->config->getValue("folder_field");
        
        $fileName = Utils::fixUploadName($this->fileName);
        
        $folder = $this->createFolderName($field, $this->submission);
        
        if($folder == "/"){
            $fileName = ($this->submissionID . "-" . $fileName);
        }
        
        # Disable date for now to support dropdown or radio button values
        # $folder .= " - ".date("M j, Y");
        $baseName = Utils::path($this->config->getValue('path')."/".$this->form->getTitle()."/".$folder."/".$fileName, true);
        
        $prop = array(
            "action"   => "sendFileToFTP",
            "username" => $this->username,
            "formID"   => $this->formID,
            "basePath" => urlencode($baseName),
            "filePath" => $transferFile
        );
        
        # Console::log($prop, $serverAddr."/server.php");
        Utils::suppressRequest($serverAddr."/server.php", $prop);
    }
    
    /**
     * Send the file to given FTP integration
     * @param object $baseName
     * @param object $filePath
     * @return 
     */
    public function sendFile($baseName, $filePath){
        $this->ftp = new FTPLib($this->config->getValue("host"), $this->config->getValue("username"), $this->config->getValue("password"), $this->config->getValue("port"));
        $this->ftp->connect();
        //$baseName = str_replace("%2F", "/", rawurlencode($baseName));
        
        #Console::log($baseName.", ".$filePath);
        try{
            if(@$this->ftp->putFile($filePath, $baseName)){
                #Console::log("File sent");
                return true;
            }else{
                $e = error_get_last();
                #Console::log($e, "File cannot be sent");
                throw new Exception("File cannot be sent:".$e['message']);
            }
        }catch(Exception $e){
            Console::error($e->getMessage(), "FTP Error");
            throw $e;
        }
        
    }
    
    /**
     * Returns the list of files onad folder under given path
     * @param object $path
     * @return 
     */
    public function getDir($path){
        $this->ftp = new FTPLib($this->config->getValue("host"), $this->config->getValue("username"), $this->config->getValue("password"), $this->config->getValue("port"));
        $this->ftp->connect();
        return $this->ftp->getFilesArray($path);
    }
    
    public function deleteSubmissionFiles(){ }
    
    public function removeIntegration(){
        $this->config->removeAll();
    }
}
?>