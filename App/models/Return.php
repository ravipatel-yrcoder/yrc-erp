<?php
class Models_Return extends TinyPHP_ActiveRecord
{
    public $tableName = "returns";

    public $company_id = 0;
    public $received_location_id = 0;
    public $return_number = "";
    public $return_type = "customer";
    public $reference_type = "";
    public $reference_id = 0;
    public $party_type = null;
    public $party_id = null;
    public $return_date = null;
    public $status = "draft";
    public $notes = null;
    public $created_by = 0;
    public $received_by = null;
    public $received_at = null;
    public $cancelled_by = null;
    public $cancelled_at = null;
    public $created_at = null;
    public $updated_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', array($this, 'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this, 'doBeforeUpdate'));
    }

    protected function doBeforeCreate() {
        $date = date("Y-m-d H:i:s");
        $this->created_at = $date;
        $this->updated_at = $date;
        return !$this->hasErrors();
    }

    protected function doBeforeUpdate() {
        $this->updated_at = date("Y-m-d H:i:s");
        return !$this->hasErrors();
    }
}
?>
