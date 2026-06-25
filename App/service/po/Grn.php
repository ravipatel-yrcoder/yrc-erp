<?php

class Service_Po_Grn extends Service_Base
{
    private const ALLOWED_STATUSES = ['draft', 'in_transit', 'received'];

    private function getGrnOrFail(int $grnId): Models_PurchaseOrderGrn {
        
        // validate purchase order receive(Receipt/Grn) and permissions
        $poGrn = new Models_PurchaseOrderGrn($grnId);
        if( $poGrn->isEmpty ) {
            throw new Service_Exception("The requested purchase receipt was not found", 404);
        }

        if( $poGrn->company_id != $this->context->companyId ) {
            throw new Service_Exception("You do not have permission to access this purchase receipt", 403);
        }

        return $poGrn;
    }


    private function getPurchaseOrderOrFail(int $poId) : Models_PurchaseOrder {

        // validate purchase order and permissions
        $purchaseOrder = new Models_PurchaseOrder($poId);
        if( $purchaseOrder->isEmpty ) {
            throw new Service_Exception("The requested purchase order was not found", 404);
        }

        if( $purchaseOrder->company_id != $this->context->companyId ) {
            throw new Service_Exception("You do not have permission to access this purchase order", 403);
        }

        return $purchaseOrder;
    }


    private function guardPurchaseOrderReceivable(Models_PurchaseOrder $po) {

        $allowedStatuses = ['confirmed', 'partially_received'];
        if (!in_array($po->status, $allowedStatuses, true)) {
            throw new Service_Exception('This purchase order cannot be received in its current status', 422);
        }
    }


