<?php
class Service_Po_Inquiry extends Service_Base
{
    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function getInquiryOrFail(int $id): Models_PurchaseInquiry
    {
        $inquiry = new Models_PurchaseInquiry($id);
        if ($inquiry->isEmpty) {
            throw new Service_Exception("Purchase inquiry not found", 404);
        }
        if ($inquiry->company_id != $this->context->companyId) {
            throw new Service_Exception("You do not have permission to access this inquiry", 403);
        }
        return $inquiry;
    }

    private function getInquiryVendorOrFail(int $inquiryVendorId, int $inquiryId): Models_PurchaseInquiryVendor
    {
        $iv = new Models_PurchaseInquiryVendor($inquiryVendorId);
        if ($iv->isEmpty || $iv->inquiry_id != $inquiryId) {
            throw new Service_Exception("Vendor record not found for this inquiry", 404);
        }
        return $iv;
    }

    private function getVendorQuoteOrFail(int $vendorId, int $inquiryId): Models_PurchaseVendorQuote
    {
        $quote = new Models_PurchaseVendorQuote();
        $quote->fetchByProperty(['vendor_id', 'inquiry_id'], [$vendorId, $inquiryId]);
        if ($quote->isEmpty) {
            throw new Service_Exception("No quote found for this vendor", 404);
        }
        return $quote;
    }

    private function validateItems(array $items): bool
    {
        if (empty($items)) {
            $this->addError(validationErrMsg("one_item_required", "line item"), "items");
            return false;
        }

        $valid          = true;
        $seenProductIds = [];

        foreach ($items as $i => $item) {
            $row = $i + 1;
            $productId    = (int) ($item['product_id'] ?? 0);
            $requiredQty  = (float) ($item['required_qty'] ?? 0);
            $productUomId = (int) ($item['product_uom_id'] ?? 0);

            if (!$productId) {
                $this->addError("Row {$row}: Product is required", "items");
                $valid = false;
                continue;
            }

            $product = new Models_Product($productId);
            if ($product->isEmpty || $product->company_id != $this->context->companyId || $product->status !== 'active') {
                $this->addError("Row {$row}: Invalid or inactive product", "items");
                $valid = false;
            } else {
                if (in_array($productId, $seenProductIds)) {
                    $this->addError("Row {$row}: Duplicate product — each product can only appear once", "items");
                    $valid = false;
                }
                $seenProductIds[] = $productId;
            }

            if ($requiredQty <= 0) {
                $this->addError("Row {$row}: Required quantity must be greater than zero", "items");
                $valid = false;
            }

            if (!$productUomId) {
                $this->addError("Row {$row}: Unit of measure is required", "items");
                $valid = false;
            }
        }

        return $valid;
    }

    private function diffAndSaveItems(int $inquiryId, array $newItems): array
    {
        $existing = $this->db->fetchAll(
            "SELECT * FROM purchase_inquiry_items WHERE inquiry_id = ?",
            [$inquiryId]
        );
        $existingByProduct = [];
        foreach ($existing as $row) {
            $existingByProduct[$row->product_id] = $row;
        }

        $itemLog      = [];
        $newProductIds = [];
        $sortOrder    = 0;
        foreach ($newItems as $item) {
            $productId    = (int) $item['product_id'];
            $requiredQty  = (float) $item['required_qty'];
            $productUomId = (int) $item['product_uom_id'];
            $description  = isset($item['description']) ? trim($item['description']) : null;
            $notes        = isset($item['notes']) ? trim($item['notes']) : null;

            $product    = new Models_Product($productId);
            $productUom = new Models_ProductUom($productUomId);
            $uomCode    = $productUom->base_uom->code ?? $productUom->code ?? '';

            $newProductIds[] = $productId;

            if (isset($existingByProduct[$productId])) {
                $existingRow = $existingByProduct[$productId];
                $changed = (
                    (float) $existingRow->required_qty   !== $requiredQty  ||
                    (int)   $existingRow->product_uom_id !== $productUomId ||
                    $existingRow->description !== $description              ||
                    $existingRow->notes       !== $notes
                );
                if ($changed) {
                    $this->db->update('purchase_inquiry_items', [
                        'required_qty'   => $requiredQty,
                        'product_uom_id' => $productUomId,
                        'uom_code'       => $uomCode,
                        'description'    => $description,
                        'notes'          => $notes,
                        'sort_order'     => $sortOrder,
                        'updated_at'     => date('Y-m-d H:i:s'),
                    ], "id = {$existingRow->id}");

                    $itemLog[] = [
                        'event'        => 'updated',
                        'product_name' => $product->name,
                        'old_qty'      => (float) $existingRow->required_qty,
                        'new_qty'      => $requiredQty,
                        'uom'          => $uomCode,
                    ];
                }
            } else {
                $pii = new Models_PurchaseInquiryItem();
                $pii->inquiry_id     = $inquiryId;
                $pii->product_id     = $productId;
                $pii->product_name   = $product->name ?? '';
                $pii->product_sku    = $product->sku ?? '';
                $pii->description    = $description;
                $pii->required_qty   = $requiredQty;
                $pii->product_uom_id = $productUomId;
                $pii->uom_code       = $uomCode;
                $pii->sort_order     = $sortOrder;
                $pii->notes          = $notes;

                if (!$pii->create()) {
                    throw new Service_Exception("Failed to save item: {$product->name}");
                }

                $itemLog[] = [
                    'event'        => 'added',
                    'product_name' => $product->name,
                    'qty'          => $requiredQty,
                    'uom'          => $uomCode,
                ];
            }
            $sortOrder++;
        }

        foreach ($existingByProduct as $productId => $row) {
            if (!in_array($productId, $newProductIds)) {
                $this->db->query("DELETE FROM purchase_inquiry_items WHERE id = ?", [$row->id]);
                $itemLog[] = [
                    'event'        => 'removed',
                    'product_name' => $row->product_name,
                    'qty'          => (float) $row->required_qty,
                    'uom'          => $row->uom_code,
                ];
            }
        }

        return $itemLog;
    }

    private function recalculateQuoteTotals(int $quoteId): void
    {
        $quote = new Models_PurchaseVendorQuote($quoteId);
        if ($quote->isEmpty) return;

        $rows = $this->db->fetchAll(
            "SELECT pvqi.unit_price, pvqi.discount_amount, pvqi.tax_amount, pii.required_qty
             FROM purchase_vendor_quote_items pvqi
             JOIN purchase_inquiry_items pii ON pii.id = pvqi.inquiry_item_id
             WHERE pvqi.quote_id = ? AND pvqi.can_supply = 1",
            [$quoteId]
        );

        $subtotal      = 0;
        $itemDiscTotal = 0;
        $taxTotal      = 0;
        foreach ($rows as $row) {
            $subtotal      += (float) $row->unit_price * (float) $row->required_qty;
            $itemDiscTotal += (float) $row->discount_amount;
            $taxTotal      += (float) $row->tax_amount;
        }

        $grandTotal = round($subtotal - $itemDiscTotal + $taxTotal + (float) $quote->freight_charges + (float) $quote->other_charges, 4);

        $this->db->update('purchase_vendor_quotes', [
            'subtotal'    => round($subtotal, 4),
            'tax_total'   => round($taxTotal, 4),
            'grand_total' => $grandTotal,
            'updated_at'  => date('Y-m-d H:i:s'),
        ], "id = {$quoteId}");
    }

