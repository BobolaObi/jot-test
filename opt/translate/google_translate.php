<?

#$a = translateText( array("test", "red", "new"), "en", "tr" );
#print "\n\ntranslation";
#print_r($a);


function translateText($src_texts = array(), $src_lang, $dest_lang){
  //setting language pair
  $lang_pair = $src_lang.'|'.$dest_lang;
 
  $cachedf = "/opt/cached_google_translations";
  if(file_exists($cachedf)){
	  $cached = join("", file($cachedf));
	  $cache = unserialize(base64_decode($cached));
	  #print_r(unserialize($cached));
	  foreach ($src_texts as $src_text){
		if($cache[$dest_lang][$src_text] != ""){
			#print "found $src_text";
			$r[] = $cache[$dest_lang][$src_text];
		}else{
			#print "not found $src_text";
			$nocache = 1;
		}
	  }
	  if(!$nocache){
		#print "\nreturning cache";
		return $r;
	  }
  }

  $src_texts_query = "";
  foreach ($src_texts as $src_text){
    $src_texts_query .= "&q=".urlencode($src_text);
  }

  $url = "http://ajax.googleapis.com/ajax/services/language/translate?v=1.0".$src_texts_query."&langpair=".urlencode($lang_pair);
  print ".";
  #print $url;

  // sendRequest
  // note how referer is set manually

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_REFERER, "http://www.YOURWEBSITE.com");
  $body = curl_exec($ch);
  curl_close($ch);

  // now, process the JSON string
  $json = json_decode($body, true);


  if ($json['responseStatus'] != 200){
    print_r($json);
    return false;
  }


  $results = $json['responseData'];
  
  $return_array = array();
  
  foreach ($results as $result){
    if ($result['responseStatus'] == 200){
      $return_array[] = $result['responseData']['translatedText'];
    } else {
      $return_array[] = false;
    }
  }
  
  $i=0;
  if( count($cache)==0 ){
	  $cache = array("tr"	=>	array());
  }
  foreach ($src_texts as $src){
	if($cache[$dest_lang][$src] == "" && $return_array[$i] != ""){
		$cache[$dest_lang][$src] = $return_array[$i];		
	}
	$i++;
  }
  $cached = base64_encode(serialize($cache));
  file_put_contents($cachedf, $cached);
  
  //return translated text
  return $return_array;
}

?>
