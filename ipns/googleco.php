<?php
    include "../lib/init.php";
    // @TODO Complete this ipn
    if($_POST["_type"] == "new-order-notification"){
        GoogleCheckout::ipn($_POST);
    }
    
?>