    public function logHistory(int $id, array $payload): int
    {
        $history = new Models_PurchaseInquiryHistory();
        $history->company_id     = $this->context->companyId;
        $history->inquiry_id     = $id;
        $history->log_type       = $payload['log_type'];
        $history->title          = $payload['title'];
        $history->reference_type = $payload['reference_type'] ?? null;
        $history->reference_id   = $payload['reference_id'] ?? null;
        $history->meta           = empty($payload['meta']) ? null : json_encode($payload['meta'], JSON_UNESCAPED_UNICODE);
        $history->created_by     = $this->context->userId;
        $histId = $history->create();
        if (!$histId) {
            throw new Service_Exception("Failed to log inquiry history");
        }
        return (int) $histId;
    }

    private function buildInquiryPdf(Models_PurchaseInquiry $inquiry): string
    {
        $items = $this->db->fetchAll(
            "SELECT * FROM purchase_inquiry_items WHERE inquiry_id = ? ORDER BY sort_order, id",
            [$inquiry->id]
        );

        $company = new Models_Company($inquiry->company_id);

        $data = [
            'inquiry' => $inquiry,
            'items'   => $items,
            'company' => $company,
        ];

        return Helpers_Pdf::render('pdf.purchase-inquiry', ['printData' => $data], []);
    }

    public function getPdfBytes(int $id): string
    {
        if (!$this->context->canAccess('purchase_inquiries')) {
            throw new Service_Exception("You do not have permission to access this inquiry", 403);
        }
        $inquiry = $this->getInquiryOrFail($id);
        return $this->buildInquiryPdf($inquiry);
    }

    // -------------------------------------------------------------------------
    // Public methods
    // -------------------------------------------------------------------------

    public function getFormContext(int $id = 0): array
    {
        $companyId = $this->context->companyId;

        $rows = $this->db->fetchAll(
            "SELECT p.id, p.name, p.sku,
                    pu.id AS uom_id, u.code AS uom_code, pu.is_base AS is_base_uom
             FROM products p
             LEFT JOIN product_uoms pu ON pu.product_id = p.id AND pu.status = 'active'
             LEFT JOIN uoms u ON u.id = pu.base_uom_id
             WHERE p.company_id = ? AND p.status = 'active'
             ORDER BY p.name",
            [$companyId]
        );

        $products = [];
        foreach ($rows as $row) {
            $pid = $row->id;
            if (!isset($products[$pid])) {
                $products[$pid] = ['id' => $pid, 'name' => $row->name, 'sku' => $row->sku, 'uoms' => []];
            }
            if ($row->uom_id) {
                $products[$pid]['uoms'][] = [
                    'uom_id'      => $row->uom_id,
                    'code'        => $row->uom_code,
                    'is_base_uom' => (int) $row->is_base_uom,
                ];
            }
        }
        $products = array_values($products);

        $inquiryDetails = [];
        if ($id > 0) {
            $d = $this->getDetails($id);
            $inquiryDetails = array_merge(['id' => $id], (array) $d['inquiry'], ['items' => $d['items']]);
        }

        $vendorRows = $this->db->fetchAll(
            "SELECT id, display_name AS name FROM vendors WHERE company_id = ? AND status = 'active' ORDER BY display_name",
            [$companyId]
        );
        $vendors = array_map(fn($v) => ['id' => (int) $v->id, 'name' => $v->name], $vendorRows);

        $ptRows = $this->db->fetchAll(
            "SELECT id, name FROM payment_terms WHERE company_id = ? AND status = 'active' ORDER BY name",
            [$companyId]
        );
        $paymentTerms = array_map(fn($r) => ['id' => (int) $r->id, 'name' => $r->name], $ptRows);

        $seqService = new Service_Sequence(new Service_TenantContext($companyId, $this->context->userId));
        $suggested_inquiry_number = $seqService->nextPreview("purchase_inquiry");

        return [
            'products'                 => $products,
            'vendors'                  => $vendors,
            'paymentTerms'             => $paymentTerms,
            'inquiryDetails'           => $inquiryDetails,
            'suggested_inquiry_number' => $suggested_inquiry_number,
        ];
    }

    public function create(array $data): array
    {
        if (!$this->context->canDo('purchase_inquiries', 'write')) {
            throw new Service_Exception("You do not have permission to create purchase inquiries", 403);
        }

        $companyId = $this->context->companyId;
        $userId    = $this->context->userId;

        $locationId = (int) ($data['company_location_id'] ?? 0);
        if (!$locationId) {
            $defaultLocation = $this->db->fetchOne(
                "SELECT id FROM company_locations WHERE company_id = ? AND is_default = 1 LIMIT 1",
                [$companyId]
            );
            if ($defaultLocation) {
                $locationId = (int) $defaultLocation->id;
            }
        }
        if (!$locationId) {
            $this->addError("Location is required", "company_location_id");
        }

        $inquiryNumberInput     = trim($data['inquiry_number'] ?? '');
        $inquiryNumberSuggested = trim($data['inquiry_number_suggested'] ?? '');

        if (!empty($inquiryNumberInput) && $inquiryNumberInput !== $inquiryNumberSuggested) {
            $exists = $this->db->fetchOne(
                "SELECT id FROM purchase_inquiries WHERE company_id = ? AND inquiry_number = ? LIMIT 1",
                [$companyId, $inquiryNumberInput]
            );
            if ($exists) {
                $this->addError(validationErrMsg("duplicate", "Inquiry number"), "inquiry_number");
            }
        }

        $items = $data['items'] ?? [];
        $this->validateItems($items);

        $requiredByDate = !empty($data['required_by_date']) ? $data['required_by_date'] : null;
        if ($requiredByDate && !strtotime($requiredByDate)) {
            $this->addError("Invalid required by date", "required_by_date");
        }

        if ($this->hasErrors()) {
            return ["success" => false, "errors" => $this->getErrors()];
        }

        $this->db->startTransaction();

        try {
            $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));
            if (empty($inquiryNumberInput) || $inquiryNumberInput === $inquiryNumberSuggested) {
                $inquiryNumber = $seqService->nextCommit("purchase_inquiry");
            } else {
                $inquiryNumber = $inquiryNumberInput;
                $seqService->advanceCounter("purchase_inquiry", $inquiryNumber);
            }

            $inquiry = new Models_PurchaseInquiry();
            $inquiry->company_id          = $companyId;
            $inquiry->company_location_id = $locationId;
            $inquiry->inquiry_number      = $inquiryNumber;
            $inquiry->title               = !empty($data['title']) ? trim($data['title']) : null;
            $inquiry->required_by_date    = $requiredByDate;
            $inquiry->status              = 'draft';
            $inquiry->notes               = !empty($data['notes']) ? trim($data['notes']) : null;
            $inquiry->internal_notes      = !empty($data['internal_notes']) ? trim($data['internal_notes']) : null;
            $inquiry->created_by          = $userId;

            $newId = $inquiry->create();
            if (!$newId) {
                throw new Service_Exception("Failed to create purchase inquiry");
            }

            $this->diffAndSaveItems((int) $newId, $items);

            $this->logHistory((int) $newId, [
                'log_type' => 'created',
                'title'    => "Purchase inquiry created: {$inquiryNumber}",
                'meta'     => ['item_count' => count($items)],
            ]);

