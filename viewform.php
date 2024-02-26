<?

use Legacy\Jot\Form;

include_once "lib/init.php";
# Secure form ID
$id = (float) $_GET['formID'];
$forceDisplay = isset($_GET["forceDisplay"]);

Form::displayForm($id, $forceDisplay);