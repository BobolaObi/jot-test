<?php
include "../lib/init.php";

ksort($_POST);

# Create a new subscription
$jotFormPayPalSubscriptions = new JotFormPayPalSubscriptions($_POST);

try{
    $jotFormPayPalSubscriptions->setProperties();
    $jotFormPayPalSubscriptions->runSubscriptionAction();
}
catch (Exception $exception){
    $postMessage = $jotFormPayPalSubscriptions->generateNote("<br/>"); 
    
    list($message, $notes) = Utils::generateErrorMessage($exception);
    
    JotFormSubscriptions::sendEmail( "Jotform Paypal Subscription Error", $message . "<br/>" . $postMessage . "<br/>" . $notes);
}
