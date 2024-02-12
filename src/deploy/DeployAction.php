<?php

/**
  * Class DeployAction
  * Abstract class for an Action in Deploy
  */
abstract class DeployAction {
    
	const CLEAN_MSG = 'HUDSON:CLEAN';
    
    /**
     * @var Deploy
     */
    protected $deploy;
    
    /**
     * @var DeployLog
     */
    protected $log;
    
    public $isTest = true;
    
    /**
     * Contructs the action class with the deploy
     * @param Deploy $deploy
     */
    public function __construct(Deploy $deploy){
        $this->deploy = $deploy;
        # Get the singleton object of the log object.
        $this->log = DeployLog::getInstance($deploy);
    }
    
    /**
     * Returns if the build is forced or not
     * @return boolean
     */
    public function isBuildForced(){
        return  $this->log->isHudsonBuild() ||  # is hudson build.
                $this->deploy->getLastSuccessBuild() === null ||    # can get last success build
                $this->deploy->getLastCachedNumber() === null;      # can get last cache number
    }
    
    /**
     * Returns if the message is $msg is setled or not.
     * The message that must be search
     * @param $msg
     * @return boolean
     */
    protected function doesMessageContains($msg){
        return $this->log->searchMessage('HUDSON:'.$msg) === false ? false : true ;
    }
    
    /**
     * This executes the action neccesary.
     */
    abstract public function execute() ;
    /**
     * This execute is needed when there is a problem.
     */
    abstract public function fallDown() ;
}
