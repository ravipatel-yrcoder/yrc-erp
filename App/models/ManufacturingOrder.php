<?php
class Models_ManufacturingOrder extends TinyPHP_ActiveRecord
{
    public $tableName = "manufacturing_orders";

    public $company_id = 0;
    public $mo_number = "";
    public $product_id = 0;
    public $product_name = null;
    public $product_sku = null;
    public $bom_id = 0;
    public $bom_name = "";
    public $source_location_id = null;
    public $destination_location_id = null;
    public $planned_qty = 0;
    public $produced_qty = 0;
    public $planned_date = null;
    public $status = "draft";
    public $allocation_status = "not_allocated";
    public $notes = null;
    public $origin_type = "manual";
    public $origin_id = null;
    public $track_serial_genealogy = 0;
    public $created_by = 0;
    public $confirmed_by = null;
    public $confirmed_at = null;
    public $created_at = null;
    public $updated_at = null;

    private $_material_items = null;
    private $_product = null;
    private $_history = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
        $this->addListener('beforeUpdate', [$this, 'doBeforeUpdate']);
        $this->addLazyLoadProperty('material_items');
        $this->addLazyLoadProperty('product');
        $this->addLazyLoadProperty('history');
    }

    protected function lazyLoadProperty($property) {
        if ($property === 'material_items') {
            if (is_null($this->_material_items)) {
                $this->_material_items = $this->getMaterialItems();
            }
            return $this->_material_items;
        }
        if ($property === 'product') {
            if (is_null($this->_product)) {
                $this->_product = new Models_Product($this->product_id);
            }
            return $this->_product;
        }
        if ($property === 'history') {
            if (is_null($this->_history)) {
                $this->_history = $this->getHistory();
            }
            return $this->_history;
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

    private function getMaterialItems(): array {
        if (!$this->id) return [];
        $sql = "SELECT mi.*, COALESCE(mi.product_name, p.name) AS product_name, COALESCE(mi.product_sku, p.sku) AS product_sku, p.stock_tracking_method
                FROM manufacturing_order_material_items AS mi
                LEFT JOIN products AS p ON p.id = mi.product_id
                WHERE mi.manufacturing_order_id = ?
                ORDER BY mi.sort_order ASC, mi.id ASC";
        return $this->query($sql, [$this->id]);
    }

    private function getHistory(): array {
        if (!$this->id) return [];
        $sql = "SELECT
                    h.*,
                    u.name                                        AS performed_by,
                    DATE_FORMAT(h.created_at, '%d %b %Y, %H:%i') AS date_time
                FROM manufacturing_order_history AS h
                LEFT JOIN users AS u ON u.id = h.created_by
                WHERE h.manufacturing_order_id = ?
                ORDER BY h.created_at DESC";
        return $this->query($sql, [$this->id]);
    }
}
