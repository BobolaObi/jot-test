<?php
 
include "../../lib/init.php";

if (!isset($_GET['key']) || $_GET['key'] !== "2175222484"){
	die("Access denied.");
}

# Downgrade all users in the downgrade schedule list.
$res = DB::read ("SELECT * FROM `scheduled_downgrades` WHERE eot_time < NOW()");

$results = array();

# Foreach one check the downgrade reason
foreach ($res->result as $row){
    
    $removeFromScheduledList = false;
    
    $result = new stdClass();
    $result->username = $row['username'];
    
    # Stop checking user if username is empty.
    if (!trim($result->username)){
        continue;
    }
    
    # Fetch to username.
    if ( $user = User::find( $result->username ) ){
        
        # Read the reason for downgrade.
        if ($row['reason'] === "overlimit") {

            # Check if user is overlimited still.
            $monthlyUsage = MonthlyUsage::find($user);
            if ( $monthlyUsage->isOverQuota() ){
                # Disable user.
                $result->explanation = "Disabled because overlimited: " . $user->accountType;
                $user->disableUser();
            }else{
                # Disable user.
                $result->explanation = "User upgraded.";
            }
            
            $removeFromScheduledList = true;
                    
        } else if ($row['reason'] === "eot" || $row['reason'] === "cancel" ) {
            # Get the eot of the user.
            
            # Contruct the JotFormSubscriptions from username
            $jotformSubscriptions = new JotFormSubscriptions();
            
            try{
                $jotformSubscriptions->setUser ($result->username);
                $jotformSubscriptions->calculateEOT();
                
                if ( $jotformSubscriptions->eotDate !== false
                     && strtotime($jotformSubscriptions->eotDate) > strtotime("now") ){
                    # Update eot time
                    DB::write("UPDATE `scheduled_downgrades` SET `eot_time` = ':eot_time' WHERE `username` = ':username'",
                        $jotformSubscriptions->eotDate, $row['username']);
                    $result->explanation = "New eot time found: ".$jotformSubscriptions->eotDate;
                }
                else{
                    # Downgrade user.
                    $result->explanation = "User downgraded.";
                    $user->downgradeUser();
                    $removeFromScheduledList = true;
                }
            }catch (Exception $exception){
                list ($result->explanation, $_details) = Utils::generateErrorMessage ($exception);
                $result->explanation = str_replace ("<hr>", "", $result->explanation);
            }
        }
        
    }else{
        $result->explanation = "Cannot create user object.";
        $removeFromScheduledList = true;
    }
    
    if ($removeFromScheduledList){
        # Delete user from scheduled list.
        DB::write("DELETE FROM `scheduled_downgrades` WHERE `username` = ':username'", $row['username']);
    }
        
    array_push($results, $result);
}

Utils::print_r($results);
