<?php
class Models_PurchaseOrder extends TinyPHP_ActiveRecord
{
    public $tableName = "purchase_orders";

    public $inquiry_id = null;
    public $company_id = 0;
    public $company_location_id = null;
    public $vendor_id = 0;
    public $currency_code = 'INR';
    public $po_number = "";
    public $receiving_type = "inventory";
    public $receiving_warehouse_id = NULL;
    public $delivery_address_text = NULL;
    public $delivery_address_snapshot = NULL;
    public $vendor_address_snapshot = NULL;
    public $reference = null;
    public $order_date = null;
    public $confirmation_date = null;
    public $expected_delivery_date = null;
    public $payment_terms = null;
    public $payment_term_id = null;
    public $shipment_preference = null;
    public $status = "draft";
    public $notes = null;
    public $internal_notes = null;
    public $subtotal = 0;
    public $item_discount_total = 0;
    public $subtotal_after_item_discount = 0;
    public $order_discount_amount = 0;
    public $discount_total = 0;
    public $discount_info = null;
    public $tax_amount = 0;
    public $round_off_amount = 0;
    public $grand_total = 0;
    public $adjustment_label = null;
    public $adjustment_amount = 0;
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
        else if( $property === 'vendor' ) {
            
            if( is_null($this->_vendor) ) {                
                $this->_vendor = new Models_Vendor($this->vendor_id);
            }
            return $this->_vendor;
        }
    }

    protected function doBeforeCreate() {        

        //$companyId = auth()->getCompanyId();
        //$userId = auth()->user()->id;
        $date = date("Y-m-d H:i:s");

        //$this->company_id = $companyId;
        //$this->created_by = $userId;
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

            $sql = "SELECT a.* FROM purchase_order_items AS a
                    WHERE a.purchase_order_id=?";
            $lineItems = $this->query($sql, [$this->id]);

            foreach($lineItems as &$item) {
                $item->tax_info      = $item->tax_info      ? json_decode($item->tax_info)      : [];
                $item->discount_info = $item->discount_info ? json_decode($item->discount_info, true) : null;
            }
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
    public function getReceivableItems($includeReceivedItems=false): array
    {
        if (empty($this->id)) {
            return [];
        }
        
        $sql = "
            SELECT
                poi.*,
                COALESCE(poi.product_name, p.name) AS product_name,
                p.stock_tracking_method,
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
            LEFT JOIN products p ON p.id = poi.product_id
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

            $remainingQty = round($orderedQty - ($receivedQty + $inTransitQty), 4);

            // Hard guard — never allow negative values
            if ($remainingQty < 0) {
                $remainingQty = 0;
            }

            // Skip fully received lines (optional but recommended)
            if ($remainingQty <= 0 && $includeReceivedItems === false) {
                continue;
            }

            $items[] = [
                'po_item_id' => (int) $row->id,
                'product_id' => (int) $row->product_id,
                'product_name' => $row->product_name,
                'stock_tracking_method' => $row->stock_tracking_method ?? 'none',
                'description' => $row->description,
                'ordered_qty' => $orderedQty,
                'received_qty' => $receivedQty,
                'in_transit_qty' => $inTransitQty,
                'remaining_qty' => $remainingQty,
                'uom_code' => $row->uom_code,
            ];
        }

        return $items;
    }


}

?>