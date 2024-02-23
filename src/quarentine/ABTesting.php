<?php

use Legacy\Jot\UserManagement\Session;
use Legacy\Jot\UserManagement\User;
use Legacy\Jot\Utils\DB;
use Legacy\Jot\Utils\Utils;


abstract class ABTesting {
    static    $groupNames = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    protected $totalGroup = 2;
    protected $maxParticipient = 100;
    protected $total = false;
    protected $resetOnLogin = false;
    public    $user;
    public    $className;
    static    $instances = array();
    const     SESSION = 'ABTest';
    
    abstract static function getClass();
    abstract static function getGroupNames();
    
    /**
     * Gets the instance of this object to be used as singleton
     * @return 
     */
    static function getInstance($className = false){
        if(isset(self::$instances[$className])){
            return self::$instances[$className];
        }
        
        if($className === false){
            $a = get_called_class();
        }else{
            $a = $className;
        }
        
        self::$instances[$className] = new $a();
        self::$instances[$className]->className = $a;
        return self::$instances[$className];
    }
    
    /**
     * Checks if the participiant is suitiable for this test
     * return true if it's OK
     * @param object $user
     * @return bool
     */
    abstract function checkParticipant();
    
    /**
     * Tell test to assign user from here
     * @return 
     */
    public function assignTestUser($username = false){
        return ABTestingController::assignUserTo($this->className, $username);
    }
    
    /**
     * Get the group number of participiant
     * @return 
     */
    public function getGroupNumber(){
        
        if( array_key_exists($this->className, ABTestingController::$activeTests) ){
            if(isset($_SESSION[ABTesting::SESSION]) && $_SESSION[ABTesting::SESSION]['test_name'] == $this->className){
                return $_SESSION[ABTesting::SESSION]['group_name'];
            }
        }
        
        return false;
    }
    
    /**
     * Get the letter represantation of a group
     * @return 
     */
    public function getGroupName(){
        $no = $this->getGroupNumber();
        if($no !== false){
            return self::$groupNames[$no];
        }
        return false;
    }
    
    /**
     * Get the total
     * @return 
     */
    public function getParticipantsTotal(){
        //if($this->total === false){
        $res = DB::read("SELECT count(*) as `total` FROM `test_participants` WHERE `test_name` = ':name'", $this->className);
        $this->total = $res->first['total'];
        //}
        return $this->total;
    }
    
    /**
     * Check is there any room for a new participant
     * @return bool
     */
    public function isAvailable(){
        $total = $this->getParticipantsTotal();
        
        if($total >= ($this->maxParticipient * $this->totalGroup) ){
            return false;
        }
        
        return true;
    }
    
    /**
     * Get a group number for given participant
     * @return 
     */
    public function assignGroup(){
        $total = $this->getParticipantsTotal();
        return ($total % $this->totalGroup);
    }
    
    /**
     * Returns the participant info from database
     * @param object $username
     * @return 
     */
    public function getTestParticipant($username){
        $res = DB::read("SELECT * FROM `test_participants` WHERE `username`=':username' AND `test_name`=':testName'", $username, $this->className);
        if($res->rows > 0){
            $this->user = User::find($username);
            return $res->first;
        }
        return false;
    }
    
    /**
     * Checks if the current user already a participant or not
     * @return 
     */
    public function isParticipant($username = false){
        
        if($username === false){
            $username = $this->user->username;
        }
        
        $res = DB::read("SELECT * FROM `test_participants` WHERE `username`=':username'", $username);
        if($res->rows > 0){
            return $res->first;
        }
        return false;
    }
    
    /**
     * Returns the list of completed goals for this participant
     * @return 
     */
    public function getParticipantsGoalNames(){
        $res = DB::read("SELECT `goal_name` FROM `test_goals` WHERE `username`=':username'", $this->user->username);
        $goals = array();
        foreach($res->result as $line){
            $goals[] = $line['goal_name'];
        }
        return $goals;
    }
    
