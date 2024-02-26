<?php
/**
 * Handles the integration with JotForm and DropBox
 * @package JotForm_Integrations
 * @copyright Copyright (c) 2010, Interlogy LLC
 */

namespace forms;

use Legacy\Jot\Integrations\Configs;
use Legacy\Jot\Integrations\Console;
use Legacy\Jot\Integrations\Dropbox_API;
use Legacy\Jot\Integrations\Dropbox_OAuth_PEAR;
use Legacy\Jot\Integrations\Dropbox_OAuth_PHP;
use Legacy\Jot\Integrations\Exception;
use Legacy\Jot\Integrations\Form;
use Legacy\Jot\Integrations\Integrations;
use Legacy\Jot\Integrations\Server;
use Legacy\Jot\Integrations\UFS;
use Legacy\Jot\Integrations\Utils;

class DropBoxIntegration extends UFS{
    
    public $consumerKey, $consumerSecret, $redirectURL, $config, $root;
    /**
     * @var Dropbox_OAuth_PEAR
     */
    private $oauth;
    /**
     * @var Dropbox_API
     */
    public $dropbox;
    
    /**
     * If inititated empty uses the current session values
     * @constructor
     * @param  $formID  // [optional]
     * @param  $username  // [optional]
     * @return 
     */
    public function __construct($username=null, $formID=null, $sid = null, $uploadProperties = null, $submission = null){
        
        #Parents constructor
        parent::__construct($username, $formID, $sid, $uploadProperties);
        
        $this->submission = $submission;
        
        $this->consumerKey      = Configs::DROPBOX_KEY;
        $this->consumerSecret   = Configs::DROPBOX_SECRET;
        $this->redirectURL      = HTTP_URL."api/dropbox/";
        # User specific information
        $this->config           = new Integrations('dropbox', $this->formID, $this->username);
        $this->root             = "dropbox";
        
        if(@class_exists("OAuth")){
            $this->oauth   = new Dropbox_OAuth_PHP($this->consumerKey, $this->consumerSecret);
        }else{
            $this->oauth   = new Dropbox_OAuth_PEAR($this->consumerKey, $this->consumerSecret);
        }
        
        $this->dropbox = new Dropbox_API($this->oauth);
        if($formID){
            $this->form = new Form($formID);
        }
        
        # Re-Authentcate user
        $this->getSessionBack();
    }
    
    /**
     * Check if the user set an integration with dropbox
     * @return 
     */
    public function hasIntegration(){
        return !$this->config->isNew();
    }
    
    /**
     * Sends file to dropbox account
     * @param  $remotePath
     * @param  $localPath
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
        $baseName = Utils::path("JotForm/"./*$this->username."/".*/$this->form->getTitle()."/".$folder."/".$fileName, true);
        
        $prop = array(
            "action"   => "sendFileToDropBox",
            "username" => $this->username,
            "formID"   => $this->formID,
            "basePath" => urlencode($baseName),
            "filePath" => $transferFile
        );
        
        // Console::log($prop, $serverAddr."/server.php");
        Utils::suppressRequest($serverAddr."/server.php", $prop);
    }
    
    /**
     * Send a file to DropBox server
     * @param  $baseName
     * @param  $filePath
     * @return 
     */
    public function sendFile($baseName, $filePath){
        
        # File path must be completely decoded except the directory seperators
        $baseName = str_replace("%2F", "/", rawurlencode($baseName));
        $baseName = str_replace("%28", "(", $baseName);
        $baseName = str_replace("%29", ")", $baseName);
        
        Console::log("$baseName, $filePath", "sendFile Request");
        try{
            if($this->dropbox->putFile($baseName, $filePath)){
                Console::log("File sent");
                return true;
            }else{
                Console::log("File cannot be sent");
            }
        }catch(Exception $e){
            Console::error($e->getMessage(), "DropBox Error");
        }
    }
    
    /**
     * re-creates the stored authentication parameters
     * @return 
     */
    public function getSessionBack(){
        if($this->config->getValue('dropbox_token')){
            $_SESSION['state']  = $this->config->getValue('dropbox_state');
            $_SESSION['oauth_tokens']  = $this->config->getValue('dropbox_token');
            # $_SESSION['dropbox_secret'] = $this->config->getValue('dropbox_secret');
            $this->oauth->setToken($_SESSION['oauth_tokens']);
        }
    }
    /**
     * Completes the authentication process
     * @return 
     */
    public function completeAuthentication(){
        
        $this->oauth->setToken($_SESSION['oauth_tokens']);
        $tokens = $this->oauth->getAccessToken();
        //print_r($tokens);
        $_SESSION['state'] = 3;
        $_SESSION['oauth_tokens'] = $tokens;
        
        $this->config->setValue("dropbox_state",  $_SESSION['state']);
        $this->config->setValue("dropbox_token",  $_SESSION['oauth_tokens']);
        # $this->config->setValue("dropbox_secret", $_SESSION['dropbox_secret']);
        $this->config->save();
        echo "<script>window.opener.Submissions.dropbox(true);window.close();</script>";
    }
    
    /**
     * Initiates authentication process
     * @return 
     */
    public function authenticate(){
        $tokens = $this->oauth->getRequestToken();
        $_SESSION['state'] = 2;
        $_SESSION['oauth_tokens'] = $tokens;
        Utils::redirect($this->oauth->getAuthorizeUrl($this->redirectURL));
    }
    
    public function deleteSubmissionFiles(){ }
    
    public function removeIntegration(){
        $this->config->removeAll();
        unset($_SESSION['state']);
        unset($_SESSION['oauth_tokens']);
    }
    
    public function getUserInformation(){
        
    }
    
}
