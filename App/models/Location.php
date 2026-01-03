<?php
class Models_Location extends TinyPHP_ActiveRecord
{
    public $tableName = "company_locations";

    public $company_id = 0;
    public $name = "";
    public $code = null;
    public $type = "";
    public $address_line1 = null;
    public $address_line2 = null;
    public $city = null;
    public $state = null;
    public $country = null;
    public $zip = null;
    public $is_main = 0;
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

        $this->company_id = auth()->getCompanyId();

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

        $this->validateLocationInfo();

        return !$this->hasErrors();
    }

    private function isUniqueCode($code, $id=0) {
        
        $code = strtolower(trim($code));
        $companyId = auth()->getCompanyId();

        $bind = [$code, $companyId];
        $sql = "SELECT COUNT(id) FROM company_locations
                WHERE lower(code)=? AND company_id=?";
        if( $id ) {
            $sql .=" AND id!=?";
            $bind[] = $id;
        }
        
        $count = self::getVar($sql, $bind);

        return !$count == 1;
    }

    
    public function validateLocationInfo() {

        if(empty($this->name)) {
            $this->addError(validationErrMsg("required", "Name"), "name");
        }

        if(!in_array($this->type, array_keys(config("constants.company.location_types")))) {
            $this->addError(validationErrMsg("missing_or_invalid", "Location type"), "type");
        }

        if( !empty($this->code) && !$this->isUniqueCode($this->code, $this->id) ) {
            $this->addError(validationErrMsg("duplicate", "Code"), "code");
        }

        // Optionally, validate status
        if(!in_array($this->status, ['active','inactive','archived'])) {
            $this->addError(validationErrMsg("missing_or_invalid", "Status"), "status");
        }

        return !$this->hasErrors();
    }
    
}
?>