<?php
/**
 * Hadnles Click Bank payments
 * @package JotForm_Payments
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\Payments;
class ClickBank{
    
    private $currency, $returnURL, $paymentType, $custom, $ipnURL, $options, $postURL, $postData = array(), $formID, $submissionID;
    
    private $productName, $productPrice, $itemNo, $login;
    
    /**
     * 
     * @constructor
     * @param object $options
     */
    function __construct($options){
        
        $this->options      = $options;
        $this->itemNumber   = $options["itemNumber"];
        $this->login        = $options["login"];
        $this->itemNumber   = $options["itemNumber"];
        $this->productName  = $options["productName"];
        $this->productPrice = $options["productPrice"];
        $this->formID       = $options["formID"];
        $this->submissionID = $options["sid"];
        
        $this->ipnURL       = HTTP_URL."ipns/clickbank.php";
        $this->postURL      = "http://". $this->itemNumber .".". $this->login .".pay.clickbank.net";
        
        $this->returnURL    = $options["returnURL"];
        $this->paymentType  = $options["paymentType"];
        $this->isSubscription = $this->paymentType == "subscription";
        $this->currency     = $options["currency"];
        $this->custom       = $options["formID"]."-".$options["sid"];
        
        $this->createPostData(); # initiate the data for post
    }
    
    /**
     * http://serkan.interlogy.com/builder/thankyou.html?
     *     item=1&
     *     cbreceipt=TESTDQ6E&
     *     time=1259068182&
     *     cbpop=9F917B34&
     *     cbaffi=0&
     *     cname=john+doe&
     *     cemail=serkan%40interlogy.com&
     *     czip=11377&
     *     ccountry=US&
     *     total=9.90&
     *     detail=Tshirt&
     *     payment_id=124877377192168123&
     *     currency=USD&
     *     custom=93191416705-124877377192168123
    */
   
    /**
     * Sets the items in post data
     * @return 
     */
    private function createPostData(){
        $this->addToPost(array(
            "detail" => $this->productName,
            "payment_id" => $this->options["sid"],
            "total" => $this->productPrice,
            "currency" => $this->currency,
            "custom" => $this->custom
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
        PaymentDataLog::addLog($this->formID, $this->submissionID, "CLICKBANK", "POSTED_REQUEST", "transact", $this->postData);
        Utils::postRedirect($this->postURL, $this->postData);
    }
    
    public function ipn($request){
        // Collect Data
        list($formID, $submissionID) = explode("-", $request['custom']);
        
        PaymentDataLog::addLog($formID, $submissionID, "CLICKBANK", "IPN_RESPONSE", ($request['ctransaction']? $request['ctransaction'] : "transact"), $request);
        
        Submission::continueSubmission($submissionID);
    }
}
