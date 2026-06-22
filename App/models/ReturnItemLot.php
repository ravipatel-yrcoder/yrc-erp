<?php
class Models_ReturnItemLot extends TinyPHP_ActiveRecord
{
    public $tableName = "return_item_lots";

    public $company_id = 0;
    public $return_id = 0;
    public $return_item_id = 0;
    public $lot_id = 0;
    public $quantity = 0;
    public $created_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', array($this, 'doBeforeCreate'));
    }

    protected function doBeforeCreate() {
        $this->created_at = date("Y-m-d H:i:s");
        return !$this->hasErrors();
    }
}
?>
