<?php
class Service_So_ProformaInvoice extends Service_Base {

    private function getProformaOrFail(int $proformaId): Models_SalesProformaInvoice {
        $pf = new Models_SalesProformaInvoice($proformaId);
        if ($pf->isEmpty) {
            throw new Service_Exception("Proforma invoice not found", 404);
        }
        if ($pf->company_id != $this->context->companyId) {
            throw new Service_Exception("You do not have permission to access this proforma invoice", 403);
        }
        return $pf;
    }


    public function logHistory(int $proformaId, array $payload): int {
        $meta = empty($payload['meta']) ? null : json_encode($payload['meta'], JSON_UNESCAPED_UNICODE);
        $h = new Models_SalesProformaInvoiceHistory();
        $h->company_id                = $this->context->companyId;
        $h->sales_proforma_invoice_id = $proformaId;
        $h->log_type                  = $payload['log_type'];
        $h->title                     = $payload['title'];
        $h->meta                      = $meta;
        $h->created_by                = $this->context->userId;
        $id = (int) $h->create();
        if (!$id) throw new Service_Exception("Failed to log proforma invoice history");
        return $id;
    }


    private function validateEmailList(string $list): bool {
        $addrs = array_filter(array_map('trim', explode(',', $list)));
        if (empty($addrs)) return false;
        foreach ($addrs as $addr) {
            if (!filter_var($addr, FILTER_VALIDATE_EMAIL)) return false;
        }
        return true;
    }


    private function buildPdfBytes(int $proformaId): string {
        $data        = $this->get($proformaId);
        $settingsSvc = new Service_CompanySettings($this->context);
        $snapshotDecl = $data['invoice_declaration'] ?? '';
        $data['settings'] = [
            'show_amount_in_words' => (bool)(int) $settingsSvc->get('doc_config.proforma_invoice.show_amount_in_words', 1),
            'show_signature'       => (bool)(int) $settingsSvc->get('doc_config.proforma_invoice.show_signature', 1),
            'show_bank_details'    => (bool)(int) $settingsSvc->get('doc_config.proforma_invoice.show_bank_details', 1),
            'bank_details'         => (string) $settingsSvc->get('bank_details_1', ''),
            'declaration'          => ($snapshotDecl !== '' && $snapshotDecl !== null)
                                        ? $snapshotDecl
                                        : (string) $settingsSvc->get('doc_declaration.proforma_invoice', ''),
        ];
        $watermark   = ($data['status'] ?? '') === 'cancelled' ? 'CANCELLED' : '';
        $emailConfig = new Service_EmailConfig($this->context);
        $templateKey = $emailConfig->getPdfTemplate('proforma_invoice');
        $registry    = config('pdf_templates.proforma_invoice', []);
        $view        = $registry[$templateKey]['view'] ?? $registry['template_1']['view'] ?? 'pdf.proforma-invoice';
        return Helpers_Pdf::render($view, ['printData' => $data], ['watermark' => $watermark]);
    }


    private function resolveDefaultPiTerms(Models_SalesOrder $so): ?string {
        $settingsSvc = new Service_CompanySettings($this->context);
        $inheritFromSO = (bool)(int) $settingsSvc->get('doc_config.proforma_invoice.tc_inherit_from_so', 1);
        if ($inheritFromSO) {
            if (!empty($so->so_terms)) return $so->so_terms;
            return ((string) $settingsSvc->get('doc_terms.sales_order', '') ?: null);
        }
        return ((string) $settingsSvc->get('doc_terms.proforma_invoice', '') ?: null);
    }


