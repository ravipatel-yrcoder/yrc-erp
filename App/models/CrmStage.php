<?php
class Models_CrmStage extends TinyPHP_ActiveRecord
{
    public $tableName = "crm_stages";

    public $company_id = 0;
    public $name = "";
    public $probability = 0;
    public $sort_order = 0;
    public $is_won = 0;
    public $is_lost = 0;
    public $color = null;
    public $status = "active";
    public $created_by = null;
    public $created_at = null;
    public $updated_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
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
        $this->updated_at = date("Y-m-d H:i:s");
        return !$this->hasErrors();
    }
}
?>
