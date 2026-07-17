<?php
class Models_PurchaseOrderGrn extends TinyPHP_ActiveRecord
{
    public $tableName = "purchase_order_grns";

    public $company_id = 0;
    public $purchase_order_id = 0;
    public $grn_number = "";
    public $status = "draft";

    public $received_date = null;
    public $received_by = null;
    public $in_transit_date = null;

    public $warehouse_id = 0;

    public $vendor_document_number = null;
    public $vendor_document_date = null;

    public $notes = null;

    public $created_by = 0;
    public $created_at = null;
    public $updated_at = null;

    // private properties
    private $_line_items = null;
    private $_purchase_order = null;   

    protected $dbIgnoreFields = ["id"];

    public function init()
    {
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
        $this->addListener('beforeUpdate', [$this, 'doBeforeUpdate']);

        $this->addLazyLoadProperty('line_items');
        $this->addLazyLoadProperty('purchase_order');
    }

    protected function lazyLoadProperty($property)
    {
        if( $property === 'line_items' )
        {
            if( is_null($this->_line_items) ) {
                $this->_line_items = $this->getLineItems();
            }
            return $this->_line_items;
        }
        else if( $property === 'purchase_order' )
        {
            if( is_null($this->_purchase_order) ) {
                $this->_purchase_order = new Models_PurchaseOrder($this->purchase_order_id);
            }
            return $this->_purchase_order;
        }
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

    private function getLineItems() {

        $lineItems = [];
        if( $this->id ) {

            $sql = "SELECT a.*, COALESCE(c.product_name, b.name) AS product_name, c.uom_code AS uom_code
                    FROM purchase_order_grn_items AS a
                    LEFT JOIN purchase_order_items AS c ON c.id = a.purchase_order_item_id
                    LEFT JOIN products AS b ON b.id = a.product_id
                    WHERE a.purchase_order_grn_id=?";
            $lineItems = $this->query($sql, [$this->id]);
        }

        return $lineItems;
    }
}