    public function getFormContext(int $soId): array {
        if (!$this->context->canDo('proforma_invoices', 'create')) {
            throw new Service_Exception("You do not have permission to create proforma invoices", 403);
        }
        if (!Service_CompanySettings::isProformaInvoiceEnabled($this->context->companyId)) {
            throw new Service_Exception("Proforma invoice feature is not enabled for your company", 403);
        }

        $companyId = $this->context->companyId;

        $so = new Models_SalesOrder($soId);
        if ($so->isEmpty || $so->company_id != $companyId) {
            throw new Service_Exception("Sales order not found", 404);
        }
        if (!in_array($so->status, ['draft', 'confirmed'])) {
            throw new Service_Exception("Proforma invoices can only be created for open or confirmed sales orders", 422);
        }

        $sql = "SELECT
                    soi.id, soi.product_id,
                    COALESCE(soi.product_name, p.name) AS product_name,
                    COALESCE(soi.product_sku, p.sku) AS sku,
                    soi.tax_classification_type, soi.tax_classification_code,
                    soi.description, soi.ordered_qty, soi.unit_price,
                    soi.discount_amount, soi.discount_info,
                    soi.taxable_amount, soi.tax_amount, soi.tax_info,
                    soi.line_total, soi.uom_code, soi.product_uom_id
                FROM sales_order_items soi
                LEFT JOIN products p ON p.id = soi.product_id
                WHERE soi.sales_order_id = ?
                ORDER BY soi.id ASC";
        $soItems = $this->db->fetchAll($sql, [$soId]);

        $items = [];
        foreach ($soItems as $row) {
            $discountInfo = $row->discount_info ? json_decode($row->discount_info, true) : null;
            $taxInfo      = $row->tax_info      ? json_decode($row->tax_info, true)      : [];
            $items[] = [
                'sales_order_item_id'    => (int) $row->id,
                'product_id'             => (int) $row->product_id,
                'product_name'           => $row->product_name,
                'sku'                    => $row->sku,
                'tax_classification_type'=> $row->tax_classification_type,
                'tax_classification_code'=> $row->tax_classification_code,
                'description'            => $row->description,
                'quantity'               => (float) $row->ordered_qty,
                'product_uom_id'         => $row->product_uom_id ? (int) $row->product_uom_id : null,
                'uom_code'               => $row->uom_code,
                'unit_price'             => (float) $row->unit_price,
                'discount_amount'        => (float) $row->discount_amount,
                'discount_info'          => $discountInfo,
                'taxable_amount'         => (float) $row->taxable_amount,
                'tax_amount'             => (float) $row->tax_amount,
                'tax_info'               => $taxInfo,
                'line_total'             => (float) $row->line_total,
            ];
        }

        // Enrich tax_info with gst_component so the PI preview compute call
        // can run GST summary without a DB query (one bulk fetch here at form-open time).
        $allTaxIds = [];
        foreach ($items as $item) {
            foreach ($item['tax_info'] as $t) {
                if (!empty($t['id'])) $allTaxIds[] = (int) $t['id'];
            }
        }
        if (!empty($allTaxIds)) {
            $unique = array_unique($allTaxIds);
            $ph     = implode(',', array_fill(0, count($unique), '?'));
            $gcRows = $this->db->fetchAll("SELECT id, gst_component FROM taxes WHERE id IN ($ph)", array_values($unique));
            $gcMap  = [];
            foreach ($gcRows as $gcRow) {
                $gcMap[$gcRow->id] = $gcRow->gst_component;
            }
            foreach ($items as &$item) {
                foreach ($item['tax_info'] as &$t) {
                    $t['gst_component'] = $gcMap[$t['id'] ?? 0] ?? 'none';
                }
                unset($t);
            }
            unset($item);
        }

        $billingAddress = [];
        if (!empty($so->billing_address_snapshot)) {
            $billingAddress = json_decode($so->billing_address_snapshot, true) ?: [];
        }
        $soCustomer = new Models_Customer((int) $so->customer_id);
        if (empty($billingAddress) && !$soCustomer->isEmpty) {
            $billingAddress = $soCustomer->getBillingAddress() ?: [];
        }
        $shippingAddress = [];
        if (!empty($so->shipping_address_snapshot)) {
            $shippingAddress = json_decode($so->shipping_address_snapshot, true) ?: [];
        }

        $customerSvc = new Service_Customer($this->context);
        $customerBillingAddresses = $soCustomer->isEmpty ? [] : $customerSvc->getBillingAddresses((int) $so->customer_id);
        $customerGstin = $soCustomer->isEmpty ? '' : ($soCustomer->gstin ?? '');

        $gstConfig  = require APP_PATH . '/config/indian_gst.php';
        $gstStates  = [];
        foreach ($gstConfig['states'] as $code => $info) {
            $gstStates[$code] = $info['name'];
        }

        $piSettings        = new Service_CompanySettings($this->context);
        $pfValidityDays    = (int) $piSettings->get('proforma_invoice_validity_days', 0);
        $defaultValidUntil = $pfValidityDays > 0 ? date('Y-m-d', strtotime("+{$pfValidityDays} days")) : null;

        return [
            'so_id'                       => (int) $so->id,
            'so_number'                   => $so->so_number,
            'customer_id'                 => (int) $so->customer_id,
            'customer_gstin'              => $customerGstin,
            'customer_gst_treatment'      => $soCustomer->gst_treatment ?? 'b2b',
            'customer_billing_addresses'  => $customerBillingAddresses,
            'gst_states'                  => $gstStates,
            'payment_terms_text'          => $so->payment_terms,
            'notes'                       => $so->notes,
            'place_of_supply_code'        => $so->place_of_supply_code,
            'place_of_supply_name'        => $so->place_of_supply_name,
            'billing_address_snapshot'    => $billingAddress,
            'shipping_address_snapshot'   => $shippingAddress,
            'default_terms_conditions'    => $this->resolveDefaultPiTerms($so),
            'proforma_date'               => date('Y-m-d'),
            'default_valid_until'         => $defaultValidUntil,
            'proforma_number_preview'     => (new Service_Sequence($this->context))->nextPreview('sales_proforma_invoices'),
            'subtotal'                    => (float) $so->subtotal,
            'item_discount_total'         => (float) $so->item_discount_total,
            'subtotal_after_item_discount'=> (float) $so->subtotal_after_item_discount,
            'order_discount_amount'       => (float) $so->order_discount_amount,
            'discount_total'              => (float) $so->discount_total,
            'discount_info'               => $so->discount_info ? json_decode($so->discount_info, true) : null,
            'tax_amount'                  => (float) $so->tax_amount,
            'round_off_amount'            => (float) $so->round_off_amount,
            'adjustment_label'            => $so->adjustment_label ?? null,
            'adjustment_amount'           => (float) ($so->adjustment_amount ?? 0),
            'grand_total'                 => (float) $so->grand_total,
            'items'                       => $items,
        ];
    }


