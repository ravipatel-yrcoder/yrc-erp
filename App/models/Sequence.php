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

        $companyId = auth()->getCompanyId();
        $date = date("Y-m-d H:i:s");

        $this->company_id = $companyId;
        $this->created_at = $date;
        $this->updated_at = $date;
        
        return $this->validate();
    }



    private function isUnique($id, $companyId, $sequenceKey) {

        $sequenceKey = strtolower(trim($sequenceKey));
        
        $bind = [$sequenceKey, $companyId, "1"];
        $sql = "SELECT COUNT(id) FROM sequences
                WHERE lower(sequence_key)=? AND company_id=? AND is_active=?";
        if( $id ) {
            $sql .=" AND id!=?";
            $bind[] = $id;
        }
        
        $count = self::getVar($sql, $bind);

        return !$count == 1;
    }


    private function validate() {
        
        $companyId = auth()->getCompanyId();

        if(empty($this->sequence_key)) {
            $this->addError(validationErrMsg("required", "Sequence key "), "sequence_key");
        } else {

            if( !$this->isUnique($this->id, $companyId, $this->sequence_key) ) {
                $this->addError(validationErrMsg("duplicate", "Sequence key "), "sequence_key");
            }
        }

        return !$this->hasErrors();
    }

}
?>