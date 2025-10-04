<?php
class Models_AuthToken extends TinyPHP_ActiveRecord
{
    public $tableName = "auth_tokens";
    
    public $user_id = null;
    public $token_type = null;
    public $token_hash = null;
    public $expires_at = null;
    public $revoked = 0;
    public $device_info = NULL;
    public $ip_address = NULL;
    public $created_at = NULL;
    public $last_used_at = NULL;
    
    public function init() {
        $this->addListener("beforeCreate", [$this, "doBeforeCreate"]);
        $this->addListener("beforeUpdate", [$this, "doBeforeUpdate"]);
    }

    protected function doBeforeCreate() {
        $this->created_at = date("Y-m-d H:i:s");
        return true;
    }

    protected function doBeforeUpdate() {
        $this->last_used_at = date("Y-m-d H:i:s");
        return true;
    }
}
?>