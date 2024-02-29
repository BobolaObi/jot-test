<?
    include "../lib/init.php";
    
    PayPal::ipn($_POST? $_POST : $_GET);
?>
