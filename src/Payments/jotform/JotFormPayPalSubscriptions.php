<?php 


/**
 * This class holds subscription methods for
 * PayPal
 */
class JotFormPayPalSubscriptions extends JotFormSubscriptions {
    
    public function __construct ($_data){
        $this->data = $_data;
    }
    
    /**
     * Sets object details from data which equals to post data.
     */
    public function setProperties(){
        
        # set the gateway
        $this->gateway = "PAYPAL";
        
        # Control if custom data is available.
        if ( !isset($this->data['custom']) ) {
            throw new Exception( "Custom is not set.");
        }

        # Split the username and period
        $temp = preg_split("/-/", $this->data['custom']);

        $_username = implode("-", array_splice($temp, 0, count($temp)-1 ) );
        $_period = implode("-", $temp);
        
        # Control and set subscr_id
        if (!isset($this->data['subscr_id'])){
            throw new Exception( "Subscription id is missing." );
        }
        $this->subscriptionID = $this->data['subscr_id'];
        
        # Set the period of the subscription
        $this->setSubscriptionPeriod ($_period);
        
        # Set the subscription type from item_name
        $itemName = isset($this->data['item_name']) ? $this->data['item_name'] : "" ;
        $this->setSubscriptionType ($itemName);
        
        # Set payment status.
        if ( isset( $this->data['payment_status']) ){
            $this->paymentStatus = $this->data['payment_status'];
        }
        
        $this->setTotalAmount();
        $this->setActionDate();
        
        # Set the currency, default one is USD
        $this->currency = isset($this->data["mc_currency"]) ? $this->data["mc_currency"] : "USD";
        
        # Set user.
        $this->setUser($_username);
        
        # set the subscription
        $this->setSubscriptionAction ();
    }

    /**
     * Sets the subscription type $this->type.
     * $this->type is an instance of AccountType object.
     * If premium passes in $_itemName, premium object is setled.
     * If professional passes in $_itemName, professional is setled.
     * 
     * @param String $_itemName
     */
    private function setSubscriptionType ($_itemName) {
        if ( stristr ($_itemName, "premium") ){
            $this->type = AccountType::find("PREMIUM");
        }else if ( stristr($_itemName, "professional") ){
            $this->type = AccountType::find("PROFESSIONAL");
        }else {
            throw new Exception ("Cannot set subscription type");
        }
    }
        
    /**
     * Sets action date according to the action of the operation.
     */
    private function setActionDate(){
        # Used for payment exact date of payment  (subscr_payment)
        if ( isset($this->data['payment_date']) ){
            $this->operationDate = date( "Y-m-d H:i:s", strtotime($this->data['payment_date']) );
        }
        
        # Used for the start date or cancellation date (subscr_signup or subscr_cancel)
        if ( isset($this->data['subscr_date']) ){
            $this->operationDate = date( "Y-m-d H:i:s", strtotime($this->data['subscr_date']) );
        }

        # Used for retry date if the payment is failed.
        if ( isset($this->data['retry_at']) ){
            $this->operationDate = date( "Y-m-d H:i:s", strtotime($this->data['retry_at']) );
        }
        
        # Used for the date when the subscription modification will be effective (subscr_modify)
        if ( isset($this->data['subscr_effective']) ){
            $this->operationDate = date( "Y-m-d H:i:s", strtotime($this->data['subscr_effective']) );
        }
    }
    
    /**
     * Set the period of the subscription
     */
    private function setSubscriptionPeriod ($_period) {
        switch ( trim($_period) ){
            case "monthly":
                $this->period = JotFormSubscriptionPeriods::Monthly;
                break;
            case "yearly":
                $this->period = JotFormSubscriptionPeriods::Yearly;
                break;
            case "biyearly":
                $this->period = JotFormSubscriptionPeriods::BiYearly;
                break;
            default:
                throw new Exception("Cannot find subscription period: ".$_period);
                break;
        }
        
    }
    
    /**
     * Get the total amount of the payment.
     */
    private function setTotalAmount (){
        if ( isset($this->data['mc_gross'] ) ){

            $this->total = $this->data['mc_gross'];
            
        }else {

            $this->total = 0;
        
        }
    }
    
    /**
     * Set subscription action.
     */
    private function setSubscriptionAction () {
        # if txn_type is setled use it. If not than it could be a refund.
        $action = isset($this->data['txn_type']) ? $this->data['txn_type'] : false;

        # if reason code is refund, than we understand that it's a refund.
        if ($action === false && isset($this->data['reason_code']) ){
            $action = ( trim($this->data['reason_code']) === "refund" ) ? $this->data['reason_code'] : false;
        }
        
        switch ( trim($action) ){
            case "subscr_failed":
                # retry_at is the date that will retry.
                $this->action = JotFormSubscriptionActions::Failed;
                break;
            case "subscr_cancel":
                # subscr_date is the date only effects here.
                $this->action = JotFormSubscriptionActions::Cancel;
                break;
            case "subscr_payment":
                # payment_date is the date the payment is done.
                $this->action = JotFormSubscriptionActions::Payment;
                break;
            case "subscr_signup":
                # subscr_date is the date only effects here.
                $this->action = JotFormSubscriptionActions::SignUp;
                break;
            case "subscr_eot":
                $this->action = JotFormSubscriptionActions::EOT;
                break;
            case "subscr_modify":
                # subscr_effective is the date only effects here.
                $this->action = JotFormSubscriptionActions::Modify;
                break;
            case "refund":
                $this->action = JotFormSubscriptionActions::Refund;
                break;
            default:
                throw new Exception("Cannot find subscription action.");
                break;
                
        }
    }
}






