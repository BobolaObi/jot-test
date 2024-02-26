<?
# this scripts runs through JotForm files and finds all strings that ends with locale in JS and PHP files.  
# then it sends these strings to google translate for translations. 

include "config.php";
include "google_translate.php";
include "overlap/overlap.php"; 
include "manual/manual.php"; 

# TESTING MODE:
#$browse = array( "index.php");
#$run_lang = array('tr-TR' =>      'tr');
#$run_lang = array('es-ES' =>      'es', 'tr-TR' =>      'tr');


chdir("../../");
$strings = [];
foreach($browse as $file){
	$lines = file($file);
	foreach($lines as $line){
		if( preg_match("/locale-button.*value=\"(.*?)\"/", $line, $m) ){
			if(!$ignore_list[$m[1]])
				$strings[] = $m[1];
		}
		if( preg_match("/class=\".*locale.*\">(.*?)<\/[^b]/", $line, $m) ){
			if(!$ignore_list[$m[1]])
				$strings[] = $m[1];
		}
		if( preg_match_all("/[\"']([^\'\"]*?)[\"']\.locale\(/", $line, $m) ){
			foreach($m[1] as $i){
				if(!$ignore_list[$i])
					$strings[] = $i;
			}
		}
		if( strstr($line, "var tips =") ){ //ignore tooltips which is at te end of the file
			break;
		}
		if( strstr($line, "<!-- do not translate -->") ){ //ignore until the end of the file
			break;
		}
	}
}

$strings = array_unique($strings);
foreach($strings as $s){
	$strings2[] = $s;
	#print $s."\n";
}
$strings = $strings2;

foreach($run_lang as $lang => $slang)
{
	$translated = [];
	$k = 5;
	for($i=0; $i<count($strings); $i=$i+$k){
		#print "$i\n";
		for($j=0; $j<$k; $j++){
			$na[$j] = $strings[$i+$j];
			if($override[$na[$j]])
				$na[$j] = $override[$na[$j]];
		}
		#print_r($na);
		$translated = array_merge($translated, translateText($na, 'en', $slang));
		#if($i>30) break;
	}

	$transjs = "Locale.language = {
	    \"langCode\": \"$lang\",\n";

	for($i=0; $i<count($strings); $i++){
		if($lang_override[$lang][$strings[$i]])
			$translated[$i] = $lang_override[$lang][$strings[$i]];
		if($lang_override2[$lang][$strings[$i]])
			$translated[$i] = $lang_override2[$lang][$strings[$i]];
		$transjs .= "\t\"".$strings[$i]."\": \"".$translated[$i]."\"";
		if($i!=count($strings)-1)
			$transjs .= ",";
		$transjs .= "\n";	
	}

	$transjs .= "}\n\n";

	#print "js/locale/locale_$lang.js\n\n";
	#print $transjs;
	#print "\n\n";
	file_put_contents("js/locale/locale_$lang.js", $transjs);
}



?>
