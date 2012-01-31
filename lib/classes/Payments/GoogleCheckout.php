<?php
/**
 * Handles the Google Checkout transactions
 * @package JotForm_Payments
 * @copyright Copyright (c) 2009, Interlogy LLC
 */
class GoogleCheckout {
    
    private $merchantID, $returnURL, $paymentType, $isSubscription, $postURL, $custom, $formID, $submissionID;
    
    public $postData = array(), $options;
    
    /**
     * Initializes the GoogleCheckout object with options
     * @constructor
     * @param object $options Options for GoogleCheckout
     * <ul>
     *   <li>merchantID: Merchant ID</li>
     *   <li>currency: curency code</li>
     *   <li>returnURL: User will be redirected ofter payment</li>
     *   <li>paymentType: type of the payment "subscription", "product" or "donation"</li>
     * </ul>
     * @TODO Add donation support
     */
    function GoogleCheckout($options){
        
        $this->options      = $options;
        $this->merchantID   = $options["merchantID"];
        $this->returnURL    = $options["returnURL"];
        $this->paymentType  = $options["paymentType"];
        $this->isSubscription = $this->paymentType == "subscription";
        $this->formID         = $options["formID"];
        $this->submissionID   = $options["sid"];
        
        $server = "checkout";
        
        if(Utils::debugOption("useSandbox")){
            $server = "sandbox";
        }
        
        if($server == "sandbox"){
            $this->postURL = "https://".$server.".google.com/checkout/api/checkout/v2/checkoutForm/Merchant/".$this->merchantID;
        }else{
            $this->postURL = "https://".$server.".google.com/api/checkout/v2/checkoutForm/Merchant/".$this->merchantID;
        }
        
        $this->currency     = $options["currency"];
        $this->custom       = $options["formID"]."-".$options["sid"];
        
        $this->createPostData();
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
     * Initiates the post datawith initial values
     * @return null 
     */
    private function createPostData(){
        
        $this->addToPost(array(
            "type" => "checkout-shopping-cart",
            "shopping-cart.merchant-private-data" => $this->custom,
            "checkout-flow-support.merchant-checkout-flow-support.continue-shopping-url" => $this->returnURL
        ));
        
    }
    
    /**
     * Converts duration texts for paypal format
     * @param object $duration
     * @return 
     */
    private function convertDurationText($duration){
        
        switch($duration){
            case 'Daily' :      return "DAILY";
            case 'Weekly' :     return "WEEKLY";
            case 'Monthly' :    return "MONTHLY";
            case 'Bi-Monthly' : return "EVERY_TWO_MONTHS";
            case 'Quarterly' :  return "QUARTERLY";
            case 'Yearly' :     return "YEARLY";
            default :           return "MONTHLY";
        }
    }
    
    /**
     * Will create product configuration according to payment type
     * @param object $productsToBuy Array of products selected on the form.
     */
    public function setProducts($productsToBuy){
        
        # Subscriptions and products uses the same interface
        if($this->isSubscription || $this->paymentType == "product"){
            
            $itemCount = 1;
            foreach($productsToBuy as $i => $product){
                
                $description = array();      # Descriptions will placed here such as Color: Red, Size: XL
                $price = $product['price'];  # First Payment price
                
                if($this->isSubscription){   # If it's a subscription add additional info
                    
                    $price = $product["setupfee"] > 0? "0" : $product["price"]; # If there is a setupfee overwrite the first payment price, 
                                                                                # make it zero, because we will add setup fee as a different product and
                                                                                # charge it for the first month, so first month price for the subscription should be free
                                                                                
                    $price = $product["trial"] == "None"? $price : "0";         # If trial is active then make first payment free, trial has a higher priority
                    
                    $prefix = "shopping-cart.items.item-".$itemCount.".subscription."; # To make it shorther only for beautify
                    
                    $this->addToPost(array(
                       $prefix. "period" => $this->convertDurationText($product['period']),    # Period text MONTHLY, YEARLY, QUARTERLY
                       $prefix. "type" => "google",                                            # Not sure what this property does but docs says this is better
                       #$prefix. "payments.subscription-payment-".$itemCount.".times" => "12", # Total Duration of subscription. Useless for us, We may activate this support automatic cancellations 
                       $prefix. "payments.subscription-payment-".$itemCount.".maximum-charge" => $product['price'],        # You cannot charge more than this price, it's none-sense
                       $prefix. "payments.subscription-payment-".$itemCount.".maximum-charge.currency" => $this->currency, # Currency for none-senseness
                       
                       # Product information
                       $prefix. "recurrent-item.item-name" => $product['name'],
                       $prefix. "recurrent-item.quantity" => "1", # We don't have quantity support for subscriptions
                       # $prefix. "recurrent-item.start-date" => "2009-12-01T08:15:30", # I don't know what to do with this
                       $prefix. "recurrent-item.unit-price" => $product['price'], 
                       $prefix. "recurrent-item.unit-price.currency" => $this->currency,
                    ));
                    
                    # Since google doesn't have setup fee support we will add setup fee as a different product
                    if($product["setupfee"] > 0){
                        $this->addToPost(array(
                            "shopping-cart.items.item-".($itemCount+1).".item-name" => "Setup Fee", #{@TODO This may get localized}
                            "shopping-cart.items.item-".($itemCount+1).".quantity" => "1",
                            "shopping-cart.items.item-".($itemCount+1).".unit-price" => $product["setupfee"],
                            "shopping-cart.items.item-".($itemCount+1).".unit-price.currency" => $this->currency,
                            "shopping-cart.items.item-".($itemCount+1).".item-description" => "Setup for this subscription" # This too
                        ));
                    }
                }
                
                $this->addToPost(array(
                    "shopping-cart.items.item-".$itemCount.".item-name" => $product['name'],
                    "shopping-cart.items.item-".$itemCount.".quantity" => "1", # this is the default value for quantity, if no quantity is set it should be 1
                    "shopping-cart.items.item-".$itemCount.".unit-price" => $price,
                    "shopping-cart.items.item-".$itemCount.".unit-price.currency" => $this->currency,
                ));
                
                if(is_array($product["options"])){
                    foreach($product["options"] as $i => $opt){
                        if($opt['type'] == "quantity"){ # overwrite the default quantity value
                            $this->postData["shopping-cart.items.item-".$itemCount.".quantity"] = $opt["selected"] < 1? 1 : $opt["selected"];
                            continue;
                        }
                        
                        # !IMPORTANT! These two lines are useless, I only wish they would work. :) Just change description
                        $this->postData["shopping-cart.items.item-".$itemCount.".item-attribute-name"] = $opt["name"];
                        $this->postData["shopping-cart.items.item-".$itemCount.".item-attribute-value"] = $opt["selected"];
                        
                        # Size, color or etc.
                        $description[] = $opt["name"].": ".$opt["selected"];
                    }
                }
                
                if($this->isSubscription){ # if it's a subscription then add the same description for it                    
                    $this->postData["shopping-cart.items.item-".$itemCount.".subscription.recurrent-item.item-description"] = join(", ", $description);
                }
                
                $this->postData["shopping-cart.items.item-".$itemCount.".item-description"] = join(", ", $description);
                $itemCount++; # skip to the next item
            }
            
        }else if($this->paymentType == "donation"){
            $product = $productsToBuy;
            $this->addToPost(array(
                "shopping-cart.items.item-1.item-name" => $product['donationText'],
                "shopping-cart.items.item-1.quantity" => "1", # this is the default value for quantity, if no quantity is set it should be 1
                "shopping-cart.items.item-1.unit-price" => $product['price'],
                "shopping-cart.items.item-1.item-description" => $product['name'],
                "shopping-cart.items.item-1.unit-price.currency" => $this->currency
            ));
        }
    }
    /**
     * Redirect user to complete payment
     */
    public function transact(){
        PaymentDataLog::addLog($this->formID, $this->submissionID, "GOOGLECO", "POSTED_DATA", "transact", $this->postData);
        echo Utils::postRedirect($this->postURL, $this->postData);
    }
    
    /**
     * Get the response back from google and complete the submission
     * @param object $request
     */
    public function ipn($request){
        // Collect Data
        $custom = $request["shopping-cart_merchant-private-data"];
        list($formID, $submissionID) = explode("-",$custom);
        
        PaymentDataLog::addLog($formID, $submissionID, "GOOGLECO", "IPN_RESPONSE", $request["_type"], $request);
        $request['gateway'] = "googleco";
        if($request["_type"] == "new-order-notification"){
            Submission::continueSubmission($submissionID, "", false, $request);
        }
    }
}
?>