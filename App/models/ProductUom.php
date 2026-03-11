<?php
class Models_ProductUom extends TinyPHP_ActiveRecord
{
    public $tableName = "product_uoms";

    public $company_id = 0;
    public $product_id = 0;
    public $name = null;
    public $base_uom_id = null;
    public $conversion_factor = 1;
    public $is_base = 0;
    public $status = 'active';
    public $created_by = null;
    public $created_at = null;
    public $updated_at = null;
    
    private $_base_uom = null;
    
    protected $dbIgnoreFields = ["id"];

    public function init(){
        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this,'doBeforeUpdate'));

        $this->addLazyLoadProperty('base_uom');
    }

    protected function lazyLoadProperty($property)
    {
        if( $property === 'base_uom' )
        {
            if( is_null($this->_base_uom) ) {
                $this->_base_uom = new Models_Uom($this->base_uom_id);
            }
            return $this->_base_uom;
        }
    }

    protected function doBeforeCreate() {

        $date = date("Y-m-d H:i:s");

        $this->created_at = $date;
        $this->updated_at = $date;

        return !$this->hasErrors();
    }

    protected function doBeforeUpdate() {

        $date = date("Y-m-d H:i:s");
        $this->updated_at = $date;
        
        return !$this->hasErrors();
    }

}
?>