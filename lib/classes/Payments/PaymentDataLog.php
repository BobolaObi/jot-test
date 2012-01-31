<?php
/**
 * Keeps the log of the all payments
 */
class PaymentDataLog{
    /**
     * set to true if you want to stop logs for one specific location
     * @var boolean
     */
    public static $stopLog = false;
    
    /**
     * Converts given array data to readable string data
     * @return 
     * @param object $data
     */
    public static function convertArrayToReadable($data, $prefix = ""){
        $string = "";
        $indent = "    ";
        foreach($data as $key => $value){
            if(is_array($value)){
                $string .= $prefix.'"'.$key.'"'." =>[\n" . self::convertArrayToReadable($value, $prefix.$indent) . $prefix . "]\n";
            }else{
                # Remove credit card numbers from logs. leave a little for debugging options
                if(strpos($key, "cc_number") !== false){ 
                    $value = substr($value, 0, 4)."-XXXX-XXXX-".substr($value, 0, -4);
                }
                # Completely remove security number
                if(strpos($key, "cc_ccv") !== false){
                    $value = "XXX";
                }
                $string .= $prefix.'"'.$key.'"'." => (".$value.")\n";
            }
        }
        return $string;
    }
    
    /**
     * Adds an entry to payment logs table
     * @param object $formID Id of the form containing payment gateway
     * @param object $submissionID ID of the submission which contains payment
     * @param object $gateway gateway name of the payment, such as PAYPAL, PAYPALPRO, GOOGLECO, 2CO, ONEBIP, WORLDPAY, CLICKBANK, AUTHNET
     * @param object $type Type of the log such as (POSTED_DATA, IPN_RESPONSE)
     * @param object $name Name of the log to identify its content such as (web_access, subscr_start)
     * @param object $data Content of the log data. Probably the post data or manually entered value 
     */
    public static function addLog($formID, $submissionID, $gateway, $type, $name, $data){
        
        if(self::$stopLog){
            return;
        }
        
        try{
            if(is_array($data)){
                $data = self::convertArrayToReadable($data);
            }
            DB::insert("payment_data_log", array(
                "form_id" => $formID,
                "submission_id" => $submissionID,
                "gateway" => $gateway,
                "log_type" => $type,
                "log_name" => strtoupper($name),
                "log_data" => $data
            ));
        }catch(Exception $e){
            // Try catch here to prevent errors from stopping payments
            Console::error("Log cannot be written", "error on log");
        }
    }
}
