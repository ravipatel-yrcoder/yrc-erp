<?php
class Models_Sequence extends TinyPHP_ActiveRecord
{
    public $tableName = "sequences";

    public $company_id = 0;
    public $sequence_key = "";
    public $pattern = null;
    public $padding = 6;
    public $last_number = 0;
    public $reset_period = "none";
    public $is_active = 1;
    public $created_at = null;
    public $updated_at = null;
    
    protected $dbIgnoreFields = ["id"];

    public function init(){
        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
    }

    protected function doBeforeCreate() {

        $date = date("Y-m-d H:i:s");
        
        $this->created_at = $date;
        $this->updated_at = $date;
        
        return !$this->hasErrors();
    }

}
?>