    private function validateReceivePayload(array $payload, Models_PurchaseOrder $po, int $excludeGrnId = 0) {

        $status = $payload['status'] ?? "";        
        $receiveDate = $payload['received_date'] ?? "";
        $receiveItems = $payload['receive_items'] ?? "";


        if( !in_array($status, self::ALLOWED_STATUSES) ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Receive status"), "status");
        }

        if (in_array($status, ['received']) && (empty($receiveDate) || !strtotime($receiveDate)) ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Receive date"), "received_date");
        }

        if (empty($receiveItems) || !is_array($receiveItems)) {
            $this->addError(validationErrMsg("one_item_required", "receive item"), "receive_items");
        }
        else
        {
            // Purchase order receivable items to validate receive items in payload.
            // When editing an existing GRN, include all PO items (even fully-allocated ones)
            // and add back the current GRN's in-transit contribution so its own quantities
            // are not blocked by the remaining_qty check.
            $poReceivableItemsByPoItemId = [];
            if ($excludeGrnId > 0) {
                foreach($po->getReceivableItems(true) as $item) {
                    $poReceivableItemsByPoItemId[$item['po_item_id']] = $item;
                }
                $existingGrn = new Models_PurchaseOrderGrn($excludeGrnId);
                foreach ($existingGrn->line_items as $lineItem) {
                    $poItemId = $lineItem->purchase_order_item_id;
                    if (isset($poReceivableItemsByPoItemId[$poItemId])) {
                        $poReceivableItemsByPoItemId[$poItemId]["remaining_qty"] =
                            round((float)$poReceivableItemsByPoItemId[$poItemId]["remaining_qty"] + (float)$lineItem->received_qty, 4);
                    }
                }
            } else {
                foreach($po->getReceivableItems() as $item) {
                    $poReceivableItemsByPoItemId[$item['po_item_id']] = $item;
                }
            }

            $uomCodes = array_values(array_unique(array_filter(array_map(fn($i) => $poReceivableItemsByPoItemId[$i['po_item_id'] ?? 0]['uom_code'] ?? null, $receiveItems))));
            $uomCodeDecimalMap = [];
            if ($uomCodes) {
                $ph = implode(',', array_fill(0, count($uomCodes), '?'));
                $uomRows = $this->db->fetchAll("SELECT code, allow_decimal, name FROM uoms WHERE code IN ($ph)", $uomCodes);
                foreach ($uomRows as $r) {
                    $uomCodeDecimalMap[$r->code] = ['allow_decimal' => (bool)(int)$r->allow_decimal, 'name' => $r->name];
                }
            }

            $itemLevelErrors = [];
            $poItemIds = [];
            $index = 0;
            foreach($receiveItems as $receiveItem)
            {
                $row = $index + 1;
                $poItemId = $receiveItem["po_item_id"] ?? 0;
                $receiveQty = $receiveItem["receive_qty"] ?? 0;

                $poReceivableItem = $poReceivableItemsByPoItemId[$poItemId] ?? [];
                $poItemProdTrackingMethod = $poReceivableItem["stock_tracking_method"] ?? "none";

                if( empty($poReceivableItem) )  {
                    $itemLevelErrors["receive_items.{$index}.invalid_po_item"] = validationErrMsg("invalid", "Item at row {$row}");
                    continue;
                }

                if( !isPositiveNumeric($receiveQty) ) {
                    $itemLevelErrors["receive_items.{$index}.invalid_receive_qty"] = validationErrMsg("invalid", "Receive quantity at row {$row}");
                }
                else {
                    $remainingQty = (float) $poReceivableItem["remaining_qty"];
                    if( $receiveQty > $remainingQty ) {
                        $itemLevelErrors["receive_items.{$index}.invalid_receive_qty"] = validationErrMsg("invalid", "Receive quantity at row {$row}");
                    } else {
                        $uomCode = $poReceivableItem['uom_code'] ?? null;
                        if ($uomCode && isset($uomCodeDecimalMap[$uomCode]) && !$uomCodeDecimalMap[$uomCode]['allow_decimal'] && !isWholeNumber((float)$receiveQty)) {
                            $itemLevelErrors["receive_items.{$index}.invalid_receive_qty"] = "Receive quantity must be a whole number for {$uomCodeDecimalMap[$uomCode]['name']} at row {$row}";
                        }
                    }
                }

                if (in_array($poItemId, $poItemIds)) {
                    $itemLevelErrors["receive_items.{$index}.duplicate_po_item"] = "Duplicate item detected at row {$row}";
                }

                // Serial/lot validation: required on receive; count must always match qty when provided
                if( in_array($poItemProdTrackingMethod, ["serial", "lot"]) ) {
                    $serialOrLotNumbers = $receiveItem["serial_or_lot_numbers"] ?? [];
                    if( $status === "received" && empty($serialOrLotNumbers) ) {
                        $itemLevelErrors["receive_items.{$index}.missing_lot_serial"] = "Missing {$poItemProdTrackingMethod} numbers at row {$row}";
                    } elseif( !empty($serialOrLotNumbers) && count($serialOrLotNumbers) != $receiveQty ) {
                        $itemLevelErrors["receive_items.{$index}.missing_lot_serial"] = ucfirst($poItemProdTrackingMethod)." numbers count must match receive qty at row {$row}";
                    }
                }

                $poItemIds[] = $poItemId;
                $index++;
            }

            // Cross-item serial/lot duplicate and DB conflict checks — build serial→row-index map
            // so each error message references the specific row and serial number
            $serialToRowIndices = [];
            $serialCheckIdx = 0;
            foreach ($receiveItems as $item) {
                foreach ($item['serial_or_lot_numbers'] ?? [] as $sn) {
                    $sn = trim((string) $sn);
                    if ($sn !== '') $serialToRowIndices[$sn][$serialCheckIdx] = true;
                }
                $serialCheckIdx++;
            }

            $allSubmittedSerials = array_keys($serialToRowIndices);

            if (!empty($allSubmittedSerials)) {
                $companyId = $po->company_id;
                $ph = implode(',', array_fill(0, count($allSubmittedSerials), '?'));

                // 1. Same serial assigned to more than one item in this submission
                foreach ($serialToRowIndices as $sn => $idxSet) {
                    if (count($idxSet) > 1) {
                        foreach (array_keys($idxSet) as $idx) {
                            $row = $idx + 1;
                            $itemLevelErrors["receive_items.{$idx}.duplicate_serial"] = "Row {$row}: serial '{$sn}' is assigned to multiple items";
                        }
                    }
                }

                // 2. Already committed to inventory
                $inInv = $this->db->fetchAll(
                    "SELECT serial_number FROM inv_serials WHERE company_id = ? AND serial_number IN ({$ph})",
                    array_merge([$companyId], $allSubmittedSerials)
                );
                foreach ($inInv as $invRow) {
                    $sn = $invRow->serial_number;
                    foreach (array_keys($serialToRowIndices[$sn] ?? []) as $idx) {
                        $row = $idx + 1;
                        $itemLevelErrors["receive_items.{$idx}.duplicate_serial"] = "Row {$row}: serial '{$sn}' is already in inventory";
                    }
                }

                // 3. Already staged in a different receipt
                $excludeClause = $excludeGrnId > 0 ? " AND purchase_order_grn_id != {$excludeGrnId}" : "";
                $inStaging = $this->db->fetchAll(
                    "SELECT serial_number FROM purchase_order_grn_item_serials
                     WHERE company_id = ? AND serial_number IN ({$ph}) AND status = 'available'{$excludeClause}",
                    array_merge([$companyId], $allSubmittedSerials)
                );
                foreach ($inStaging as $stagingRow) {
                    $sn = $stagingRow->serial_number;
                    foreach (array_keys($serialToRowIndices[$sn] ?? []) as $idx) {
                        $row = $idx + 1;
                        $itemLevelErrors["receive_items.{$idx}.duplicate_serial"] = "Row {$row}: serial '{$sn}' is already staged in another receipt";
                    }
                }
            }

            foreach($itemLevelErrors as $errKey => $errMsg) {
                $this->addError($errMsg, $errKey);
            }
        }

    }


