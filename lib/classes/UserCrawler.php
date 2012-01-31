<?php

abstract class UserCrawler{
	
	/**
	 * 
	 * The critical load limit used in isLoadCritical function.
	 * @var Integer
	 */
	const loadLimit = 2; 
	
	/**
	 * Used for limit of fetched users at once.
	 * And used for number of users executed at once without time delay.
	 * @var Integer
	 */
	const defaultLimit = 100;

	/**
	 * The start of the records, it is 0 by default.
	 * @var Interger
	 */
	protected $start = 0;
	/**
	 * The limit of fetching users each time. Default is 100.
	 * @var Integer
	 */
	protected $limit = UserCrawler::defaultLimit;
	/**
	 * This flag is for fetching users from old database. By default it is false. 
	 * @var Boolean
	 */
	protected $oldUsers = false;
	/**
	 * Array of values, of a row in user table.
	 * @var Array
	 */
	protected $userDetails;
	/**
	 * Delay after $x number of user. $x is defined by $userFrequency.
	 * @var Integer
	 */
	protected $executeDelay = 0;
	/**
	 * The number of users that will be executed without delay. By default this is 
	 * eqal to the numver of $limit.
	 * @var Integer
	 */
	protected $userFrequency = UserCrawler::defaultLimit;

	/**
	 * Holds number of total users.
	 * @var Interger
	 */
	protected $totalUser;
	
    /**
     * Criteria to be used in the USER Select query. Filters users for this criteria
     * @var Array
     */
    protected $criteria = "1";
    
	/**
	 * This function executes an operation on user.
	 * It must be overwritten by other classes extends this class.
	 */
    abstract public function execute(); 
    abstract public function setProperties();

    /**
     * Checks the table is completed or not.
     * Returns true or false
     */
    public function isFinished(){
    	return $this->start + $this->limit >= $this->totalUser + $this->limit ;
    }
    
    /**
     * Sets the where clause. Don't use parameter binding with : or # just write regular where clause
     * @param object $criteria
     * @return 
     */
    public function setCriteria($criteria){
        $this->criteria = $criteria;
    }
    
	/**
	 * Initialize the class.
	 */
	public function __construct ($chunk = false){
		
        $this->setProperties();
        
		$this->totalUser = $this->getTotalUser();

		if ($chunk !== false){
			$this->setStart($chunk);
		}

		# Open log file to start writing
		Console::openConsole();

		# Set memory limit and execution time.
		ini_set('memory_limit','1000M');
		ini_set('max_execution_time','0'); // Never timeout
		set_time_limit(0);

		# Connect old database if it is setled.
		if ($this->oldUsers !== false){
			DB::useConnection("main");
		}
	}
    /**
     * Sets the size of each chunk
     * defaults to 100
     * @param object $size
     * @return 
     */
	public function setChunkSize($size){
		$this->limit = $size;
	}
	
	/**
	 * Set the start point.
	 * @param Integer $start
	 */
	public function setStart($start){
		$this->start = $start;
	}
	
	/**
	 * Get the next chunk.
	 */
	public function setNextStart(){
		$this->start = $this->start + $this->limit;
	}
	
	/**
	 * Collect a chunk of user according to the start (default 0) and
     * limit(default 100) properties of the class.
	 * To set those properties use setLimit and setStart.
	 * Returns the users in an array.
	 * @return Array (user row)
	 */
	private function getChunk(){

		$this->log('Getting the user from '.$this->start.' to '.($this->start + $this->limit));

		$res = DB::read("SELECT * FROM `users`
                  WHERE ".$this->criteria." 
                  ORDER BY `created_at` ASC
                  LIMIT #start, #limit", $this->start, $this->limit);
		return $res->result;
	}
	
    /**
     * Checks if stop file exists in temp folder
     * @return 
     */
    public function checkEmergencyStop(){
        if(@file_exists("/tmp/stop")){
            @unlink("/tmp/stop");
            return true;
        }
        return false;
    }
    
	/**
	 * This function browse all users for an operation.
	 */
	public function browseUsers(){
		
		
		# Get the total number of users and log it.
		$this->log( 'Total of ' . $this->totalUser . ' Users will be migrated');
		$index = 0;
		# foreach user..
		$users = $this->getChunk();
		foreach($users as $key=>$user){
            $index++; #current user index
            $startTime = microtime(true);
            if($this->checkEmergencyStop() === true){
                return "Emergency Stop";
            }
            
            # If user clicked stop button on the browser finish process
            # Does not work right now. I don't know the reason
            if(connection_aborted() === 1){
                Console::log("Operation cancelled from browser");
                return "Operation cancelled from browser";
            }
            
			if ( ($key % $this->userFrequency) === 0 ){
                # Sleep according to user frequency.
                usleep($this->executeDelay * 1000000);
            }

			# get username
			$username = $user['username'];
			
			if ( $user['status'] === "SUSPENDED" || $user['status'] === "AUTOSUSPENDED" ){
				continue;
			}
			
			# log the memory usage.
			$this->log("User " . $username . " Started With: " . Utils::bytesToHuman(memory_get_usage()));
			
			try{
				$this->userDetails = $user;
                $res = $this->execute();
			}catch(Exception $e){
				Console::error($e, "User Crawler Error.");
			}
            
            # Flush to check connection
			flush(); ob_flush(); ob_end_flush();
            
            $this->saveStatus($username, $index, $startTime);
            
            # log memory usage after using.
			$this->log("User " . $username . " Ended With: " . Utils::bytesToHuman(memory_get_usage()). " Peak usage was: ".Utils::bytesToHuman(memory_get_peak_usage()));
            // Last execute script told us to break loop
            if($res === 'break'){
                return "Script stoppped inside execute method";
            }
		}
        return true;
	}
	
    /**
     * Saves the current status of the script into a file or database
     * @return 
     */
    private function saveStatus($username, $userIndex, $startTime){
        $end = microtime(true);
        $status = array(
            "username"   => $username,
            "index"      => $userIndex,
            "chunkStart" => $this->start,
            "chunkEnd"   => (float) ($this->start + $this->limit),
            "totalUsers" => $this->totalUser,
            "startTime"  => $startTime,
            "time"       => $end,
            "spend"      => sprintf("%01.3f", abs($end - $startTime))
        );
        Console::log($status);
        $json = json_encode($status);
        # file_put_contents("/tmp/crawlStatus.json", $json);
        Settings::setSetting("usercrawler", "status", $json);
    }
    
	private function getTotalUser(){
		$countResult = DB::read("SELECT count(*) FROM `users` WHERE ".$this->criteria);
		 
		return $countResult->first['count(*)'];
	}
	
	protected function log($message){
		Console::log($message, __CLASS__);
	}
	
	protected function isLoadCritical($printLoad = false){
        $loadAvgParams = preg_split(  "/\s+/",  @shell_exec ("cat /proc/loadavg") ) ;
        
        if ( isset($loadAvgParams[0]) ){
        	$loadAvg = floatval($loadAvgParams[0]);
            if ($printLoad){
        	   Console::log("Load avg is: " . $loadAvg);
            }
        	if ($loadAvg > self::loadLimit){
        		return true;
        	}
        }
        return false;    
	}
	
	public function __destruct(){
        Console::closeConsole();
    }
}
