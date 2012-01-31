<?php 

/**
 * Singleton class for parsing the deploy log.
 */
class DeployLog{

	/**
	 * @var DeployLog
	 */
    private static $instance;
    
    /**
     * @var Deploy
     */
    private $deploy;
    
    /**
     * @var XML
     */
    private $changeLogXML;
    
    /**
     * @var array
     */
    private $changeLogArray = array();
    
    /**
     * Only holds the changed files path as key
     * and modification as value
     * Another array is used for better
     * indexing.
     * Ex:
     * array(
     *  "/lib/x.php" => array( "author" => seyfettin, "action" => "M"),
     *  "/lib/y.php" => array( "author" => aladdin, "action" => "D"),
     * )
     * @var Array
     */
    private $changedFiles = array();
    
    /**
     * Get the instance of the DeployLog class
     * @param  Deploy
     * @return DeployLog
     */
    public static function getInstance($deploy){
         if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c($deploy);
        }
        return self::$instance;
    }
    
    /**
     * @param Deploy $deploy
     */
    private function __construct($deploy){
        $this->deploy = $deploy;
        
       if (file_exists($this->getChangeLogPath())){
            $this->changeLogXML = simplexml_load_file($this->getChangeLogPath());
            if ($this->changeLogXML === false){
                throw new Exception("Cannot fetch file {$this->getChangeLogPath()} for fetching the changed files.");
            }
        }else{
        	if ($this->deploy->isDevelopment()){
        		$this->changeLogXML = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><log><logentry revision="3690"><author>seyhun</author><date>2011-02-07T12:01:31.668732Z</date><paths><path action="M">/lib/classes/deploy/DeployUpdateS3.php</path><path action="A">/lib/classes/deploy/DeployLog.php</path><path action="D">/lib/classes/deploy/DeployCacheManager.php</path><path action="M">/lib/classes/deploy/Deploy.php</path><path action="M">/css/includes/admin.css</path><path action="M">/lib/classes/deploy/DeployAction.php</path></paths><msg>DeployLog implemented.</msg></logentry></log>');
        	}else{
                throw new Exception("Change log file does not exists: " . $this->getChangeLogPath());
        	}
        }
        
        $this->setChangeLogArray();
        $this->setChangedFiles();
    }
    
    /**
     * Checks if the build is triggered from admin panel.
     * @return boolean
     */
    public function isHudsonBuild(){
    	return count($this->changeLogXML->logentry) > 0 ? false : true;
    }
    
    /**
     * Fills the changedFiles.
     */
    private function setChangeLogArray(){

        $lastSuccessBuild = $this->deploy->getLastSuccessBuild();
        # If cannot reach lastSuccess Build return false
        if ($lastSuccessBuild !== null){
            $currentBuild = $this->deploy->getBuildNumber();
            # Foreach build merge the logs and find the list of files changed.
            for ( $buildNumber = $lastSuccessBuild + 1; $buildNumber <= $currentBuild; $buildNumber++){
                # Change log for the build.
                if ( file_exists($this->getChangeLogPath($buildNumber)) ){
                    $changeLogXML = simplexml_load_file( $this->getChangeLogPath($buildNumber) );
                    $numberOfAuthors = count($changeLogXML->logentry);
                    for ($i = 0; $i < $numberOfAuthors; $i++){
                        $author = (string)$changeLogXML->logentry[$i]->author;
                        $message = (string)$changeLogXML->logentry[$i]->msg;
                        $numberOfAuthorFiles = count($changeLogXML->logentry[$i]->paths->path);
                        if (!is_array($this->changeLogArray[$author])){
                            $this->changeLogArray[$author] = array();
                        }
                        $this->changeLogArray[$author][] = $message;
                    } # end of authors loop.
                } # end of checking file exists.
            } # end of build number loop.
        } # baby dont go
    }
    
    /**
     * Return the path of the changelog.xml
     * @param int $buildNumber if not setled the last build number will be used.
     */
    private function getChangeLogPath ($buildNumber = null){
    	if ($buildNumber === null){
    		$buildNumber = $this->deploy->getBuildNumber(); 
    	}
        return  Deploy::PROJECT_PATH . "builds" . DIRECTORY_SEPARATOR . 
                $buildNumber . DIRECTORY_SEPARATOR . "changelog.xml";
    }
    
    /**
     * Search the $msg in the svn commit message and returns the
     * author if its find else it returns false.
     * @param $msg
     * @return string $author
     */
    public function searchMessage($msg){
    	foreach($this->changeLogArray as $author => $messages){
    	    foreach ($messages as $commitMessage){
                if (stristr($commitMessage, $msg)){
                    return $author;
                }
    	    }
    	}
    	return false;
    }
    
    /**
     * Search the regexpr in files changed with the svn commit and returns the
     * author if its found else it returns false.
     * @return string $author
     */
    public function searchFiles($regExpr){
        foreach($this->changedFiles as $fileName => $information){
        	$author = $information["author"];
        	$action = $information["action"];
            if ( preg_match($regExpr, $fileName) ){
                return $author;
            }
        }
        return false;
    }
    
    /**
     * Get the list of the chaged files from the last successfull build.
     */
    private function setChangedFiles(){
    	$lastSuccessBuild = $this->deploy->getLastSuccessBuild();
    	# If cannot reach lastSuccess Build return false
    	if ($lastSuccessBuild !== null){
	        $currentBuild = $this->deploy->getBuildNumber();
	        # Foreach build merge the logs and find the list of files changed.
	        for ( $buildNumber = $lastSuccessBuild + 1; $buildNumber <= $currentBuild; $buildNumber++){
	            # Change log for the build.
	            if ( file_exists($this->getChangeLogPath($buildNumber)) ){
                    $changeLogXML = simplexml_load_file( $this->getChangeLogPath($buildNumber) );
	                $numberOfAuthors = count($changeLogXML->logentry);
	                for ($i = 0; $i < $numberOfAuthors; $i++){
	                    $author = (string)$changeLogXML->logentry[$i]->author;
                        $numberOfAuthorFiles = count($changeLogXML->logentry[$i]->paths->path);
	                    for($j = 0; $j < $numberOfAuthorFiles; $j++){
	                        $pathXML = $changeLogXML->logentry[$i]->paths->path[$j];
	                        $fileName = Utils::path("/".(string)$pathXML, true);
	                        $action = (string)$pathXML->attributes()->action;
	                        $this->changedFiles[$fileName] = array( "action" => $action , "author" => $author);
	                    } # end of files loop. 
	                } # end of authors loop.
	            } # end of checking file exists.
	        } # end of build number loop.
    	} # baby dont go
    } # end of function.
    
    /**
     * Only holds the changed files path as key
     * and modification as value
     * Another array is used for better
     * indexing.
     * Ex:
     * array(
     *  "/lib/x.php" => array( "author" => seyfettin, "action" => "M"),
     *  "/lib/y.php" => array( "author" => aladdin, "action" => "D"),
     * )
     * @var Array
     */
    public function getChangedFiles(){
    	return $this->changedFiles;
    }
}

