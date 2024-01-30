<?php

// xml files are generated using: 
// $ mysql -u root --xml -D jotform_lang -e 'SELECT o_text, suggested_translation FROM original_text, suggestions WHERE suggestions.l_id =3 AND suggestions.o_t_id = original_text.o_t_id' > es-ES.xml
// change suggestions.l_id for different languages. id = 3 is for Spanish.

# TODO What if there are more than one translations for the same word/sentence?

function createJson($options) {
	// Read the Turkish version first and translate only those strings
	// found there. trArray => turkish array.
	$fName = "locale_tr-TR.js";
	$fHandle = fopen($fName, 'r');
	$trJson = "";
	$trJson = file_get_contents($fName); //, FILE_TEXT, NULL, -1, 10000);
	$trJson = preg_replace("/(.*\n)+.*\{/", "{", $trJson);
	$turkishArray = json_decode($trJson);
	fclose($fHandle);

	
	// This is where the translation will be read.
	$xml = $options['xmlObject'];
	// The array translations will be kept at.
	$trans = array();
	// Populate the translations array
	for ($i = 0; $i < count($xml->row); $i++) {
		$enWord = trim(''.$xml->row[$i]->field[0]);
		$transWord = trim(''.$xml->row[$i]->field[1]);
		// If the word is included in Turkish, add it to other languages.
		// Note that $transWord must not be empty.
		if (isset($turkishArray->{$enWord}) && $transWord) {
			// Check if it was included before though.
			if (isset($trans[ $enWord ])) {
				if (in_array($transWord, $trans[$enWord])) {
					continue;
				}
				$trans[ $enWord  ][] = $transWord;
			} else {
				$trans[ $enWord ] = array($transWord);
			}
		}
	}
	
	// Open the file translations will be written to.
	$fName = "locale_" . $options['langCode'] . '.js';
	$fHandle = fopen($fName, "w")
		OR die("Can't create file: " . $fName . "\n");

	// Write to the language file from the translations array.		
	fwrite($fHandle, "var language = {\n");
	$altTrans = array();
	$toWrite = "";
	foreach($trans as $enWord => $altTrans) {
		foreach($altTrans as $index => $transWord) {
			fwrite($fHandle, $toWrite);
			$toWrite = "    \"" . $enWord . (($index != 0)? $index: "") . "\": \"" . $transWord . "\",\n";
		}
	}
	// Remove the last comma.
	fwrite($fHandle, substr($toWrite, 0, -2) . "\n}");
	fclose($fHandle);
}

function main(){
	$xmlFiles = array( "de-DE", "es-ES", "fr-FR", "it-IT", "pt-PT" );
	foreach ($xmlFiles as $index => $lang) {
		$filename = $lang . '.xml';
		if (file_exists($filename)) {
		    $xmlObject = simplexml_load_file($filename);
			createJson(array('xmlObject' => $xmlObject, 'langCode' => $lang) );
		} else {
		    echo('Skipped file ' . $file . ', could not open for processing.');
		}
	}
}

main();
?>
