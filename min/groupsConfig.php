<?php
/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */

/** 
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http://yourdomain/min/builder/
 **/

include_once dirname(__FILE__) . "/../lib/classes/Translations.php";

// This array is what will be returned, this is all that is needed.
$allGroups = array();

$formBuilder1   = array('//js/prototype.js', '//js/protoplus.js', '//js/common.js', '//js/effects.js', '//js/dragdrop.js');
$formBuilder2   = array('//js/protoplus-ui.js', '//js/builder/formBuilder.js', '//js/widgets/feedbackwidget.js');
$formBuilder3   = array('//js/builder/build_source.js', '//js/builder/question_properties.js', '//js/builder/question_definitions.js', 
                           '//js/location.js');

// Make sure each language is added to the minification group.
foreach(Translations::$languagesAvailable as $localeCode) {
	if ($localeCode == 'en-US') {
		$languageGroup = array('//js/locale/locale_en-US.js', '//js/locale/locale.js');
	} else {
		$languageGroup = array('//js/locale/locale_en-US.js', '//js/locale/locale_' . $localeCode . '.js', '//js/locale/locale.js');
	}
	$allGroups['formBuilder_' . $localeCode] = array_merge($formBuilder1, $languageGroup, $formBuilder2) ;
}

$loginForm = array('//js/includes/loginForm.js');
$orangeBox = array('//js/prototype.js','//js/protoplus.js','//js/protoplus-ui.js','//js/orangebox.js');
$feedback = array('//js/prototype.js','//js/protoplus.js','//js/protoplus-ui.js','//js/feedback.js');

$jotform = array('//js/prototype.js', '//js/protoplus.js', '//js/protoplus-ui.js', '//js/jotform.js', '//js/calendarview.js');

$allGroups['formBuilder3'] = $formBuilder3;
$allGroups['formBuilder3login'] = array_merge($formBuilder3, $loginForm);

$allGroups['indexCss'] = array('//css/style.css', '//css/fancy.css', '//css/styles/form.css',
                '//sprite/context-menu.css', '//sprite/toolbar.css', '//sprite/controls.css',
                '//sprite/index.css');

$allGroups['formCss'] = array('//css/styles/form.css', '//css/calendarview.css');

$allGroups['orangebox'] = $orangeBox;
$allGroups['feedback'] = $feedback;
$allGroups['jotform'] = $jotform;
 
// custom source example
/*'js2' => array(
	dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
	// do NOT process this file
	new Minify_Source(array(
		'filepath' => dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
		'minifier' => create_function('$a', 'return $a;')
	))
),//*/

/*'js3' => array(
	dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
	// do NOT process this file
	new Minify_Source(array(
		'filepath' => dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
		'minifier' => array('Minify_Packer', 'minify')
	))
),//*/

return $allGroups;
