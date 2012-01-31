<?
    include "../lib/init.php";
    
    TwoCheckOut::ipn($_POST? $_POST : $_GET);
?>
