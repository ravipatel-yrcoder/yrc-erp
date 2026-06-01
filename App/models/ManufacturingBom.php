<?php
class Models_ManufacturingBom extends TinyPHP_ActiveRecord
{
    public $tableName = "manufacturing_boms";

    public $company_id = 0;
    public $product_id = 0;
    public $name = "";
    public $output_qty = 1;
    public $is_default = 0;
    public $notes = null;
    public $status = "active";
    public $created_by = 0;
    public $created_at = null;
    public $updated_at = null;

    private $_items = null;
    private $_product = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
        $this->addListener('beforeUpdate', [$this, 'doBeforeUpdate']);
        $this->addLazyLoadProperty('items');
        $this->addLazyLoadProperty('product');
    }

    protected function lazyLoadProperty($property) {
        if ($property === 'items') {
            if (is_null($this->_items)) {
                $this->_items = $this->getItems();
            }
            return $this->_items;
        }
        if ($property === 'product') {
            if (is_null($this->_product)) {
                $this->_product = new Models_Product($this->product_id);
            }
            return $this->_product;
        }
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

    private function getItems(): array {
        if (!$this->id) return [];
        $sql = "SELECT bi.*, p.name AS product_name
                FROM manufacturing_bom_items AS bi
                LEFT JOIN products AS p ON p.id = bi.product_id
                WHERE bi.bom_id = ?
                ORDER BY bi.sort_order ASC, bi.id ASC";
        return $this->query($sql, [$this->id]);
    }
}
