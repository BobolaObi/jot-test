<?php
/**
 * Handles WordPay Payments
 * @package JotForm_Utils
 * @copyright Copyright (c) 2009, Interlogy LLC
 */class WorldPay{
    
    private $postURL, $postData = array(), $options, $returnURL, $paymentType, $isSubscription, $currency, $custom, $installationID, $formID, $submissionID;
    
    function __construct($options){
        
        $this->ipnURL       = HTTP_URL."ipns/worldpay.php";
        $this->postURL      = "https://secure.wp3.rbsworldpay.com/wcc/purchase";
        $this->options      = $options;
        
        $this->installationID = $options["installationID"];
        $this->returnURL      = $options["returnURL"];
        $this->paymentType    = $options["paymentType"];
        $this->isSubscription = $this->paymentType == "subscription";
        $this->currency       = $options["currency"];
        $this->custom         = $options["formID"]."-".$options["sid"];
        $this->formID         = $options["formID"];
        $this->submissionID   = $options["sid"];
        
        $this->createPostData(); # initiate the data for post
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
     * Initiates the post data with initial values
     * @return null 
     */
    private function createPostData(){
        $this->addToPost(array(
            "testMode" => "0", // "100", # This is in test mode
            "instId"   => $this->installationID,
            "currency" => $this->currency,
            "futurePayType" => $this->isSubscription? "regular" : false, # if subscription then set type other wise remove from post
            "cartId"   => $this->custom
        ));
    }
    
    /**
     * Will create product configuration according to payment type
     * @param object $productsToBuy Array of products selected on the form.
     */
    public function setProducts($productsToBuy){
        
        if($this->isSubscription){
            foreach($productsToBuy as $i => $product){
                if($product["setupfee"] > 0 || $product['trial'] != "None"){
                    $product["setupfee"] = $product["setupfee"]? $product["setupfee"] : 0;
                    if($product['trial'] == "None"){
                        $trial = $this->convertDurationText($product['period']);
                    }else{
                        $trial = $this->convertDurationText($product['trial']);
                    }
                    
                    $this->addToPost(array(
                        "a1" => $product["setupfee"],
                        "t1" => $trial["time"],
                        "p1" => $trial["period"] 
                    ));
                }
                
                $subscription = $this->convertDurationText($product['period']);
                $this->addToPost(array(
                    "item_name" => $product["name"],
                    "a3" => $product["price"],
                    "t3" => $subscription["time"],
                    "p3" => $subscription["period"] 
                ));
            }

        }else if($this->paymentType == "product"){
            
            $itemDescription = array();
            $amount = 0;
            foreach($productsToBuy as $i => $product){
                $price = $product['price'];
                $description = array();
                
                # make sure quantitiy is first
                if(is_array($product["options"])){
                    foreach($product["options"] as $i => $opt){
                        if($opt['type'] == "quantity"){
                            $price *= $opt["selected"];
                            $opt["name"] = "Qty";
                        }
                        
                        $description[] = $opt["name"].":".$opt["selected"];
                    }
                }
                
                $amount += $price;
                $itemDescription[] = $product["name"]." (".join(", ", $description).")";
            }
            
            $this->addToPost(array(
                "desc" => join(", ", $itemDescription),
                "amount" => $amount
            ));
            
        }else if($this->paymentType == "donation"){
            $product = $productsToBuy;
            
            $this->addToPost(array(
                "desc" => $product['donationText'],
                "amount" => $product['price']
            ));
        }
    }
    /**
     * Make transaction redirect user to PayPal
     * @return 
     */
    public function transact(){
        PaymentDataLog::addLog($this->formID, $this->submissionID, "WORLDPAY", "POSTED_REQUEST", "transact", $this->postData);
        Utils::postRedirect($this->postURL, $this->postData);
    }
    
    public static function ipn($request){
        
        list($formID, $submissionID) = explode("-",$request['cartId']);
        
        PaymentDataLog::addLog($formID, $submissionID, "WORLDPAY", "IPN_RESPONSE", "completed", $request);
        $request['gateway'] = 'worldpay';
        Submission::continueSubmission($submissionID, "", false, $request);
    }
}