            $this->db->commit();
            return ["success" => true, "data" => ["inquiry_id" => (int) $newId, "inquiry_number" => $inquiryNumber]];

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function update(int $id, array $data): array
    {
        if (!$this->context->canDo('purchase_inquiries', 'write')) {
            throw new Service_Exception("You do not have permission to update purchase inquiries", 403);
        }

        $inquiry = $this->getInquiryOrFail($id);

        if (in_array($inquiry->status, ['awarded', 'cancelled'])) {
            throw new Service_Exception("Cannot edit a {$inquiry->status} inquiry", 422);
        }

        $items = $data['items'] ?? null;
        if ($items !== null && $inquiry->status !== 'draft') {
            throw new Service_Exception("Items cannot be changed after the inquiry has been sent", 422);
        }

        $requiredByDate = !empty($data['required_by_date']) ? $data['required_by_date'] : null;
        if ($requiredByDate && !strtotime($requiredByDate)) {
            $this->addError("Invalid required by date", "required_by_date");
        }

        if ($items !== null) {
            $this->validateItems($items);
        }

        if ($this->hasErrors()) {
            return ["success" => false, "errors" => $this->getErrors()];
        }

        $this->db->startTransaction();

        try {
            $editableFields = [
                'title'          => 'Title',
                'required_by_date' => 'Required by date',
                'notes'          => 'Notes',
                'internal_notes' => 'Internal notes',
            ];

            $oldValues = [];
            foreach ($editableFields as $field => $label) {
                $oldValues[$field] = $inquiry->$field ?? null;
            }

            if (isset($data['title'])) {
                $inquiry->title = !empty($data['title']) ? trim($data['title']) : null;
            }
            if (isset($data['required_by_date'])) {
                $inquiry->required_by_date = $requiredByDate;
            }
            if (isset($data['notes'])) {
                $inquiry->notes = !empty($data['notes']) ? trim($data['notes']) : null;
            }
            if (isset($data['internal_notes'])) {
                $inquiry->internal_notes = !empty($data['internal_notes']) ? trim($data['internal_notes']) : null;
            }

            if (!$inquiry->update()) {
                throw new Service_Exception("Failed to update purchase inquiry");
            }

            $updatedDetails = [];
            foreach ($editableFields as $field => $label) {
                $oldVal = $oldValues[$field] ?? '';
                $newVal = $inquiry->$field ?? '';
                if ($oldVal != $newVal) {
                    $updatedDetails[] = [
                        'field'   => $field,
                        'label'   => $label,
                        'old_val' => $oldVal,
                        'new_val' => $newVal,
                    ];
                }
            }

            if (!empty($updatedDetails)) {
                $this->logHistory($id, [
                    'log_type' => 'updated_details',
                    'title'    => 'Inquiry details updated',
                    'meta'     => $updatedDetails,
                ]);
            }

            if ($items !== null) {
                $itemLog = $this->diffAndSaveItems($id, $items);
                if (!empty($itemLog)) {
                    $this->logHistory($id, [
                        'log_type' => 'updated_line_items',
                        'title'    => 'Line items updated',
                        'meta'     => $itemLog,
                    ]);
                }
            }

            $this->db->commit();
            return ["success" => true, "data" => []];

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function sendToVendor(int $id, int $vendorId, string $to, string $subject, string $body, string $cc = '', string $bcc = ''): void
    {
        if (!$this->context->canDo('purchase_inquiries', 'send_email')) {
            throw new Service_Exception("You do not have permission to send RFQs", 403);
        }

        $inquiry = $this->getInquiryOrFail($id);

        if (in_array($inquiry->status, ['awarded', 'cancelled'])) {
            throw new Service_Exception("Cannot send RFQ for a {$inquiry->status} inquiry", 422);
        }

        $itemCount = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM purchase_inquiry_items WHERE inquiry_id = ?",
            [$id]
        );
        if ((int) $itemCount->cnt === 0) {
            throw new Service_Exception("Inquiry must have at least one item before sending RFQ", 422);
        }

        $to = trim($to);
        if (empty($to)) {
            throw new Service_Exception("Recipient email address is required", 422);
        }

        $vendor = new Models_Vendor($vendorId);
        if ($vendor->isEmpty || $vendor->company_id != $this->context->companyId || $vendor->status !== 'active') {
            throw new Service_Exception("Invalid or inactive vendor", 422);
        }

        $existing = $this->db->fetchOne(
            "SELECT id, status FROM purchase_inquiry_vendors WHERE inquiry_id = ? AND vendor_id = ?",
            [$id, $vendorId]
        );

        // Block sends only to rejected or awarded vendors; allow new, pending, sent, responded
        if ($existing && in_array($existing->status, ['rejected', 'awarded'])) {
            throw new Service_Exception("Vendor cannot receive an RFQ in its current status", 422);
        }

        // Extract recipient name from "Name<email>" format (before transaction)
        $toEmail = $to;
        $toName  = $vendor->display_name;
        if (preg_match('/<(.+?)>/', $to, $m)) {
            $toEmail = trim($m[1]);
            $toName  = trim(str_replace("<{$toEmail}>", '', $to));
        }

        // Resolve email config and build PDF outside transaction (read-only)
        $emailConfig = new Service_EmailConfig($this->context);
        $smtpConfig  = $emailConfig->getSMTPConfig();
        $docConfig   = $emailConfig->getDocConfig('rfq');
        $resolved    = $emailConfig->resolveFrom($docConfig, $this->context->userId);
        $from        = "{$resolved['name']}<{$resolved['email']}>";

        $pdfBytes = $this->buildInquiryPdf($inquiry);

        $now   = date('Y-m-d H:i:s');
        $isNew = !$existing;

        // Persist vendor status and history — commit before sending email
        $this->db->startTransaction();

        try {
            if ($isNew) {
                $iv = new Models_PurchaseInquiryVendor();
                $iv->inquiry_id  = $id;
                $iv->vendor_id   = $vendorId;
                $iv->vendor_name = $vendor->display_name;
                $iv->status      = 'sent';
                $ivId = $iv->create();
                if (!$ivId) {
                    throw new Service_Exception("Failed to add vendor to inquiry");
                }
            } else {
                $ivId = (int) $existing->id;
                $this->db->update('purchase_inquiry_vendors', [
                    'status'     => 'sent',
                    'updated_at' => $now,
                ], "id = {$ivId}");
            }

            $this->db->update('purchase_inquiry_vendors', [
                'sent_at'              => $now,
                'vendor_contact_name'  => $toName,
                'vendor_contact_email' => $toEmail,
                'updated_at'           => $now,
            ], "id = {$ivId}");

            if ($inquiry->status === 'draft') {
                $this->db->update('purchase_inquiries', ['status' => 'sent', 'updated_at' => $now], "id = {$id}");
            }

            $this->logHistory($id, [
                'log_type' => 'rfq_sent',
                'title'    => $isNew ? "RFQ sent to {$vendor->display_name}" : "RFQ resent to {$vendor->display_name}",
                'meta'     => [
                    'from'    => $resolved['email'],
                    'to'      => $toEmail,
                    'cc'      => $cc,
                    'bcc'     => $bcc,
                    'subject' => $subject,
                    'vendors' => [['vendor_id' => $vendorId, 'vendor_name' => $vendor->display_name]],
                ],
            ]);

            $this->db->commit();

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }

        // Send email after commit — if it fails, vendor stays 'sent' (retryable via resend)
        $mailer = new Helpers_Mailer();
        if (!empty($cc))  $mailer->addCC($cc);
        if (!empty($bcc)) $mailer->addBCC($bcc);
        $mailer->addStringAttachment($pdfBytes, "{$inquiry->inquiry_number}.pdf", 'application/pdf');

        $sent = $mailer->sendMail($from, $to, $subject, $body, $smtpConfig);

        if (!$sent) {
            $errors = $mailer->getErrors();
            throw new Service_Exception("Email delivery failed: " . implode('; ', $errors) . " — Vendor status has been updated and can be resent.", 500);
        }
    }

    public function saveVendorPrices(int $id, int $inquiryVendorId, array $header, array $items): void
    {
        if (!$this->context->canDo('purchase_inquiries', 'write')) {
            throw new Service_Exception("You do not have permission to enter vendor prices", 403);
        }

        $inquiry = $this->getInquiryOrFail($id);
        $iv      = $this->getInquiryVendorOrFail($inquiryVendorId, $id);

        if (!in_array($inquiry->status, ['sent', 'partially_responded', 'fully_responded'])) {
            throw new Service_Exception("Prices can only be entered when the inquiry is in 'sent', 'partially_responded', or 'fully_responded' status", 422);
        }

        if (!in_array($iv->status, ['sent', 'responded'])) {
            throw new Service_Exception("Prices can only be entered for vendors in 'sent' or 'responded' status", 422);
        }

        // Validate items
        $inquiryItemIds = array_column(
            $this->db->fetchAll("SELECT id FROM purchase_inquiry_items WHERE inquiry_id = ?", [$id]),
            'id'
        );
        $inquiryItemIds = array_map('intval', $inquiryItemIds);

        foreach ($items as $i => $item) {
            $row = $i + 1;
            $inquiryItemId = (int) ($item['inquiry_item_id'] ?? 0);
            if (!in_array($inquiryItemId, $inquiryItemIds)) {
                $this->addError("Row {$row}: Invalid inquiry item", "items");
            }
            $canSupply = isset($item['can_supply']) ? (int) $item['can_supply'] : 1;
            if (!in_array($canSupply, [0, 1])) {
                $this->addError("Row {$row}: Invalid can_supply value", "items");
            }
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            if ($canSupply && $unitPrice < 0) {
                $this->addError("Row {$row}: Unit price cannot be negative", "items");
            }
        }

        if ($this->hasErrors()) {
            throw new Service_Exception("Validation failed", 422);
        }

        $this->db->startTransaction();

        try {
            $now       = date('Y-m-d H:i:s');
            $vendorId  = $iv->vendor_id;
            $userId    = $this->context->userId;

            // Upsert quote header
            $existingQuote = $this->db->fetchOne(
                "SELECT id FROM purchase_vendor_quotes WHERE vendor_id = ? AND inquiry_id = ?",
                [$vendorId, $id]
            );

            $paymentTermId = !empty($header['payment_term_id']) ? (int) $header['payment_term_id'] : null;
            $paymentTermsSnapshot = null;
            if ($paymentTermId) {
                $pt = new Models_PaymentTerm($paymentTermId);
                if (!$pt->isEmpty) {
                    $paymentTermsSnapshot = $pt->name;
                }
            }

            $quoteData = [
                'quote_status'           => 'submitted',
                'vendor_quote_number'    => !empty($header['vendor_quote_number'])  ? trim($header['vendor_quote_number'])  : null,
                'vendor_quote_date'      => !empty($header['vendor_quote_date'])     ? $header['vendor_quote_date']           : null,
                'quote_validity_date'    => !empty($header['quote_validity_date'])   ? $header['quote_validity_date']         : null,
                'payment_term_id'        => $paymentTermId,
                'payment_terms_snapshot' => $paymentTermsSnapshot,
                'delivery_terms'         => !empty($header['delivery_terms'])        ? trim($header['delivery_terms'])        : null,
                'lead_time_days'         => isset($header['lead_time_days']) && $header['lead_time_days'] !== '' ? (int) $header['lead_time_days'] : null,
                'freight_charges'        => (float) ($header['freight_charges']      ?? 0),
                'other_charges_label'    => !empty($header['other_charges_label'])   ? trim($header['other_charges_label'])   : null,
                'other_charges'          => (float) ($header['other_charges']        ?? 0),
                'vendor_quote_notes'     => !empty($header['vendor_quote_notes'])    ? trim($header['vendor_quote_notes'])    : null,
                'updated_at'             => $now,
            ];

            if ($existingQuote) {
                $quoteId = (int) $existingQuote->id;
                $this->db->update('purchase_vendor_quotes', $quoteData, "id = {$quoteId}");
            } else {
                $quoteData['vendor_id']   = $vendorId;
                $quoteData['inquiry_id']  = $id;
                $quoteData['created_by']  = $userId;
                $quoteData['created_at']  = $now;
                $quoteId = (int) $this->db->insert('purchase_vendor_quotes', $quoteData);
                if (!$quoteId) {
                    throw new Service_Exception("Failed to save vendor quote");
                }
            }

            // Upsert quote items
            foreach ($items as $item) {
                $inquiryItemId = (int) $item['inquiry_item_id'];
                $canSupply     = (int) ($item['can_supply'] ?? 1);
                $unitPrice     = (float) ($item['unit_price'] ?? 0);
                $discountAmt   = (float) ($item['discount_amount'] ?? 0);
                $itemNotes     = !empty($item['notes']) ? trim($item['notes']) : null;

                // Get required_qty for line total calculation
                $inquiryItem = $this->db->fetchOne(
                    "SELECT required_qty, product_id FROM purchase_inquiry_items WHERE id = ?",
                    [$inquiryItemId]
                );
                $requiredQty = (float) $inquiryItem->required_qty;
                $productId   = (int) $inquiryItem->product_id;

                // Resolve taxes from product defaults
                $taxIds = [];
                if ($canSupply) {
                    $taxRows = $this->db->fetchAll(
                        "SELECT tax_id FROM product_default_taxes WHERE product_id = ? AND apply_on = 'purchase'",
                        [$productId]
                    );
                    if (!empty($taxRows)) {
                        $taxIds = array_map(fn($r) => (int) $r->tax_id, $taxRows);
                    }
                }

                if (!empty($item['tax_ids']) && is_array($item['tax_ids'])) {
                    $taxIds = $item['tax_ids'];
                }

                $calc = $canSupply
                    ? Service_Po_Order::calcLineItem($requiredQty, $unitPrice, $discountAmt, $taxIds)
                    : ['taxable_amount' => 0, 'tax_amount' => 0, 'line_total' => 0, 'tax_info' => null];

                $discountInfo = !empty($item['discount_info']) ? json_encode($item['discount_info'], JSON_UNESCAPED_UNICODE) : null;

                $existing = $this->db->fetchOne(
                    "SELECT id FROM purchase_vendor_quote_items WHERE quote_id = ? AND inquiry_item_id = ?",
                    [$quoteId, $inquiryItemId]
                );

                $itemData = [
                    'can_supply'      => $canSupply,
                    'unit_price'      => $canSupply ? round($unitPrice, 4) : 0,
                    'discount_amount' => round($discountAmt, 4),
                    'discount_info'   => $discountInfo,
                    'tax_amount'      => $calc['tax_amount'],
                    'tax_info'        => $calc['tax_info'],
                    'line_total'      => $calc['line_total'],
                    'notes'           => $itemNotes,
                    'updated_at'      => $now,
                ];

                if ($existing) {
                    $this->db->update('purchase_vendor_quote_items', $itemData, "id = {$existing->id}");
                } else {
                    $itemData['inquiry_id']      = $id;
                    $itemData['quote_id']        = $quoteId;
                    $itemData['inquiry_item_id'] = $inquiryItemId;
                    $itemData['product_id']      = $productId;
                    $itemData['created_at']      = $now;
                    $this->db->insert('purchase_vendor_quote_items', $itemData);
                }
            }

            $this->recalculateQuoteTotals($quoteId);

            $this->db->commit();

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function markVendorResponded(int $id, int $inquiryVendorId): void
    {
        if (!$this->context->canDo('purchase_inquiries', 'write')) {
            throw new Service_Exception("You do not have permission to update vendor status", 403);
        }

        $inquiry = $this->getInquiryOrFail($id);
        $iv      = $this->getInquiryVendorOrFail($inquiryVendorId, $id);

        if ($iv->status !== 'sent') {
            throw new Service_Exception("Vendor must be in 'sent' status to mark as responded", 422);
        }

        $this->db->startTransaction();

        try {
            $now = date('Y-m-d H:i:s');
            $this->db->update('purchase_inquiry_vendors', [
                'status'       => 'responded',
                'responded_at' => $now,
                'updated_at'   => $now,
            ], "id = {$inquiryVendorId}");

            // Auto-transition inquiry: sent → partially_responded, partially_responded → fully_responded
            if ($inquiry->status === 'sent') {
                $this->db->update('purchase_inquiries', ['status' => 'partially_responded', 'updated_at' => $now], "id = {$id}");
                $inquiry->status = 'partially_responded';
            }

            if ($inquiry->status === 'partially_responded') {
                $totalActive    = (int) $this->db->fetchVar(
                    "SELECT COUNT(*) FROM purchase_inquiry_vendors
                     WHERE inquiry_id = ? AND status NOT IN ('rejected', 'awarded')",
                    [$id]
                );
                $respondedCount = (int) $this->db->fetchVar(
                    "SELECT COUNT(*) FROM purchase_inquiry_vendors
                     WHERE inquiry_id = ? AND status = 'responded'",
                    [$id]
                );
                if ($totalActive > 0 && $respondedCount >= $totalActive) {
                    $this->db->update('purchase_inquiries', ['status' => 'fully_responded', 'updated_at' => $now], "id = {$id}");
                    $inquiry->status = 'fully_responded';
                }
            }

            $this->logHistory($id, [
                'log_type' => 'vendor_responded',
                'title'    => "Vendor responded: {$iv->vendor_name}",
                'meta'     => ['vendor_id' => $iv->vendor_id, 'vendor_name' => $iv->vendor_name],
            ]);

            $this->db->commit();

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function withdrawVendorQuote(int $id, int $inquiryVendorId): void
    {
        if (!$this->context->canDo('purchase_inquiries', 'write')) {
            throw new Service_Exception("You do not have permission to withdraw vendor quotes", 403);
        }

        $inquiry = $this->getInquiryOrFail($id);
        $iv      = $this->getInquiryVendorOrFail($inquiryVendorId, $id);

        if ($iv->status !== 'responded') {
            throw new Service_Exception("Quote can only be withdrawn for a vendor in 'responded' status", 422);
        }

        $quote = $this->getVendorQuoteOrFail($iv->vendor_id, $id);
        if ($quote->quote_status !== 'submitted') {
            throw new Service_Exception("Quote is not in 'submitted' status", 422);
        }

        $this->db->startTransaction();

        try {
            $now = date('Y-m-d H:i:s');
            $this->db->update('purchase_vendor_quotes', [
                'quote_status' => 'withdrawn',
                'updated_at'   => $now,
            ], "id = {$quote->id}");

            $this->db->update('purchase_inquiry_vendors', [
                'status'     => 'sent',
                'updated_at' => $now,
            ], "id = {$inquiryVendorId}");

            $this->logHistory($id, [
                'log_type' => 'vendor_quote_withdrawn',
                'title'    => "Quote withdrawn: {$iv->vendor_name}",
                'meta'     => ['vendor_id' => $iv->vendor_id, 'vendor_name' => $iv->vendor_name],
            ]);

            $this->db->commit();

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getComparison(int $id): array
    {
        if (!$this->context->canAccess('purchase_inquiries')) {
            throw new Service_Exception("You do not have permission to view this inquiry", 403);
        }

        $inquiry = $this->getInquiryOrFail($id);

        $rows = $this->db->fetchAll(
            "SELECT
               pii.id AS item_id, pii.sort_order, pii.product_id,
               pii.product_name, pii.product_sku, pii.required_qty, pii.uom_code,
               piv.id AS inquiry_vendor_id, piv.vendor_id, piv.vendor_name, piv.status AS vendor_status,
               pvq.id AS quote_id, pvq.quote_status, pvq.grand_total AS vendor_quote_total,
               pvqi.can_supply, pvqi.unit_price, pvqi.tax_amount, pvqi.line_total, pvqi.notes AS item_notes
             FROM purchase_inquiry_items pii
             JOIN purchase_inquiry_vendors piv ON piv.inquiry_id = pii.inquiry_id
             LEFT JOIN purchase_vendor_quotes pvq
               ON pvq.vendor_id = piv.vendor_id AND pvq.inquiry_id = pii.inquiry_id AND pvq.quote_status = 'submitted'
             LEFT JOIN purchase_vendor_quote_items pvqi
               ON pvqi.quote_id = pvq.id AND pvqi.inquiry_item_id = pii.id
             WHERE pii.inquiry_id = ?
             ORDER BY pii.sort_order, pii.id, piv.id",
            [$id]
        );

        // Pivot by item_id → vendor_id
        $itemsMap   = [];
        $vendorTotals = [];

        foreach ($rows as $row) {
            $itemId   = (int) $row->item_id;
            $vendorId = (int) $row->vendor_id;

            if (!isset($itemsMap[$itemId])) {
                $itemsMap[$itemId] = [
                    'item_id'      => $itemId,
                    'sort_order'   => $row->sort_order,
                    'product_id'   => $row->product_id,
                    'product_name' => $row->product_name,
                    'product_sku'  => $row->product_sku,
                    'required_qty' => $row->required_qty,
                    'uom_code'     => $row->uom_code,
                    'vendors'      => [],
                ];
            }

            if (!isset($vendorTotals[$vendorId])) {
                $vendorTotals[$vendorId] = [
                    'vendor_id'           => $vendorId,
                    'vendor_name'         => $row->vendor_name,
                    'vendor_status'       => $row->vendor_status,
                    'inquiry_vendor_id'   => $row->inquiry_vendor_id,
                    'vendor_quote_total'  => $row->vendor_quote_total,
                ];
            }

            $itemsMap[$itemId]['vendors'][$vendorId] = [
                'vendor_id'         => $vendorId,
                'vendor_name'       => $row->vendor_name,
                'inquiry_vendor_id' => $row->inquiry_vendor_id,
                'quote_id'          => $row->quote_id,
                'can_supply'        => $row->can_supply,
                'unit_price'        => $row->unit_price,
                'tax_amount'        => $row->tax_amount,
                'line_total'        => $row->line_total,
                'item_notes'        => $row->item_notes,
                'is_lowest_price'   => false,
            ];
        }

        // Mark lowest price per item
        foreach ($itemsMap as &$item) {
            $lowestPrice   = null;
            $lowestVendors = [];

            foreach ($item['vendors'] as $vId => $vData) {
                if ($vData['unit_price'] === null) continue;
                if ((int) $vData['can_supply'] === 0) continue;

                $price = (float) $vData['unit_price'];
                if ($lowestPrice === null || $price < $lowestPrice) {
                    $lowestPrice   = $price;
                    $lowestVendors = [$vId];
                } else if ($price == $lowestPrice) {
                    $lowestVendors[] = $vId;
                }
            }

            foreach ($lowestVendors as $vId) {
                $item['vendors'][$vId]['is_lowest_price'] = true;
            }

            $item['vendors'] = array_values($item['vendors']);
        }

        return [
            'inquiry' => [
                'id'             => $inquiry->id,
                'inquiry_number' => $inquiry->inquiry_number,
                'status'         => $inquiry->status,
            ],
            'items'   => array_values($itemsMap),
            'vendors' => array_values($vendorTotals),
        ];
    }

    public function award(int $id, int $inquiryVendorId): int
    {
        if (!$this->context->canDo('purchase_inquiries', 'award')) {
            throw new Service_Exception("You do not have permission to award purchase inquiries", 403);
        }

        $inquiry = $this->getInquiryOrFail($id);
        $iv      = $this->getInquiryVendorOrFail($inquiryVendorId, $id);

        if (!in_array($inquiry->status, ['sent', 'partially_responded', 'fully_responded'])) {
            throw new Service_Exception("Inquiry must be in 'sent', 'partially_responded', or 'fully_responded' status to award", 422);
        }

        if (!in_array($iv->status, ['sent', 'responded'])) {
            throw new Service_Exception("Can only award to a vendor in 'sent' or 'responded' status", 422);
        }

        // Load quote if it exists (not required)
        $existingQuoteRow = $this->db->fetchOne(
            "SELECT id FROM purchase_vendor_quotes WHERE vendor_id = ? AND inquiry_id = ? AND quote_status = 'submitted'",
            [$iv->vendor_id, $id]
        );
        $quote = null;
        if ($existingQuoteRow) {
            $quote = new Models_PurchaseVendorQuote((int) $existingQuoteRow->id);
            if ($quote->isEmpty) $quote = null;
        }

        $this->db->startTransaction();

        try {
            $companyId = $this->context->companyId;
            $userId    = $this->context->userId;
            $now       = date('Y-m-d H:i:s');

            // Generate PO number
            $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));
            $poNumber   = $seqService->nextCommit("purchase_orders");

            // Resolve vendor billing address snapshot
            $vendor      = new Models_Vendor($iv->vendor_id);
            $billingAddr = $vendor->getBillingAddress();
            $vendorAddrSnapshot = !empty($billingAddr) ? json_encode($billingAddr, JSON_UNESCAPED_UNICODE) : null;

            // Calculate expected delivery date
            $expectedDeliveryDate = null;
            if ($quote && !empty($quote->lead_time_days)) {
                $expectedDeliveryDate = date('Y-m-d', strtotime("+{$quote->lead_time_days} days"));
            } else if (!empty($inquiry->required_by_date)) {
                $expectedDeliveryDate = $inquiry->required_by_date;
            }

            // Combine freight + other charges from quote into the single PO adjustment field.
            // The PO has one adjustment slot; both charges affect grand total so they are merged.
            $freightCharges  = $quote ? (float) $quote->freight_charges : 0.0;
            $otherCharges    = $quote ? (float) $quote->other_charges   : 0.0;
            $adjustmentAmt   = round($freightCharges + $otherCharges, 4);
            if ($adjustmentAmt > 0 && $quote) {
                if ($freightCharges > 0 && $otherCharges > 0) {
                    $adjustmentLabel = 'Freight & Other Charges';
                } elseif ($freightCharges > 0) {
                    $adjustmentLabel = 'Freight';
                } else {
                    $adjustmentLabel = $quote->other_charges_label ?: null;
                }
            } else {
                $adjustmentLabel = null;
            }

            // Insert purchase order
            $poData = [
                'inquiry_id'              => $id,
                'company_id'              => $companyId,
                'company_location_id'      => $inquiry->company_location_id,
                'vendor_id'               => $iv->vendor_id,
                'currency_code'           => $vendor->currency_code ?? 'INR',
                'po_number'               => $poNumber,
                'receiving_warehouse_id'  => !Service_CompanySettings::isMultiWarehouseEnabled($companyId)
                                                ? (Service_Company::getDefaultWarehouseId($companyId) ?? null)
                                                : null,
                'status'                  => 'draft',
                'order_date'              => date('Y-m-d'),
                'expected_delivery_date'  => $expectedDeliveryDate,
                'payment_term_id'         => $quote ? $quote->payment_term_id : null,
                'payment_terms'           => $quote ? $quote->payment_terms_snapshot : null,
                'notes'                   => $quote ? $quote->vendor_quote_notes : null,
                'adjustment_label'        => $adjustmentLabel,
                'adjustment_amount'       => $adjustmentAmt,
                'vendor_address_snapshot' => $vendorAddrSnapshot,
                'created_by'              => $userId,
                'created_at'              => $now,
                'updated_at'              => $now,
            ];

            $poId = (int) $this->db->insert('purchase_orders', $poData);
            if (!$poId) {
                throw new Service_Exception("Failed to create purchase order");
            }

            // Load inquiry items for snapshot data
            $inquiryItems = $this->db->fetchAll(
                "SELECT * FROM purchase_inquiry_items WHERE inquiry_id = ? ORDER BY sort_order, id",
                [$id]
            );
            $inquiryItemsById = [];
            foreach ($inquiryItems as $ii) {
                $inquiryItemsById[(int) $ii->id] = $ii;
            }

            $poSubtotal      = 0.0;
            $poItemDiscounts = 0.0;
            $poTaxTotal      = 0.0;
            $lineItemRows    = [];

            if ($quote) {
                $quoteItems = $this->db->fetchAll(
                    "SELECT pvqi.*, pii.product_uom_id, pu.conversion_factor
                     FROM purchase_vendor_quote_items pvqi
                     JOIN purchase_inquiry_items pii ON pii.id = pvqi.inquiry_item_id
                     LEFT JOIN product_uoms pu ON pu.id = pii.product_uom_id
                     WHERE pvqi.quote_id = ? AND pvqi.can_supply = 1",
                    [$quote->id]
                );

                foreach ($quoteItems as $qi) {
                    $ii = $inquiryItemsById[(int) $qi->inquiry_item_id] ?? null;
                    if (!$ii) continue;

                    $lineSubtotal  = (float) $ii->required_qty * (float) $qi->unit_price;
                    $discountAmt   = (float) $qi->discount_amount;
                    $lineTotal     = (float) $qi->line_total;
                    $taxAmount     = (float) $qi->tax_amount;
                    $taxableAmount = round($lineTotal - $taxAmount, 4);

                    $poSubtotal      += $lineSubtotal;
                    $poItemDiscounts += $discountAmt;
                    $poTaxTotal      += $taxAmount;

                    $lineItemRows[] = [
                        'product_id'                 => $ii->product_id,
                        'product_name'               => $ii->product_name,
                        'product_sku'                => $ii->product_sku,
                        'description'                => $ii->description,
                        'product_uom_id'             => $qi->product_uom_id,
                        'conversion_factor_snapshot' => $qi->conversion_factor ?? 1,
                        'uom_code'                   => $ii->uom_code,
                        'ordered_qty'                => $ii->required_qty,
                        'unit_price'                 => $qi->unit_price,
                        'discount_amount'            => $qi->discount_amount,
                        'discount_info'              => $qi->discount_info,
                        'taxable_amount'             => $taxableAmount,
                        'tax_amount'                 => $taxAmount,
                        'tax_info'                   => $qi->tax_info,
                        'line_total'                 => $lineTotal,
                    ];
                }
            } else {
                // No quote — create PO items from inquiry items at ₹0 for the user to fill in
                foreach ($inquiryItems as $ii) {
                    $puRow = $this->db->fetchOne(
                        "SELECT conversion_factor FROM product_uoms WHERE id = ? LIMIT 1",
                        [$ii->product_uom_id]
                    );
                    $lineItemRows[] = [
                        'product_id'                 => $ii->product_id,
                        'product_name'               => $ii->product_name,
                        'product_sku'                => $ii->product_sku,
                        'description'                => $ii->description,
                        'product_uom_id'             => $ii->product_uom_id,
                        'conversion_factor_snapshot' => $puRow ? ($puRow->conversion_factor ?? 1) : 1,
                        'uom_code'                   => $ii->uom_code,
                        'ordered_qty'                => $ii->required_qty,
                        'unit_price'                 => 0,
                        'discount_amount'            => 0,
                        'discount_info'              => null,
                        'taxable_amount'             => 0,
                        'tax_amount'                 => 0,
                        'tax_info'                   => null,
                        'line_total'                 => 0,
                    ];
                }
            }

            // Collect names of items vendor cannot supply (can_supply=0) for audit trail
            $skippedItemNames = [];
            if ($quote) {
                $skippedRows = $this->db->fetchAll(
                    "SELECT pvqi.inquiry_item_id FROM purchase_vendor_quote_items pvqi
                     WHERE pvqi.quote_id = ? AND pvqi.can_supply = 0",
                    [$quote->id]
                );
                foreach ($skippedRows as $skip) {
                    $ii = $inquiryItemsById[(int) $skip->inquiry_item_id] ?? null;
                    if ($ii) {
                        $skippedItemNames[] = $ii->product_name;
                    }
                }
            }

            $poService = new Service_Po_Order($this->context);
            $poService->insertLineItemsPrecomputed($poId, $userId, $lineItemRows);

            $poRawSubtotal      = round($poSubtotal, 4);
            $poItemDiscTotal    = round($poItemDiscounts, 4);
            $poSubAfterItemDisc = round($poRawSubtotal - $poItemDiscTotal, 4);
            $poTaxRounded       = round($poTaxTotal, 4);
            $poGrandTotal       = round($poSubAfterItemDisc + $poTaxRounded + $adjustmentAmt, 4);

            $this->db->update('purchase_orders', [
                'subtotal'                     => $poRawSubtotal,
                'item_discount_total'          => $poItemDiscTotal,
                'subtotal_after_item_discount' => $poSubAfterItemDisc,
                'tax_amount'                   => $poTaxRounded,
                'adjustment_label'             => $adjustmentLabel,
                'adjustment_amount'            => $adjustmentAmt,
                'grand_total'                  => $poGrandTotal,
                'updated_at'                   => $now,
            ], "id = {$poId}");

            // Log PO creation history
            $poService->logHistory($poId, [
                'log_type'       => 'created',
                'title'          => "Order created #{$poNumber}",
                'reference_type' => 'purchase_inquiry',
                'reference_id'   => $id,
                'meta'           => [
                    'status'         => 'draft',
                    'source'         => 'purchase_inquiry',
                    'inquiry_number' => $inquiry->inquiry_number,
                    'item_count'     => count($inquiryItems),
                    'skipped_items'  => !empty($skippedItemNames) ? $skippedItemNames : null,
                ],
            ]);

            // Update winning vendor
            $this->db->update('purchase_inquiry_vendors', [
                'status'     => 'awarded',
                'po_id'      => $poId,
                'updated_at' => $now,
            ], "id = {$inquiryVendorId}");

            // Reject all other active vendors and their quotes
            $this->db->query(
                "UPDATE purchase_inquiry_vendors SET status = 'rejected', updated_at = ?
                 WHERE inquiry_id = ? AND id != ? AND status IN ('sent','responded')",
                [$now, $id, $inquiryVendorId]
            );

            $this->db->query(
                "UPDATE purchase_vendor_quotes SET quote_status = 'rejected', updated_at = ?
                 WHERE inquiry_id = ? AND vendor_id != ? AND quote_status = 'submitted'",
                [$now, $id, $iv->vendor_id]
            );

            // Accept winning quote if one exists
            if ($quote) {
                $this->db->update('purchase_vendor_quotes', [
                    'quote_status' => 'accepted',
                    'updated_at'   => $now,
                ], "id = {$quote->id}");
            }

            // Transition inquiry to awarded
            $this->db->update('purchase_inquiries', [
                'status'     => 'awarded',
                'awarded_at' => $now,
                'updated_at' => $now,
            ], "id = {$id}");
            $inquiry->status = 'awarded';

            // Get rejected vendors for history
            $rejectedVendors = $this->db->fetchAll(
                "SELECT vendor_id, vendor_name FROM purchase_inquiry_vendors
                 WHERE inquiry_id = ? AND id != ? AND status = 'rejected'",
                [$id, $inquiryVendorId]
            );
            $rejectedList = array_map(fn($v) => ['vendor_id' => $v->vendor_id, 'vendor_name' => $v->vendor_name], $rejectedVendors);

            $this->logHistory($id, [
                'log_type'       => 'vendor_awarded',
                'title'          => "Awarded to {$iv->vendor_name} — PO {$poNumber} created",
                'meta'           => ['vendor_id' => $iv->vendor_id, 'vendor_name' => $iv->vendor_name, 'po_id' => $poId, 'po_number' => $poNumber],
                'reference_type' => 'purchase_order',
                'reference_id'   => $poId,
            ]);

            if (!empty($rejectedList)) {
                $this->logHistory($id, [
                    'log_type' => 'vendor_rejected',
                    'title'    => count($rejectedList) . " other vendor(s) rejected",
                    'meta'     => ['vendors' => $rejectedList],
                ]);
            }

            $this->db->commit();
            return $poId;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function cancel(int $id): void
    {
        if (!$this->context->canDo('purchase_inquiries', 'cancel')) {
            throw new Service_Exception("You do not have permission to cancel purchase inquiries", 403);
        }

        $inquiry = $this->getInquiryOrFail($id);

        if ($inquiry->status === 'awarded') {
            throw new Service_Exception("An awarded inquiry cannot be cancelled", 422);
        }
        if ($inquiry->status === 'cancelled') {
            throw new Service_Exception("This inquiry is already cancelled", 422);
        }

        $this->db->startTransaction();

        try {
            $now = date('Y-m-d H:i:s');
            $this->db->update('purchase_inquiries', [
                'status'     => 'cancelled',
                'updated_at' => $now,
            ], "id = {$id}");
            $inquiry->status = 'cancelled';

            $this->logHistory($id, [
                'log_type' => 'cancelled',
                'title'    => 'Inquiry cancelled',
            ]);

            $this->db->commit();

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getDetails(int $id): array
    {
        if (!$this->context->canAccess('purchase_inquiries')) {
            throw new Service_Exception("You do not have permission to view this inquiry", 403);
        }

        $inquiry = $this->getInquiryOrFail($id);

        $items = $this->db->fetchAll(
            "SELECT * FROM purchase_inquiry_items WHERE inquiry_id = ? ORDER BY sort_order, id",
            [$id]
        );

        $vendors = $this->db->fetchAll(
            "SELECT piv.*,
                    pvq.id AS quote_id,
                    pvq.quote_status,
                    pvq.grand_total AS quote_grand_total,
                    pvq.vendor_quote_number,
                    pvq.vendor_quote_date,
                    (SELECT COUNT(*) FROM purchase_vendor_quote_items pvqi WHERE pvqi.quote_id = pvq.id) AS quote_item_count,
                    (SELECT COUNT(*) FROM purchase_vendor_quote_items pvqi2 WHERE pvqi2.quote_id = pvq.id AND pvqi2.can_supply = 1 AND pvqi2.unit_price > 0) AS priced_item_count
             FROM purchase_inquiry_vendors piv
             LEFT JOIN purchase_vendor_quotes pvq ON pvq.vendor_id = piv.vendor_id AND pvq.inquiry_id = piv.inquiry_id
             WHERE piv.inquiry_id = ?
             ORDER BY piv.created_at",
            [$id]
        );

        $totalItems = count($items);
        foreach ($vendors as &$v) {
            $v->address_snapshot = $v->vendor_address_snapshot ? json_decode($v->vendor_address_snapshot, true) : null;
            $v->total_items      = $totalItems;
        }

        $createdBy = $this->db->fetchOne(
            "SELECT CONCAT(first_name, ' ', last_name) AS name FROM users WHERE id = ?",
            [$inquiry->created_by]
        );

        return [
            'inquiry'     => array_merge((array) $inquiry->toArray(), [
                'created_by_name' => $createdBy ? $createdBy->name : null,
            ]),
            'items'       => $items,
            'vendors'     => $vendors,
            'total_items' => $totalItems,
        ];
    }

    public function getList(array $filters = []): array
    {
        if (!$this->context->canAccess('purchase_inquiries')) {
            throw new Service_Exception("You do not have permission to view purchase inquiries", 403);
        }

        $companyId = $this->context->companyId;
        $where     = ["pi.company_id = {$companyId}"];
        $params    = [];

        if (!empty($filters['status'])) {
            $statuses = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));
            $where[] = "pi.status IN ({$placeholders})";
            $params  = array_merge($params, $statuses);
        }

        if (!empty($filters['vendor_id'])) {
            $where[] = "EXISTS (SELECT 1 FROM purchase_inquiry_vendors piv WHERE piv.inquiry_id = pi.id AND piv.vendor_id = ?)";
            $params[] = (int) $filters['vendor_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "DATE(pi.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(pi.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['required_by_from'])) {
            $where[] = "pi.required_by_date >= ?";
            $params[] = $filters['required_by_from'];
        }
        if (!empty($filters['required_by_to'])) {
            $where[] = "pi.required_by_date <= ?";
            $params[] = $filters['required_by_to'];
        }

        if (!empty($filters['search'])) {
            $search  = '%' . $filters['search'] . '%';
            $where[] = "(pi.inquiry_number LIKE ? OR pi.title LIKE ?)";
            $params[] = $search;
            $params[] = $search;
        }

        $whereClause = implode(' AND ', $where);

        $rows = $this->db->fetchAll(
            "SELECT pi.id, pi.inquiry_number, pi.title, pi.required_by_date, pi.status,
                    pi.created_at, pi.awarded_at,
                    (SELECT COUNT(*) FROM purchase_inquiry_items pii WHERE pii.inquiry_id = pi.id) AS item_count,
                    (SELECT COUNT(*) FROM purchase_inquiry_vendors piv WHERE piv.inquiry_id = pi.id) AS vendor_count,
                    (SELECT COUNT(*) FROM purchase_inquiry_vendors piv2 WHERE piv2.inquiry_id = pi.id AND piv2.status = 'responded') AS responded_count,
                    u.first_name, u.last_name
             FROM purchase_inquiries pi
             LEFT JOIN users u ON u.id = pi.created_by
             WHERE {$whereClause}
             ORDER BY pi.created_at DESC",
            $params
        );

        return $rows;
    }

    public function getHistory(int $id): array
    {
        if (!$this->context->canAccess('purchase_inquiries')) {
            throw new Service_Exception("You do not have permission to view this inquiry", 403);
        }

        $this->getInquiryOrFail($id);

        $rows = $this->db->fetchAll(
            "SELECT h.*, CONCAT(u.first_name, ' ', u.last_name) AS created_by_name
             FROM purchase_inquiry_history h
             LEFT JOIN users u ON u.id = h.created_by
             WHERE h.inquiry_id = ?
             ORDER BY h.created_at DESC",
            [$id]
        );

        foreach ($rows as &$row) {
            $row->meta = $row->meta ? json_decode($row->meta, true) : null;
        }

        return $rows;
    }

    public function getEmailDefaults(int $id, int $vendorId): array
    {
        if (!$this->context->canAccess('purchase_inquiries')) {
            throw new Service_Exception("You do not have permission to view this inquiry", 403);
        }

        $inquiry = $this->getInquiryOrFail($id);

        $vendor = new Models_Vendor($vendorId);
        if ($vendor->isEmpty || $vendor->company_id != $this->context->companyId) {
            throw new Service_Exception("Vendor not found", 404);
        }

        $company = new Models_Company($inquiry->company_id);

        // For resend: prefer the stored contact email from purchase_inquiry_vendors
        $iv = $this->db->fetchOne(
            "SELECT vendor_contact_name, vendor_contact_email FROM purchase_inquiry_vendors WHERE inquiry_id = ? AND vendor_id = ?",
            [$id, $vendorId]
        );

        $to = '';
        if ($iv && !empty($iv->vendor_contact_email)) {
            $to = $iv->vendor_contact_email;
        } elseif (!empty($vendor->email)) {
            $to = $vendor->email;
        }

        $emailSvc  = new Service_EmailConfig($this->context);
        $docConfig = $emailSvc->getDocConfig('rfq');

        $user = new Models_User($this->context->userId);

        $addrParts    = [];
        if (!empty($company->address)) $addrParts[] = nl2br($company->address);
        $cityZip      = trim(($company->city ?? '') . ' - ' . ($company->zipcode ?? ''), ' -');
        if ($cityZip !== '') $addrParts[] = $cityZip;
        $stateCountry = trim(($company->state ?? '') . ', ' . ($company->country ?? ''), ', ');
        if ($stateCountry !== '') $addrParts[] = $stateCountry;

        $tokens = [
            '{company_name}'     => $company->name                                                                             ?? '',
            '{company_address}'  => implode('<br>', $addrParts),
            '{user_name}'        => !$user->isEmpty ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '',
            '{user_email}'       => !$user->isEmpty ? ($user->email  ?? '') : '',
            '{user_mobile}'      => !$user->isEmpty ? ($user->phone  ?? '') : '',
            '{po_number}'        => $inquiry->inquiry_number                                                                   ?? '',
            '{vendor_name}'      => $vendor->display_name                                                                     ?? '',
            '{order_date}'       => $inquiry->required_by_date                                                                 ?? '',
            '{required_by_date}' => $inquiry->required_by_date                                                                 ?? '',
        ];

        $subject = !empty($docConfig['email_subject'])
            ? str_replace(array_keys($tokens), array_values($tokens), $docConfig['email_subject'])
            : "Purchase Inquiry {$inquiry->inquiry_number} — {$company->name}";

        $body = !empty($docConfig['email_body'])
            ? str_replace(array_keys($tokens), array_values($tokens), $docConfig['email_body'])
            : "Dear {$vendor->display_name},<br><br>Please find attached our Purchase Inquiry {$inquiry->inquiry_number}.<br><br>Kindly provide your best quotation at the earliest.<br><br>Regards,<br>{$company->name}";

        $pdfBytes = $this->buildInquiryPdf($inquiry);

        return [
            'to'         => $to,
            'subject'    => $subject,
            'body'       => $body,
            'cc'         => $docConfig['email_cc']  ?? '',
            'bcc'        => $docConfig['email_bcc'] ?? '',
            'attachment' => [
                'name'      => "{$inquiry->inquiry_number}.pdf",
                'mime_type' => 'application/pdf',
                'content'   => base64_encode($pdfBytes),
            ],
        ];
    }
}
?>
