<?php
class Service_So_Pdf extends Service_Base {


    public function buildPrintData(int $soId): array {

        $so = new Models_SalesOrder($soId);
        if ($so->isEmpty || $so->company_id != $this->context->companyId) {
            throw new Service_Exception("Sales order not found", 404);
        }

        // Company profile
        $company = $this->db->fetchOne("SELECT name, email, phone, address, city, state, country, zipcode FROM companies WHERE id = ?", [$this->context->companyId]);

        // Salesperson name
        $salesperson = null;
        if ($so->salesperson_id) {
            $spRow = $this->db->fetchOne("SELECT name AS full_name FROM users WHERE id = ? AND company_id = ?", [$so->salesperson_id, $this->context->companyId]);
            $salesperson = $spRow ? trim($spRow->full_name) : null;
        }

        // Billing address — prefer snapshot stored on SO, fallback to live customer address
        $billingAddress = null;
        if (!empty($so->billing_address_snapshot)) {
            $billingAddress = json_decode($so->billing_address_snapshot, true);
        }
        if (empty($billingAddress)) {
            $customer = new Models_Customer($so->customer_id);
            $billingAddress = !$customer->isEmpty ? $customer->getBillingAddress() : [];
        }

        // Line items
        $lineItems = [];
        foreach ($so->line_items as $item) {
            $taxes = is_array($item->tax_info) ? $item->tax_info : [];
            $taxLabel = '';
            if (!empty($taxes)) {
                $taxParts = array_map(fn($t) => $t->name ?? '', $taxes);
                $taxLabel = implode(', ', array_filter($taxParts));
            }

            $lineItems[] = [
                'product_name' => $item->product_name,
                'description'  => $item->description,
                'qty'          => $item->ordered_qty,
                'uom_code'     => $item->uom_code,
                'unit_price'   => $item->unit_price,
                'discount'     => $item->discount_amount,
                'tax_label'    => $taxLabel,
                'tax_amount'   => $item->tax_amount,
                'line_total'   => $item->line_total,
            ];
        }

        return [
            'company'     => $company ? (array) $company : [],
            'so'          => [
                'id'                     => $so->id,
                'so_number'              => $so->so_number,
                'order_date'             => $so->order_date,
                'expected_delivery_date' => $so->expected_delivery_date,
                'payment_terms'          => $so->payment_terms,
                'reference'              => $so->reference,
                'notes'                  => $so->notes,
                'subtotal'               => $so->subtotal,
                'discount_amount'        => $so->discount_amount,
                'tax_amount'             => $so->tax_amount,
                'total_amount'           => $so->total_amount,
            ],
            'customer'        => ['name' => $so->customer->display_name ?? ''],
            'billing_address' => $billingAddress,
            'salesperson'     => $salesperson,
            'line_items'      => $lineItems,
        ];
    }


    public function callPdfService(string $printViewUrl, array $cookies = []): string {

        $serviceUrl = config('pdf.service_url');
        $serviceSecret = config('pdf.service_secret');

        $payload = json_encode([
            'url' => $printViewUrl,
            'token' => $serviceSecret,
            'cookies' => $cookies,
        ]);

        $ch = curl_init($serviceUrl . '/render-pdf');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Service_Exception("PDF service connection failed: {$curlError}", 503);
        }

        if ($httpCode !== 200) {
            $detail = '';
            if ($response) {
                $decoded = json_decode($response, true);
                $detail  = $decoded['detail'] ?? $decoded['error'] ?? '';
            }
            $msg = "PDF service error (HTTP {$httpCode})" . ($detail ? ": {$detail}" : '');
            throw new Service_Exception($msg, 503);
        }

        return $response;
    }
}