    private function getLineItemsDiff(array $existingItems, array $incomingItems): array
    {
        $existingByPoItemId = [];
        foreach ($existingItems as $row) {
            $existingByPoItemId[(int)$row->purchase_order_item_id] = $row;
        }

        $itemsToCreate = [];
        $itemsToUpdate = [];
        $itemsToDelete = [];

        $incomingPoItemIds = [];
        foreach ($incomingItems as $item) {
            $poItemId = (int)$item['po_item_id'];
            if (isset($existingByPoItemId[$poItemId])) {
                $item['_existing'] = $existingByPoItemId[$poItemId];
                $itemsToUpdate[] = $item;
                $incomingPoItemIds[] = $poItemId;
            } else {
                $itemsToCreate[] = $item;
            }
        }

        foreach ($existingByPoItemId as $poItemId => $existingRow) {
            if (!in_array($poItemId, $incomingPoItemIds)) {
                $itemsToDelete[] = $existingRow;
            }
        }

        return [$itemsToCreate, $itemsToUpdate, $itemsToDelete];
    }


    private function saveLineItems(Models_PurchaseOrderGrn $grn, array $receiveItems): array
    {
        $updateLog = [];
        $failedMsg = "Purchase receipt update failed: line item could not be saved";

        [$itemsToCreate, $itemsToUpdate, $itemsToDelete] = $this->getLineItemsDiff($grn->line_items, $receiveItems);

        // DELETE
        foreach ($itemsToDelete as $existingRow) {
            $rowId = $existingRow->id;
            $this->db->delete('purchase_order_grn_items', "id = $rowId");
            $this->db->delete('purchase_order_grn_item_serials', "purchase_order_grn_item_id = $rowId");
            $updateLog[] = [
                'event'     => 'deleted',
                'prod_name' => $existingRow->product_name,
                'old_qty'   => formatQty($existingRow->received_qty),
                'new_qty'   => 0,
            ];
        }

        // UPDATE
        foreach ($itemsToUpdate as $item) {
            $existingRow = $item['_existing'];
            $rowId   = $existingRow->id;
            $updated = $this->db->update(
                'purchase_order_grn_items',
                ['received_qty' => $item['receive_qty']],
                "id = $rowId"
            );
            if ($updated === false) {
                throw new Service_Exception($failedMsg, 500);
            }
            if ((float)$existingRow->received_qty !== (float)$item['receive_qty']) {
                $updateLog[] = [
                    'event'     => 'updated',
                    'prod_name' => $existingRow->product_name,
                    'old_qty'   => formatQty($existingRow->received_qty),
                    'new_qty'   => formatQty($item['receive_qty']),
                ];
            }
        }

        // CREATE
        foreach ($itemsToCreate as $item) {
            $poItemId = (int)$item['po_item_id'];
            $poItem   = new Models_PurchaseOrderItem($poItemId);
            $product  = new Models_Product($poItem->product_id);
            $grnItem  = new Models_PurchaseOrderGrnItem();
            $grnItem->purchase_order_grn_id  = $grn->id;
            $grnItem->purchase_order_item_id = $poItemId;
            $grnItem->product_id             = $poItem->product_id;
            $grnItem->ordered_qty            = $poItem->ordered_qty;
            $grnItem->received_qty           = $item['receive_qty'];
            if (!$grnItem->create()) {
                throw new Service_Exception($failedMsg, 500);
            }
            $updateLog[] = [
                'event'     => 'created',
                'prod_name' => $product->name,
                'old_qty'   => 0,
                'new_qty'   => formatQty($item['receive_qty']),
            ];
        }

        return $updateLog;
    }


    public function logHistory($grnId, $payload) {

        $meta = empty($payload["meta"]) ? null : json_encode($payload["meta"], JSON_UNESCAPED_UNICODE);

        $history = new Models_PurchaseOrderGrnHistory();
        $history->company_id = $this->context->companyId;
        $history->purchase_order_grn_id = $grnId;
        $history->log_type = $payload["log_type"];
        $history->title = $payload["title"];
        $history->reference_type = $payload["reference_type"] ?? null;
        $history->reference_id = $payload["reference_id"] ?? null;
        $history->meta = $meta;
        $history->created_by = $this->context->userId;

        if( !$history->create() ) {
            throw new Service_Exception("Failed to log receive history");
        }
    }


