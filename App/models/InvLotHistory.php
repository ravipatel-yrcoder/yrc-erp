<?php
class Models_InvLotHistory extends TinyPHP_ActiveRecord
{
    public $tableName = "inv_lot_history";

    public $company_id = 0;
    public $lot_id = 0;
    public $product_id = 0;
    public $log_type = "";
    public $title = "";
    public $reference_type = null;
    public $reference_id = null;
    public $meta = null;
    public $created_by = null;
    public $created_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init()
    {
        $this->addListener('beforeCreate', array($this, 'doBeforeCreate'));
    }

    protected function doBeforeCreate()
    {
        $this->created_at = date("Y-m-d H:i:s");
        return $this->validate();
    }

    public function validate()
    {
        return !$this->hasErrors();
    }
}
?>
