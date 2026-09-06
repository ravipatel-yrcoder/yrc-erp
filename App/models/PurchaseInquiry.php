<?php
class Models_PurchaseInquiry extends TinyPHP_ActiveRecord
{
    public $tableName = "purchase_inquiries";

    public $company_id        = 0;
    public $company_location_id = 0;
    public $inquiry_number    = "";
    public $title             = null;
    public $required_by_date      = null;
    public $status                = "draft";
    public $notes             = null;
    public $internal_notes        = null;
    public $declaration_snapshot  = null;
    public $awarded_at            = null;
    public $created_by            = 0;
    public $created_at        = null;
    public $updated_at        = null;

    protected $dbIgnoreFields = ["id"];

    public function init()
    {
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
        $this->addListener('beforeUpdate', [$this, 'doBeforeUpdate']);
    }

    protected function doBeforeCreate()
    {
        $date = date("Y-m-d H:i:s");
        $this->created_at = $date;
        $this->updated_at = $date;
        return !$this->hasErrors();
    }

    protected function doBeforeUpdate()
    {
        $this->updated_at = date("Y-m-d H:i:s");
        return !$this->hasErrors();
    }
}
?>
