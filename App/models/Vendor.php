<?php
class Models_Vendor extends TinyPHP_ActiveRecord
{
    public $tableName = "vendors";

    public $company_id = 0;
    public $vendor_code = null;
    public $vendor_type = "company";
    public $legal_name = null;
    public $display_name = null;
    public $email = null;
    public $phone = null;
    public $website = null;
    public $pan = null;
    public $gstin = null;
    public $currency_code = 'INR';
    public $payment_term_id = null;
    public $notes = null;
    public $status = "active";    
    public $created_by = null;
    public $created_at = null;
    public $updated_at = null;

    protected $billing_address = [];
    protected $shipping_address = [];

    
    protected $dbIgnoreFields = ["id", "billing_address", "shipping_address"];

    public function init(){

        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this,'doBeforeUpdate'));

        $this->addListener('afterCreate', array($this,'doAfterCreate'));
        $this->addListener('afterUpdate', array($this,'doAfterUpdate'));
    }


    protected function doBeforeCreate() {        

        // DB Transaction
        $this->startTransaction();

        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;
        $date = date("Y-m-d H:i:s");

        $this->company_id = $companyId;
        $this->created_by = $userId;
        $this->created_at = $date;
        $this->updated_at = $date;
        
        // run validation
        $this->validate();

        if( $this->hasErrors() ) {
            $this->rollback();
        }

        return !$this->hasErrors();
    }


    protected function doBeforeUpdate() {

        // DB Transaction
        $this->startTransaction();

        $date = date("Y-m-d H:i:s");        
        $this->updated_at = $date;

        // run validation
        $this->validate();

        if( $this->hasErrors() ) {
            $this->rollback();
        }

        return !$this->hasErrors();
    }

        
    protected function doAfterCreate() {

        global $db;

        $vendorId = $this->id;
        if( $vendorId )
        {
            $vendorCodeUpdated = false;

            try {
                
                // generate vendor code and update            
                $vendorCode = Service_Sequence::nextCommit($this->company_id, "vendors"); 
                if( $vendorCode ) {
                    $db->update($this->tableName, ["vendor_code" => $vendorCode], "id=$vendorId");
                }

                $vendorCodeUpdated = true;

            } catch(Exception $e) {
                $this->addError("Unable to generate vendor code", "company_name");
            }


            // Sync vendor addresses
            if( $vendorCodeUpdated === true ) {
                $this->syncAddresses();   
            }
        }        

        $this->hasErrors() ? $this->rollback() : $this->commit();
    }
    
    
    protected function doAfterUpdate() {
        
        $vendorId = $this->id;
        if( $vendorId )
        {
            // Sync vendor addresses
            $this->syncAddresses();
        }


        $this->hasErrors() ? $this->rollback() : $this->commit();
    }


    private function syncAddresses() {

        if( $this->id ) {

            $saveAction = strtoupper($this->_getCurrentAction());

            if( $saveAction === "CREATE" || $saveAction === "UPDATE" ) {
                
                $billingAddress = $this->billing_address;
                if( $billingAddress['attention'] || $billingAddress['country'] || $billingAddress['address_line1'] || $billingAddress['address_line2'] || $billingAddress['city'] || $billingAddress['state'] || $billingAddress['postal_code'] || $billingAddress['phone'] ) {
                    
                    $vendorBillingAddress = new Models_VendorAddress();
                    $vendorBillingAddress->fetchByProperty(["vendor_id", "address_type"], [$this->id, "billing"]);

                    $vendorBillingAddress->vendor_id = $this->id;
                    $vendorBillingAddress->address_type = "billing";
                    $vendorBillingAddress->attention = $billingAddress['attention'];
                    $vendorBillingAddress->phone = $billingAddress['phone'];
                    $vendorBillingAddress->address_line1 = $billingAddress['address_line1'];
                    $vendorBillingAddress->address_line2 = $billingAddress['address_line2'];
                    $vendorBillingAddress->city = $billingAddress['city'];
                    $vendorBillingAddress->state = $billingAddress['state'];
                    $vendorBillingAddress->postal_code = $billingAddress['postal_code'];
                    $vendorBillingAddress->country = $billingAddress['country'];                    

                    if( $vendorBillingAddress->isEmpty ) {
                        $vendorBillingAddress->is_default = 1;
                        if( !$vendorBillingAddress->create() ) {
                            $this->addError("Unable to save billing address");
                        }

                    } else {

                        if( !$vendorBillingAddress->update() ) {
                            $this->addError("Unable to save billing address");
                        }
                    }                    
                }


                $shippingAddress = $this->shipping_address;
                if( $shippingAddress['attention'] || $shippingAddress['country'] || $shippingAddress['address_line1'] || $shippingAddress['address_line2'] || $shippingAddress['city'] || $shippingAddress['state'] || $shippingAddress['postal_code'] || $shippingAddress['phone'] ) {
                    
                    $vendorShippingAddress = new Models_VendorAddress();
                    $vendorShippingAddress->fetchByProperty(["vendor_id", "address_type"], [$this->id, "shipping"]);

                    $vendorShippingAddress->vendor_id = $this->id;
                    $vendorShippingAddress->address_type = "shipping";
                    $vendorShippingAddress->attention = $shippingAddress['attention'];
                    $vendorShippingAddress->phone = $shippingAddress['phone'];
                    $vendorShippingAddress->address_line1 = $shippingAddress['address_line1'];
                    $vendorShippingAddress->address_line2 = $shippingAddress['address_line2'];
                    $vendorShippingAddress->city = $shippingAddress['city'];
                    $vendorShippingAddress->state = $shippingAddress['state'];
                    $vendorShippingAddress->postal_code = $shippingAddress['postal_code'];
                    $vendorShippingAddress->country = $shippingAddress['country'];                    

                    if( $vendorShippingAddress->isEmpty ) {
                        $vendorShippingAddress->is_default = 1;
                        if( !$vendorShippingAddress->create() ) {
                            $this->addError("Unable to save shipping address");
                        }

                    } else {

                        if( !$vendorShippingAddress->update() ) {
                            $this->addError("Unable to save shipping address");
                        }
                    }                    
                }
            }            
        }

    }


    public function validate() {

        $companyId = auth()->getCompanyId();

        if(empty($this->legal_name)) {
            $this->addError(validationErrMsg("required", "Company name"), "company_name");
        }

        if(empty($this->email)) {
            $this->addError(validationErrMsg("required", "Email"), "email");
        } else if( !isValidEmail($this->email) ) {
            $this->addError(validationErrMsg("invalid", "Email"), "email");
        }

        if(empty($this->phone)) {
            $this->addError(validationErrMsg("invalid", "Phone"), "phone");
        }

        if(empty($this->phone)) {
            $this->addError(validationErrMsg("invalid", "Phone"), "phone");
        }

        if( $this->payment_term_id ) {
            $paymentTerm = new Models_PaymentTerm($this->payment_term_id);
            if( !(!$paymentTerm->isEmpty && $paymentTerm->company_id == $companyId) ) {
                $this->addError(validationErrMsg("invalid", "Payment terms"), "payment_term_id");
            }
        }

        return !$this->hasErrors();
    }


    public function getBillingAddress() {

        $address = [];
        if( $this->id )
        {
            $vendorAddress = new Models_VendorAddress();
            $vendorAddress->fetchByProperty(["vendor_id", "address_type"], [$this->id, "billing"]);
            if( !$vendorAddress->isEmpty ) {
                $address = array_merge(['id' => $vendorAddress->id], $vendorAddress->toArray());
            }
        }

        return $address;
    }


    public function getShippingAddress() {
        
        $address = [];
        if( $this->id )
        {
            $vendorAddress = new Models_VendorAddress();
            $vendorAddress->fetchByProperty(["vendor_id", "address_type"], [$this->id, "shipping"]);
            if( !$vendorAddress->isEmpty ) {
                $address = array_merge(['id' => $vendorAddress->id], $vendorAddress->toArray());
            }
        }

        return $address;

    }
}
?>