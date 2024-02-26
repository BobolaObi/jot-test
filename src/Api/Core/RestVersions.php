<?php
/**
* Class RestVersions 
* This class holds the versions of the API system
* Singleton class
*/

namespace Legacy\Jot\Api\Core;


class RestVersions{
    
    /**
     * Static instance of the
     * RestVersions object
      * @var //RestVersion
     */
    static private $instance;
    
    /**
     * Holds the versions of the API as the key of the
     * array and the value defines if the version is
     * available or not.
      * @var //array $versions
     */
    private $versions = array();
    
    /**
     * Holds the default API version of the system.
      * @var //string $defaultVersion
     */
    private $defaultVersion;
    
    /**
     * Get the instance of the RestVersions class
     * @return RestVersions
     */
    public static function getInstance(){
         if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }
    
    /**
     * Checks if the version is available under
     * the system.
     * 
     * @param string $verison
     * @return booloan $isAvailable
     */
    public function isVersionAvailable($version){
        return isset($this->versions[$version]); 
    }
    
    /**
     * Return if the version is disabled.
     * @param string $version
     * @return boolean $isDisabled
     */
    public function isVersionDisabled($version){
        return !$this->versions[$version];
    }
    
    /**
     * Add the version to the system.
     * @param string $versionNumber
     * @param boolean $isDefaultVersion
     */
    public function addVersion ($version, $isDefaultVersion = false, $isVersionAvailable = true ){
        if ($isDefaultVersion){
            $this->defaultVersion = $version;
        }
        $this->versions[$version] = $isVersionAvailable;
    }
    
    /**
     * Return the default version of the API
     * $return string
     */
    public function getDefaultVersion (){
        if (!$this->isVersionAvailable($this->defaultVersion)){
            throw new \Exception("Default version of the API is not available.");
        }
        return $this->defaultVersion;
    }
    
    /**
     * Test the $fetchedVersion if it is ordinary
     * for a version number or not.
     * @param string $fetchedVersion
     * @return bool
     */
    public function isVersionSyntaxCorrect($fetchedVersion){
    	return preg_match ('/^v\d+(\.\d+)?$/', $fetchedVersion);
    }
}


