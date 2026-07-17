<?php
class Models_CompanyLocation extends TinyPHP_ActiveRecord
{
    public $tableName = "company_locations";

    public $company_id    = 0;
    public $name          = "";
    public $code          = null;
    public $address_line1 = null;
    public $address_line2 = null;
    public $city          = null;
    public $state         = null;
    public $country       = null;
    public $zip           = null;
    public $gstin         = null;
    public $phone         = null;
    public $email         = null;
    public $is_default    = 0;
    public $status        = "active";
    public $created_at    = null;
    public $updated_at    = null;

    protected $dbIgnoreFields = ["id"];

    public function init()
    {
        $this->addListener('beforeCreate', array($this, 'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this, 'doBeforeUpdate'));
    }

    protected function doBeforeCreate()
    {
        $date = date("Y-m-d H:i:s");
        $this->created_at = $date;
        $this->updated_at = $date;

        return $this->validate();
    }

    protected function doBeforeUpdate()
    {
        $this->updated_at = date("Y-m-d H:i:s");

        return $this->validate();
    }

    public function validate()
    {
        if (empty($this->name)) {
            $this->addError(validationErrMsg("required", "Name"), "name");
        }

        if (!in_array($this->status, ['active', 'inactive'])) {
            $this->addError(validationErrMsg("missing_or_invalid", "Status"), "status");
        }

        return !$this->hasErrors();
    }
}
?>
