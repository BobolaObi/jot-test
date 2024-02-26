<?php

use Legacy\Jot\Configs;
use Legacy\Jot\Utils\Utils;


?>
<? 

/**
 * Specify document mode for Javascript. 
 */

$modes = array();

if (DEBUGMODE) {
    $modes[] = "document.DEBUG = true";
    $modes[] = "document.debugOptions = ".Utils::getCookie('debug_options');
}

if (DISABLE_SUBMISSON_PAGES) {
    $modes[] = "document.disableSubmissions = true";
}

if (APP) {
    $modes[] = "document.APP = true";
    $modes[] = "document.SUBFOLDER = '".Configs::SUBFOLDER."'";
}

if (BETA) {
    $modes[] = "document.BETA = true";
}

# Collect all modes together and print on the screen
if(count($modes) !== 0){
    echo '<script type="text/javascript">';
    echo "\n    " . join(";\n    ", $modes) . ";\n";
    echo "</script>";
}

?>