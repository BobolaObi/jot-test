<?php

$_POST = "Username: eipdu
amount1: 9.00
amount3: 9.00
business: aytekin@interlogy.com
charset: windows-1252
custom: eipdu-monthly
first_name: Juan Alberto
item_name: JotForm premium monthly
last_name: Cernicchiaro hiriart
mc_amount1: 9.00
mc_amount3: 9.00
mc_currency: USD
notify_version: 3.0
payer_email: eipdu@adinet.com.uy
payer_id: 729PJ73NY6BFW
payer_status: verified
period1: 1 M
period3: 1 M
reattempt: 0
receiver_email: aytekin@interlogy.com
recurring: 1
residence_country: UY
subscr_date: 14:56:24 Jun 01, 2010 PDT
subscr_id: S-4M792249H41657405
txn_type: subscr_cancel
verify_sign: AvANCQEy2Ufz4UhLgiVzChU9zGUnAJH8vdZJm3o5It.ovhO87OP4tvFk ";
	
include "../lib/init.php";
	
$_POST = Utils::convertOldPaymentLogToArray($_POST);
ksort($_POST);


# Decide whether it is paypal or plimus data.
if ( isset($_POST['item_name']) ){
	$payment = new JotFormPayPalSubscriptions($_POST);
}else if ( isset($_POST['contractName']) ){
	$payment = new JotFormPlimusSubscriptions($_POST);
}else{
	# If the type is not found than throw exception
	throw new Exception ("Cannot find payment type.");
}

try{
	$payment->setProperties();
	$payment->runSubscriptionAction();
}
catch (Exception $exception){
    $postMessage = $payment->generateNote("<br/>"); 
    
    list($message, $notes) = Utils::generateErrorMessage($exception);
    
    JotFormSubscriptions::sendEmail( "Jotform Plimus Subscription Error", $message . "<br/>" . $postMessage . "<br/>" . $notes);
    
}

