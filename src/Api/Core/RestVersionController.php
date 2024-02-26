<?php
/**
* Class RestVersionController 
* This class manages the versions of the API
*/

namespace Legacy\Jot\Api\Core;

class RestVersionController {
    
	/**
	  * @var //RestServer
	 */
	private $rest;
	
    /**
     * Holds the version number which
     * the API is working with.
      * @var //string $version
     */
    private $currentVersion;

    /**
    * Contructor of RestVersionController
    * @param RestServer $rest
    * @return RestVersionController $rest;
    */
    public function __construct($rest=null) {
        $this->rest = $rest;
    }

    /**
     * Returns the current version used in the API
     * @return string $currentVersion
     */
    public function getCurrentVersion(){
        return $this->currentVersion;
    }
    
    /**
     * Sets the current version of the resp api.
     * Return true if a valid versio is wanted,
     * false in otherwise
     * @return boolean
     */
    public function setCurrentVersion(){
        # wanted api version is fetched from request
        $wantedAPIVersion = $this->rest->getRequest()->getWantedAPIVersion();
        
        # if wanted api version is null its an alias of the last version
        if ($wantedAPIVersion === null){
            $this->currentVersion = RestVersions::getInstance()->getDefaultVersion();
            return true;
        }else if( !RestVersions::getInstance()->isVersionAvailable($wantedAPIVersion) ){
            # return 301 moved permanantly
            $this->rest->getResponse()->cleanHeader();
            $this->rest->getResponse()->addHeader("HTTP/1.1 301 Moved Permanently");
            return false;
        }else if ( RestVersions::getInstance()->isVersionDisabled($wantedAPIVersion) ){
            # return 302 moved temp
            $this->rest->getResponse()->cleanHeader();
            $this->rest->getResponse()->addHeader("HTTP/1.1 302 Found");
            return false;
        }else{
            $this->currentVersion = $wantedAPIVersion;
            return true;
        }
    }
    
}

?>