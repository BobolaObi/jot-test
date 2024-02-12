<?php
/**
 * Handles 2Co Payments
 * @package JotForm_Payments
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\Payments;
class TwoCheckOut{
    
    private $postURL, $postData = array(), $options, $returnURL, $paymentType, $isSubscription, $currency, $custom, $vendorNumber, $formID, $submissionID;
    
    function __construct($options){
        $this->ipnURL       = HTTP_URL."/ipns/2co.php";
        $this->postURL      = "https://www.2checkout.com/checkout/purchase";
        $this->options      = $options;
        
        $this->vendorNumber = $options["vendorNumber"];
        $this->returnURL    = $options["returnURL"];
        $this->paymentType  = $options["paymentType"];
        $this->isSubscription = $this->paymentType == "subscription";
        $this->currency     = $options["currency"];
        $this->custom       = $options["formID"]."-".$options["sid"];
        $this->formID       = $options["formID"];
        $this->submissionID = $options["sid"];
        
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
            "sid"   => $this->vendorNumber,
            "currency" => $this->currency,
            "cart_order_id" => $this->custom,
            "return_url" => $this->ipnURL,
            "id_type" => "1",
            "demo" => Utils::debugOption('useSandbox')? "Y" : false,
            //"demo" => "Y",
            "x_receipt_link_url" => $this->returnURL
        ));
    }
    
    
    /**
     * Will create product configuration according to payment type
     * @param object $productsToBuy Array of products selected on the form.
     */
    public function setProducts($productsToBuy){
        $itemCount = 1;
        if($this->isSubscription){
            foreach($productsToBuy as $i => $product){
                $this->addToPost(array(
                    "product_id"+$itemCount => $product["2coid"],
                    "quantity"+$itemCount => "1",
                ));
                $itemCount++;
            }

        }else if($this->paymentType == "product"){
             
            $total = 0;
            foreach($productsToBuy as $i => $product){
                $this->postData["c_price_".$itemCount] = $product['price'];
                $this->postData["c_name_".$itemCount] = $product['name'];
                
                $price = $product['price'];
                $description = array();
                # make sure quantitiy is first
                if(is_array($product["options"])){
                    foreach($product["options"] as $i => $opt){
                        if($opt['type'] == "quantity"){
                            $this->postData["c_prod_".$itemCount] = $itemCount.",".$opt["selected"];
                            $price *= $opt["selected"];
                            continue;
                        }
                        
                        $description[] = "( ". $opt["name"] . ": ". $opt["selected"] ." )";
                    }
                }
                $total += $price;
                $d = join(", ", $description);
                if(empty($d)){
                    $d = $product['name'];
                }
                $this->postData["c_description_".$itemCount] = $d;
                $itemCount++;
            }
            $this->postData["total"] = $total;
            
        }else if($this->paymentType == "donation"){
            // {@TODO Donation here}
        }
    }
    
    /**
     * Make transaction redirect user to PayPal
     * @return 
     */
    public function transact(){
        
        PaymentDataLog::addLog($this->formID, $this->submissionID, "2CO", "POSTED_REQUEST", "transact", $this->postData);
        
        Utils::postRedirect($this->postURL, $this->postData);
    }
    
    public static function ipn($request){
        
        list($formID, $submissionID) = explode("-",$request['cart_order_id']);
        
        PaymentDataLog::addLog($formID, $submissionID, "2CO", "IPN_RESPONSE", "completed", $request);
        $request['gateway'] = '2co';
        Submission::continueSubmission($submissionID, "", false, $request);
    }
}
?>