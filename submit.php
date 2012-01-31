<?
include_once "lib/init.php";
Utils::stopPostBack();
try{
    $submit = new Submission();
    $submit->submit();
}catch(Exception $e){
    Utils::error($e);
}
?>