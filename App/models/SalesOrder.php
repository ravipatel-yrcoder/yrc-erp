<?php
class Models_SalesOrder extends TinyPHP_ActiveRecord
{
    public $tableName = "sales_orders";

    public $company_id = 0;
    public $so_number = "";
    public $customer_id = 0;
    public $reference = null;
    public $salesperson_id = null;
    public $price_list_id = null;
    public $location_id = 0;
    public $order_date = null;
    public $expected_delivery_date = null;
    public $payment_term_id = null;
    public $payment_terms = null;
    public $status = "draft";
    public $billing_address_snapshot = null;
    public $shipping_address_snapshot = null;
    public $subtotal = 0;
    public $discount_amount = 0;
    public $discount_info = null;
    public $tax_amount = 0;
    public $total_amount = 0;
    public $notes = null;
    public $internal_notes = null;
    public $created_by = null;
    public $created_at = null;
    public $updated_at = null;

    private $_line_items = null;
    private $_customer = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', array($this, 'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this, 'doBeforeUpdate'));
        $this->addLazyLoadProperty('line_items');
        $this->addLazyLoadProperty('customer');
    }

    protected function lazyLoadProperty($property) {
        if ($property === 'line_items') {
            if (is_null($this->_line_items)) {
                $this->_line_items = $this->getLineItems();
            }
            return $this->_line_items;
        }
        if ($property === 'customer') {
            if (is_null($this->_customer)) {
                $this->_customer = new Models_Customer($this->customer_id);
            }
            return $this->_customer;
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

    private function getLineItems() {
        $lineItems = [];
        if ($this->id) {
            $sql = "SELECT a.*, b.name AS product_name
                    FROM sales_order_items AS a
                    LEFT JOIN products AS b ON b.id = a.product_id
                    WHERE a.sales_order_id = ?
                    ORDER BY a.id ASC";
            $lineItems = $this->query($sql, [$this->id]);
            foreach ($lineItems as &$item) {
                $item->tax_info = $item->tax_info ? json_decode($item->tax_info)      : [];
                $item->discount_info = $item->discount_info ? json_decode($item->discount_info) : null;
            }
        }
        return $lineItems;
    }
}
?>
