<?php
/**
 * PayPal Website Payments Pro integration
 * @package JotForm_Payments
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\Payments;
class PayPalPro{
    
    private $options, $paymentType, $returnURL, $postURL, $custom, $isSubscription, $formID, $submissionID;
    
    /**
     * @var API Version
     */
    private $version = "54.0";
    
    /**
     * @var method of the payment express or credit
     */
    public $paymentMethod;
    public $sandbox = false;
    public $postData = array();
    
    
    /**
     * Initializes the PayPalPro object with options
     * @constructor
     * @param object $options Options for PayPalPro
     * <ul>
     *   <li>sid: Submission ID</li>
     *   <li>formID: Form ID</li>
     *   <li>apiPassword: username</li>
     *   <li>apiUsername: password</li>
     *   <li>signature:   signature</li>
     *   <li>currency:  currency code</li>
     *   <li>returnURL: User will be redirected ofter payment</li>
     *   <li>paymentType: type of the payment "subscription", "product" or "donation"</li>
     * </ul> 
     * @param object $options
     * @return 
     */
    function __construct($options){
        
        $this->options        = $options;
        $this->returnURL      = HTTP_URL."ipns/paypal_pro_express.php";
        $this->paymentType    = $options["paymentType"];
        $this->paymentMethod  = $options["paymentMethod"];
        $this->custom         = $options["formID"]."-".$options["sid"];
        $this->isSubscription = $this->paymentType == "subscription";
        $this->formID         = $options["formID"];
        $this->submissionID   = $options["sid"];
        $this->sandbox        = Utils::debugOption('useSandbox');
        
        $environment          = "";
        if($this->sandbox){
            $environment = "sandbox";
        }
        
        $this->postURL = "https://api-3t.paypal.com/nvp";
        if("sandbox" === $environment || "beta-sandbox" === $environment) {
        	$this->postURL = "https://api-3t.$environment.paypal.com/nvp";
        }
        
        $this->postData = array(
            "USER"          => $options['apiUsername'],
            "PWD"           => $options['apiPassword'],
            "SIGNATURE"     => $options['signature'],
            "VERSION"       => $this->version,
            "CUSTOM"        => $this->custom,
            "CURRENCYCODE"  => $options['currency'],
            "IPADDRESS"     => $_SERVER['REMOTE_ADDR'],
            "RETURNURL"     => $this->returnURL,
            "CANCELURL"     => !empty($_SERVER['HTTP_REFERRER'])? $_SERVER['HTTP_REFERRER'] : HTTP_URL."form/".$this->formID
        );
    }
    
    /**
     * Merges the new array with postData
     * @param object $data
     * @return 
     */
    public function addToPost($data){
        $this->postData = array_merge($this->postData, $data);
    }
    
    /**
     * Checks the response code for success value
     * @param object $code
     * @return 
     */
    private function checkResponseCode($response){
        if(strtolower($response["ACK"]) == "success" 
        || strtolower($response["ACK"]) == "successwithwarning" 
        || strtolower($response["STATUS"]) == "activeprofile"
        || strtolower($response["PAYMENTSTATUS"]) == "completed"
        ){
            return true;
        }
        return false;
    }
    
    
    /**
     * Makes express checkout request
     * Only for express payment method 
     * @return string Token returned by paypal
     */
    public function setExpressCheckout(){
        
        $this->addToPost(array(
            "METHOD"        => "SetExpressCheckout",
            "PAYMENTACTION" => "Authorization"
        ));
        
        if($this->isSubscription){
            $this->addToPost(array(
                "L_BILLINGTYPE0"=>"RecurringPayments",
                "L_BILLINGAGREEMENTDESCRIPTION0" => $this->postData["DESC"],
                "L_PAYMENTTYPE0" => "Any"
            ));
        }
        
        PaymentDataLog::addLog($this->formID, $this->submissionID, "PAYPALPRO", "POSTED_REQUEST", "SetExpressCheckout", $this->postData);
        
        $responseText = Utils::postRequest($this->postURL, $this->postData);
        $responseArray = $this->parseResponse($responseText);
        
        PaymentDataLog::addLog($this->formID, $this->submissionID, "PAYPALPRO", "RESPONSE", "SetExpressCheckout", $responseArray);
        # Console::log($responseArray);
        
        if($this->checkResponseCode($responseArray)){
            return $responseArray["TOKEN"];
        }else{
            Utils::errorPage($responseArray['L_LONGMESSAGE0'], $responseArray['L_SHORTMESSAGE0'], "SetExpressCheckout: ".$responseText);
        }
    }
    
    /**
     * Redirect User to paypal site for express checkout payment
     * @param object $token
     * @return 
     */
    public function goExpressCheckout($token){
        $env = "";
        if($this->sandbox){
            $env = ".sandbox";
        }
        Utils::postRedirect("https://www".$env.".paypal.com/cgi-bin/webscr", array(
            "cmd" => "_express-checkout",
            "token" => $token
        ));
    }
    
    /**
     * It's not used
     * @return 
     */
    public function SetCustomerBillingAgreement(){
        // May not be needed
    }
    
    public function getExpressCheckoutDetails(){
        $method = "GetExpressCheckoutDetails";
        
        $this->addToPost(array(
            "METHOD"        => $method,
            "TOKEN"         => urlencode($_REQUEST["token"])
        ));
        
        PaymentDataLog::addLog($this->formID, $this->submissionID, "PAYPALPRO", "POSTED_REQUEST", $method, $this->postData);
        
        $responseText = Utils::postRequest($this->postURL, $this->postData);
        $responseArray = $this->parseResponse($responseText);
        
        PaymentDataLog::addLog($this->formID, $this->submissionID, "PAYPALPRO", "RESPONSE", $method, $responseArray);
        
        $responseArray['gateway'] = 'paypalpro';
        
        # Do whatever you do with this information
        return $responseArray;
    }
    
    /**
     * Make the payment for express checkout
     * Only for express method
     */
    public function doExpressCheckoutPayment(){
        
        if($this->isSubscription){
            $method = 'CreateRecurringPaymentsProfile';
        }else{
            $method = 'DoExpressCheckoutPayment';
        }
        
        $this->addToPost(array(
            "METHOD"        => $method,
            "TOKEN"         => urlencode($_REQUEST["token"]),
            "PAYERID"       => urlencode($_REQUEST["PayerID"]), 
            "PAYMENTACTION" => "Sale",
        ));
        
        PaymentDataLog::addLog($this->formID, $this->submissionID, "PAYPALPRO", "POSTED_REQUEST", $method, $this->postData);
        
        $responseText = Utils::postRequest($this->postURL, $this->postData);
        $responseArray = $this->parseResponse($responseText);
        
        PaymentDataLog::addLog($this->formID, $this->submissionID, "PAYPALPRO", "RESPONSE", $method, $responseArray);
        
        if(!$this->checkResponseCode($responseArray)){
            Utils::errorPage($responseArray['L_LONGMESSAGE0'], $responseArray['L_SHORTMESSAGE0'], "DoExpressCheckout: ".$responseText);
        }
    }
    
    /**
     * Converts duration texts for paypal format
     * @param object $duration
     * @return 
     */
    private function convertDurationText($duration){
        
        switch($duration){
            case 'Daily' :      return array("period" => "1", "time"=> "Day");
            case 'Weekly' :     return array("period" => "1", "time"=> "Week");
            case 'Bi-Weekly' :  return array("period" => "2", "time"=> "Week");
            case 'Monthly' :    return array("period" => "1", "time"=> "Month");
            case 'Bi-Monthly' : return array("period" => "2", "time"=> "Month");
            case 'Quarterly' :  return array("period" => "3", "time"=> "Month");
            case 'Semi-Yearly': return array("period" => "6", "time"=> "Month");
            case 'Yearly' :     return array("period" => "1", "time"=> "Year");
            case 'Bi-Yearly' :  return array("period" => "2", "time"=> "Year");
            case 'None' :       return array("period" => "0", "time"=> "Day");
            case 'One Day' :    return array("period" => "1", "time"=> "Day");
            case 'Three Days' : return array("period" => "3", "time"=> "Day");
            case 'Five Days' :  return array("period" => "5", "time"=> "Day");
            case '10 Days' :    return array("period" => "10", "time"=> "Day");
            case '15 Days' :    return array("period" => "15", "time"=> "Day");
            case '30 Days' :    return array("period" => "30", "time"=> "Day");
            case '60 Days' :    return array("period" => "60", "time"=> "Day");
            case '6 Months' :   return array("period" => "6", "time"=> "Month");
            case '1 Year' :     return array("period" => "1", "time"=> "Year");
            default :           return array("period" => "1", "time"=> "Month");
        }
    }
    
    /**
     * Creates an expression date for paypal
     * @param object $month
     * @param object $year
     * @return 
     */
    function makeExpirationDate($month, $year){
        $months = array("January"=>"01","February"=>"02","March"=>"03","April"=>"04","May"=>"05","June"=>"06","July"=>"07","August"=>"08","September"=>"09","October"=>"10","November"=>"11","December"=>"12");
        return $months[$month].$year;
    }
    /**
     * sets the credit card information 
     * @param object $cardNumber
     * @param object $expirationMonth
     * @param object $expirationYear
     * @param object $securityCode
     * @param object $type [optional]
     */
    public function setCreditCard($cardNumber, $expirationMonth, $expirationYear, $securityCode, $type = 'VISA'){
        $this->addToPost(array(
            "CREDITCARDTYPE" => Utils::identifyCreditCard($cardNumber),
            "ACCT"           => $cardNumber,
        	"EXPDATE"        => $this->makeExpirationDate($expirationMonth, $expirationYear),
            "CVV2"           => $securityCode,
        ));
    }
    
    /**
     * Sets the billing information
     * @param object $firstName
     * @param object $lastName
     * @param object $address
     * @param object $city
     * @param object $state
     * @param object $country
     * @param object $zip
     */
    public function setBillingInfo($firstName, $lastName, $address, $city, $state, $country, $zip, $email){
        
        $this->addToPost(array(
            "FIRSTNAME"      => $firstName,
            "LASTNAME"       => $lastName,
        	"STREET"         => $address,
            "CITY"           => $city, 
            "STATE"          => Utils::getStateAbbr($state),
            "ZIP"            => $zip,
            "EMAIL"          => $email,
            "COUNTRYCODE"    => Utils::getCountryAbbr($country),
        ));
        
    }
    
    /**
     * Sets the products selected by user
     * @param object $productsToBuy
     */
    public function setProducts($productsToBuy){
        $description = array();
        
        if($this->paymentType == 'donation'){
            $this->addToPost(array(
                "AMT"   => $productsToBuy['price'],
                "DESC"  => $productsToBuy['donationText']
            ));
            return;
        }
            
        if($this->isSubscription){
            
            foreach($productsToBuy as $i => $product){
                
                $price = $product["price"];
                if($product["setupfee"] > 0 || $product['trial'] != "None"){
                    $product["setupfee"] = $product["setupfee"]? $product["setupfee"] : 0;
                    
                    $price = $product["setupfee"];
                     
                    if($product['trial'] == "None"){
                        $trial = $this->convertDurationText($product['period']);
                        
                    }else if($product["setupfee"] > 0 && $product['trial'] != "None"){
                        $price = $product["setupfee"];
                        $trial = $this->convertDurationText($product['trial']);
                    }else{
                        $price = false;
                        $trial = $this->convertDurationText($product['trial']);
                    }
                    
                    $this->addToPost(array(
                        "TRIALAMT" => $product["setupfee"],
                        "TRIALBILLINGPERIOD" => $trial["time"],
                        "TRIALBILLINGFREQUENCY" => $trial["period"],
                        "TRIALTOTALBILLINGCYCLES" => "1", 
                    ));
                }
                
                $subscription = $this->convertDurationText($product['period']);
                $this->addToPost(array(
                    "DESC" => $product["name"], // {@TODO: Add proper description here. Like Free for the first month $9 for each month}
                    "AMT" => $product["price"],
                    "INITAMT" =>  $price,
                    "BILLINGPERIOD" => $subscription["time"],
                    "BILLINGFREQUENCY" => $subscription["period"],
                    "PROFILESTARTDATE" => date("Y-n-j")."T0:0:0",
                    "PROFILEREFERENCE" => $this->options["sid"],
                    "TOTALBILLINGCYCLES" => "0"
                ));
            }
            
        }else{
            
            $totalPrice = 0;
            $itemNo = 0;
            foreach($productsToBuy as $i => $product){
                $name  = $product['name']; 
                $price = $product['price'];
                $desc  = "";
                $qty   = "1";
                $itemNo++;
                # make sure quantitiy is first
                if(isset($product["options"]) && is_array($product["options"])){
                    foreach($product["options"] as $i => $opt){
                        if($opt['type'] == "quantity"){
                            $price = $price * $opt["selected"];
                            $qty = $opt["selected"];
                            continue;
                        }
                        $desc .= " (".$opt["name"].": ". $opt["selected"].")";
                    }
                }
                
                if($this->paymentMethod == 'express'){
                    $this->addToPost(array(
                        "L_NAME".$itemNo => $product['name'],
                        "L_DESC".$itemNo => $desc,
                        "L_AMT".$itemNo  => $product['price'],
                        "L_NUMBER".$itemNo => $itemNo,
                        "L_QTY".$itemNo  => $qty
                    ));
                }
                
                $description[] = $name.$desc;
                $totalPrice += $price;
                
            }
            
            if($this->paymentMethod == 'express'){
                $this->addToPost(array(
                    "ITEMAMT" => $totalPrice,
                ));
            }
            $this->addToPost(array(
            	"AMT" => $totalPrice,
            	"DESC"	=> join(", ", $description)
            ));
            
        }
    }
    
    /**
     * Parses the response returned by paypal
     * @param object $responseText
     * @return 
     */
    //TOKEN=EC%2d9BC46817L8362461D&TIMESTAMP=2010%2d06%2d07T14%3a30%3a54Z&CORRELATIONID=b2b9b2fb28012&ACK=Success&VERSION=54%2e0&BUILD=1336399&TRANSACTIONID=6MS88638DN178650D&TRANSACTIONTYPE=expresscheckout&PAYMENTTYPE=instant&ORDERTIME=2010%2d06%2d07T14%3a30%3a53Z&AMT=2%2e95&FEEAMT=0%2e42&TAXAMT=0%2e00&CURRENCYCODE=USD&PAYMENTSTATUS=Completed&PENDINGREASON=None&REASONCODE=None
    private function parseResponse($responseText){
        
        $response = explode('&', $responseText);
        $responseArray = array();
        foreach($response as $value){
            list($key, $val) = explode("=", $value);
            $responseArray[$key] = urldecode($val);
        }
        return $responseArray;
    }
    
    /**
     * Does the transaction
     * @param object $paymentAction [optional]
     * @param object $return returns the response insted of throwing an error
     */
    public function transact($paymentAction = 'Sale', $return = false){
        
        if($this->isSubscription){
            $method = 'CreateRecurringPaymentsProfile';
        }else{
            $method = 'DoDirectPayment';
        }
        
        if($return){
            PaymentDataLog::$stopLog = true;
        }
        
        $this->addToPost(array(
            "METHOD"    => $method,
            "VERSION"   => $this->version,
            "PAYMENTACTION"  => $paymentAction,
        ));
        
        PaymentDataLog::addLog($this->formID, $this->submissionID, "PAYPALPRO", "POSTED_REQUEST", $method, $this->postData);
        
        $responseText = Utils::postRequest($this->postURL, $this->postData);
        
        $responseArray = $this->parseResponse($responseText);        
        PaymentDataLog::addLog($this->formID, $this->submissionID, "PAYPALPRO", "RESPONSE", $method, $responseArray);
        
        if($return){
            if( ! $this->checkResponseCode($responseArray)){
                return $responseArray['L_LONGMESSAGE0']."::".$responseArray['L_SHORTMESSAGE0']."::".$responseArray['L_ERRORCODE0'];
            }else{
                return true;
            }
        }
        
        if(strtolower(@$responseArray["STATUS"]) != "activeprofile" && 
           strtolower($responseArray["ACK"]) != "success" && 
           strtolower($responseArray["ACK"]) != "successwithwarning"){

           Utils::errorPage($responseArray['L_LONGMESSAGE0'], $responseArray['L_SHORTMESSAGE0'], "Transact: ".$responseText);
        }
    }
    /**
     * Tests this integration with a dummy value
     * @param object $username
     * @param object $password
     * @param object $signature
     * @return array response
     */
    public static function testIntegration($username, $password, $signature){
        $paypro = new PayPalPro(array(
            "sid"          => rand(0, 1000),
            "apiPassword"  => $password,
            "apiUsername"  => $username,
            "signature"    => $signature,
            "currency"     => "USD"
        ));
        $paypro->setProducts(array(array(
            "icon" => "",
            "name" => "Test Product".rand(0, 10000),
            "period" => "Monthly",
            "pid" => rand(0, 200),
            "price" => "3.91",
            "setupfee" => "5",
            "trial" => "None"
        )));
        $paypro->setCreditCard("4997641160155808", "November", "2019", "777");
        $paypro->setBillingInfo("John","Doe","Addres","city","NY","US","11377" );
        
        // We may display a link with error code inside
        // https://www.x.com/search.jspa?q=10548+Invalid+Configuration
        
        if(strpos($paypro->transact("Authorization", true), "10002")){
            return "Please check your api login credentials::API Login Failed";
        }
        return true;
    }
    
    /**
     * IPN for express checkouts
     * @param object $request
     */
    static function ipn($request){
        
        //PaymentDataLog::addLog($this->formID, $this->submissionID, "PAYPALPRO", "IPN_RESPONSE", $request["token"], $request);
        $request['gateway'] = 'paypalpro';
        Submission::continueSubmission($request["token"], "", false, $request);
    }
}
