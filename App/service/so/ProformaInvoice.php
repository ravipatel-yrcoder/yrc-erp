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


    private function writeHistory(int $proformaId, string $logType, string $title, array $meta = []): void {
        $h = new Models_SalesProformaInvoiceHistory();
        $h->company_id                = $this->context->companyId;
        $h->sales_proforma_invoice_id = $proformaId;
        $h->log_type                  = $logType;
        $h->title                     = $title;
        $h->meta                      = !empty($meta) ? json_encode($meta) : null;
        $h->created_by                = $this->context->userId;
        $h->create();
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
        $data = $this->get($proformaId);
        return Helpers_Pdf::render('pdf.proforma-invoice', ['printData' => $data]);
    }


    public function getFormContext(int $soId): array {
        $companyId = $this->context->companyId;

        $so = new Models_SalesOrder($soId);
        if ($so->isEmpty || $so->company_id != $companyId) {
            throw new Service_Exception("Sales order not found", 404);
        }
        if ($so->status !== 'confirmed') {
            throw new Service_Exception("Proforma invoices can only be created for confirmed sales orders", 422);
        }

        $sql = "SELECT
                    soi.id, soi.product_id,
                    COALESCE(soi.product_name, p.name) AS product_name,
                    COALESCE(soi.product_sku, p.sku) AS sku,
                    soi.description, soi.ordered_qty, soi.unit_price,
                    soi.discount_amount, soi.discount_info,
                    soi.taxable_amount, soi.tax_amount, soi.tax_info,
                    soi.line_total, soi.uom_code, soi.product_uom_id,
                    soi.sort_order
                FROM sales_order_items soi
                LEFT JOIN products p ON p.id = soi.product_id
                WHERE soi.sales_order_id = ?
                ORDER BY soi.sort_order ASC, soi.id ASC";
        $soItems = $this->db->fetchAll($sql, [$soId]);

        $items = [];
        foreach ($soItems as $row) {
            $discountInfo = $row->discount_info ? json_decode($row->discount_info, true) : null;
            $taxInfo      = $row->tax_info      ? json_decode($row->tax_info, true)      : [];
            $items[] = [
                'sales_order_item_id' => (int) $row->id,
                'product_id'          => (int) $row->product_id,
                'product_name'        => $row->product_name,
                'sku'                 => $row->sku,
                'description'         => $row->description,
                'quantity'            => (float) $row->ordered_qty,
                'product_uom_id'      => $row->product_uom_id ? (int) $row->product_uom_id : null,
                'uom_code'            => $row->uom_code,
                'unit_price'          => (float) $row->unit_price,
                'discount_amount'     => (float) $row->discount_amount,
                'discount_info'       => $discountInfo,
                'taxable_amount'      => (float) $row->taxable_amount,
                'tax_amount'          => (float) $row->tax_amount,
                'tax_info'            => $taxInfo,
                'line_total'          => (float) $row->line_total,
            ];
        }

        $billingAddress = [];
        if (!empty($so->billing_address_snapshot)) {
            $billingAddress = json_decode($so->billing_address_snapshot, true) ?: [];
        }
        $shippingAddress = [];
        if (!empty($so->shipping_address_snapshot)) {
            $shippingAddress = json_decode($so->shipping_address_snapshot, true) ?: [];
        }

        $validityDays = (int) (new Service_CompanySettings($this->context))->get('sales.quote_validity_days', 15);
        $validUntil   = $validityDays > 0 ? date('Y-m-d', strtotime("+{$validityDays} days")) : null;

        return [
            'so_id'                    => (int) $so->id,
            'so_number'                => $so->so_number,
            'customer_id'              => (int) $so->customer_id,
            'payment_terms'            => $so->payment_terms,
            'notes'                    => $so->notes,
            'billing_address_snapshot' => $billingAddress,
            'shipping_address_snapshot'=> $shippingAddress,
            'proforma_date'            => date('Y-m-d'),
            'valid_until'              => $validUntil,
            'subtotal'                 => (float) $so->subtotal,
            'item_discount_total'      => (float) $so->item_discount_total,
            'subtotal_after_item_discount' => (float) $so->subtotal_after_item_discount,
            'order_discount_amount'    => (float) $so->order_discount_amount,
            'discount_total'           => (float) $so->discount_total,
            'discount_info'            => $so->discount_info ? json_decode($so->discount_info, true) : null,
            'tax_amount'               => (float) $so->tax_amount,
            'round_off_amount'         => (float) $so->round_off_amount,
            'grand_total'              => (float) $so->grand_total,
            'items'                    => $items,
        ];
    }


    public function create(int $soId, array $data): array {

        if (!$this->context->canDo('proforma_invoices', 'create')) {
            throw new Service_Exception("You do not have permission to create proforma invoices", 403);
        }
        if (!Service_CompanySettings::isProformaInvoiceEnabled($this->context->companyId)) {
            throw new Service_Exception("Proforma invoice feature is not enabled for your company", 403);
        }

        $so = new Models_SalesOrder($soId);
        if ($so->isEmpty || $so->company_id != $this->context->companyId) {
            $this->addError("Sales order not found", "sales_order_id");
        } elseif ($so->status !== 'confirmed') {
            $this->addError("Proforma invoices can only be created for confirmed sales orders", "sales_order_id");
        }

        $proformaDate = trim($data['proforma_date'] ?? '');
        if (empty($proformaDate) || !strtotime($proformaDate)) {
            $this->addError("Proforma date is required", "proforma_date");
        }

        $items = (array) ($data['items'] ?? []);
        $validItems = array_filter($items, fn($i) => !empty($i['product_id']) && (float)($i['quantity'] ?? 0) > 0);
        if (empty($validItems)) {
            $this->addError("At least one item with quantity greater than zero is required", "items");
        }

        if ($this->hasErrors()) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $db = $this->db;
        $db->startTransaction();

        try {
            $seqSvc = new Service_Sequence($this->context);
            $proformaNumber = $seqSvc->nextCommit('sales_proforma_invoices');

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

            $pf = new Models_SalesProformaInvoice();
            $pf->company_id                    = $this->context->companyId;
            $pf->proforma_number               = $proformaNumber;
            $pf->sales_order_id                = $soId;
            $pf->customer_id                   = (int) $so->customer_id;
            $pf->proforma_date                 = $proformaDate;
            $pf->valid_until                   = !empty($data['valid_until']) ? $data['valid_until'] : null;
            $pf->billing_address_snapshot      = $billingSnapshot;
            $pf->shipping_address_snapshot     = $shippingSnapshot;
            $pf->payment_terms                 = !empty($data['payment_terms']) ? trim($data['payment_terms']) : null;
            $pf->notes                         = !empty($data['notes']) ? trim($data['notes']) : null;
            $pf->terms_conditions              = !empty($data['terms_conditions']) ? trim($data['terms_conditions']) : null;
            $pf->subtotal                      = (float) ($data['subtotal'] ?? 0);
            $pf->item_discount_total           = (float) ($data['item_discount_total'] ?? 0);
            $pf->subtotal_after_item_discount  = (float) ($data['subtotal_after_item_discount'] ?? 0);
            $pf->order_discount_amount         = (float) ($data['order_discount_amount'] ?? 0);
            $pf->discount_total                = (float) ($data['discount_total'] ?? 0);
            $pf->discount_info                 = !empty($data['discount_info']) ? json_encode($data['discount_info'], JSON_UNESCAPED_UNICODE) : null;
            $pf->tax_amount                    = (float) ($data['tax_amount'] ?? 0);
            $pf->round_off_amount              = (float) ($data['round_off_amount'] ?? 0);
            $pf->adjustment_label              = !empty($data['adjustment_label']) ? trim($data['adjustment_label']) : null;
            $pf->adjustment_amount             = (float) ($data['adjustment_amount'] ?? 0);
            $pf->grand_total                   = (float) ($data['grand_total'] ?? 0);
            $pf->status                        = 'draft';
            $pf->created_by                    = $this->context->userId;
            $pfId = $pf->create();

            foreach (array_values($items) as $idx => $item) {
                if (empty($item['product_id']) || (float)($item['quantity'] ?? 0) <= 0) continue;

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
                $pfItem->discount_amount           = (float) ($item['discount_amount'] ?? 0);
                $pfItem->discount_info             = $discountInfo ? json_encode($discountInfo, JSON_UNESCAPED_UNICODE) : null;
                $pfItem->taxable_amount            = (float) ($item['taxable_amount'] ?? 0);
                $pfItem->tax_amount                = (float) ($item['tax_amount'] ?? 0);
                $pfItem->tax_info                  = $taxInfo ? json_encode($taxInfo, JSON_UNESCAPED_UNICODE) : null;
                $pfItem->line_total                = (float) ($item['line_total'] ?? 0);
                $pfItem->sort_order                = $idx;
                $pfItem->create();
            }

            $this->writeHistory($pfId, 'created', "Proforma invoice {$proformaNumber} created");

            $db->commit();

            return ['success' => true, 'data' => ['id' => $pfId, 'proforma_number' => $proformaNumber]];

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }


    public function get(int $proformaId): array {
        $pf = $this->getProformaOrFail($proformaId);

        $items = $this->db->fetchAll(
            "SELECT * FROM sales_proforma_invoice_items WHERE sales_proforma_invoice_id = ? ORDER BY sort_order ASC, id ASC",
            [$proformaId]
        );

        $parsedItems = [];
        foreach ($items as $item) {
            $parsedItems[] = [
                'id'                   => (int) $item->id,
                'sales_order_item_id'  => $item->sales_order_item_id ? (int) $item->sales_order_item_id : null,
                'product_id'           => (int) $item->product_id,
                'product_name'         => $item->product_name,
                'sku'                  => $item->sku,
                'description'          => $item->description,
                'quantity'             => (float) $item->quantity,
                'product_uom_id'       => $item->product_uom_id ? (int) $item->product_uom_id : null,
                'uom_code'             => $item->uom_code,
                'unit_price'           => (float) $item->unit_price,
                'discount_amount'      => (float) $item->discount_amount,
                'discount_info'        => $item->discount_info ? json_decode($item->discount_info, true) : null,
                'taxable_amount'       => (float) $item->taxable_amount,
                'tax_amount'           => (float) $item->tax_amount,
                'tax_info'             => $item->tax_info ? json_decode($item->tax_info, true) : [],
                'line_total'           => (float) $item->line_total,
                'sort_order'           => (int) $item->sort_order,
            ];
        }

        $history = $this->db->fetchAll(
            "SELECT h.*, u.name AS user_name
             FROM sales_proforma_invoice_history h
             LEFT JOIN users u ON u.id = h.created_by
             WHERE h.sales_proforma_invoice_id = ?
             ORDER BY h.id ASC",
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
            "SELECT display_name FROM customers WHERE id = ? LIMIT 1",
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
            'proforma_date'                   => $pf->proforma_date,
            'valid_until'                     => $pf->valid_until,
            'billing_address'                 => $billingAddress,
            'shipping_address'                => $shippingAddress,
            'payment_terms'                   => $pf->payment_terms,
            'notes'                           => $pf->notes,
            'terms_conditions'                => $pf->terms_conditions,
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
            $this->writeHistory($proformaId, 'cancelled', $title);

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

        $pdfBytes = $this->buildPdfBytes($proformaId);

        $mailer = new Helpers_Mailer();
        if (!empty($cc))  $mailer->addCC($cc);
        if (!empty($bcc)) $mailer->addBCC($bcc);
        $mailer->addStringAttachment($pdfBytes, "{$pf->proforma_number}.pdf", 'application/pdf');

        $attachments = (array) ($payload['attachments'] ?? []);
        foreach ($attachments as $att) {
            $name     = $att['name'] ?? 'attachment';
            $mimeType = $att['mime_type'] ?? 'application/octet-stream';
            $content  = $att['content'] ?? '';
            if (!empty($content)) {
                $mailer->addStringAttachment(base64_decode($content), $name, $mimeType);
            }
        }

        $sent = $mailer->sendMail($from, $to, $subject, $body, $smtpConfig);
        if (!$sent) {
            $detail = implode('; ', $mailer->getErrors()) ?: 'Unknown SMTP error';
            throw new Service_Exception("Failed to send email: {$detail}", 500);
        }

        $db = $this->db;
        $db->startTransaction();
        try {
            if ($pf->status === 'draft') {
                $pf->status  = 'sent';
            }
            $pf->sent_at = date('Y-m-d H:i:s');
            $pf->update();

            $this->writeHistory($proformaId, 'sent', "Proforma invoice emailed to {$to}", [
                'to'      => $to,
                'cc'      => $cc ?: null,
                'bcc'     => $bcc ?: null,
                'subject' => $subject,
            ]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }

        return ['success' => true];
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
            $this->writeHistory((int) $row->id, 'outdated', "Sales order was amended — proforma {$row->proforma_number} is now outdated");
        }
    }
}
?>
