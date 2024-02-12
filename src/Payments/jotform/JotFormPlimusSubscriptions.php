<?php 

/**
 * This class holds subscription methods for
 * PayPal operations
 *
 */
class JotFormPlimusSubscriptions extends JotFormSubscriptions {
    
    public function __construct ($_data){
        $this->data = $_data;
        
    }
    
    public function setProperties(){
		
        $this->gateway = "PLIMUS";
        
        if ( isset($this->data['JotForm_Login_Name']) ){
            $_username = $this->data['JotForm_Login_Name'];
        }else if( isset($this->data['username']) ){
            $_username = $this->data['username'];
        }else{
            throw new Exception("Cannot get username.");
        }
        
        $_period    = $this->data['contractName'];
        
        $this->setSubscriptionPeriod ($_period);
        
        $itemName = isset( $this->data['contractName'] ) ? $this->data['contractName'] : "";
        $this->setSubscriptionType ($itemName);
        
        $this->setTotalAmount();
        
        $this->setActionDate();
        
        if (!isset($this->data['accountId'])){
            throw new Exception("Cannot get the account ID");
        }
        $this->subscriptionID = $this->data['accountId'];
        
        # Set the currency, default one is USD
        $this->currency = isset($this->data["invoiceChargeCurrency"]) ? $this->data["invoiceChargeCurrency"] : "USD";
        
        $this->setUser($_username);
        
        # set the subscription
        $this->setSubscriptionAction ();
        
    }
    
    private function setSubscriptionType ($_itemName) {
        if ( stristr ($_itemName, "premium") ){
            $this->type = AccountType::find("PREMIUM");
        }else if ( stristr($_itemName, "professional") ){
            $this->type = AccountType::find("PROFESSIONAL");
        }else {
            $this->type = AccountType::find("PREMIUM");
        }
    }
    
    protected function setActionDate(){
        # Used for payment exact date of payment  (subscr_payment)
        if ( isset($this->data['transactionDate']) ){
            $this->operationDate = date( "Y-m-d H:i:s", strtotime($this->data['transactionDate']) );
        }
    }
        
    /**
     * Set the period of the subscription
     */
    private function setSubscriptionPeriod ($_period) {
        $_period = trim($_period);

        if ( stristr ($_period, "monthly") ){
            $this->period = JotFormSubscriptionPeriods::Monthly;
        }else if ( stristr($_period, "bi-yearly") || stristr($_period, "biyearly") ){
            $this->period = JotFormSubscriptionPeriods::BiYearly;
        }else if ( stristr($_period, "yearly") ){
            $this->period = JotFormSubscriptionPeriods::Yearly;
        }else {
            throw new Exception ("Cannot find subscription period");
        }
    }
    
    private function setTotalAmount (){

        if ( isset($this->data['contractPrice'] ) ){
            $this->total = $this->data['contractPrice'];
        }else {
            $this->total = 0;
        }
    }
    
    /**
     * Set subscription action.
     */
    private function setSubscriptionAction () {

        $action = isset($this->data['transactionType']) ? $this->data['transactionType'] : "";
        
        switch ( trim($action) ){
            case "DECLINE":
                # retry_at is the date that will retry.
                $this->action = JotFormSubscriptionActions::Failed;
                break;
            case "CANCEL":
            case "CANCELLATION":
                # subscr_date is the date only effects here.
                $this->action = JotFormSubscriptionActions::Cancel;
                break;
            case "CHARGE":
            case "RECURRING":
                
                # payment_date is the date the payment is done.
                $this->action = JotFormSubscriptionActions::Payment;
                # Add the payment status here.
                $this->paymentStatus = "Completed";
                
                break;
                /*
                # subscr_date is the date only effects here.
                $this->action = JotFormSubscriptionActions::SignUp;
                break;
                $this->action = JotFormSubscriptionActions::EOT;
                break;
                */
            case "CONTRACT_CHANGE":
                # subscr_effective is the date only effects here.
                $this->action = JotFormSubscriptionActions::Modify;
                break;
            case "AUTH_ONLY":
            case "REFUND":
            case "CHARGEBACK":
            case "CANCELLATION_REFUND":
            case "CANCELLATION_CHARGEBACK":
                throw new Exception ("Cannot find type in action list A: " . trim($action));
                break;
            default:
                throw new Exception ("Cannot find type in action list B: " . trim($action));
                break;
                
        }
    }
}






