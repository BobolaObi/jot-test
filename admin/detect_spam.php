<?php
    require_once "../lib/init.php";
    # Set php execution time to infinite
    set_time_limit(0);
    
?>
<style>
    body{
        font-size:12px;
        font-family:Verdana, Geneva, Arial, Helvetica, sans-serif;
        background:#f5f5f5;
    }
</style>
<?php
    if(!isset($_POST['start'])){
?>  
    <div style="text-align:center; padding:100px;">
        <form action="detect_spam.php" method="post">
            <div><h2>This operation will take some time. Are you sure you want to continue?</h2></div><br>
            <input type="text" name="count" value="500" size="5" > Forms will be scanned<br><br>
            <input type="submit" name="start" value="Scan"> <input type="button" value="No, Go back" onclick="history.go(-1)" >
        </form>
    </div>
<?        
        exit;
    }

    echo "Cleaning Spam Prob Table for whitelisted and deleted forms<br><hr>";
    flush();
    ob_flush();

    # REMOVE all white listed forms
    $query = "UPDATE `spam_prob` SET `status` = 'IGNORE' WHERE form_id IN (SELECT form_id FROM `whitelist` WHERE 1)";
    $result = DB::write($query);
    
    # REMOVE all deleted and suspended forms
    $query = "UPDATE `spam_prob` SET `status` = 'IGNORE' WHERE form_id IN (SELECT id FROM `forms` WHERE `status` = 'DELETED' OR `status` = 'SUSPENDED' OR `status` = 'AUTOSUSPENDED' )";
    $result = DB::write($query);

    # REMOVE all completely deleted forms.
    $query = "UPDATE `spam_prob` SET `status` = 'IGNORE' WHERE form_id NOT IN (SELECT id FROM `forms` WHERE 1)";
    $result = DB::write($query);
    
    echo "Cleaning ended.<br/><hr/>";
    
    $count = $_POST['count']? $_POST['count'] : "500";
    
    echo "Detecting Spam Prob for the latest $count forms<br/><hr/>";
    
    flush();
    ob_flush();
    
/*
 * We change this to improve performance
 */      
    $queryStmt = "  SELECT id as form_id
					FROM forms
					WHERE id NOT
					IN (
					SELECT form_id
					FROM spam_prob
					)
					ORDER BY updated_at DESC
					LIMIT $count";
	
	$result = DB::read( $queryStmt);
	
    $c = 0;
	foreach( $result->result as $row){
		
        $formID = $row['form_id'];
		
		$queryStmt =  "SELECT * FROM forms WHERE id = $formID AND `status` != 'DELETED' ".
		              "AND `status` != 'SUSPENDED' AND `status` != 'AUTOSUSPENDED' ";
		
		$result2 = DB::read( $queryStmt);
		$row2 = $result2->first;
        
		if( $row2 !== false){
			$formTitle = isset($row2['title']) ? $row2['title'] : false;	
			$username = isset($row2['username']) ? $row2['username'] : false;
			$status = isset($row2['status']) ? $row2['status'] : false;
			$isSpam = false;
			
			//Compute the spam probability of the form
			$phishingFilter = new PhishingFilter($formID); 
			$spamProb = $phishingFilter->setSpamProb();
			
			echo "FormID: " . $formID . " - " . $spamProb . "<br/>";
	        $c++;
			flush();
			ob_flush();
		}
	}	
	
    echo "<br>$c forms have been scanned.";
    echo '<meta HTTP-EQUIV="REFRESH" content="0; url=checkPhishing.php">';
?>
