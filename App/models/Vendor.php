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
    }


    protected function doBeforeCreate() {        

        $date = date("Y-m-d H:i:s");

        $this->created_at = $date;
        $this->updated_at = $date;
        
        return !$this->hasErrors();
    }


    protected function doBeforeUpdate() {

        $date = date("Y-m-d H:i:s");        
        $this->updated_at = $date;

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