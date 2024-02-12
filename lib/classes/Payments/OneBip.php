<?php
/**
 * Handles OneBip payments
 * @package JotForm_Payments
 * @copyright Copyright (c) 2009, Interlogy LLC
 */class OneBip{
    
    private $currency, $returnURL, $paymentType, $custom, $ipnURL, $options, $postURL, $postData = array(), $formID, $submissionID;
    
    private $productName, $productPrice, $itemNumber, $username;
    
    /**
     * 
     * @constructor
     * @param object $options
     */
    function __construct($options){
        
        $this->options      = $options;
        $this->itemNumber   = $options["itemNumber"];
        $this->username     = $options["username"];
        $this->itemNumber   = $options["itemNumber"];
        $this->productName  = $options["productName"];
        $this->productPrice = $options["productPrice"];
        $this->formID       = $options["formID"];
        $this->submissionID = $options["sid"];
        
        $this->ipnURL       = "http://".HTTP_URL."/ipns/onebip.php";
        $this->postURL      = "http://www.onebip.com/otms/";
        
        $this->returnURL    = $options["returnURL"];
        $this->paymentType  = $options["paymentType"];
        $this->isSubscription = $this->paymentType == "subscription";
        $this->currency     = $options["currency"];
        $this->custom       = $options["formID"]."-".$options["sid"];
        
        $this->createPostData(); # initiate the data for post
    }
    
    /**
     * Sets the items in post data
     * @return 
     */
    private function createPostData(){
        $this->addToPost(array(
            "item" => $this->itemNumber,
            "merchantParam" => $this->custom,
            "payment_description" => $this->productName,
            "payment_id" => $this->options["sid"],
            "total" => $this->productPrice,
            "curr" => $this->currency,
            "custom" => $this->custom,
            "return_url" => $this->returnURL
        ));
    }
    
    /**
     * Merges the new array with postData
     * @param object $data
     * @return 
     */
    private function addToPost($data){
        $this->postData = array_merge($this->postData, $data);
    }
    
    /**
     * Transact
     * @return 
     */
    public function transact(){
        
        PaymentDataLog::addLog($this->formID, $this->submissionID, "ONEBIP", "POSTED_REQUEST", "transact", $this->postData);
        
        Utils::postRedirect($this->postURL, $this->postData);
    }
    
    public function ipn($request){
        // Collect Data
        list($formID, $submissionID) = explode("-", $request['custom']);
        
        PaymentDataLog::addLog($formID, $submissionID, "ONEBIP", "IPN_RESPONSE", "response", $request);
        
        Submission::continueSubmission($submissionID);
    }
}

/**
 * "http://www.onebip.com/otms/?".
	    "item=".$props["onebip_itemid"]."&".
		&merchantParam=
		&custom=$payment_id-$form_id-$sid
		&total=".$props['onebip_productprice']
		&curr=".$props['onebip_curr']
		&payment_description=".urlencode($props['onebip_productname']);
 */