<?
    include "../lib/init.php";
    
    WorldPay::ipn($_POST? $_POST : $_GET);
?>
