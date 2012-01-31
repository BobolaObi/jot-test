<?php

/**
 * Completes the deploy scripts. Its being called buy Hudson at a commit.
 */
class Deploy{
    
    /**
     * The path of the jotform project
     * in hudson environment.
     * @var string
     */
    const PROJECT_PATH = '/opt/hudson/jobs/jotform/';
    
    const CACHE_VERSION_PATH = 'js_css_version';
    
    const LAST_SUCCESS_PATH = 'last_success_build';

    /**
     * Parameters send to the build files
     * @var array
     */
    private $params;
    
    /**
     * Build number of hudson
     * @var int
     */
    private $buildNumber;

    /**
     * Fetches the last build number that
     * cache is changed.
     * @var integer
     */
    private $lastCachedNumber;
    
    /**
     * The last success build.
     * @var integer
     */
    private $lastSuccessBuild;
    
    /**
     * SVN commit number.
     * @var unknown_type
     */
    private $svnNumber;
    
    /**
     * Holds the action that will be executed in
     * the deploy
     * @var array
     */
    private $deployActions = array();
    
    /**
     * Holds the actions that are deployed success
     * @var array
     */
    private $deployedActions = array();
    
    /**
     * Holds if its in development mode or not.
     * Is setled in construct.
     * @var boolean
     */
    private $isDevelopment = false;
    
    /**
     * The list of servers.
     * @var array
     */
    private $servers = array();
    
    /**
     * @var DeployConfig
     */
    private $config;
    
    /**
     * @param array $params
     */
    public function __construct($params){
    	
    	/**
    	 * Set if its in development mode.
    	 */
    	if ( $_SERVER['SERVER_ADDR'] === "64.34.169.225" ){
    		$this->isDevelopment = false;
    	}else{
            $this->isDevelopment = true;
    	}
    	
    	$this->config = new DeployConfig($this->isDevelopment);
        
        $this->params = $params;
        $this->buildNumber = $this->getParam(1);
        $this->svnNumber = $this->getParam(2);
        
        if ($this->buildNumber === NULL ){
            throw new Exception("Missing parameter in deploy constructer buildNumber:" . var_export($this->buildNumber, true));
        }
        
        $this->lastCachedNumber = $this->fetchLastCachedNumber();
        $this->lastSuccessBuild = $this->fetchLastSuccessBuild();
        
        # Log the fetched data
        $this->comment("Current build: {$this->buildNumber}");
        $this->comment("Last Cached build: {$this->lastCachedNumber}");
        $this->comment("Last Success build: {$this->lastSuccessBuild}");
        $this->comment("");
        
        $listServers = Server::getServerList();
        $this->servers = $listServers['remote'];
        
    }
    
    /**
     * list of servers.
     * @return array
     */
    public function getServers(){
        return $this->servers;
    }
    
    /**
     * This function starts to build the new version.
     */
    public function buildNewVersion(){
    	$this->comment("Testing development mode.");
    	return;
        foreach ($this->deployActions as $deployAction){
        	$className = get_class($deployAction);
            $this->comment( "STARTING DeployAction: " . $className);
            Profile::start("deploy_$className");
            $this->deployedActions[] = $deployAction;
            $deployAction->execute();
            $this->comment( "ENDING DeployAction: " . $className . " Total: " . Profile::end("deploy_$className") . " seconds.");
            $this->comment("");
        }
        $this->finishDeploy();
    }
    
    /**
     * If there is a fail, all deploy actions fallDown method is executed.
     */
    public function fallDeployActions(){
        foreach ($this->deployedActions as $deployedAction){
        	$className = get_class($deployedAction);
            $this->comment($className . " reverting started...");
            Profile::start("deploy_$className");
            $deployedAction->fallDown();
            $this->comment($className . " reverting finished in " . Profile::end("deploy_$className") . " seconds.");
            $this->comment("");
        }
    }
    
    /**
     * Complete the last actions for deployment.
     */
    private function finishDeploy(){
    	$res = @file_put_contents( self::PROJECT_PATH . self::LAST_SUCCESS_PATH, $this->buildNumber );
    	if (!$res){
    		throw new Exception("Cannot write to ".self::PROJECT_PATH . self::LAST_SUCCESS_PATH);
    	}
    	$this->comment("Build finished.");
        # TODO: save new last cached version
    }
    
    /**
     * Find the last build number which
     * cache change is need and return.
     * @return integer
     */
    private function fetchLastCachedNumber(){
    	
    	# In development mode return DEV.
    	if ($this->isDevelopment){
    		return "DEV";
    	}
    	
        $fileName = self::PROJECT_PATH . self::CACHE_VERSION_PATH;
        $fileContent = @file_get_contents($fileName);
        if ($fileContent === false){
        	return null;
        }
        return trim($fileContent);
    }
    
    /**
     * Fetchs the last success build number.
     * @return version
     */
    private function fetchLastSuccessBuild (){
        # In development mode return DEV.
        if ($this->isDevelopment){
            return "DEV";
        }
        $fileName = self::PROJECT_PATH . self::LAST_SUCCESS_PATH;
        $fileContent = @file_get_contents($fileName);
        if ($fileContent === false){
        	return null;
        }
        return trim($fileContent);
    }
    
    /**
     * Prints the message on the hudson panel
     * @param string $message
     * @param string $nl new line
     */
    public function comment($message, $nl = "\n"){
    	if ($this->isDevelopment){
    		$nl = "<br/>";
    	}
        print($message . $nl);
    }
    
    /**
     * Return the parameter wanted
     * @param integer $index
     * @return mixed $index: Returns the wanted parameter
     *                       if its not defined returns null.
     */
    private function getParam($index){
        return isset($this->params[$index]) ? trim($this->params[$index]) : null;
    }
    
    /**
     * @return int $buildNumber
     */
    public function getBuildNumber(){
        return $this->buildNumber;    
    }
    
    /**
     * @return int $svnNumber
     */
    public function getSvnNumber(){
        return $this->svnNumber;
    }
    
    /**
     * Add a deploy action
     * @param DeployAction $className
     */
    public function addAction($className){
        $deployAction = new $className($this);
        if ( $this->isDevelopment || !$deployAction->isTest){
            $this->comment("{$className} is added to build list.");
        }else{
            $this->comment("{$className} NOT added because its in test mode.");
        }
    }
    
    /**
     * return if development or not
     * @return boolean
     */
    public function isDevelopment(){
        return $this->isDevelopment;
    }
    
    /**
     * Return jotform dir
     * @return string $jotformDir
     */
    public function getJotformDir(){
        if ($this->isDevelopment()){
            return dirname(__FILE__) .  DIRECTORY_SEPARATOR . "..".
                                        DIRECTORY_SEPARATOR . "..".
                                        DIRECTORY_SEPARATOR . "..".
                                        DIRECTORY_SEPARATOR;
        }
        return self::PROJECT_PATH . "workspace/jotform3/";
    }
    
    /**
     * @return int
     */
    public function getLastCachedNumber(){
        return $this->lastCachedNumber;
    }
    
    /**
     * @return int
     */
    public function getLastSuccessBuild(){
    	return $this->lastSuccessBuild;
    }
    
}
