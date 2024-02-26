<?

use Legacy\Jot\Submission;
use Legacy\Jot\Utils\Utils;

include_once "lib/init.php";
Utils::stopPostBack();
try{
    $submit = new Submission();
    $submit->submit();
}catch(Exception $e){
    Utils::error($e);
}
?>