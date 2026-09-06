<?php
class Api_ComputeController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    /**
     * POST /api/compute/document-totals
     *
     * Preview-only compute — zero DB queries.
     * All config (round_off_config, gst_config, gst_component per tax) must be
     * provided by the client. The save path uses Service_DocumentCompute::saveCompute()
     * directly from SO/PO/PI services with DB-authoritative data.
     */
    public function documentTotalsAction(TinyPHP_Request $request) {

        $inputs = $request->getInputs();

        $items             = (array) ($inputs['items'] ?? []);
        $orderDiscount     = $inputs['order_discount'] ?? 0;
        $orderDiscountType = (string) ($inputs['order_discount_type'] ?? 'flat');
        $adjustmentAmount  = $inputs['adjustment_amount'] ?? 0;
        $adjustmentLabel   = (string) ($inputs['adjustment_label'] ?? '');
        $roundOffRequested = (bool) ($inputs['round_off_requested'] ?? false);
        $documentType      = (string) ($inputs['document_type'] ?? 'so');

        // All config from client — no DB reads
        $roCfg     = $inputs['round_off_config'] ?? ['mode' => 'off', 'round_to' => 1, 'method' => 'nearest'];
        $gstConfig = $inputs['gst_config'] ?? null;

        // Validate: at least one valid item
        $hasItem = false;
        foreach ($items as $item) {
            if (!empty($item['product_id']) && (float) ($item['quantity'] ?? 0) > 0) {
                $hasItem = true;
                break;
            }
        }
        if (!$hasItem) {
            return response([], 'At least one valid item is required', 422)
                ->errors(['items' => 'At least one valid item is required'])
                ->sendJson();
        }

        // Resolve supply_type and place_of_supply_code server-side from raw billing address data.
        // resolveForDocument() is pure string + config — zero DB queries.
        if ($gstConfig !== null) {
            $resolved = Service_Gst::resolveForDocument(
                (string) ($gstConfig['billing_address_gstin']  ?? ''),
                (string) ($gstConfig['customer_gstin']         ?? ''),
                (string) ($gstConfig['billing_address_state']  ?? ''),
                (string) ($gstConfig['company_gstin']          ?? ''),
                (string) ($gstConfig['company_state']          ?? ''),
                (string) ($gstConfig['customer_gst_treatment'] ?? 'b2b')
            );
            $gstConfig['place_of_supply_code'] = $resolved['place_of_supply_code'];
            $gstConfig['supply_type']          = $resolved['supply_type'];
        }

        $result = Service_DocumentCompute::previewCompute([
            'document_type'       => $documentType,
            'items'               => $items,
            'order_discount'      => $orderDiscount,
            'order_discount_type' => $orderDiscountType,
            'adjustment_amount'   => $adjustmentAmount,
            'adjustment_label'    => $adjustmentLabel,
            'round_off_config'    => $roCfg,
            'round_off_requested' => $roundOffRequested,
            'gst_config'          => $gstConfig,
        ]);

        return response($result)->sendJson();
    }
}
