<?php
class Models_Uom extends TinyPHP_ActiveRecord
{
    public $tableName = "uoms";

    public $name = "";
    public $code = null;
    public $status = "active";
    public $created_at = null;
    public $updated_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init()
    {
        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this,'doBeforeUpdate'));
    }

    protected function doBeforeCreate() {

        $date = date("Y-m-d H:i:s");        
        $this->created_at = $date;
        $this->updated_at = $date;

        return $this->validate();
    }

    protected function doBeforeUpdate() {

        $date = date("Y-m-d H:i:s");        
        $this->updated_at = $date;

        return $this->validate();
    }

    public function validate() {
        return !$this->hasErrors();
    }
}
?>