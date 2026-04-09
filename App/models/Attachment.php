<?php
class Models_Attachment extends TinyPHP_ActiveRecord
{
    public $tableName = "attachments";

    public $company_id = 0;
    public $entity = "";
    public $entity_id = 0;
    public $file_name = "";
    public $original_name = "";
    public $file_size = 0;
    public $mime_type = "";
    public $created_by = null;
    public $created_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
    }

    protected function doBeforeCreate() {
        $this->created_at = date("Y-m-d H:i:s");
        return !$this->hasErrors();
    }
}
?>
