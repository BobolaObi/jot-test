<?php
    include_once "../lib/init.php";
    if ( !isset($skipAdminCheck) || !$skipAdminCheck ){
            Session::checkAdminPages();
    }
    #DB::connect();

    DB::setConnection('lang', "jotform_lang", DB_USER, DB_PASS, DB_HOST);
	define( 'SPAM_PROB', 0.5);
	define( 'UNSPAM_PROB', 0.5);

	function testWord(){
		
		$word = "free";
		$backgroundStrength = 5;
		
		//Probability that a given word is spam
		$wordBeingSpamProb = computeSpamProbForWord( $word);
				
		echo "ilk: " . $wordBeingSpamProb;
								
		//Discard the word if it is not previously learned in the training phase
		if( $wordBeingSpamProb != null){
											
			//Update the wordBeingSpamProb if the word is a rare word
			$totalNumOfOccuranceOfWord = getSpamCount( $word) + getUnspamCount( $word);
			
			echo "totalNumOfOccuranceOfWord: " . $totalNumOfOccuranceOfWord . "<br/>";

			//A word is rare if its total num of occurance in learning phase is smaller than $backgroundStrengh
			if( $totalNumOfOccuranceOfWord <= $backgroundStrength){
				
				echo "efe";
				
				$wordBeingSpamProb = updateSpamProbForRareWord( $wordBeingSpamProb,
																$backgroundStrength,
																$totalNumOfOccuranceOfWord);
													
				echo "sonra: " . $wordBeingSpamProb;										
			}
		}
	}
	
	
	//Creates the parsed word array whose values will be inserted to the database
	function createParsedWordArray( $formID, $formTitle){
		
		//Get the form label names of the retrived forms
		$questionNames = get_question_names( $formID);
						
		//Extract each word from form label names
		$questionNamesStr = implode( " " , $questionNames);
		$questionNamesStr = $questionNamesStr . " " . $formTitle;
		
		//Take out characters that are not a word such as , . : 
		$questionNamesStr = preg_replace("/\W/", " ", $questionNamesStr);
		
		//Convert all words to lowercase
		$questionNamesStr = strtolower( $questionNamesStr);
	
		//Get the words in an array
		$questionNamesArray = parseFunctionNames( $questionNamesStr);
						
		//Extract out the words that are inside ignoreWords array
		$questionNamesArray = deleteIgnoredWords( $questionNamesArray);	
						
		//Extract out the small words, words with letters less than 3
		$questionNamesArray = deleteSmallWords( $questionNamesArray);
				
		//Extract out duplicate keywords
		$questionNamesArray = deleteDuplicateWords( $questionNamesArray);

		return $questionNamesArray;
	}
	
	//Converts the given string into an array
	function parseFunctionNames( $questionNamesStr){
		
		$questionNamesArray = array();
		
		$tokenizer = strtok( $questionNamesStr, " ");
				
		while ( $tokenizer !== false) {	
											
			$questionNamesArray[] = $tokenizer;			    
		    $tokenizer = strtok(" ");
		}
		
		return $questionNamesArray;			
	}
	
	//This function deletes the words from questionNamesArray that are in the $ignoreWords array
	function deleteIgnoredWords( $questionNamesArray){
		
		$ignoreWords = array( '1', '2', '3', '4', '5', '6', '7', '8', '9', 'name', 'undefined', 'personal', 'his', 'her', 'we', 'you', 'and', 'untitled', 'form', 'e', 'i', 'a', 'about', 'an', 'are', 'as', 'at', 'be', 'by', 'com', 'de', 'en', 'for', 'from', 'how', 'in', 'is', 'it', 'la',  'of', 'on', 'or', 'that', 'the', 'this', 'to', 'was', 'what', 'when', 'where', 'who', 'will', 'with', 'und',  'the',  'www');
		
		$clearedArray = array();
		
		foreach( $questionNamesArray as $questionName){
				
			$isIgnoreWord = false;
			
			foreach( $ignoreWords as $ignoreWord){
				
				if( $questionName == $ignoreWord){
					$isIgnoreWord = true;
					break;
				}
			}
			
			if( $isIgnoreWord === false){
				$clearedArray[] = $questionName;
			}
		}
		
		return $clearedArray;		
	}
	
	function deleteSmallWords( $questionNamesArray){
						
		$clearedArray = array();
		
		foreach( $questionNamesArray as $question){
			
			if( strlen($question) > 3 ){
				$clearedArray[] = $question;				
			}
		}
		
		return $clearedArray;		
	}
	
	function deleteDuplicateWords( $questionNamesArray){
		
		$clearedArray = array();
		
		for( $i = 0; $i < sizeof( $questionNamesArray); $i++){
		
			$hasDuplicate = false;
		
			for( $k = $i+1; $k < sizeof( $questionNamesArray); $k++){
				
				if( $questionNamesArray[$i] == $questionNamesArray[$k]){
					$hasDuplicate = true;
					break;
				}				
			}	
			
			if( $hasDuplicate === false){
				$clearedArray[] = $questionNamesArray[$i];
			}
		}

		return $clearedArray;	
	}

	/*
	 * Returns the spam value of the given form
	 */
	function getSpamProbForForm( $formID, $formTitle){
		
		//Get the parsed question names
		$questionNamesArray = createParsedWordArray( $formID, $formTitle);			
								
		if( !empty( $questionNamesArray)){
					
			//Compute & store the probability for the form
			$backgroundStrength = 5; 	//Words that appear less than 5 times are considered as rare words.
			$spamProbOfForm = computeSpamProbForForm( $questionNamesArray, $backgroundStrength);
			
			return $spamProbOfForm;
		}
		else{
			return null;
		}
	}
	
    function is_white_listed($formID){
        $query = "SELECT * FROM `whitelist` WHERE form_id='$formID'";
        $result = do_query($query);
        if($line = mysql_fetch_assoc($result)){
            return true;
        }
        return false;
    }
    
	/*
	 * Inserts the spam probability of the form into database
	 */
	function setSpamProbForForm( $formID, $spamProbOfForm){
		//Set the spamProbOfForm in the spam_prob table		
		if( !($spamProbOfForm === null || $formID == "" || $formID == 0)){			
			$query = "REPLACE INTO spam_prob (form_id, spam_prob, suspended) VALUES ($formID , $spamProbOfForm , false)";
			do_query($query) or die($query . "<br>" . mysql_error());
		}
	}
	

	//This function computes the spam probability of a form given its words in the $wordArray
	//$backgroundStrength is the minimum number of occurances of a word in learning phase for the word
	//not to be considered as a rare word
	function computeSpamProbForForm( $wordArray, $backgroundStrength){

/*		
		print("<pre>");
		print_r( $wordArray);
		echo "<br/>";
*/		
		//This variable holds P(S|W1).P(S|W2).P(S|W3)....
		$multiplicationValue1 = 1;
		
		//This variable holds (1-P(S|W1)).(1-P(S|W2)).(1-P(S|W2))....
		$multiplicationValue2 = 1;
				
		foreach( $wordArray as $word){
	
			//Probability that a given word is spam
			$wordBeingSpamProb = computeSpamProbForWord( $word);
									
			//Discard the word if it is not previously learned in the training phase
			if( $wordBeingSpamProb != null){
												
				//Update the wordBeingSpamProb if the word is a rare word
				$totalNumOfOccuranceOfWord = getSpamCount( $word) + getUnspamCount( $word);
	
				//A word is rare if its total num of occurance in learning phase is smaller than $backgroundStrengh
				if( $totalNumOfOccuranceOfWord <= $backgroundStrength){
					$wordBeingSpamProb = updateSpamProbForRareWord( $wordBeingSpamProb,
																	$backgroundStrength,
																	$totalNumOfOccuranceOfWord);
				}
										
				//If the word is definetely a spam word, set its prob to .99 so thatr
				//multiplicationValue2 will not be 0
				if( $wordBeingSpamProb == 1){
					$wordBeingSpamProb = 0.99;
				}
				
				//Probability that a given word is not spam
				$wordBeingUnspamProb = 1 - $wordBeingSpamProb;
				
				//Update the values of multiplicationValues
				$multiplicationValue1 = $multiplicationValue1 * $wordBeingSpamProb;
				$multiplicationValue2 = $multiplicationValue2 * $wordBeingUnspamProb;
										
				$denum = $multiplicationValue1 + $multiplicationValue2;
				$tmpProb = $multiplicationValue1 / $denum;				
			}
		}	
		

		$denumerator = $multiplicationValue1 + $multiplicationValue2;
	
		//Compute and return the final spam probability of the $wordArray		
		$totalProb = $multiplicationValue1 / $denumerator;
		
		return $totalProb;	
		
	}
	
	//This function computes the probability of a given word being spam
	function computeSpamProbForWord( $word){
		
		//P(W|S) - Given the form is spam, the probability of the selected word being $word
		$totalNumOfSpamWords = getTotalNumOfSpamWords();
		if( $totalNumOfSpamWords != 0){			
			$probOfWordFromSpam = getSpamCount( $word) / $totalNumOfSpamWords;
		}
		else{
			$probOfWordFromSpam = 1;
		}
		
		//P(W|S') - Given the form is unspam, the probability of the selected word being $word
		$totalNumOfUnspamWords = getTotalNumOfUnspamWords();
		if( $totalNumOfUnspamWords !=0 ){			
			$probOfWordFromUnspam = getUnspamCount($word) / $totalNumOfUnspamWords;
		}
		else{ 
			$probOfWordFromUnspam = 1;
		}
			
				
		//P(S) - Probability of any form being spam
		$spamProb = SPAM_PROB;
		
		//P(S') - Probability of any form being unspam
		$unspamProb = UNSPAM_PROB;
		
		$spamValueNumerator = $probOfWordFromSpam * $spamProb;		
		$spamValueDenumerator = ($probOfWordFromSpam * $spamProb) + ($probOfWordFromUnspam * $unspamProb); 
	
		//P(S|W) - Given the word, the probability of the form being spam
		//If numerator and denumerator are 0 -> word is not a learned word -> discard it
		if( $spamValueNumerator == 0 ||  $spamValueDenumerator == 0){
			$spamValue = null;
		}
		else{
			$spamValue = $spamValueNumerator / $spamValueDenumerator;
		}		
		return $spamValue;
			
	}
	
	//This function updates the probability of a rare word being spam
	//$wordSpamValue is the probability of the given words being spam P(S|W)
	//$backgroundStrength is the number of times the given word has been encountered in the learning phase (s)
	//$totalNumOfOccurance is the number of times a given word should be encountered not to be considered as a rare word (n)
	function updateSpamProbForRareWord( $wordSpamValue, $backgroundStrength, $totalNumOfOccurance){
		
		$spamProb = SPAM_PROB;
		
		//s.P(S) + n.P(S|W)
		$numerator = ($backgroundStrength * $spamProb) + ($totalNumOfOccurance * $wordSpamValue);
		
		//s+n
		$denumerator = $backgroundStrength + $totalNumOfOccurance;
		
		//P'(S|W) = s.P(S) + n.P(S|W) / s+n
		$rareWordSpamProb = $numerator / $denumerator;
		
		return $rareWordSpamProb;
	}
	
	
	
	/*
	 * This function retrives the apperance_count of $word which is accepted as spam
	 */
	function getSpamCount( $word){
				
		$queryStmt = "SELECT occurance_count FROM spam_filter WHERE word = '$word' AND is_spam = true";
					
		$result = do_query( $queryStmt);
						
		$row =  mysql_fetch_array($result, MYSQL_ASSOC);

		if( isset($row['occurance_count'])){
						
			$occuranceCount = $row['occurance_count'];	
		}else{
			$occuranceCount = 0;
		}

		return $occuranceCount;
	}
	
	/*
	 * This function retrives the apperance_count of $word which is NOT accepted as spam
	 */
	function getUnspamCount( $word){
		
		$queryStmt = "SELECT occurance_count FROM spam_filter WHERE word = '$word' AND is_spam = false";
		
		$result = do_query( $queryStmt);
		
		$row =  mysql_fetch_array($result, MYSQL_ASSOC);

		if( isset($row['occurance_count'])){
						
			$occuranceCount = $row['occurance_count'];	
		}else{
			$occuranceCount = 0;
		}	
		
		return $occuranceCount;
	}
	
	/*
	 * This function returns the summation of all words' apperance_count which are accepted as spam
	 */
	function getTotalNumOfSpamWords(){
	
		$queryStmt = "SELECT SUM(occurance_count) as occurance_sum FROM spam_filter WHERE is_spam = true";
	
		$result = do_query( $queryStmt);
	
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		
		$occuranceSum = $row['occurance_sum'];
		
		return $occuranceSum;
	}
	
	/*
	 * This function returns the summation of all words' apperance_count which are NOT accepted as spam
	 */
	function getTotalNumOfUnspamWords(){
		
		$queryStmt = "SELECT SUM(occurance_count) as occurance_sum FROM spam_filter WHERE is_spam = false";
	
		$result = do_query( $queryStmt);
		
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		
		$occuranceSum = $row['occurance_sum'];
		
		return $occuranceSum;
	}
		
	//Filter table (spam_filter)
	//word, occurance_count, is_spam
	function createSpamFilterTable(){
		
		
		$createTableStmt = "CREATE TABLE spam_filter(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							word VARCHAR(50),
							occurance_count INT,
							is_spam BOOL,
							UNIQUE(word, is_spam) 
							)";
		
		$result = do_query( $createTableStmt);
		
		echo "spam_filter table: " . $result . "<br/>";
		
		
	}

	function createSpamProbabilitiesTable(){
		
		$createTableStmt = "CREATE TABLE spam_prob(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							form_id BIGINT,
							spam_prob FLOAT,
							suspended BOOL,
                            status VARCHAR(50),
							UNIQUE(form_id) 
							)";
		
		$result = do_query( $createTableStmt);
		
		echo "spam_prob table: " . $result . "<br/>";
	}
	function get_question_names($form_id, $max_length=100)
    {
        $questions = array();
        $query = "SELECT qp.question_id, qp.value
                    FROM question_properties qp
                    WHERE qp.form_id='$form_id'
                    AND qp.prop='text'
                    AND qp.form_id='$form_id'";
        $result = mysql_query($query);
        while($line = mysql_fetch_array($result, MYSQL_ASSOC)){
            $questions{$line{'question_id'}} = htmlentities(substr($line{'value'}, 0, $max_length), ENT_QUOTES, 'utf-8' );
            #$questions{$line{'question_id'}} = substr($line{'value'}, 0, $max_length);
            #print "<li> $line{'question_id'} - $line{'value'}";
        }
        return $questions;
    }
	function do_query($query){
        $result = mysql_query($query);
        if (!$result){
            die($query);
        }
        return $result;
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
	#print "Country($country)<br>";

	mysql_select_db("jotform_new");
	return $country;
   }

     function get_user_country($username){
        $query = "SELECT ip FROM users WHERE username='$username'";
        $res  = DB::read($query);
        $ip = $res->first['ip'];
        return get_country($ip);
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



?>