    /**
     * Adds user to database
     * @return 
     */
    public function joinTest(){
        
        $participant = $this->isParticipant();
        
        if($participant === false){
            $group = $this->assignGroup();
            
            $_SESSION[ABTesting::SESSION] = array(
                "test_name"     => $this->className,
                "group_name"    => $group,
                "goals"         => array()
            );
            
            DB::insert('test_participants', array(
                "username"  => $this->user->username,
                "test_name"  => $this->className,
                "group_name" => $group
            ));
        }else{
            $_SESSION[ABTesting::SESSION] = $participant;
            $_SESSION[ABTesting::SESSION]['goals'] = $this->getParticipantsGoalNames();
        }
        
        return $group;
    }
    
    /**
     * Delete user from participants table
     * @return 
     */
    public function dropGuest(){
        $guestName = Utils::getCookie("guest");
        if ($this->resetOnLogin){
            DB::write("DELETE FROM `test_participants` WHERE `username` = ':username' AND `test_name`=':testName' ", $guestName, $this->className);
            DB::write("DELETE FROM `test_goals` WHERE `username` = ':username'", $guestName);
            unset($_SESSION[ABTesting::SESSION]);
        }
    }
    /**
     * Update the username on the tables when user creates an account or logins
     * @return 
     */
    public function updateGuest(){
        $guestName = Utils::getCookie("guest");
        if($participant = $this->isParticipant($guestName)){
            DB::write("UPDATE `test_participants` SET `username`=':username' WHERE `username`=':guestName'", $this->user->username, $guestName);
            DB::write("UPDATE `test_goals` SET `username`=':username' WHERE `username`=':guestName'", $this->user->username, $guestName);
            $_SESSION[ABTesting::SESSION] = $participant;
            $_SESSION[ABTesting::SESSION]['goals'] = $this->getParticipantsGoalNames();
            unset($_SESSION[ABTesting::SESSION]['id']);
            unset($_SESSION[ABTesting::SESSION]['username']);
            unset($_SESSION[ABTesting::SESSION]['created_at']);
        }
    }
    
    /**
     * Check if the user already set the goal
     * @param object $goalName
     * @param object $username [optional] if provided checks the user from database
     * @return 
     */
    public function hasGoal($goalName, $username = false){
        if($username !== false){
            $res = DB::read("SELECT * FROM `test_goals` WHERE `goal_name`=':name' AND `username`=':username'", $goalName, $username);
            if($res->rows > 0){
                return true;
            }
        }else{
            if(isset($_SESSION[ABTesting::SESSION])){
                if($_SESSION[ABTesting::SESSION]['test_name'] == $this->className){                    
                    if(in_array($goalName, $_SESSION[ABTesting::SESSION]['goals'])){
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * Saves the goal to database
     * @param object $goalName
     * @return 
     */
    public function saveGoal($goalName, $username = false, $className = false){
        if($username){
            $user = $this->getTestParticipant($username);
            if($this->hasGoal($goalName, $username)){ return false; }
        }else if(isset($_SESSION[ABTesting::SESSION])){
            $user = $_SESSION[ABTesting::SESSION];
            $username = Session::$username;
            if($this->hasGoal($goalName)){ return false; }
        }else{
            return false;
        }
        
        # Check if this user is participating this test
        if($user['test_name'] == $className){
            
            if(isset($_SESSION[ABTesting::SESSION])){
                $_SESSION[ABTesting::SESSION]['goals'][] = $goalName;
            }
            
            DB::insert('test_goals', array(
                "username"  => $username,
                "goal_name" => $goalName
            ));
        }
    }
    
    #############################################
    ##          Shortcuts after here           ##
    #############################################
    
    /**
     * Register this test to work on user actions
     * Calling this method means this test will run
     * @return 
     */
    static function register($className){
        ABTestingController::registerTest($className); #get_called_class());
    }
    
    /**
     * Save a goal for test participant
     * @param object $goalName
     * @param object $username [optional]
     * @return 
     */
    public static function setGoal($goalName, $className, $username = false){
        $instance = self::getInstance($className);
        if($instance){
            $instance->saveGoal($goalName, $username, $className);
        }
        return false;
    }
    
    /**
     * Get the group name of the user
     * @return 
     */
    public static function getGroup($className){
        $instance = self::getInstance($className);
        if($instance){
            return $instance->getGroupName();
        }
        return false;
    }
    
    /**
     * Assign a user for this test
     * @return 
     */
    public static function assignUser($className, $username = false){
        $instance = self::getInstance($className);
        
        if($instance){
            return $instance->assignTestUser($username);
        }
        
        return false;
    }
}