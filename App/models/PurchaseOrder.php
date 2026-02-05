<?php
class Models_PurchaseOrder extends TinyPHP_ActiveRecord
{
    public $tableName = "purchase_orders";

    public $company_id = 0;
    public $location_id = 0;
    public $vendor_id = 0;
    public $po_number = "";
    public $receiving_type = "inventory";
    public $receiving_location_id = NULL;
    public $delivery_address_text = NULL;
    public $delivery_address_snapshot = NULL;
    public $reference = null;
    public $order_date = null;
    public $confirmation_date = null;
    public $expected_delivery_date = null;
    public $payment_terms = null;
    public $shipment_preference = null;
    public $status = "draft";
    public $notes = null;
    public $created_by = 0;
    public $created_at = null;
    public $updated_at = null;
    
    // private properties
    private $_line_items = null;
    private $_vendor = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {

        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this,'doBeforeUpdate'));

        $this->addLazyLoadProperty('line_items');
        $this->addLazyLoadProperty('vendor');
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
        if( $property === 'vendor' ) {
            
            if( is_null($this->_vendor) ) {                
                $this->_vendor = new Models_Vendor($this->vendor_id);
            }
            return $this->_vendor;
        }
    }

    protected function doBeforeCreate() {        

        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;
        $date = date("Y-m-d H:i:s");

        $this->company_id = $companyId;
        $this->created_by = $userId;
        $this->created_at = $date;
        $this->updated_at = $date;
        
        return !$this->hasErrors();
    }

    protected function doBeforeUpdate() {

        $date = date("Y-m-d H:i:s");        
        $this->updated_at = $date;

        return !$this->hasErrors();
    }


    private function getLineItems() {

        $lineItems = [];
        if( $this->id ) {

            $sql = "SELECT a.*, b.name AS product_name FROM purchase_order_items AS a
                    LEFT JOIN products AS b ON b.id = a.product_id
                    WHERE
                    a.purchase_order_id=?";
            $lineItems = $this->query($sql, [$this->id]);
        }

        return $lineItems;
    }


    /**
     * Get receivable items for this purchase order.
     *
     * Rules:
     * - ordered_qty comes from PO line
     * - received_qty = POSTED GRNs only (stored on PO line)
     * - in_transit_qty = GRN items where GRN.status = in_transit OR draft`
     * - remaining_qty = ordered - received - in_transit
     *
     * This method is READ-ONLY and SAFE to call from UI & services.
     */
    public function getReceivableItems(): array
    {
        if (empty($this->id)) {
            return [];
        }

        $sql = "
            SELECT
                poi.*,
                p.name AS product_name,
                COALESCE(
                    SUM(
                        CASE
                            WHEN grn.status IN('draft', 'in_transit')
                            THEN gi.received_qty
                            ELSE 0
                        END
                    ),
                    0
                ) AS in_transit_qty
            FROM purchase_order_items poi
            INNER JOIN products p ON p.id = poi.product_id
            LEFT JOIN purchase_order_grn_items gi ON gi.purchase_order_item_id = poi.id
            LEFT JOIN purchase_order_grns grn ON grn.id = gi.purchase_order_grn_id AND grn.status IN('draft', 'in_transit')
            WHERE poi.purchase_order_id = ?
            GROUP BY poi.id";
        $results = $this->query($sql, [$this->id]);

        $items = [];

        foreach ($results as $row) {

            $orderedQty   = (float) $row->ordered_qty;
            $receivedQty  = (float) $row->received_qty;
            $inTransitQty = (float) $row->in_transit_qty;

            $remainingQty = $orderedQty - ($receivedQty + $inTransitQty);

            // Hard guard — never allow negative values
            if ($remainingQty < 0) {
                $remainingQty = 0;
            }

            // Skip fully received lines (optional but recommended)
            if ($remainingQty <= 0) {
                continue;
            }

            $items[] = [
                'po_item_id' => (int) $row->id,
                'product_id' => (int) $row->product_id,
                'product_name' => $row->product_name,
                'description' => $row->description,
                'ordered_qty' => $orderedQty,
                'received_qty' => $receivedQty,
                'in_transit_qty' => $inTransitQty,
                'remaining_qty' => $remainingQty,
            ];
        }

        return $items;
    }


}

?>