<?php
class Models_SalesDelivery extends TinyPHP_ActiveRecord
{
    public $tableName = "sales_deliveries";

    public $company_id = 0;
    public $dn_number = "";
    public $sales_order_id = 0;
    public $customer_id = 0;
    public $location_id = 0;
    public $back_order_of = null;
    public $fulfilment_type = null;
    public $status = "draft";
    public $dispatch_date = null;
    public $delivery_date = null;
    public $carrier = null;
    public $tracking_number = null;
    public $shipping_address_snapshot = null;
    public $notes = null;
    public $created_by = null;
    public $created_at = null;
    public $updated_at = null;

    private $_items = null;
    private $_customer = null;
    private $_sales_order = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', array($this, 'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this, 'doBeforeUpdate'));
        $this->addLazyLoadProperty('items');
        $this->addLazyLoadProperty('customer');
        $this->addLazyLoadProperty('sales_order');
    }

    protected function lazyLoadProperty($property) {
        if ($property === 'items') {
            if (is_null($this->_items)) {
                $this->_items = $this->getItems();
            }
            return $this->_items;
        }
        if ($property === 'customer') {
            if (is_null($this->_customer)) {
                $this->_customer = new Models_Customer($this->customer_id);
            }
            return $this->_customer;
        }
        if ($property === 'sales_order') {
            if (is_null($this->_sales_order)) {
                $this->_sales_order = new Models_SalesOrder($this->sales_order_id);
            }
            return $this->_sales_order;
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

    private function getItems() {
        $items = [];
        if ($this->id) {
            $sql = "SELECT a.*, b.name AS product_name
                    FROM sales_delivery_items AS a
                    LEFT JOIN products AS b ON b.id = a.product_id
                    WHERE a.sales_delivery_id = ?
                    ORDER BY a.id ASC";
            $items = $this->query($sql, [$this->id]);
        }
        return $items;
    }
}
?>
