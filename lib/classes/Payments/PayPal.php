<?php
/**
 * Handles the PayPal transactions
 * @package JotForm_Payments
 * @copyright Copyright (c) 2009, Interlogy LLC
 */
class PayPal {
    
    private $business, $currency, $returnURL, $paymentType, $cmd, $custom, $ipnURL, $options, $formID, $submissionID;
    
    /**
     * @var Contains the created post data will be send to payment gateway
     */
    public $postData;
    
    /**
     * Initializes the paypal object with options
     * @constructor
     * @param object $options Options for paypal
     * <ul>
     *   <li>sid: SubmissionsID</li>
     *   <li>formID: Form ID</li>
     *   <li>business: account email</li>
     *   <li>currency: curency code</li>
     *   <li>returnURL: User will be redirected ofter payment</li>
     *   <li>paymentType: type of the payment "subscription", "product" or "donation"</li>
     * </ul>
     */
    function PayPal($options){
        
        $this->ipnURL       = HTTP_URL."ipns/paypal.php";
        $this->options      = $options;
        $this->business     = $options["business"];
        $this->returnURL    = $options["returnURL"];
        $this->paymentType  = $options["paymentType"];
        $this->isSubscription = $this->paymentType == "subscription";
        $this->currency     = $options["currency"];
        $this->custom       = $options["formID"]."-".$options["sid"];
        $this->formID       = $options["formID"];
        $this->submissionID = $options["sid"];
        
        if($options["payerAddress"] == "Yes"){
            $this->payerAddress = "2";
        }else if($options["payerAddress"] == "No"){
            $this->payerAddress = "1";
        }else{
            $this->payerAddress = "0";
        }
        
        # this may differ for donations
        $this->cmd          = $this->isSubscription? "_xclick-subscriptions" : "_cart";
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
        $this->postData = array(
            "business"      => $this->business,  # Paypal Account Email
            "charset"       => "utf-8",          # Charset of the product names 
            "cmd"           => $this->cmd,       # Paypal command for payment type
            "currency_code" => $this->currency,  # Currency for payment
            "custom"        => $this->custom,    # Custom string for identifying the payment 
            "rm"            => 2,                # Return method, POST will be used
            "notify_url"    => $this->ipnURL,    # IPN notification URL
            "no_shipping"   => $this->payerAddress, # Require address or not
            "return"        => $this->returnURL, # Thank you URL will be here 
        );
        
        if($this->isSubscription){
            $this->postData["src"] = 1;         # Payment will recur
        }else{
            $this->postData["upload"] = 1;      # Upload a shopping cart
        }
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
             
            $itemCount = 1;
            foreach($productsToBuy as $i => $product){
                $this->postData["amount_".$itemCount] = $product['price'];
                $this->postData["item_name_".$itemCount] = $product['name'];
                # make sure quantitiy is first
                if(isset($product["options"])){
                    foreach($product["options"] as $i => $opt){
                        if($opt['type'] == "quantity"){
                            $this->postData["quantity_".$itemCount] = $opt["selected"];
                            continue;
                        }
                         
                        $this->postData["on".($i+1)."_".$itemCount] = $opt["name"];
                        $this->postData["os".($i+1)."_".$itemCount] = $opt["selected"];
                        
                    }
                }
                $itemCount++;
            }
            
        }else if($this->paymentType == "donation"){
            $product = $productsToBuy;
            $this->postData["amount_1"] = $product['price'];
            $this->postData["item_name_1"] = $product['donationText'];
        }
    }
    
    /**
     * Converts duration texts for paypal format
     * @param object $duration
     * @return 
     */
    private function convertDurationText($duration){
        
        switch($duration){
            case 'Daily' :      return array("period" => "1", "time"=> "D");
            case 'Weekly' :     return array("period" => "1", "time"=> "W");
            case 'Bi-Weekly' :  return array("period" => "2", "time"=> "W");
            case 'Monthly' :    return array("period" => "1", "time"=> "M");
            case 'Bi-Monthly' : return array("period" => "2", "time"=> "M");
            case 'Quarterly' :  return array("period" => "3", "time"=> "M");
            case 'Semi-Yearly': return array("period" => "6", "time"=> "M");
            case 'Yearly' :     return array("period" => "1", "time"=> "Y");
            case 'Bi-Yearly' :  return array("period" => "2", "time"=> "Y");
            case 'None' :       return array("period" => "0", "time"=> "D");
            case 'One Day' :    return array("period" => "1", "time"=> "D");
            case 'Three Days' : return array("period" => "3", "time"=> "D");
            case 'Five Days' :  return array("period" => "5", "time"=> "D");
            case '10 Days' :    return array("period" => "10", "time"=> "D");
            case '15 Days' :    return array("period" => "15", "time"=> "D");
            case '30 Days' :    return array("period" => "30", "time"=> "D");
            case '60 Days' :    return array("period" => "60", "time"=> "D");
            case '6 Months' :   return array("period" => "6", "time"=> "M");
            case '1 Year' :     return array("period" => "1", "time"=> "Y");
            default :           return array("period" => "1", "time"=> "M");
        }
    }
    
    /**
     * Make transaction redirect user to PayPal
     * @return 
     */
    public function transact(){
        PaymentDataLog::addLog($this->formID, $this->submissionID, "PAYPAL", "POSTED_DATA", "transaction", $this->postData);
        
        if(Utils::debugOption('useSandbox')){
            Utils::postRedirect("https://www.sandbox.paypal.com/cgi-bin/webscr", $this->postData);
        }else{
            Utils::postRedirect("https://www.paypal.com/cgi-bin/webscr", $this->postData);
        }
        
    }
    
    /**
     * IPN for paypal, collects the returned information, completes the submission and keeps payment logs
     * @param object $request
     * @return 
     */
    public static function ipn($request){
        $note = "";
        foreach($request as $k => $v){
        	$note .= "$k: $v\n";
        }
        
        #Console::log($note, "PayPal IPN Request");
        
        if ($request['txn_type']){
        	$status = $request['txn_type'];            
        }else{
        	$status = $request['payment_status'];
        }	
                  
        switch($status){
        	case "subscr_signup":
        		$total = $request['mc_amount1'];
        		$status = "COMPLETE";
        		break;
        	case "subscr_cancel":
        		$total = 0;
        		$status = "CANCELLED";
        		break;
        	case "subscr_payment":
        		$total = $request['mc_gross'];
        		$status = "COMPLETE";
        		break;
        	case "web_accept":
        		$total = $request['mc_gross'];
        		$status = "COMPLETE";
        		break;
        	case "Refunded":
        		$total = 0;
        		$status = "REFUNDED";
        		break;
        	case "failed":
        		$total = 0;
        		$status = "FAILED";
        		break;
        	default:
        		$total = 0;
        		break;
        }
        $request['gateway'] = 'paypal';
        //{@TODO check the IPN authentication}
        
        // Collect Data
        list($formID, $submissionID) = explode("-",$request['custom']);
        
        PaymentDataLog::addLog($formID, $submissionID, "PAYPAL", "IPN_RESPONSE", $status, $request);
        
        Submission::continueSubmission($submissionID, '', true, $request);
    }
}
