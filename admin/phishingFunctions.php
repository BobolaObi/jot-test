<?php
function train( $incomingformID, $incomingformTitle, $isSpam){
        
        # Look if incoming form id is setled.
        if( $incomingformID ){
            
            # If isSpam is ignore, then this form will be ignored, not added to database
            try{
                $phishingFilter = new PhishingFilter($incomingformID);
                
                # mark the form as spam or not.
                if( $isSpam !== 'ignore'){
                    if ($isSpam !== "false"){
                        $phishingFilter->markAsSpam();
                    }else{
                        $phishingFilter->markAsNotSpam();
                    }
                }else{      
                    # If a form is ignored, it is stored in spam_prob table with spam probability 0.5
                    $phishingFilter->setSpamProb(0.5);
                }
            }catch (\Exception $e){
            }
        }
    }
    
    function sendNewForm( $queryType){          
        if( $queryType == "Random"){

            $queryStmt = "SELECT COUNT(*) as count FROM forms WHERE id NOT IN (SELECT form_id FROM spam_prob)";         
            $result = mysql_query( $queryStmt);
            $row = mysql_fetch_assoc($result);
            $count = $row['count'];
            
            
            if($count == 0){
                echo json_encode(["noform"=>true]);
                return;
            }

            srand(time());
            $random = (rand() % $count);
            
            $queryStmt = "SELECT id, title, username, status
                          FROM forms 
                          WHERE id NOT IN (SELECT form_id FROM spam_prob)
                          AND `status` != 'SUSPENDED' 
                          AND `status` != 'AUTOSUSPENDED' 
                          AND `status` != 'DELETED'
                          LIMIT " . $random . ", 1";

            $result = Utils::do_query( $queryStmt);
            
            if($row = mysql_fetch_assoc($result)){
                $formID = $row['id'];
                $formTitle = $row['title']; 
                $username = $row['username'];
                $status = $row['status'];
                
                $phishingFilter = new PhishingFilter($formID);
                
                $responseArray = ["formID" => $formID,
                                        "formTitle" => $formTitle,
                                        "spamPercentage" => number_format( $phishingFilter->getSpamProb() * 100, 2),
                                        "username" => $username,
                                        "isPremium" => is_premium($username),
                                        "status" => $status];
            }else{
                echo json_encode(["noform"=>true, "query"=>$queryStmt. " ". mysql_error()]);
                return;
            }
        }
        else if( $queryType == "Today" || $queryType == "ThisWeek"){
            
            
            $queryStmt = "SELECT `form_id` FROM `submissions` ORDER BY `created_at` DESC LIMIT 100";

            $result = Utils::do_query( $queryStmt);
            $responseArrayEmpty = true;
            while( $row = mysql_fetch_assoc($result)){
                $formID = $row['form_id'];
                
                //echo "formID: $formID";
                
                $queryStmt = "SELECT *
                              FROM forms 
                              WHERE id = " . $formID ." 
                              AND `status` != 'SUSPENDED' 
                              AND `status` != 'AUTOSUSPENDED' 
                              AND `status` != 'DELETED'
                              AND id NOT IN (SELECT form_id FROM spam_prob)";

                $result2 = Utils::do_query( $queryStmt);
                $row2 = mysql_fetch_assoc($result2);
                if( $row2 !== false){
                    
                    $formTitle = $row2['title'];    
                    $username = $row2['username'];
                    $status = $row2['status'];
                    $isSpam = false;
                    
                    # Compute the spam probability of the form
                    $phishingFilter = new PhishingFilter($formID);
                    
                    $responseArrayEmpty = false;
                    
                    $responseArray = ["formID" => $formID,
                                            "formTitle" => $formTitle,
                                            "spamPercentage" => number_format( $phishingFilter->getSpamProb() * 100, 2),
                                            "username" => $username,
                                            "isPremium"=>is_premium($username),
                                            "status" => $status];
                    break;
                }
            }
            
            if($responseArrayEmpty === true){
                echo json_encode(["noform"=>true, "query"=>$queryStmt." ".mysql_error()]);
                return;
            }
            
        }else if($queryType == "Undecided"){
            $count = 0;
            $responseArray = false;

            $query = "SELECT * FROM spam_prob 
                      WHERE spam_prob >= 0.92 
                      AND   spam_prob  < 0.98
                      AND   `status` != 'IGNORE'
                      ORDER BY RAND() 
                      LIMIT 1";
            $res = Utils::do_query($query);
            
            if($line = mysql_fetch_assoc($res)){
                $q = "SELECT * FROM forms WHERE id='".$line['form_id']."'";
                $r = Utils::do_query($q);

                if($form = mysql_fetch_assoc($r)){
                    $form['username'] = isset($form['username']) ? $form['username'] : false;
                    $premium = is_premium($form['username']);
                    if($form['username'] == "" || empty($form['username'])){
                        $premium = false;
                    }
                    $responseArray = ["formID" => $form['id'],
                                            "formTitle" => $form['title'],
                                            "spamPercentage" => number_format($line['spam_prob'], 2) * 100,
                                            "username" => $form['username'],
                                            "isPremium"=>$premium, "status" => $form['status']];
                }else{
                    $deleteFromSpamProb = DB::write("DELETE FROM `spam_prob` WHERE `form_id` = ':s'", $line['form_id']);
                    return sendNewForm("Undecided");
                }

            }
            
            if(!$responseArray){
                echo json_encode(["noform"=>true, "query"=>$query." ".mysql_error()]);
                return;
            }
        }
        else if( $queryType == "Suspicious"){
            
            # Keyword to look in form title.
            $title_keywords = ["twitter", "tweet", "twit", "yahoo", "gmail", "orkut", "cabal", "habbo", "mobius", "steam",  "gaia", "wow", "gold", "hack", "steal", "generator", "ebay", "hotmail", "myspace", "msn", "facebook", "paypal", "fling", "kontor", "zynga", "microsoft", "rapid", "stem"];
        
            # Create the query only using the keywords.
            $or_conditions = [];
            foreach ($title_keywords as $title_keyword){
                $or_conditions[] = " title LIKE '%$title_keyword%'";
            }
            
            $queryStmt = "SELECT id, title, username, status FROM forms 
                          WHERE status != 'DELETED'  
                          AND status != 'SUSPENDED'
                          AND status != 'AUTOSUSPENDED'
                          AND (".implode(" OR ",$or_conditions)." )
                          AND id NOT IN (SELECT form_id FROM spam_prob)
                          LIMIT 1";
                          
            $result = Utils::do_query( $queryStmt);
            
            if($row = mysql_fetch_assoc($result)){
                $formID = $row['id'];
                $formTitle = $row['title']; 
                $username = $row['username'];
                $status = $row['status'];
                
                # Compute the spam probability of the form
                $phishingFilter = new PhishingFilter($formID);
                
                $premium = is_premium($username);
                $responseArray = ["formID" => $formID, "formTitle" => $formTitle,
                                        "spamPercentage" => number_format( $phishingFilter->getSpamProb() * 100, 2),
                                        "username" => $username,
                                        "isPremium"=>$premium, "status" => $status];
            }else{
                echo json_encode(["noform"=>true, "query"=>$queryStmt." ".mysql_error()]);
                return;
            }

        }
        
        echo json_encode($responseArray);

    }
    
    
    function whitelistTrain(){
        
        $queryStmt = "SELECT f.id as id, f.title as title FROM forms f, whitelist w
                      WHERE f.id = w.form_id AND f.id NOT IN (SELECT form_id FROM spam_prob)";
        
        $result = Utils::do_query( $queryStmt);
        
        while( $row = mysql_fetch_assoc($result)){
            
            //Get the formID and formTitle
            $formID = $row['id'];
            $formTitle = $row['title'];
            $isSpam = false;
            
            //Train the whilelist as not spam
            train( $formID, $formTitle, $isSpam);                       
        }           
    }
    
    function promptResponse($str, $status){
        if(isset($_GET['normal'])){
            echo $str."\n";
        }else{
            echo "<script> window.parent.promptResponse({status: '$status', text:'" . addslashes($str) . "'});</script>";
        }
        flush();
        ob_flush();
    }
    
    function suspendForms( $spamThreshold){
        
        $deletedForms = "";
        $suspendedAccounts = "";
        $premiumAccounts = "";
        $delCount = 0;
        $suspendCount = 0;
        $premiumCount = 0;
        
        //Mark the selected forms as spam if their spam_prob is higher than the spamThreshold
        $queryStmt = "  UPDATE `spam_prob`
                        SET suspended = true                        
                        WHERE spam_prob > $spamThreshold
                        AND suspended = false";
        
        $result = Utils::do_query( $queryStmt); 
        
        promptResponse("Initializing", "start");
        
        $query = "SELECT 
                      f.username as username,
                      f.id as id, 
                      s.spam_prob as spam_prob 
                  FROM forms f, spam_prob s 
                  WHERE f.id = s.form_id
                  AND s.spam_prob > $spamThreshold
                  AND f.`status` != 'DELETED'
                  AND f.`status` != 'SUSPENDED'
                  AND f.`status` != 'AUTOSUSPENDED'
                  AND s.suspended = true";
        
        $result = Utils::do_query($query) or die($query);
        while($line = mysql_fetch_assoc($result)){
            
            if(is_white_listed($line['id'])){
                // Form was white listed
                continue;
            }
            
            $log = "";
            if(isset($line['username']) && $line['username'] && strtolower($line['username']) != 'anonymous' ){
                
                if(is_premium($line['username'])){
                    $log = "\n---\nPREMIUM: ".$line['username']." > ".$line['id']." = ".$line['spam_prob']." was a premium account\n---\n";
                    $spamP = $line['spam_prob'] * 100;
                    $premiumAccounts .= "Premium: ".$line['username']." > ".$line['id']." = %".$spamP."<br>";
                    $premiumCount++;
                    
                    promptResponse($line['username']." > ".$line['id']." = ".$line['spam_prob']." was a premium account ---", "going");
                    
                }else{
                    promptResponse("Suspending ".$line['username']."... ", "going");
                    
                    User::autoSuspend($line['username']);

                    $spamP = $line[spam_prob] * 100;
                    $suspendedAccounts .= "Suspended: ".$line['username']." > ".$line['id']." = %".$spamP."<br>";
                    $suspendCount++;
                    
                    promptResponse($line['username']." > ".$line['id']." = ".$line['spam_prob'], "going");
                }
                
            }else if(!$line['username'] || $line['username'] == ""){
                
                if($line['id']){
                    promptResponse("Deleting ".$line['id']."... ", "going");

                    $form = new Form($line['id']);
                    $form->deleteForm();
                    
                    $log = "\n------\nAnonymous: ".$line['id']." = ".$line['spam_prob']."\n\n";
                    
                    $insert = "REPLACE INTO `deleted_forms` (`id`)  VALUES('".$line['id']."')";
                    
                    $log .= $del_q."\n".$del_qp."\n".$del_f."\n-----\n";
                    
                    Utils::do_query($insert) or die(add_to_admin_log($insert."\n\n".mysql_error(), "Error Inserting to Deleted form: ".$line['id']));
                    
                    $deletedForms .= "Deleted Form: ".$line['id']."<br>";
                    $delCount++;
                    
                    promptResponse("Anonymous: ".$line['id']." = ".$line['spam_prob'], "going");
                }
            }
            if($log){
                add_to_admin_log($log);
            }
        }
        
        $report  = "== Suspending Accounts is Completed ==<br><br>";
        $report .= "Accounts Suspended: $suspendCount<br>";
        $report .= "Deleted Forms: $delCount<br>";
        $report .= "Premium Accounts Skipped: $premiumCount<br>";
        
        $report .= "<br>== Suspended Accounts ==<br>";
        $report .= $suspendedAccounts."<br><br>";
        
        $report .= "<br>== Deleted Forms ==<br>";
        $report .= $deletedForms."<br><br>";
        
        $report .= "<br>== Premium Accounts ==<br>";
        $report .= $premiumAccounts."<br><br>";
        
        promptResponse($report, "completed");
    }
    
    function getPhishingForm( $formID){
        
        $queryStmt = "SELECT id, title, username, status FROM forms 
                      WHERE id = $formID";
                
        $result = Utils::do_query( $queryStmt);
        
        $row = mysql_fetch_assoc($result);

        $formID = $row['id'];
        $formTitle = $row['title']; 
        $username = $row['username'];
        $status = $row ['status'];
        
        # Compute the spam probability of the form
        $phishingFilter = new PhishingFilter($formID);

        $premium = is_premium($username);
        $responseArray = ["formID" => $formID, "formTitle" => $formTitle,
                                "spamPercentage" => number_format( $phishingFilter->getSpamProb() * 100, 2),
                                "username" => $username,
                                "isPremium"=>$premium,
                                "status" => $status];
            
        echo json_encode($responseArray);
    }
    function is_premium($username){
        $query = "SELECT account_type FROM users WHERE username = '".$username."' AND ( account_type='PREMIUM' OR account_type='PROFESSIONAL' OR account_type='ADMIN' OR account_type='OLDPREMIUM' )";
        $result = Utils::do_query($query) or die($query);
        if($line = mysql_fetch_assoc($result)){
            return true;
        }
        return false;
    }
    
    function add_to_admin_log($log, $title=""){
        
        $title = $title? "\n## ".$title : "## Log Entry";
        $title .= "\t\t[ ".date('F d, Y \a\t H:i:s')." ] : \n\n";
        
        $logfile = "/tmp/admin_log";
        $fh = @fopen($logfile, 'a+');
        @fwrite($fh, $title.$log);
        @fclose($fh);
        
        return $log;
    }
    
    function get_form_username($formID){
        
        $query = "SELECT username FROM `forms` WHERE `id`='$formID'";
        $result = Utils::do_query($query);
        if($line = mysql_fetch_assoc($result)){
            return $line['username'];
        }
        
        return "";
    }

    function is_white_listed($formID){
        $query = "SELECT * FROM `whitelist` WHERE form_id='$formID'";
        $result = Utils::do_query($query);
        if($line = mysql_fetch_assoc($result)){
            return true;
        }
        return false;
    }
    
    function log_suspends($log, $title=""){

        $title = $title? "\n## ".$title : "## Log Entry";
        $title .= "\t\t\t\t Auto Suspend Server, [ ".date('F d, Y \a\t H:i:s')." ] : \n";
        
        $logfile = "/tmp/auto_suspend_log";
        $fh = @fopen($logfile, 'a+');
        @fwrite($fh, $title.$log);
        @fclose($fh);
        echo "\n<hr>\n".$title."\n<br>\n".$log;
        flush();
        ob_flush();
        return $log;
    }
    function get_user_country($username){
        $query = "SELECT ip FROM users WHERE username='$username'";
        $res  = DB::read($query);
        $ip = $res->first['ip'];
        return get_country($ip);
    }
    function get_country($ip){
        mysql_select_db("jotform_lang");
        $ip = ip2long($ip);
        $country = "";
        if ($ip){
            $query = "SELECT ctry FROM ip_to_country WHERE $ip >= ipFrom AND $ip <= ipTo";
            $result = mysql_query($query);
            while($line = mysql_fetch_array($result, MYSQL_ASSOC)){
                $country = $line['ctry'];
            }
        }
        mysql_select_db("jotform_new");
        return $country;
   }
   function get_form_count($username){
        $query = "SELECT count(id) as c FROM forms WHERE username='$username'";
        $res  = DB::read($query);
        return $res->first['c'];
   }

   function get_user_age($username){
        $query = "SELECT DATEDIFF(CURDATE(), created_at) as age FROM users WHERE username='$username'";
        $res  = DB::read($query);
        $age = $res->first['age'];
        if($age !== NULL)
            return $age;
        else
        return 1000;
   }

   function get_user_email($username){
        $query = "SELECT email FROM users WHERE username='$username'";
        $res  = DB::read($query);
        return $res->first['email'];
   }
   
    