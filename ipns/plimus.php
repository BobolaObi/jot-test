<?php
include "../lib/init.php";

ksort($_POST);

# Create a new subscription
$jotFormPlimusSubscriptions = new JotFormPlimusSubscriptions($_POST);

try{
    $jotFormPlimusSubscriptions->setProperties();
    $jotFormPlimusSubscriptions->runSubscriptionAction();
}
catch (Exception $exception){
    $postMessage = $jotFormPlimusSubscriptions->generateNote("<br/>"); 
    
    list($message, $notes) = Utils::generateErrorMessage($exception);
    
    JotFormSubscriptions::sendEmail( "Jotform Plimus Subscription Error", $message . "<br/>" . $postMessage . "<br/>" . $notes);
    
}
