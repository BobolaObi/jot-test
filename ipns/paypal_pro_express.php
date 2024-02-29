<?
    include "../lib/init.php";
  
    PayPalPro::ipn($_POST? $_POST : $_GET);
?>
