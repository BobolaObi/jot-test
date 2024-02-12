<?php
/**
 * Handles the Authorize.net transactions
 * @package JotForm_Payments
 * @copyright Copyright (c) 2009, Interlogy LLC
 */class AuthorizeDotNet{
    
    private $loginID, $transactionKey, $currency, $returnURL, $paymentType, $cmd, $isSubscription, $options, $formID, $submissionID,
            $goBackMessage = "<br><br>Please <a href=\"javascript:window.history.back(-1)\">go back</a> and fix this";
    
    /**
     * @var Contains the created post data will be send to payment gateway
     */
    public $postData;
    
    public $xmlData;
    
    /**
     * Initializes the Authorize.Net object with options
     * @constructor
     * @param object $options Options for Authorize.Net
     * <ul>
     *   <li>loginID: login id</li>
     *   <li>transactionKey: transaction key</li>
     *   <li>currency:  currency code</li>
     *   <li>returnURL: User will be redirected ofter payment</li>
     *   <li>paymentType: type of the payment "subscription", "product" or "donation"</li>
     * </ul>
     * @TODO Add donation support
     */
    function __construct($options){
        $this->options = $options;
        $this->loginID        = $options["loginID"];
        $this->transactionKey = $options["transactionKey"];
        $this->returnURL      = $options["returnURL"];
        $this->paymentType    = $options["paymentType"];
        $this->currency       = $options["currency"];
        $this->isSubscription = $this->paymentType == "subscription"; 
        $this->formID         = $options["formID"];
        $this->submissionID   = $options["sid"];
        
        $this->createPostData(); # initiate the data for post
    }
    
    
    
    /**
     * Initiates the post datawith initial values
     * @return null 
     */
    private function createPostData(){
        
        if($this->isSubscription){
            
            
            $this->xmlData = array(
                "merchantAuthentication" => array(
                    "name" => $this->loginID,
                    "transactionKey" => $this->transactionKey
                ),
                "refId" => $this->options["sid"],
                "subscription" => array()
                
            ); 
            
            return;
        }
        
        
        $this->postData = array(
            // the API Login ID and Transaction Key must be replaced with valid values
            "x_login"	        => $this->loginID,
            "x_tran_key"	    => $this->transactionKey,
            "x_version"	        => "3.1",
            "x_delim_data"	    => "TRUE",
            "x_delim_char"	    => "|",
            "x_relay_response"	=> "FALSE",
            "x_type"	        => "AUTH_CAPTURE",
            "x_method"	        => "CC",
        );
    }
    /**
     * Sets the credit card information
     * @param object $cardNumber
     * @param object $expirationMonth
     * @param object $expirationYear
     * @param object $securityCode
     */
    public function setCreditCard($cardNumber, $expirationMonth, $expirationYear, $securityCode){
        
        if($this->isSubscription){
            $this->xmlData["subscription"] = array_merge($this->xmlData["subscription"], array(
                "payment" => array(
                    "creditCard"=>array(
                        "cardNumber" => $cardNumber,
                        "expirationDate" => $this->fixExpressionDate($expirationMonth, $expirationYear),
                        "cardCode" => $securityCode
                    )
                )
            ));
            return;
        }
        
        $this->postData = array_merge($this->postData, array(
            // Credit card info
            "x_card_num"	    => $cardNumber,
            "x_card_code"	    => $securityCode,
            "x_exp_date"	    => $this->fixExpressionDate($expirationMonth, $expirationYear)
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
        
        if($this->isSubscription){
            $this->xmlData["subscription"] = array_merge($this->xmlData["subscription"], array(
                "billTo" => array(
                    "firstName"     => $firstName,
                    "lastName"      => $lastName,
                    "address"       => $address,
                    "city"          => $city,
                    "state"	        => $state,
                    "zip"	        => $zip,
                    "country"	    => $country
                    #, "email"         => $email
                )
            ));
            return;
        }
        
        $this->postData = array_merge($this->postData, array(
            // Billing Info
            "x_first_name"      => $firstName,
            "x_last_name"       => $lastName,
            "x_address"         => $address,
            "x_city"            => $city,
            "x_state"	        => $state,
            "x_country"	        => $country,
            "x_zip"	            => $zip
            # , "x_email"           => $email
        ));
    }
    
    /**
     * Will create product configuration according to payment type
     * @param object $productsToBuy Array of products selected on the form.
     */
    public function setProducts($productsToBuy){
        
        if($this->isSubscription){
            foreach($productsToBuy as $i => $product){
                //if($product["setupfee"] > 0 || $product['trial'] != "None"){
                    $product["setupfee"] = $product["setupfee"]? $product["setupfee"] : 0;

                    $trial = $product['trial'] != "None"? "1" : "0";
                    
                    $period = $this->convertDurationText($product['period']);
                    
                    $this->xmlData["subscription"] = array_merge($this->xmlData["subscription"], array(
                        "name" => $product["name"],
                        "paymentSchedule" => array(
                            "interval" => array(
                                "length" => $period["period"],
                                "unit" => $period["time"]
                            ),
                            "startDate" => date("Y-m-d"),
                            "totalOccurrences" => "9999",
                            "trialOccurrences" => $trial
                        ),
                        "amount" => $product['price'],
                        "trialAmount" => $product["setupfee"]
                    ));
                //}
            }

        }else if($this->paymentType == "product"){
            
            $description = array();

            $total_price = 0;
            foreach($productsToBuy as $i => $product){
                $name = $product['name'];
                $price = $product['price'];
                $total_price += $product['price']; 
                // {@TODO x_line_item property can be used to send quantity and unit price information}
                # make sure quantitiy is first
                if(is_array($product["options"])){
                    foreach($product["options"] as $i => $opt){
                        if($opt['type'] == "quantity"){
                            $total_price += number_format($price * ($opt["selected"]-1), ".");
                            $name .= " quantity: ". $opt["selected"];
                            continue;
                        }
                        $name .= " (".$opt["name"].": ". $opt["selected"].")";
                    }
                }
                $description[] = $name;
            }
            

            $this->postData = array_merge($this->postData, array(
                 // Product info
                "x_amount"              => $total_price,
                "x_description"     => join(", ", $description)
            ));
            
        }else if($this->paymentType == "donation"){
            $this->postData = array_merge($this->postData, array(
                 // Product info
                "x_amount"          => $productsToBuy['price'],
                "x_description"     => $productsToBuy['donationText']
            ));
        }
    }
    
    /**
     * Converts duration texts for Authorize.Net format
     * This may not be needed for Authorize.Net
     * @param object $duration     
     * @return 
     */
    private function convertDurationText($duration){
        
        switch($duration){
            case 'Daily' :      return array("period" => "1", "time"=> "days");
            case 'Weekly' :     return array("period" => "7", "time"=> "days");
            case 'Bi-Weekly' :  return array("period" => "14", "time"=> "days");
            case 'Monthly' :    return array("period" => "1", "time"=> "months");
            case 'Bi-Monthly' : return array("period" => "2", "time"=> "months");
            case 'Quarterly' :  return array("period" => "3", "time"=> "months");
            case 'Semi-Yearly': return array("period" => "6", "time"=> "months");
            case 'Yearly' :     return array("period" => "12", "time"=> "months");
            case 'Bi-Yearly' :  return array("period" => "24", "time"=> "months");
            case 'None' :       return array("period" => "0", "time"=> "days");
            case 'One Day' :    return array("period" => "1", "time"=> "days");
            case 'Three Days' : return array("period" => "3", "time"=> "days");
            case 'Five Days' :  return array("period" => "5", "time"=> "days");
            case '10 Days' :    return array("period" => "10", "time"=> "days");
            case '15 Days' :    return array("period" => "15", "time"=> "days");
            case '30 Days' :    return array("period" => "30", "time"=> "days");
            case '60 Days' :    return array("period" => "60", "time"=> "days");
            case '6 Months' :   return array("period" => "6", "time"=> "months");
            case '1 Year' :     return array("period" => "12", "time"=> "months");
            default :           return array("period" => "1", "time"=> "months");
        }
    }
    
    /**
     * Joins the month and year in the respective format
     * @param object $month
     * @param object $year
     * @return 
     */
    private function fixExpressionDate($month, $year){
        $months = array("January"=>"01","February"=>"02","March"=>"03","April"=>"04","May"=>"05","June"=>"06","July"=>"07","August"=>"08","September"=>"09","October"=>"10","November"=>"11","December"=>"12");
        return $months[$month].substr($year, 2, 2);
    }
    
    /**
     * Create xml tree from an array
     * @param object $array
     * @return string XML 
     */
    private function makeXMLTags($array){
        foreach($array as $key => $value){
            $xml .= "<".$key.">";
            if(is_array($value)){
                $xml .= $this->makeXMLTags($value);
            }else{
                $xml .= $value;
            }
            $xml .= "</".$key.">";
        }
        return $xml;
    }
    
    /**
     * Parses the Authorize.Net XML response and returns the values
     * @param object $content
     * @return array
     */
    function parseReturn($content){
        $refId = Utils::substringBetween($content,'<refId>','</refId>');
        $resultCode = Utils::substringBetween($content,'<resultCode>','</resultCode>');
        $code = Utils::substringBetween($content,'<code>','</code>');
        $text = Utils::substringBetween($content,'<text>','</text>');
        $subscriptionId = Utils::substringBetween($content,'<subscriptionId>','</subscriptionId>');
        return array ($refId, $resultCode, $code, $text, $subscriptionId);
    }
    
    /**
     * Creates the ARM XML
     * @return string formatted xml
     */
    private function createXML(){
        $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
                "<ARBCreateSubscriptionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">";
        $xml .= $this->makeXMLTags($this->xmlData);
        $xml .= "</ARBCreateSubscriptionRequest>";
        return $xml;
    }
    
    /**
     * Makes transaction gets the response from Authorize.Net 
     * then prints errors or lets the JotForm redirect users 
     * to thank you page
     */
    public function transact($return = false){
        
        if($return){
            PaymentDataLog::$stopLog = true;
        }
        
        if($this->isSubscription){
            
            $host = "api.authorize.net";
            $path = "/xml/v1/request.api";

            $content = $this->createXML();
            
            $header  = "Host: $host\r\n";
            $header .= "User-Agent: PHP Script\r\n";
            $header .= "Content-Type: text/xml\r\n";
            $header .= "Content-Length: ".strlen($content)."\r\n";
            $header .= "Connection: close\r\n\r\n";
            
            PaymentDataLog::addLog($this->formID, $this->submissionID, "AUTHNET", "POSTED_REQUEST", "subscription", $this->xmlData);
            
            $response = Utils::postRequest("https://".$host.$path, $content, $header);
            
            if($response){
                list ($refId, $resultCode, $code, $text, $subscriptionId) = $this->parseReturn($response);
                
                PaymentDataLog::addLog($this->formID, $this->submissionID, "AUTHNET", "RESPONSE", "subscription", array (
                    "refID" => $refId,
                    "resultCode" => $resultCode,
                    "code" => $code,
                    "text" => $text,
                    "subscriptionID" => $subscriptionId
                ));
                
                if($return){
                    if(strtolower($resultCode) == "ok"){
                        return true;
                    }else{
                        return $text;
                    }
                }
                
                if(strtolower($resultCode) != "ok"){
                    Utils::errorPage($text, "Error on transaction", "$refId, $resultCode, $code, $text, $subscriptionId");
                }
            }
            return;
        }
        
        #Console::log($this->postData, "Authorize.Net post data");
        PaymentDataLog::addLog($this->formID, $this->submissionID, "AUTHNET", "POSTED_REQUEST", "normal", $this->postData);
        
        $response = Utils::postRequest("https://secure.authorize.net/gateway/transact.dll", $this->postData);
        if($response_array = @explode($this->postData["x_delim_char"], $response)){
            PaymentDataLog::addLog($this->formID, $this->submissionID, "AUTHNET", "RESPONSE", "normal", $response_array);
            /*  Respone Code
             * 1 = Approved
             * 2 = Declined
             * 3 = Error
             * 4 = Held for Review
             */
            if($return){ # for testing, ignore declined message
                if($response_array[0] == "1" || $response_array[0] == "2"){
                    return true;
                }else{
                    return $response_array[3];
                }
            }
            
            if($response_array[0] != "1"){
                Utils::errorPage($response_array[3]." ".$this->goBackMessage, "Error during transaction", $response);
            }
        }
    }
    /**
     * Tests this integration with a dummy data
     * @param object $loginId
     * @param object $transactionKey
     * @param object $paymentType
     * @return array response
     */
    public static function testIntegration($loginId, $transactionKey, $paymentType){
        $authnet = new AuthorizeDotNet(array(
            "sid" => rand(0, 1000),
            "loginID" =>        $loginId,
            "transactionKey" => $transactionKey,
            "paymentType"    => $paymentType,
            "currency"       => "USD"
        ));
        
        $authnet->setProducts(array(array(
            "icon" => "",
            "name" => "Test Product".rand(0, 10000),
            "period" => "Monthly",
            "pid" => rand(0, 200),
            "price" => 3.91,
            "setupfee" => "5",
            "trial" => "None"
        )));
        
        $authnet->setCreditCard("4111111111111111","January","2015","777");
        
        $authnet->setBillingInfo("John","Doe","Addres","city","state","United states","12345" );
        
        return $authnet->transact(true);        
    }
}
