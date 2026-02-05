<?php
class Models_Vendor extends TinyPHP_ActiveRecord
{
    public $tableName = "vendor_contacts";

    public $company_id = 0;
    public $vendor_id = 0;
    public $salutation = null;
    public $first_name = null;
    public $last_name = null;
    public $email = null;
    public $phone = null;
    public $role = null;
    public $is_primary = 0;
    public $status = "active";
    public $created_by = null;
    public $created_at = null;
    public $updated_at = null;
    
    protected $dbIgnoreFields = ["id"];

    public function init(){
    }

}
?>