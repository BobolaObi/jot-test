<?
if(false){ #test mode
	include "../config.php";
	$lang_override2 = array();
	$folder = "";
}else{
	$folder = "manual/";
}

$list = file($folder."list");
foreach($run_lang as $lang => $l){
	$f = $folder."list_".$lang;
	#print $lang."- $l *** ".$f."\n";
	if( file_exists($f) ){
		$tr = file($f);
		#print_r($tr);
		$i=0;
		foreach($list as $en){
			$tr[$i] = trim($tr[$i], "\n");
			$en = trim($en, "\n");
			$tr[$i] = str_replace('"', '', $tr[$i]);
			$lang_override2[$lang][$en] = $tr[$i];
			$i++;
		}
	}
}
#print_r($lang_override2);

?>
