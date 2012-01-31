<?php
    include_once "../lib/init.php";
    include_once "phishingFunctions.php";
    
    ini_set('error_reporting', E_ALL);
    
    if ( !isset($skipAdminCheck) || !$skipAdminCheck ){
        Session::checkAdminPages(true);
    }
    
    # Set php execution time to infinite
    set_time_limit(0);
    
    $_GET = $_GET? $_GET : $_POST;
    
    Console::logAdminOperation(Session::getSysAdmUserame() . "\n" . print_r($_GET, true));
    
    # Whitelist the user.
    if (isset($_GET['whiteList'])){
        $username = $_GET['whiteList'];
	    $query = "SELECT id FROM forms WHERE `username` = '".$username."'";
	    $result = DB::read($query);
	    foreach( $result->result as $line ){
	        DB::write( "REPLACE INTO `whitelist` (`form_id`) VALUES ('" . $line['id'] . "')" );
	    }
        return;
    }
    
    # Get the parameters.
    $action = isset($_GET['action']) ? $_GET['action'] : false;
    $queryType = isset($_GET['query_type']) ? $_GET['query_type']: false;
    $incomingformID = isset($_GET['formID']) ? $_GET['formID'] : false;
    $incomingformTitle = isset($_GET['formTitle']) ? $_GET['formTitle'] : false;
    $isSpam = isset($_GET['is_spam']) ? $_GET['is_spam'] : false;
   
    if( $action == "whitelist_train"){		
        whitelistTrain();
    }
    else if( $action == "suspend_forms"){
        suspendForms( $_GET['spam_threshold']);
    }
    else if( $action == "get_phishing_form"){
        getPhishingForm( $_GET['formID']);
    }
    else{
        if ($incomingformID){
	       # Train according to incoming data
	       train( $incomingformID, $incomingformTitle, $isSpam);
        }
            
        # Send a new form information to the requester
        sendNewForm( $queryType);
    }
?>