    public function create(int $soId, array $data): array {

        $companyId = $this->context->companyId;

        if (!$this->context->canDo('proforma_invoices', 'create')) {
            throw new Service_Exception("You do not have permission to create proforma invoices", 403);
        }
        if (!Service_CompanySettings::isProformaInvoiceEnabled($companyId)) {
            throw new Service_Exception("Proforma invoice feature is not enabled for your company", 403);
        }

        $so = new Models_SalesOrder($soId);
        if ($so->isEmpty || $so->company_id != $this->context->companyId) {
            $this->addError("Sales order not found", "sales_order_id");
        } elseif ($so->status === 'cancelled') {
            $this->addError("This sales order has been cancelled. Proforma invoices cannot be created for cancelled orders.", "sales_order_id");
        } elseif (!in_array($so->status, ['draft', 'confirmed'])) {
            $this->addError("Proforma invoices can only be created for open quotations or confirmed sales orders.", "sales_order_id");
        }

        $submittedNumber  = trim($data['proforma_number'] ?? '');
        $suggestedNumber  = trim($data['proforma_number_suggested'] ?? '');
        $isCustomNumber   = $submittedNumber !== '' && $submittedNumber !== $suggestedNumber;
        if ($submittedNumber === '') {
            $this->addError("Proforma number is required", "proforma_number");
        } elseif ($isCustomNumber) {
            $duplicate = $this->db->fetchOne(
                "SELECT id FROM sales_proforma_invoices WHERE company_id = ? AND proforma_number = ? LIMIT 1",
                [$this->context->companyId, $submittedNumber]
            );
            if ($duplicate) {
                $this->addError("Proforma number '{$submittedNumber}' is already in use", "proforma_number");
            }
        }

        $proformaDate = trim($data['proforma_date'] ?? '');
        if (empty($proformaDate) || !strtotime($proformaDate)) {
            $this->addError("Proforma date is required", "proforma_date");
        }

        $validUntil = !empty($data['valid_until']) ? trim($data['valid_until']) : null;
        if ($validUntil !== null) {
            if (!strtotime($validUntil)) {
                $this->addError("Valid until date is invalid", "valid_until");
            } elseif (!empty($proformaDate) && strtotime($proformaDate) && strtotime($validUntil) < strtotime($proformaDate)) {
                $this->addError("Valid until date must be on or after the proforma date", "valid_until");
            }
        }

        $items = (array) ($data['items'] ?? []);
        $validItems = array_filter($items, fn($i) => !empty($i['product_id']) && (float)($i['quantity'] ?? 0) > 0);
        if (empty($validItems)) {
            $this->addError("At least one item with quantity greater than zero is required", "items");
        }

        if (!$so->isEmpty && !empty($items)) {
            $soItemRows = $this->db->fetchAll(
                "SELECT id, ordered_qty, COALESCE(product_name, '') AS product_name FROM sales_order_items WHERE sales_order_id = ?",
                [$soId]
            );
            $soItemQtys = [];
            foreach ($soItemRows as $r) {
                $soItemQtys[(int) $r->id] = ['qty' => (float) $r->ordered_qty, 'name' => $r->product_name];
            }
            foreach ($items as $item) {
                $soItemId = !empty($item['sales_order_item_id']) ? (int) $item['sales_order_item_id'] : 0;
                $qty      = (float) ($item['quantity'] ?? 0);
                if ($soItemId && isset($soItemQtys[$soItemId]) && $qty > $soItemQtys[$soItemId]['qty'] + 0.0001) {
                    $name = $item['product_name'] ?? $soItemQtys[$soItemId]['name'] ?? "Item";
                    $this->addError("Quantity for \"{$name}\" ({$qty}) cannot exceed the sales order quantity ({$soItemQtys[$soItemId]['qty']})", "items");
                }
            }
        }

        if ($this->hasErrors()) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $customer    = $this->db->fetchOne("SELECT gstin, gst_treatment FROM customers WHERE id = ? LIMIT 1", [(int) $so->customer_id]);
        $company     = $this->db->fetchOne("SELECT gstin, state FROM companies WHERE id = ? LIMIT 1", [$this->context->companyId]);
        $billingAddrRaw = $data['billing_address_snapshot'] ?? '{}';
        $billingAddr    = is_array($billingAddrRaw) ? $billingAddrRaw : (json_decode($billingAddrRaw, true) ?: []);
        // Customer GSTIN: use submitted override if provided, else fall back to customer profile GSTIN
        $submittedCustomerGstin = strtoupper(trim($data['customer_gstin'] ?? ''));
        $effectiveCustomerGstin = $submittedCustomerGstin ?: ($customer->gstin ?? '');

        $db = $this->db;
        $db->startTransaction();

        try {
            $seqSvc = new Service_Sequence($this->context);
            // Always commit the sequence to keep the counter moving forward.
            // If user provided a custom number, use that; otherwise use the committed sequence number.
            $committedNumber = $seqSvc->nextCommit('sales_proforma_invoices');
            $proformaNumber  = $isCustomNumber ? $submittedNumber : $committedNumber;

            // Re-check custom number inside tx to prevent race condition
            if ($isCustomNumber) {
                $duplicateInTx = $this->db->fetchOne(
                    "SELECT id FROM sales_proforma_invoices WHERE company_id = ? AND proforma_number = ? LIMIT 1",
                    [$this->context->companyId, $submittedNumber]
                );
                if ($duplicateInTx) {
                    $db->rollBack();
                    $this->addError("Proforma number '{$submittedNumber}' is already in use", "proforma_number");
                    return ['success' => false, 'errors' => $this->getErrors()];
                }
            }

            $billingSnapshot = null;
            if (!empty($data['billing_address_snapshot'])) {
                $addr = is_string($data['billing_address_snapshot'])
                    ? json_decode($data['billing_address_snapshot'], true)
                    : (array) $data['billing_address_snapshot'];
                if (!empty(array_filter($addr))) {
                    $billingSnapshot = json_encode($addr, JSON_UNESCAPED_UNICODE);
                }
            }

            $shippingSnapshot = null;
            if (!empty($data['shipping_address_snapshot'])) {
                $addr = is_string($data['shipping_address_snapshot'])
                    ? json_decode($data['shipping_address_snapshot'], true)
                    : (array) $data['shipping_address_snapshot'];
                if (!empty(array_filter($addr))) {
                    $shippingSnapshot = json_encode($addr, JSON_UNESCAPED_UNICODE);
                }
            }

            // Compute all financial totals server-side via Service_DocumentCompute.
            // Includes GST split, round_off, and grand_total — no trust in frontend numeric values.
            $piComputeItems = [];
            foreach ($items as $item) {
                if (empty($item['product_id']) || (float)($item['quantity'] ?? 0) <= 0) continue;
                $ti = $item['tax_info'] ?? [];
                if (is_string($ti)) $ti = json_decode($ti, true) ?: [];
                $piComputeItems[] = [
                    'product_id'              => (int) $item['product_id'],
                    'quantity'                => (float) $item['quantity'],
                    'unit_price'              => (float) ($item['unit_price'] ?? 0),
                    'item_discount'           => (float) ($item['discount_amount'] ?? 0),
                    'item_discount_type'      => 'flat',
                    'tax_info'                => $ti,
                    'tax_classification_code' => (string) ($item['tax_classification_code'] ?? ''),
                ];
            }
            $roCfg             = (new Service_CompanySettings($this->context))->getRoundOffConfig();
            $roundOffRequested = (float) ($data['round_off_amount'] ?? 0) != 0;
            $piOrderDiscRate   = (float) ($data['order_discount_rate'] ?? 0);
            $piAdjAmt          = (float) ($data['adjustment_amount'] ?? 0);
            $piAdjLabel        = (string) ($data['adjustment_label'] ?? '');

            // gst_config fields are resolved below after $gstFields is set

            $pf = new Models_SalesProformaInvoice();
            $pf->company_id                    = $this->context->companyId;
            $pf->proforma_number               = $proformaNumber;
            $pf->sales_order_id                = $soId;
            $pf->customer_id                   = (int) $so->customer_id;
            $pf->proforma_date                 = $proformaDate;
            $pf->valid_until                   = $validUntil;
            $pf->billing_address_snapshot      = $billingSnapshot;
            $pf->shipping_address_snapshot     = $shippingSnapshot;
            $paymentTermsText = null;
            if (!empty($data['payment_term_id'])) {
                $pt = new Models_PaymentTerm((int) $data['payment_term_id']);
                if (!$pt->isEmpty && $pt->company_id == $companyId) {
                    $paymentTermsText = $pt->name;
                }
            } elseif (!empty($data['payment_terms'])) {
                $paymentTermsText = trim($data['payment_terms']);
            }
            $pf->payment_terms                 = $paymentTermsText;

            $gstFields = Service_Gst::resolveForDocument(
                $billingAddr['gstin'] ?? '',
                $effectiveCustomerGstin,
                $billingAddr['state'] ?? '',
                $company->gstin ?? '',
                $company->state ?? '',
                $customer->gst_treatment ?? 'b2b'
            );
            $pf->place_of_supply_code    = $gstFields['place_of_supply_code'];
            $pf->place_of_supply_name    = $gstFields['place_of_supply_name'];
            $pf->supply_type             = $gstFields['supply_type'];
            $pf->customer_gstin_snapshot = $gstFields['customer_gstin_snapshot'];
            $pf->reverse_charge          = (int) ($data['reverse_charge'] ?? 0);

            // Run compute service now that gst_config fields (place_of_supply_code, supply_type) are resolved
            $piComputed = Service_DocumentCompute::saveCompute([
                'document_type'       => 'pi',
                'items'               => $piComputeItems,
                'order_discount'      => $piOrderDiscRate,
                'order_discount_type' => 'percentage',
                'adjustment_amount'   => $piAdjAmt,
                'adjustment_label'    => $piAdjLabel,
                'round_off_config'    => $roCfg,
                'round_off_requested' => $roundOffRequested,
                'gst_config'          => [
                    'company_gstin'        => $company->gstin ?? '',
                    'company_state'        => $company->state ?? '',
                    'place_of_supply_code' => $pf->place_of_supply_code,
                    'supply_type'          => $pf->supply_type,
                    'reverse_charge'       => (bool) $pf->reverse_charge,
                ],
            ], $this->db);

            $piSubtotal        = $piComputed['subtotal'];
            $piItemDiscTotal   = $piComputed['item_discount_total'];
            $piSubAfterDisc    = round($piSubtotal - $piItemDiscTotal, 4);
            $piOrderDiscAmt    = $piComputed['order_discount_amount'];
            $piDiscountTotal   = round($piItemDiscTotal + $piOrderDiscAmt, 4);
            $gstSummary        = $piComputed['gst_summary'];

            $pf->notes                         = !empty($data['notes']) ? trim($data['notes']) : null;
            $pf->invoice_terms                 = !empty($data['invoice_terms']) ? trim($data['invoice_terms']) : null;
            $pf->subtotal                      = $piSubtotal;
            $pf->item_discount_total           = $piItemDiscTotal;
            $pf->subtotal_after_item_discount  = $piSubAfterDisc;
            $pf->order_discount_amount         = $piOrderDiscAmt;
            $pf->discount_total                = $piDiscountTotal;
            $pf->discount_info                 = !empty($data['discount_info']) ? json_encode($data['discount_info'], JSON_UNESCAPED_UNICODE) : null;
            $pf->tax_amount                    = $piComputed['tax_display'];
            $pf->cgst_total                    = $piComputed['cgst_amount'];
            $pf->sgst_total                    = $piComputed['sgst_amount'];
            $pf->ugst_total                    = $piComputed['ugst_amount'];
            $pf->igst_total                    = $piComputed['igst_amount'];
            $pf->cess_total                    = $piComputed['cess_amount'];
            $pf->gst_summary                   = $gstSummary ? json_encode($gstSummary) : null;
            $pf->round_off_amount              = $piComputed['round_off'];
            $pf->adjustment_label              = $piAdjLabel ?: null;
            $pf->adjustment_amount             = $piAdjAmt;
            $pf->grand_total                   = $piComputed['grand_total'];
            $pf->status                        = 'draft';
            $pf->created_by                    = $this->context->userId;
            $pfId = $pf->create();
            if (!$pfId) throw new Service_Exception("Failed to create proforma invoice");

            // Save items using server-computed per-item values
            $computedItemsByIdx = [];
            $ciIdx = 0;
            foreach ($piComputed['items'] as $ci) {
                if (($ci['product_id'] ?? 0) > 0 && ($ci['quantity'] ?? 0) > 0) {
                    $computedItemsByIdx[$ciIdx++] = $ci;
                }
            }
            $validIdx = 0;
            foreach (array_values($items) as $idx => $item) {
                if (empty($item['product_id']) || (float)($item['quantity'] ?? 0) <= 0) continue;

                $ci           = $computedItemsByIdx[$validIdx] ?? [];
                $discountInfo = $item['discount_info'] ?? null;
                $taxInfo      = $item['tax_info']      ?? null;

                $pfItem = new Models_SalesProformaInvoiceItem();
                $pfItem->sales_proforma_invoice_id = $pfId;
                $pfItem->sales_order_item_id       = !empty($item['sales_order_item_id']) ? (int) $item['sales_order_item_id'] : null;
                $pfItem->product_id                = (int) $item['product_id'];
                $pfItem->product_name              = $item['product_name'] ?? null;
                $pfItem->sku                       = $item['sku'] ?? null;
                $pfItem->description               = $item['description'] ?? null;
                $pfItem->quantity                  = (float) $item['quantity'];
                $pfItem->product_uom_id            = !empty($item['product_uom_id']) ? (int) $item['product_uom_id'] : null;
                $pfItem->uom_code                  = $item['uom_code'] ?? null;
                $pfItem->unit_price                = (float) ($item['unit_price'] ?? 0);
                $pfItem->discount_amount           = $ci['item_discount_amount'] ?? (float) ($item['discount_amount'] ?? 0);
                $pfItem->discount_info             = $discountInfo ? json_encode($discountInfo, JSON_UNESCAPED_UNICODE) : null;
                $pfItem->taxable_amount            = $ci['taxable_amount'] ?? (float) ($item['taxable_amount'] ?? 0);
                $pfItem->tax_amount                = $ci['tax_amount'] ?? (float) ($item['tax_amount'] ?? 0);
                $pfItem->tax_info                  = $taxInfo ? json_encode($taxInfo, JSON_UNESCAPED_UNICODE) : null;
                $pfItem->line_total                = round(($pfItem->taxable_amount ?? 0) + ($pfItem->tax_amount ?? 0), 4);
                $pfItem->sort_order                = $idx;
                if (!$pfItem->create()) {
                    throw new Service_Exception("Failed to save proforma invoice item");
                }
                $validIdx++;
            }

            $this->logHistory($pfId, ['log_type' => 'created', 'title' => "Proforma invoice {$proformaNumber} created"]);
            (new Service_So_Order($this->context))->logHistory($soId, ['log_type' => 'proforma_created', 'title' => "Proforma Invoice {$proformaNumber} created"]);

            // Snapshot declaration at creation (PI is always issued at creation, not draft-first)
            $declSvc = new Service_CompanySettings($this->context);
            $decl = (string) $declSvc->get('doc_declaration.proforma_invoice', '');
            if ($decl !== '') {
                $this->db->update('sales_proforma_invoices', ['invoice_declaration' => $decl], "id = {$pfId}");
            }

            $db->commit();

            return ['success' => true, 'data' => ['id' => $pfId, 'proforma_number' => $proformaNumber]];

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }


    public function get(int $proformaId): array {
        if (!$this->context->canAccess('proforma_invoices')) {
            throw new Service_Exception("You do not have permission to access proforma invoices", 403);
        }
        $pf = $this->getProformaOrFail($proformaId);

        $items = $this->db->fetchAll(
            "SELECT spfi.*, pm.tax_classification_type, pm.tax_classification_code
             FROM sales_proforma_invoice_items spfi
             LEFT JOIN products p  ON p.id  = spfi.product_id
             LEFT JOIN product_masters pm ON pm.id = p.master_id
             WHERE spfi.sales_proforma_invoice_id = ?
             ORDER BY spfi.sort_order ASC, spfi.id ASC",
            [$proformaId]
        );

        $parsedItems = [];
        foreach ($items as $item) {
            $parsedItems[] = [
                'id'                      => (int) $item->id,
                'sales_order_item_id'     => $item->sales_order_item_id ? (int) $item->sales_order_item_id : null,
                'product_id'              => (int) $item->product_id,
                'product_name'            => $item->product_name,
                'sku'                     => $item->sku,
                'description'             => $item->description,
                'quantity'                => (float) $item->quantity,
                'product_uom_id'          => $item->product_uom_id ? (int) $item->product_uom_id : null,
                'uom_code'                => $item->uom_code,
                'unit_price'              => (float) $item->unit_price,
                'discount_amount'         => (float) $item->discount_amount,
                'discount_info'           => $item->discount_info ? json_decode($item->discount_info, true) : null,
                'taxable_amount'          => (float) $item->taxable_amount,
                'tax_amount'              => (float) $item->tax_amount,
                'tax_info'                => $item->tax_info ? json_decode($item->tax_info, true) : [],
                'line_total'              => (float) $item->line_total,
                'sort_order'              => (int) $item->sort_order,
                'tax_classification_type' => $item->tax_classification_type,
                'tax_classification_code' => $item->tax_classification_code,
            ];
        }

        $history = $this->db->fetchAll(
            "SELECT h.*, u.name AS user_name
             FROM sales_proforma_invoice_history h
             LEFT JOIN users u ON u.id = h.created_by
             WHERE h.sales_proforma_invoice_id = ?
             ORDER BY h.id DESC",
            [$proformaId]
        );

        $parsedHistory = [];
        foreach ($history as $h) {
            $parsedHistory[] = [
                'id'         => (int) $h->id,
                'log_type'   => $h->log_type,
                'title'      => $h->title,
                'meta'       => $h->meta ? json_decode($h->meta, true) : null,
                'user_name'  => $h->user_name,
                'created_at' => $h->created_at,
            ];
        }

        $soRow = $this->db->fetchOne(
            "SELECT so_number FROM sales_orders WHERE id = ? LIMIT 1",
            [$pf->sales_order_id]
        );

        $customerRow = $this->db->fetchOne(
            "SELECT display_name, email FROM customers WHERE id = ? LIMIT 1",
            [$pf->customer_id]
        );

        $createdByRow = $this->db->fetchOne(
            "SELECT name FROM users WHERE id = ? LIMIT 1",
            [$pf->created_by]
        );

        $companyRow = $this->db->fetchOne(
            "SELECT name, legal_name, address, city, state, country, zipcode, gstin, logo_path, signature_path, pan
             FROM companies WHERE id = ? LIMIT 1",
            [$pf->company_id]
        );

        $billingAddress  = $pf->billing_address_snapshot  ? json_decode($pf->billing_address_snapshot, true)  : [];
        $shippingAddress = $pf->shipping_address_snapshot ? json_decode($pf->shipping_address_snapshot, true) : [];

        return [
            'id'                              => (int) $pf->id,
            'proforma_number'                 => $pf->proforma_number,
            'sales_order_id'                  => (int) $pf->sales_order_id,
            'so_number'                       => $soRow ? $soRow->so_number : null,
            'customer_id'                     => (int) $pf->customer_id,
            'customer_name'                   => $customerRow ? $customerRow->display_name : null,
            'customer_email'                  => $customerRow ? $customerRow->email : null,
            'proforma_date'                   => $pf->proforma_date,
            'valid_until'                     => $pf->valid_until,
            'billing_address'                 => $billingAddress,
            'shipping_address'                => $shippingAddress,
            'payment_terms'                   => $pf->payment_terms,
            'place_of_supply_code'            => $pf->place_of_supply_code,
            'place_of_supply_name'            => $pf->place_of_supply_name,
            'supply_type'                     => $pf->supply_type,
            'customer_gstin_snapshot'         => $pf->customer_gstin_snapshot,
            'reverse_charge'                  => (bool) (int) $pf->reverse_charge,
            'cgst_total'                      => (float) $pf->cgst_total,
            'sgst_total'                      => (float) $pf->sgst_total,
            'ugst_total'                      => (float) $pf->ugst_total,
            'igst_total'                      => (float) $pf->igst_total,
            'cess_total'                      => (float) $pf->cess_total,
            'gst_summary'                     => !empty($pf->gst_summary) ? json_decode($pf->gst_summary, true) : null,
            'notes'                           => $pf->notes,
            'invoice_terms'                   => $pf->invoice_terms,
            'invoice_declaration'             => $pf->invoice_declaration,
            'subtotal'                        => (float) $pf->subtotal,
            'item_discount_total'             => (float) $pf->item_discount_total,
            'subtotal_after_item_discount'    => (float) $pf->subtotal_after_item_discount,
            'order_discount_amount'           => (float) $pf->order_discount_amount,
            'discount_total'                  => (float) $pf->discount_total,
            'discount_info'                   => $pf->discount_info ? json_decode($pf->discount_info, true) : null,
            'tax_amount'                      => (float) $pf->tax_amount,
            'round_off_amount'                => (float) $pf->round_off_amount,
            'adjustment_label'                => $pf->adjustment_label,
            'adjustment_amount'               => (float) $pf->adjustment_amount,
            'grand_total'                     => (float) $pf->grand_total,
            'status'                          => $pf->status,
            'is_outdated'                     => (bool)(int) $pf->is_outdated,
            'outdated_at'                     => $pf->outdated_at,
            'sent_at'                         => $pf->sent_at,
            'created_by'                      => (int) $pf->created_by,
            'created_by_name'                 => $createdByRow ? $createdByRow->name : null,
            'created_at'                      => $pf->created_at,
            'updated_at'                      => $pf->updated_at,
            'items'                           => $parsedItems,
            'history'                         => $parsedHistory,
            'company'                         => $companyRow ? (array) $companyRow : [],
        ];
    }


    public function listForSO(int $soId): array {
        if (!$this->context->canAccess('proforma_invoices')) {
            throw new Service_Exception("You do not have permission to access proforma invoices", 403);
        }
        $companyId = $this->context->companyId;

        $rows = $this->db->fetchAll(
            "SELECT pf.id, pf.proforma_number, pf.proforma_date, pf.valid_until,
                    pf.status, pf.is_outdated, pf.grand_total, pf.created_at,
                    u.name AS created_by_name
             FROM sales_proforma_invoices pf
             LEFT JOIN users u ON u.id = pf.created_by
             WHERE pf.sales_order_id = ? AND pf.company_id = ?
             ORDER BY pf.id DESC",
            [$soId, $companyId]
        );

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id'              => (int) $row->id,
                'proforma_number' => $row->proforma_number,
                'proforma_date'   => $row->proforma_date,
                'valid_until'     => $row->valid_until,
                'status'          => $row->status,
                'is_outdated'     => (bool)(int) $row->is_outdated,
                'grand_total'     => (float) $row->grand_total,
                'created_by_name' => $row->created_by_name,
                'created_at'      => $row->created_at,
            ];
        }