    public function getCreateFormContext(int $poId) {

        $po = $this->getPurchaseOrderOrFail($poId);

        $this->guardPurchaseOrderReceivable($po);

        $receivableItems = $po->getReceivableItems();
        if (empty($receivableItems)) {
            throw new Service_Exception("All items in this purchase order have already been fully received", 422);
        }

        $companyId = $this->context->companyId;
        $userId = $this->context->userId;

        $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));

        $poId = $po->id;
        $poNumber = $po->po_number;
        $vendorId = $po->vendor_id;
        $vendorName = $po->vendor->display_name;
        $grnNumberPreview = $seqService->nextPreview("purchase_order_grns");

        return [
            'receivable_items' => $receivableItems,
            'po_id' => $poId,
            'po_number' => $poNumber,
            'vendor_id' => $vendorId,
            'vendor_name' => $vendorName,
            'receipt_number_preview' => $grnNumberPreview,
            'receipt' => [],
        ];
    }

    public function getEditFormContext(int $grnId) {

        $grn = $this->getGrnOrFail($grnId);

        // guard grn edit (only draft and in_transit are allowed)
        if( !in_array($grn->status, ["draft", "in_transit"], true) ) {

            $msg = "This receipt cannot be edited in its current status";
            if( $grn->status === "received" ) {
                $msg = "This receipt is already marked as received and cannot be edited";
            } else if( $grn->status === "cancelled" ) {
                $msg = "Cancelled receipts cannot be edited";
            }
            throw new Service_Exception($msg);
        }

        $poId = $grn->purchase_order_id;
        $poNumber = $grn->purchase_order->po_number;
        $vendorId = $grn->purchase_order->vendor_id;
        $vendorName = $grn->purchase_order->vendor->display_name;

        // Include all PO items (even fully-allocated ones) so we can correctly
        // calculate remaining quantities for items already in this GRN
        $poReceivableItems = $grn->purchase_order->getReceivableItems(true);
        $grnLineItems = $grn->line_items;

        $poReceivableItemsById = [];
        foreach ($poReceivableItems as $poReceivableItem) {
            $poReceivableItemsById[$poReceivableItem['po_item_id']] = $poReceivableItem;
        }

        // Items already in this GRN - pre-selected rows in the edit form
        $preselectedItems = [];
        $grnPoItemIds = [];
        foreach ($grnLineItems as $grnLineItem) {
            
            $poItemId = $grnLineItem->purchase_order_item_id;
            $grnPoItemIds[] = $poItemId;

            if( isset($poReceivableItemsById[$poItemId]) ) {
                
                $item = $poReceivableItemsById[$poItemId];

                // Restore this GRN's in-transit contribution so the display shows
                // only other GRNs' in-transit quantities
                $item["in_transit_qty"] = max(0, (float)$item["in_transit_qty"] - (float)$grnLineItem->received_qty);

                // Max the user may enter = current GRN qty + remaining from PO (excl. this GRN)
                $item["remaining_qty"]  = round((float)$grnLineItem->received_qty + (float)$poReceivableItemsById[$poItemId]["remaining_qty"], 4);

                // Default value to pre-fill in the qty input
                $item["current_grn_qty"] = (float)$grnLineItem->received_qty;

                $preselectedItems[] = $item;
            }
        }

        // Items from the PO that are not in this GRN but still have remaining qty -
        // available for the user to add during editing
        $addableItems = [];
        foreach ($poReceivableItems as $poReceivableItem) {
            if( !in_array($poReceivableItem['po_item_id'], $grnPoItemIds) && (float)$poReceivableItem['remaining_qty'] > 0 ) {
                $addableItems[] = $poReceivableItem;
            }
        }

        // Load staged serials for all preselected items in one query, keyed by po_item_id
        $stagedRows = $this->db->fetchAll(
            "SELECT s.serial_number, gi.purchase_order_item_id
             FROM purchase_order_grn_item_serials s
             JOIN purchase_order_grn_items gi ON gi.id = s.purchase_order_grn_item_id
             WHERE s.purchase_order_grn_id = ? AND s.status = 'available'",
            [$grnId]
        );
        $stagedByPoItemId = [];
        foreach ($stagedRows as $row) {
            $stagedByPoItemId[$row->purchase_order_item_id][] = $row->serial_number;
        }
        foreach ($preselectedItems as &$item) {
            $item['serial_or_lot_numbers'] = $stagedByPoItemId[$item['po_item_id']] ?? [];
        }
        unset($item);

        return [
            'receivable_items' => $preselectedItems,
            'addable_items' => $addableItems,
            'po_id' => $poId,
            'po_number' => $poNumber,
            'vendor_id' => $vendorId,
            'vendor_name' => $vendorName,
            'receipt_number_preview' => "",
            'receipt_id' => $grnId,
            'receipt_number' => $grn->grn_number,
            'receipt' => [
                'receipt_number' => $grn->grn_number,
                'notes' => $grn->notes,
                'received_date' => $grn->received_date,
                'vendor_document_number' => $grn->vendor_document_number,
                'vendor_document_date' => $grn->vendor_document_date,
                'status' => $grn->status,
            ],
        ];
    }


    /**
     * Persist serial numbers from the payload into purchase_order_grn_item_serials (staging).
     * Called on every save regardless of status (draft / in_transit / received).
     * Uses a diff approach: only inserts new serials and removes dropped ones; skips untouched items.
     * Also advances inv_sequence_patterns.last_number only for newly added serials.
     */
    private function saveGrnItemSerials(int $grnId, array $receiveItems): void
    {
        $companyId = $this->context->companyId;
        $userId    = $this->context->userId;

        foreach ($receiveItems as $item) {
            $poItemId           = (int) ($item['po_item_id'] ?? 0);
            $serialOrLotNumbers = $item['serial_or_lot_numbers'] ?? [];

            if (!$poItemId) continue;

            $grnItem = $this->db->fetchOne(
                "SELECT id, product_id FROM purchase_order_grn_items
                 WHERE purchase_order_grn_id = ? AND purchase_order_item_id = ? LIMIT 1",
                [$grnId, $poItemId]
            );
            if (!$grnItem) continue;

            $grnItemId = (int) $grnItem->id;
            $productId = (int) $grnItem->product_id;

            $newSerials = [];
            foreach ($serialOrLotNumbers as $sn) {
                $sn = trim((string) $sn);
                if ($sn !== '') $newSerials[] = $sn;
            }

            $existingRows    = $this->db->fetchAll(
                "SELECT serial_number FROM purchase_order_grn_item_serials
                 WHERE purchase_order_grn_item_id = ? AND status = 'available'",
                [$grnItemId]
            );
            $existingSerials = array_column(array_map('get_object_vars', $existingRows), 'serial_number');

            $toAdd    = array_diff($newSerials, $existingSerials);
            $toRemove = array_diff($existingSerials, $newSerials);

            if (empty($toAdd) && empty($toRemove)) continue;

            foreach ($toRemove as $sn) {
                $this->db->query(
                    "DELETE FROM purchase_order_grn_item_serials
                     WHERE purchase_order_grn_item_id = ? AND serial_number = ?",
                    [$grnItemId, $sn]
                );
            }

            foreach ($toAdd as $sn) {
                $staging = new Models_PurchaseOrderGrnItemSerial();
                $staging->purchase_order_grn_id      = $grnId;
                $staging->purchase_order_grn_item_id = $grnItemId;
                $staging->company_id                 = $companyId;
                $staging->serial_number              = $sn;
                $staging->status                     = 'available';
                $staging->create();
            }

            if (!empty($toAdd)) {
                $seqService = new Service_Inv_Sequence(new Service_TenantContext($companyId, $userId));
                $seqService->updateLastNumber($productId, array_values($toAdd));
            }
        }
    }


    /**
     * Create GRN
     */
    public function create(int $poId, array $payload): array
    {
        if (!$this->context->canDo('purchase_receipts', 'write')) {
            throw new Service_Exception('You do not have permission to create purchase receipts', 403);
        }


        // validate and get purchase order
        $po = $this->getPurchaseOrderOrFail($poId);

        
        // Validate purchase order receive state
        $this->guardPurchaseOrderReceivable($po);


        // Validate receive items payload
        $this->validateReceivePayload($payload, $po);        


        if ($this->hasErrors()) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }

        
        $this->db->startTransaction();

        try {

            $companyId = $this->context->companyId;
            $userId = $this->context->userId;

            $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));

            $grnStatus = $payload['status'] ?? 'draft';
        
            // Create PO GRN
            $grnNumber = $seqService->nextCommit('purchase_order_grns');
            $grn = new Models_PurchaseOrderGrn();
            $grn->company_id = $companyId;
            $grn->purchase_order_id = $poId;
            $grn->grn_number = $grnNumber;
            $grn->status = $grnStatus;
            $grn->location_id = $po->receiving_location_id ?? $po->location_id;
            $grn->notes = $payload['notes'] ?? null;
            $grn->created_by = $userId;

            $grnId = $grn->create();
            if (!$grnId) {
                throw new Service_Exception("Failed to create purchase receive", 500);
            }
            
            
            // Create PO GRN Items
            $receiveItems = $payload['receive_items'] ?? [];
            $grn->refreshById($grnId);
            $this->saveLineItems($grn, $receiveItems);
            $this->saveGrnItemSerials($grnId, $receiveItems);

            // PO GRN History
            $logPayload = [
                'log_type' => 'created',
                'title' => 'Receipt created #'.$grnNumber,
                'meta' => [
                    'status' => $grnStatus,
                    'items_count' => count($receiveItems),
                ],
            ];
            $this->logHistory($grnId, $logPayload);

            /*
            $history = new Models_PurchaseOrderGrnHistory();
            $history->company_id = $companyId;
            $history->purchase_order_grn_id = $grnId;
            $history->event_type = "created";            
            $history->notes = "Purchase receive created";
            $history->created_by = $userId;

            if( !$history->create() ) {
                throw new Service_Exception("Purchase receive update failed: history record could not be created", 500);
            }
            */

            if( $grnStatus === "received" ) {
                $this->markReceived($grn, $receiveItems);
            }

            $this->db->commit();

            return [
                "success" => true,
                "data" => [
                    "receipt_id" => $grnId,
                    "po_id" => $poId,
                    "grn_number" => $grn->grn_number,
                    "status" => $grn->status
                ]
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    /**
     * Update GRN
     */
    public function update(int $grnId, array $payload): array
    {
        if (!$this->context->canDo('purchase_receipts', 'write')) {
            throw new Service_Exception('You do not have permission to update purchase receipts', 403);
        }

        // Validate and load GRN
        $grn = $this->getGrnOrFail($grnId);

        // Guard: only draft and in_transit can be edited
        if (!in_array($grn->status, ['draft', 'in_transit'], true)) {
            $msg = "This receipt cannot be edited in its current status";
            if ($grn->status === 'received') {
                $msg = "This receipt is already marked as received and cannot be edited";
            } else if ($grn->status === 'cancelled') {
                $msg = "Cancelled receipts cannot be edited";
            }
            throw new Service_Exception($msg);
        }

        // Validate and load PO
        $po = $this->getPurchaseOrderOrFail($grn->purchase_order_id);
        $this->guardPurchaseOrderReceivable($po);

        // Validate receive payload - pass this GRN's id so its own in-transit
        // quantities are excluded from the remaining_qty check
        $this->validateReceivePayload($payload, $po, $grnId);

        if ($this->hasErrors()) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }

        $this->db->startTransaction();

        try {

            //$userId = $this->context->userId;
            $oldStatus = $grn->status;
            $newStatus = $payload['status'] ?? $oldStatus;
            $notes = $payload['notes'] ?? null;

            // Update GRN header fields
            $grn->notes = $notes;
            $grn->status = $newStatus;

            if ($newStatus === 'in_transit' && $oldStatus !== 'in_transit') {
                $grn->in_transit_date = date('Y-m-d H:i:s');
            }

            if (!$grn->update()) {
                throw new Service_Exception("Failed to update purchase receipt", 500);
            }

            $receiveItems = $payload['receive_items'] ?? [];
            $lineItemUpdateLog = $this->saveLineItems($grn, $receiveItems);
            $this->saveGrnItemSerials($grnId, $receiveItems);

            // Log item-level changes (only if something changed)
            if (!empty($lineItemUpdateLog)) {

                $this->logHistory($grnId, [
                    'log_type' => 'updated_line_items',
                    'title' => 'Receive items updated',
                    'meta' => $lineItemUpdateLog,
                ]);
            }


            // If transitioning to received for the first time, process inventory movements
            if ($newStatus === 'received' && $oldStatus !== 'received') {
                $this->markReceived($grn, $receiveItems);
            } else {

                // GRN update status log
                if( $newStatus != $oldStatus ) {

                    $logPayload = [
                        'log_type' => "status_changed",
                        'title' => 'Status changed',
                        'meta' => [
                            'old_status' => $oldStatus,
                            'new_status' => $newStatus,
                            'notes' => $notes,
                        ]
                    ];
                    $this->logHistory($grnId, $logPayload);
                }            
            }

            $this->db->commit();

            return [
                "success" => true,
                "data" => [
                    "receipt_id" => $grnId,
                    "po_id" => $po->id,
                    "grn_number" => $grn->grn_number,
                    "status" => $grn->status
                ]
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    // mark as received
    private function markReceived(Models_PurchaseOrderGrn $poGrn, $grnItems) {
        
        $companyId = $this->context->companyId;
        $userId = $this->context->userId;

        // Guard state
        if ($poGrn->status !== 'received') {
            throw new Service_Exception("This receive is not posted, can't mark as received", 422);
        }

        if (!empty($poGrn->received_date)) {
            throw new Service_Exception("This is already marked as received", 422);
        }

        if( empty($grnItems) ) {
            throw new Service_Exception("No items found to receive", 422);
        }

        $receivedAt = date('Y-m-d H:i:s');
        $invService = new Service_Inv_Movement(new Service_TenantContext($companyId, $userId));

        
        // Process each GRN item
        $grnItemsForLog = [];
        $receivedQuantities = 0;
        foreach ($grnItems as $grnItem) {

            $poItemId = $grnItem['po_item_id'];
            $receiveQty = (float) $grnItem['receive_qty'];
            
            $poItem = new Models_PurchaseOrderItem($poItemId);
            $productId = $poItem->product_id;
            
            $product = new Models_Product($productId);
            


            // Update inventory if product inventory tracking is enabled
            $prodStockTrackMethods = strtolower($product->stock_tracking_method);
            if( $prodStockTrackMethods && $prodStockTrackMethods !== "none" ) {

                // Load staged serials for this GRN item from the staging table
                $grnItemRow = $this->db->fetchOne(
                    "SELECT id FROM purchase_order_grn_items
                     WHERE purchase_order_grn_id = ? AND purchase_order_item_id = ? LIMIT 1",
                    [$poGrn->id, $poItemId]
                );
                $stagedSerials = [];
                $stagedGrnItemId = null;
                if ($grnItemRow) {
                    $stagedGrnItemId = (int) $grnItemRow->id;
                    $rows = $this->db->fetchAll(
                        "SELECT serial_number FROM purchase_order_grn_item_serials
                         WHERE purchase_order_grn_item_id = ? AND status = 'available'",
                        [$stagedGrnItemId]
                    );
                    $stagedSerials = array_column(array_map('get_object_vars', $rows), 'serial_number');
                }

                // Inventory movement
                $recordResult = $invService->record([
                    'movement_type'      => 'purchase_receipt',
                    'location_id'        => $poGrn->location_id,
                    'product_id'         => $productId,
                    'quantity'           => $receiveQty,
                    'serial_or_lot_numbers' => $stagedSerials,
                    'reference_type'     => 'po_grn',
                    'reference_id'       => $poGrn->id,
                    'notes'              => 'Purchase received',
                ]);

                if( $recordResult["success"] !== true ) {
                    $errors = $recordResult["errors"] ?? [];
                    $msg = !empty($errors) ? implode("; ", array_values($errors)) : "Inventory update failure";
                    throw new Service_Exception($msg, 422);
                }

                // Mark staged serials as received
                if ($stagedGrnItemId) {
                    $this->db->update(
                        'purchase_order_grn_item_serials',
                        ['status' => 'received'],
                        "purchase_order_grn_item_id = $stagedGrnItemId AND status = 'available'"
                    );
                }
            }
            

            // Update PO item received qty (use query to prevent race condition)
            try {
                $sql = "UPDATE purchase_order_items SET received_qty = received_qty + ? WHERE id = ?";
                $updatedReceivedQty = $this->db->query($sql, [$receiveQty, $poItemId]);
                if ($updatedReceivedQty === false) {
                    throw new Service_Exception("Failed to update purchase order item received quantity", 500);
                }
            } catch(Exception $e) {
                throw new Service_Exception("Failed to update purchase order item received quantity", 500);
            }

            // Recalculate line_status after received_qty change
            $updatedItem = $this->db->fetchOne(
                "SELECT ordered_qty, received_qty FROM purchase_order_items WHERE id = ?",
                [$poItemId]
            );
            if ($updatedItem) {
                $newStatus = ((float)$updatedItem->received_qty >= (float)$updatedItem->ordered_qty) ? 'fulfilled' : 'partial';
                $this->db->update('purchase_order_items', ['line_status' => $newStatus], "id = {$poItemId}");
            }

            $productUom = new Models_ProductUom($poItem->product_uom_id);
            $grnItemsForLog[] = [
                'prod_id' => $productId,
                'prod_name' => $product->name,
                'received_qty' => formatQty($receiveQty ),
                'uom' => $productUom->base_uom->code,                
            ];

            $receivedQuantities += $receiveQty;
        }

        // Update purchase order status
        $po = new Models_PurchaseOrder($poGrn->purchase_order_id);

        $allReceived = true;
        foreach ($po->line_items as $item) {
            if ((float) $item->received_qty < (float) $item->ordered_qty) {
                $allReceived = false;
                break;
            }
        }

        $poOldStatus = $po->status;
        $po->status = $allReceived ? 'received' : 'partially_received';
        if (!$po->update()) {
            throw new Service_Exception("Failed to update purchase order status", 500);
        }

        // Finalize GRN
        $poGrn->received_date = $receivedAt;
        $poGrn->received_by = $userId;
        if (!$poGrn->update()) {
            throw new Service_Exception("Failed to update purchase receive", 500);
        }

        // GRN history
        $logPayload = [
            'log_type' => 'received',
            'title' => 'Mark received',
            'meta' => [
                'po_status' => $po->status,
                'items_count' => count($grnItems),
                'quantities' => $receivedQuantities,
                'items' => $grnItemsForLog,
            ],
        ];
        $this->logHistory($poGrn->id, $logPayload);

        
        // Purchase Order History
        $logTitle = $po->status === "received" ? "Purchase order fully received" : "Items received against purchase order";
        //$poService = new Service_Po_Order($this->context->companyId);
        $poService = new Service_Po_Order(new Service_TenantContext($companyId, $userId));
        $poService->logHistory($poGrn->purchase_order_id, [
            'log_type' => 'received',
            'title' => $logTitle,
            'reference_type' => 'po_grn',
            'reference_id' => $poGrn->id,
            'meta' => [
                'receipt_id' => $poGrn->id,
                'receipt_number' => $poGrn->grn_number,
                'old_status' => $poOldStatus,
                'new_status' => $po->status,
                'items_count' => count($grnItems),
                'quantities' => $receivedQuantities,
            ],
        ]);
    }


    /**
     * Retrive receipt details
     */
    public function getDetails(int $grnId) : array {

        $poGrn = $this->getGrnOrFail($grnId);

        $mapping = ['grn_number' => 'receipt_number', 'received_date' => 'received_date', 'received_by' => 'received_by'];
        $grnDetails = $poGrn->toArray();
        $finalDetails = [];
        foreach($grnDetails as $key => $val) {
            $finalKey = $mapping[$key] ?? $key;
            $finalDetails[$finalKey] = $val;
        }

        // Load serial numbers for all GRN items in one query, keyed by grn_item_id
        $serialRows = $this->db->fetchAll(
            "SELECT purchase_order_grn_item_id, serial_number
             FROM purchase_order_grn_item_serials
             WHERE purchase_order_grn_id = ?
             ORDER BY id ASC",
            [$grnId]
        );
        $serialsByItemId = [];
        foreach ($serialRows as $row) {
            $serialsByItemId[(int) $row->purchase_order_grn_item_id][] = $row->serial_number;
        }

        // Attach serial_numbers array to each line item
        $lineItems = array_map(function ($item) use ($serialsByItemId) {
            $arr = (array) $item;
            $arr['serial_numbers'] = $serialsByItemId[(int) $item->id] ?? [];
            return $arr;
        }, $poGrn->line_items);

        $receiptDetails = array_merge(['id' => $grnId, "purchase_order_id" => $poGrn->purchase_order_id, "po_number" => $poGrn->purchase_order->po_number, "vendor_name" => $poGrn->purchase_order->vendor->display_name, "line_items" => $lineItems], $finalDetails);

        $data = ['receipt_details' => $receiptDetails];

        return $data;
    }   



    /**
     * Update Status
     */
    private function validateUpdateStatusPayload(array $payload) {

        $status = $payload["status"] ?? "";
        if( empty($status) ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Status"), "status");
        }
    }

    public function updateStatus(int $grnId, array $payload)
    {
        if (!$this->context->canDo('purchase_receipts', 'receive')) {
            throw new Service_Exception('You do not have permission to receive purchase orders', 403);
        }

        $grn = $this->getGrnOrFail($grnId);
        
        // Validate payload
        $this->validateUpdateStatusPayload($payload);

        if ($this->hasErrors()) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }

        $this->db->startTransaction();

        try {

            $status = $payload["status"] ?? "";
            $notes = $payload["notes"] ?? "";
            
            // Need to implement logic in front-end to generate the Serial or Lot Numbers for each receving items when
            // try to marked received
            $serialOrLotNumbersByItem = $payload["serial_or_lot_numbers"] ?? [];
            
            $oldStatus = $grn->status;

            if( $status === "in_transit" ) {
                if ($oldStatus == 'received') {
                    throw new Service_Exception("This is already marked as received, can not update to in transit");
                }

                $grn->in_transit_date = date("Y-m-d H:i:s");
            }

            if( $status === "received" && $oldStatus == 'received' ) {
                throw new Service_Exception("This is already marked as received");
            }

            // Update GRN Status, then conditional run execute logic to update relevant records
            $grn->status = $status;
            if (!$grn->update()) {
                throw new Service_Exception("Failed to update receive status");
            }

            if( $status === "received" ) {
                
                $lineItems = $grn->line_items;
                $receiveItems = [];
                foreach($lineItems as $lineItem) {
                    
                    $poItemId = $lineItem->purchase_order_item_id;

                    $serialOrLotNumbers = $serialOrLotNumbersByItem[$poItemId] ?? [];
                    $receiveItems[] = [
                        'po_item_id' => $lineItem->purchase_order_item_id,
                        'receive_qty' => $lineItem->received_qty,
                        'serial_or_lot_numbers' => $serialOrLotNumbers,
                    ];
                }

                $this->markReceived($grn, $receiveItems);

            } else {

                // GRN update status log
                $logPayload = [
                    'log_type' => "status_changed",
                    'title' => 'Status changed',
                    'meta' => [
                        'old_status' => $oldStatus,
                        'new_status' => $status,
                        'notes' => $notes,
                    ]
                ];
                $this->logHistory($grnId, $logPayload);
            }

            $this->db->commit();

            return [
                "success" => true,
                "data" => [
                    "receipt_id" => $grnId,
                    "status" => $status,
                    "old_status" => $oldStatus
                ]
            ];

        } catch(Exception $e) {
            
            $this->db->rollBack();
            throw $e;
        }

    }


    public function getHistory(int $grnId) {
        
        // guard
        $this->getGrnOrFail($grnId);

        $sql = "SELECT a.*, b.name AS performed_by FROM purchase_order_grn_history AS a
                LEFT JOIN users AS b ON b.id=a.created_by
                WHERE
                a.company_id=? AND
                a.purchase_order_grn_id=?
                ORDER BY a.id DESC";
        $results = $this->db->fetchAll($sql, [$this->context->companyId, $grnId]);

        $formattedData = [];
        foreach($results as $row)
        {
            $meta = json_decode($row->meta ?? '[]', true) ?: [];
            $formattedData[] = [
                'log_type' => $row->log_type,
                'title' => $row->title,
                'reference_type' => $row->reference_type,
                'reference_id' => $row->reference_id,
                'meta' => $meta,
                'performed_by' => $row->performed_by,
                'date_time' => formatMySqlDate($row->created_at),
            ];
        }
        
        return $formattedData;
    }

}