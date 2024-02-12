<?php
/**
 * This class holds the main methods of
 * subscription which will be same for 
 * all of Subscription types.
 *
 */
class JotFormSubscriptions{
    
    protected   $type, $action, $total, $period, $currency, $gateway, $data = array(),
                $operationDate, $subscriptionID, $paymentStatus = "";
    public      $eotDate = false, $user;
    
    
    /**
     * Set the user object from $_username of user
     * @param $_username
     */
    public function setUser($_username){
        $this->user = User::find($_username);
        if ( !isset($this->user->username) ){
            throw new Exception ("Cannot find user: ". $_username);
        }
    }
    
    /**
     * Run subscription action
     */
    public function runSubscriptionAction (){

        # Log the payment.
        $this->addJotFormPaymentLog ();

        # Call the action.
        switch ($this->action){
            case JotFormSubscriptionActions::Cancel:
                $this->calculateEOT();
                $this->scheduleDowngrade();
                break;
            case JotFormSubscriptionActions::EOT:
                $this->calculateEOT();
                $this->scheduleDowngrade();
                break;
            case JotFormSubscriptionActions::Payment:
                $this->upgradeUser();
                break;
            case JotFormSubscriptionActions::SignUp:
                break;
        }
    }
    
    /**
     * This function add user to the downgrade list if user
     * eot is calculated before to day.
     * @return eotDate in string.
     */
    public function checkAndAddUserToDowngrade(){
        $this->calculateEOT();
        return $this->eotDate;
    }
    
    /**
     * Adds user to the scheduled downgrade table.
     * 
     */
    private function scheduleDowngrade () {
        
        $res = DB::write("  REPLACE INTO `scheduled_downgrades`
                            (`username`, `eot_time`, `gateway`, `reason` )
                            VALUES
                            (':username', ':eotTime', ':gateway', ':reason' )", 
                            $this->user->username, $this->eotDate,
                            $this->gateway, $this->action );
        
        if (!$res->success){
            throw new Exception ("Cannot add to schedule downgrade table.");
        }
        
        # Send email to me for informing the upgraded user.
        $this->sendEmail("User added to scheduled downgrade", "Username: " . $this->user->username .
                         " <br/> " . $this->generateNote("<br/>"));

        return $res->success;
    }
    
    /**
     * Calculate the latest payment data and period.
     * And calculate the lastest EOT date.
     */
    public function calculateEOT(){
        if ( $this->setEOTFromNewTable() === false ){
            $this->setEotFromOldTable();
        }
    }
    
    /**
     * Calculate the latest payment date from new table
     * @return unknown_type
     */
    public function setEOTFromNewTable(){
        # Get the latest payment from new table.
        $res = DB::read("SELECT * FROM `jotform_payments` WHERE `action` = ':action' ".
                        "AND `payment_status` = 'Completed' AND `username` = ':username' ".
                        "ORDER BY `date_time` DESC LIMIT 0,1",
                        JotFormSubscriptionActions::Payment, $this->user->username);
                        

        if ($res->first){
            # Last payment found from new table
            if ( !isset($res->first['period']) || !trim($res->first['period']) ){
                throw new Exception("Period is not found.");
            }
            $this->eotDate = date( "Y-m-d H:i:s", strtotime($res->first['date_time'] . " + " . $res->first['period'] . " 1 DAY"));
            
        }
        
        return $this->eotDate;
    }
    
