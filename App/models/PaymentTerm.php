<?php
class Models_PaymentTerm extends TinyPHP_ActiveRecord
{
    public $tableName = "payment_terms";

    public $company_id = 0;
    public $name = "";
    public $days = 0;
    public $description = null;
    public $is_default = 0;
    public $status = 'active';
    public $created_by = null;
    public $created_at = null;
    public $updated_at = null;
    
    protected $dbIgnoreFields = ["id"];

    public function init(){
    }

}
?>