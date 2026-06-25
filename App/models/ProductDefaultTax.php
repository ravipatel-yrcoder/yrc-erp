<?php
class Models_ProductDefaultTax extends TinyPHP_ActiveRecord
{
    public $tableName = "product_default_taxes";

    public $company_id = 0;
    public $product_id = 0;
    public $tax_id = 0;
    public $apply_on = null;
    public $created_by = null;
    public $created_at = null;
    public $updated_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init(){
        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this,'doBeforeUpdate'));
    }

    protected function doBeforeCreate() {
        $date = date("Y-m-d H:i:s");
        $this->created_at = $date;
        $this->updated_at = $date;
        $this->validate();
        return !$this->hasErrors();
    }

    protected function doBeforeUpdate() {
        $date = date("Y-m-d H:i:s");
        $this->updated_at = $date;
        $this->validate();
        return !$this->hasErrors();
    }

    private function isValidTax($taxId) {
        $companyId = auth()->getCompanyId();
        $sql = "SELECT COUNT(id) FROM taxes WHERE id=? AND company_id=? AND status=?";
        $count = self::getVar($sql, [$taxId, $companyId, "active"]);
        return $count == 1;
    }

    protected function validate() {
        if (empty($this->tax_id)) {
            $this->addError(validationErrMsg("required", "Tax id"), "tax_id");
        }
        if (empty($this->apply_on)) {
            $this->addError(validationErrMsg("required", "Apply on"), "apply_on");
        }
        if ($this->tax_id && !$this->isValidTax($this->tax_id)) {
            $this->addError(validationErrMsg("invalid", "Tax id"), "tax_id");
        }
        return !$this->hasErrors();
    }
}
?>
