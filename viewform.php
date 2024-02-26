<?

use Legacy\Jot\Form;
use Legacy\Jot\Integrations\DropBoxIntegration;
use Legacy\Jot\Integrations\FTPIntegration;

include_once "lib/init.php";
# Secure form ID
$id = (float) $_GET['formID'];
$forceDisplay = isset($_GET["forceDisplay"]);

Form::displayForm($id, $forceDisplay);