    /**
     * Add the payment details to the database.
     */
    private function addJotFormPaymentLog(){
        $res = DB::write( "INSERT INTO
                    `jotform_payments` (`username`, `date_time`, `operation_date`, `action`, `gateway`,"
                    . " `total`, `currency`, `note`, `subscription_id`, `payment_status`, `period` )
                    VALUES ( ':username', NOW(), ':operationDate', ':action', ':gateway'," 
                    . " ':total', ':currency', ':note', ':subscriptionId', ':paymentStatus', ':period' )",
                    $this->user->username, $this->operationDate, $this->action, $this->gateway,
                    $this->total, $this->currency, $this->generateNote(), $this->subscriptionID,
                    $this->paymentStatus, $this->period );
        
        if (!$res->success){
            throw new Exception("Cannot add to payment log.");
        }

        return $res->success;
    }
    
    /**
     * Generates a string to save notes to database.
     */
    public function generateNote($glue="\n"){
        
        $note = "";
        
        foreach ($this->data as $key => $value){
            $note .= "$key: $value $glue";
        }
        
        return $note;
    }
    
    /**
     * Upgrade user
     */
    private function upgradeUser(){
        		
		$res = DB::write("UPDATE `users` SET `status` = 'ACTIVE' WHERE `username` = ':username' ", $this->user->username);

		if (!$res->success){
            throw new Exception ("Cannot activate user.");
        }

        DB::write(  "DELETE FROM `scheduled_downgrades` WHERE `username` = ':username'",
                    $this->user->username );

        # Control if user is already upgraded.
        # If account type is old premium convert it to premium.
        if ( strtolower($this->user->accountType) === "oldpremium" ){
            $oldPremium = AccountType::find('PREMIUM');
            $this->user->accountType = $oldPremium->name;
        }
            
        if ( $this->type->name !== $this->user->accountType ){
            
            # change account type of user.
            $this->user->setAccountType($this->type);
            
            # Send email to me for informing the upgraded user.
            $this->sendEmail("User Upgraded", "Username: " . $this->user->username . " <br/> " . $this->generateNote("<br/>"));
        }
        
        # Save this goal for new year sale statistics
        # MailingTest::setGoal("Upgrade Completed", "MailingTest", $this->user->username);
    }
    
    public function setEotFromOldTable($note = false){

        # If cannot find search old table :/
        $res = DB::read("SELECT `note` FROM `temp_payment_log` WHERE ".
                        "username = ':username' ".
                        "ORDER BY `date_time` DESC",
                        $this->user->username);

        # Look to the payment logs one by one to find the latest payment.
        foreach ($res->result as $row){
            
            $this->setEOTFromNote($row['note']);

            if ($this->eotDate !== false){
                break;
            }
        }
        
        # Control if last payment is found
        if ($this->eotDate === false){
            throw new Exception ("Cannot find last payment in old database");
        }
    }
    
    public function setEOTFromNote($note){
        
        # payment data which is converted from notes.
        $paymentData = Utils::convertOldPaymentLogToArray($note);
        
        # Decide whether it is paypal or plimus data.
        if ( isset($paymentData['item_name']) ){
            $payment = new JotFormPayPalSubscriptions($paymentData);
            $payment->setProperties();
        }else if ( isset($paymentData['contractName']) ){
            $payment = new JotFormPlimusSubscriptions($paymentData);
            $payment->setProperties();
        }else{
            # If the type is not found than throw exception
            throw new Exception ("Cannot get eot from last payment.");
        }
        
        # check if its a successful payment.
        if ($this->user->username === $payment->user->username
            && $payment->paymentStatus === "Completed"
            && $payment->action === JotFormSubscriptionActions::Payment){

            # Calculate the eot of user from this payment.
            $this->eotDate =  date( "Y-m-d H:i:s", strtotime($payment->operationDate . " + " . $payment->period . " 1 DAY") );
        }
        unset($payment);
    }
    
    public function setPeriodFromNote($note){
        # payment data which is converted from notes.
        $paymentData = Utils::convertOldPaymentLogToArray($note);
        
        # Decide whether it is paypal or plimus data.
        if ( isset($paymentData['item_name']) ){
            $payment = new JotFormPayPalSubscriptions($paymentData);
            $payment->setProperties();
        }else if ( isset($paymentData['contractName']) ){
            $payment = new JotFormPlimusSubscriptions($paymentData);
            $payment->setProperties();
        }else{
            # If the type is not found than throw exception
            throw new Exception ("Cannot get eot from last payment.");
        }
        
        # check if its a successful payment.
        if ($this->user->username === $payment->user->username
            && $payment->paymentStatus === "Completed"
            && $payment->action === JotFormSubscriptionActions::Payment){

            # Calculate the eot of user from this payment.
            $this->period =  $payment->period;
            $this->gateway = $payment->gateway;
		}
        unset($payment);
    }
    
    /**
     * Returns the last payment gate of the setled user.
     * @return name of gateway or false if cannot found.
     */
    public function getLastPaymentType(){
    	if (!isset($this->user)){
    		throw new Exception("User is not set for object.");
    	}
    	$gateway = false;
    	$plimusEmail = false;
    	$plimusUsername = false;
    	$period = false;
    	$res = DB::read("SELECT * FROM `jotform_payments` WHERE `username` = ':s' ORDER BY `date_time` DESC LIMIT 0,1", $this->user->username);
    	if ($res->rows > 0 ){
    		$gateway = $res->first['gateway'];
    		$period = $res->first['period'];
    		if ($gateway === "PLIMUS"){
                $note = Utils::convertOldPaymentLogToArray($res->first['note']);
                $plimusEmail = $note['email'];
                $plimusUsername = $note['username'];
    		}
    	}else{
    		$res = DB::read("SELECT `note` FROM `temp_payment_log` WHERE ".
            				"username = ':username' ".
                        	"ORDER BY `date_time` DESC",
                        	$this->user->username);
    		if ($res->rows > 0){
		        # Look to the payment logs one by one to find the latest payment.
		        foreach ($res->result as $row){
		            $this->setPeriodFromNote($row['note']);
		            if ($this->period !== false){
		                break;
		            }
		        }
		        $period = $this->period;
		        $gateway = $this->gateway;
    		}else{
    			return false;
    		}
    	}
    	return array("gateway" => $gateway, "period" => $period, "plimusUsername" => $plimusUsername, "plimusEmail" => $plimusEmail);
    }
    
    /**
     * Get the information for the jotform subscription
     * @param String $username
     * @return Array(
     *  date => The date that account will be downgraded or disables.
     *  type => This is "downgrade", "expire" or "disable".
     *  remain => Number of days that is left.
     * )
     */
    public static function getExpireDate($username){
    	# The return array
    	$expireInfo = array();
    	
    	# Look to the schedule downgrades for the user.
    	$res = DB::read("SELECT * FROM `scheduled_downgrades` WHERE `username` = ':username' ", $username);
    	
    	if ($res->rows > 0){
    		if ( $res->first["reason"] === JotFormSubscriptionActions::Cancel || $res->first["reason"] === JotFormSubscriptionActions::EOT ){
                $expireInfo['type'] = "downgrade";
    		}else if ( $res->first["reason"] === JotFormSubscriptionActions::Overlimit ){
                $expireInfo['type'] = "disable";
    		}
    		$expireInfo['date'] = $res->first["eot_time"];
    	}else{
    		try {
	            $jotformSubscriptions = new JotFormSubscriptions();
	            $jotformSubscriptions->setUser($username);
	            $jotformSubscriptions->calculateEOT();
	            $expireInfo['date'] = $jotformSubscriptions->eotDate;
                $expireInfo['type'] = "expire";
    		}catch (Exception $e){
    			# Return false because there is no information.
	            return false;
	        }
    	}
    	
    	# Reorganize the date.
    	$time = strtotime($expireInfo['date']);
        $expireInfo['date'] = date("F jS, Y" , $time);
        $expireInfo['remain'] = ($time - time()) / (60*60*24);
        
        return $expireInfo;
    }
    
    static public function sendEmail($subject, $message){
        if(APP){ return; } # If application don't send emails
        Utils::sendEmail (
            array(  "to"=>"seyhuns@gmail.com",
                    "from"=> NOREPLY,
                    "subject"=>$subject,
                    "body" => $message
            )
        );
    }
}