        return $result;
    }


    public function cancel(int $proformaId, string $note = ''): void {

        if (!$this->context->canDo('proforma_invoices', 'cancel')) {
            throw new Service_Exception("You do not have permission to cancel proforma invoices", 403);
        }

        $pf = $this->getProformaOrFail($proformaId);

        if (!in_array($pf->status, ['draft', 'sent'])) {
            throw new Service_Exception("Only draft or sent proforma invoices can be cancelled", 422);
        }

        $db = $this->db;
        $db->startTransaction();

        try {
            $pf->status = 'cancelled';
            $pf->update();

            $title = "Proforma invoice cancelled";
            if (!empty($note)) {
                $title .= " — {$note}";
            }
            $this->logHistory($proformaId, ['log_type' => 'cancelled', 'title' => $title]);
            (new Service_So_Order($this->context))->logHistory((int) $pf->sales_order_id, ['log_type' => 'proforma_cancelled', 'title' => "Proforma Invoice {$pf->proforma_number} cancelled" . (!empty($note) ? " — {$note}" : "")]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }


    public function markAsSent(int $proformaId): void {
        if (!$this->context->canDo('proforma_invoices', 'send_email')) {
            throw new Service_Exception("You do not have permission to update proforma invoice status", 403);
        }

        $pf = $this->getProformaOrFail($proformaId);

        if ($pf->status !== 'draft') {
            throw new Service_Exception("Only draft proforma invoices can be marked as sent", 422);
        }

        $db = $this->db;
        $db->startTransaction();
        try {
            $pf->status  = 'sent';
            $pf->sent_at = date('Y-m-d H:i:s');
            $pf->update();

            $this->logHistory($proformaId, [
                'log_type' => 'sent',
                'title'    => "Proforma invoice marked as sent (shared manually)",
            ]);
            (new Service_So_Order($this->context))->logHistory((int) $pf->sales_order_id, [
                'log_type' => 'email_sent',
                'title'    => "Proforma Invoice {$pf->proforma_number} marked as sent (shared manually)",
            ]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }


    public function sendEmail(int $proformaId, array $payload): array {

        if (!$this->context->canDo('proforma_invoices', 'send_email')) {
            throw new Service_Exception("You do not have permission to send proforma invoice emails", 403);
        }

        $pf = $this->getProformaOrFail($proformaId);

        if ($pf->status === 'cancelled') {
            throw new Service_Exception("Cannot send a cancelled proforma invoice", 422);
        }

        $to      = trim($payload['to'] ?? '');
        $cc      = trim($payload['cc'] ?? '');
        $bcc     = trim($payload['bcc'] ?? '');
        $subject = trim($payload['subject'] ?? '');
        $body    = trim($payload['body'] ?? '');

        if (empty($to)) {
            $this->addError("Recipient email is required", "to");
        } elseif (!$this->validateEmailList($to)) {
            $this->addError("Recipient email is invalid", "to");
        }
        if (!empty($cc) && !$this->validateEmailList($cc)) {
            $this->addError("CC email is invalid", "cc");
        }
        if (!empty($bcc) && !$this->validateEmailList($bcc)) {
            $this->addError("BCC email is invalid", "bcc");
        }
        if (empty($subject)) {
            $this->addError("Subject is required", "subject");
        }
        if (empty($body)) {
            $this->addError("Message body is required", "body");
        }

        if ($this->hasErrors()) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $emailConfig = new Service_EmailConfig($this->context);
        $smtpConfig  = $emailConfig->getSMTPConfig();
        $resolved    = $emailConfig->resolveFrom([], $this->context->userId);
        $from        = "{$resolved['name']}<{$resolved['email']}>";

        $mailer = new Helpers_Mailer();
        if (!empty($cc))  $mailer->addCC($cc);
        if (!empty($bcc)) $mailer->addBCC($bcc);

        $attachments = (array) ($payload['attachments'] ?? []);
        foreach ($attachments as $att) {
            $name     = $att['name'] ?? 'attachment';
            $mimeType = $att['mime_type'] ?? 'application/octet-stream';
            $content  = $att['content'] ?? '';
            if (!empty($content)) {
                $mailer->addStringAttachment(base64_decode($content), $name, $mimeType);
            }
        }

        $db = $this->db;
        $db->startTransaction();
        try {
            if ($pf->status === 'draft') {
                $pf->status = 'sent';
            }
            $pf->sent_at = date('Y-m-d H:i:s');
            $pf->update();

            $emailHistMeta = [
                'to'          => $to,
                'cc'          => $cc ?: null,
                'bcc'         => $bcc ?: null,
                'subject'     => $subject,
                'attachments' => [],
            ];

            $pfHistoryId = $this->logHistory($proformaId, ['log_type' => 'sent', 'title' => "Proforma invoice emailed to {$to}", 'meta' => $emailHistMeta]);
            $soSvc = new Service_So_Order($this->context);
            $soHistoryId = $soSvc->logHistory((int) $pf->sales_order_id, ['log_type' => 'email_sent', 'title' => "Proforma Invoice {$pf->proforma_number} emailed to {$to}", 'meta' => $emailHistMeta]);

            if (!empty($attachments)) {
                $attachSvc = new Service_Attachment($this->context);
                $attachSvc->saveFromBase64($attachments, 'sales_proforma_invoice_history', $pfHistoryId);
                $emailHistMeta['attachments'] = $attachSvc->listFor('sales_proforma_invoice_history', $pfHistoryId);
                $db->update('sales_proforma_invoice_history', ['meta' => json_encode($emailHistMeta)], "id = {$pfHistoryId}");
                $db->update('sales_order_history', ['meta' => json_encode($emailHistMeta)], "id = {$soHistoryId}");
            }

            $sent = $mailer->sendMail($from, $to, $subject, $body, $smtpConfig);
            if (!$sent) {
                $detail = implode('; ', $mailer->getErrors()) ?: 'Unknown SMTP error';
                throw new Service_Exception("Failed to send email: {$detail}", 500);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

        return ['success' => true];
    }


    public function getEmailDefaults(int $proformaId): array
    {
        if (!$this->context->canDo('proforma_invoices', 'send_email')) {
            throw new Service_Exception("You do not have permission to send proforma invoice emails", 403);
        }
        $this->getProformaOrFail($proformaId);
        $emailSvc = new Service_EmailConfig($this->context);
        $defaults = $emailSvc->getEmailDefaults('proforma_invoice', $proformaId);
        return $defaults;
    }


    public function generateEmailPdf(int $proformaId): array
    {
        if (!$this->context->canAccess('proforma_invoices')) {
            throw new Service_Exception("You do not have permission to access proforma invoices", 403);
        }
        $pf = $this->getProformaOrFail($proformaId);
        return [
            'name'      => "{$pf->proforma_number}.pdf",
            'mime_type' => 'application/pdf',
            'content'   => base64_encode($this->buildPdfBytes($proformaId)),
        ];
    }


    public function downloadPdf(int $proformaId): array {
        $pf = $this->getProformaOrFail($proformaId);

        if (!$this->context->canAccess('proforma_invoices')) {
            throw new Service_Exception("You do not have permission to download this proforma invoice", 403);
        }

        return [
            'bytes'    => $this->buildPdfBytes($proformaId),
            'filename' => "{$pf->proforma_number}.pdf",
        ];
    }


    public function markOutdated(int $soId): void {
        $companyId = $this->context->companyId;
        $now       = date('Y-m-d H:i:s');

        $active = $this->db->fetchAll(
            "SELECT id, proforma_number FROM sales_proforma_invoices
             WHERE sales_order_id = ? AND company_id = ? AND status IN ('draft','sent') AND is_outdated = 0",
            [$soId, $companyId]
        );

        if (empty($active)) return;

        foreach ($active as $row) {
            $this->db->update(
                'sales_proforma_invoices',
                ['is_outdated' => 1, 'outdated_at' => $now, 'updated_at' => $now],
                "id = {$row->id}"
            );
            $this->logHistory((int) $row->id, ['log_type' => 'outdated', 'title' => "Sales order was amended — proforma {$row->proforma_number} is now outdated"]);
            (new Service_So_Order($this->context))->logHistory($soId, ['log_type' => 'proforma_outdated', 'title' => "Proforma Invoice {$row->proforma_number} marked outdated due to SO amendment"]);
        }
    }
}
?>
