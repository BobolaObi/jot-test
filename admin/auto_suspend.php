<?php
    # init of jotform
    include_once "../lib/init.php";
    # phishing functions (log_suspend is user here)
    include_once "phishingFunctions.php";
    
    ini_set('error_reporting', E_ALL);
    
    # Set php execution time to infinite
    set_time_limit(0);
    
    $skipAdminCheck = $_GET['id'] == "999";
    if (!$skipAdminCheck){
    	exit;
    }
    
    log_suspends("Starting to collect todays forms", "Step 1");

    $query = "  SELECT *
				FROM `forms`
				WHERE `updated_at` > DATE_SUB( NOW() , INTERVAL 1 HOUR )
				AND `status` != 'SUSPENDED'
				AND `status` != 'AUTOSUSPENDED'
				AND `status` != 'DELETED'";
    
    log_suspends($query);
    
    $result = DB::read( $query );
	
    # Counts for report
    $scanned = 0;
    $suspended = 0;
    $deleted = 0;
    $skipped = 0;
    
    log_suspends("Forms are collected, now check them and suspend", "Step 2");
    foreach( $result->result as $line ){
		
        $formID = $line['id'];
		$formTitle = $line['title'];
		$username = $line['username'];
		
		# Compute the spam probability of the form
		$phishingFilter = new PhishingFilter($formID);
		$spamProb = $phishingFilter->setSpamProb();
		
		log_suspends("Scanned: " . $formID . " - " . ($spamProb * 100) , "Step 3");
        
        $scanned++;
		
        $prob = $spamProb * 100; 
        
        if($spamProb > 0.98){ # Suspend forms here
        

            # If form was whitelisted then skip this form
            if($phishingFilter->isWhiteListed()){
    		    log_suspends("Forms skipped because it was white listed: ".$line['id']." => $formTitle : $prob%", "Step 4");
                $skipped++; // Count skipped forms
    		    continue;
    		}
            
            # If there is a username and it's not anonymous also its not premium then skip this form
            if($username && is_premium($username)){
    		    log_suspends( "Forms skipped because it's owned by a Premium user: ".
    		                  $line['id']." => $formTitle : $prob%", "Step 4");

    		    Utils::sendEmail([
        			"from" => NOREPLY,
					"to"=> "jotformsupport@gmail.com",
					"subject" => "Form Manual Check: ".$line['id'],
					"body" => "Form skipped because $username is Premium.<br>\n\nhttp://www.jotform.com/admin/checkPhishing.php?formID=".$line['id']
                ]);
			    
    		    $skipped++; // Count skipped forms
                continue;
    		}

		    # check user's IP address, if it is US, UK, Canada, Australia, do not suspend but send email
		    # 1. Get user's IP address from users table
		    # 2. Check which country
		    $c = get_user_country($username);
		    log_suspends("Form from $c", "Step 4");
		    if( in_array($c, ["US", "CA", "UK", "FR", "ES", "AU"]) ){
    			
		    	log_suspends("Forms skipped because it's from $c", "Step 4");
			    
		    	Utils::sendEmail([
					"from" => NOREPLY,
					"to"=> "jotformsupport@gmail.com",
					"subject" => "Form Manual Check: ".$line['id'],
					"body" => "Forms skipped because it's from $c<br>\n\nhttp://www.jotform.com/admin/checkPhishing.php?formID=".$line['id']
                ]);

			    $skipped++; // Count skipped forms
				
			    continue;
		    }

		    # If the user is older than 3 months, do not suspend
		    $age = get_user_age($username);
		    if( $age > 90 ){

		    	log_suspends("Forms skipped because it was created $age days ago!", "Step 4");
			    
		    	Utils::sendEmail([
					"from" => NOREPLY,
					"to"=> "jotformsupport@gmail.com",
					"subject" => "Form Manual Check: ".$line['id'],
					"body" => "Form skipped because the account is $age days old.<br>\n\nhttp://www.jotform.com/admin/checkPhishing.php?formID=".$line['id']
                ]);
			    
				$skipped++; // Count skipped forms
				continue;
		    }

		    #If user has more than 10 forms, do not suspend. Just send an email
		    $fc = get_form_count($username);
		    if( $fc > 10 ){

		    	log_suspends("Forms skipped because it has $fc forms", "Step 4");
			    
		    	Utils::sendEmail([
					"from" => NOREPLY,
					"to"=> "jotformsupport@gmail.com",
					"subject" => "Form Manual Check: ".$line['id'],
					"body" => "Form skipped because it has $fc forms.<br>\n\nhttp://www.jotform.com/admin/checkPhishing.php?formID=".$line['id']
                ]);
				
			    $skipped++; // Count skipped forms
				continue;
		    }

            # If there is no username OR username is anonymous the delete this form            
            if(!$username || $username == "" || strtolower($username) == "anonymous"){ # Delete form
                log_suspends("Deleting Form: ".$line['id'], "Step 4");
                
                $form = new Form($line['id']);
                $form->deleteForm();
                
                $deleted++; // Count deleted forms
            }else{ // If all fails then suspend this account
                log_suspends("Suspend User: $username", "Step 4");
                
                User::autoSuspend($username);
                
                $suspended++; // Count suspended forms
            }
        }
	}
    
    $report = "Operation Completed:\n\n$scanned Forms are scanned\n$skipped Forms are skipped\n$suspended Forms are suspended\n$deleted Forms are Deleted\n";
    
    log_suspends($report, "Report: Step 5");
