<?
    include "../lib/init.php";
    
    ClickBank::ipn($_POST? $_POST : $_GET);
?>