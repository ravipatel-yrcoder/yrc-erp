
<div class="offcanvas offcanvas-end" tabindex="-1" id="addProformaInvoice" aria-labelledby="addProformaInvoiceTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 65%;">

    <div class="offcanvas-header border-bottom">
        <h5 id="addProformaInvoiceTitle" class="offcanvas-title">New Proforma Invoice</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="addProformaForm">

            <input type="hidden" id="pfFormSoId" name="sales_order_id" value="" />

            <div class="form-glob-feedback"></div>

            <!-- Header fields -->
            <div class="mb-6">
                <div class="row g-4">

                    <div class="col-md-4">
                        <label class="form-label required">Proforma Date</label>
                        <input type="text" class="form-control" name="proforma_date" id="pfProformaDate" placeholder="Proforma Date" />
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Valid Until</label>
                        <input type="text" class="form-control" name="valid_until" id="pfValidUntil" placeholder="Valid Until" />
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Payment Terms</label>
                        <input type="text" class="form-control" name="payment_terms" id="pfPaymentTerms" placeholder="e.g. Net 30" />
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="pfNotes" rows="2" placeholder="Optional notes for customer"></textarea>
                    </div>

                </div>
            </div>

            <!-- Items table -->
            <div class="mb-6">
                <h6 class="mb-3 text-uppercase text-muted small fw-semibold">Items</h6>
                <div class="table-responsive border rounded">
                    <table class="table m-0" id="pfDrawerItemsTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-end" style="width:100px">Qty</th>
                                <th class="text-end" style="width:130px">Unit Price</th>
                                <th class="text-end" style="width:110px">Discount</th>
                                <th class="text-end" style="width:90px">Tax</th>
                                <th class="text-end" style="width:130px">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="pfDrawerItemsBody">
                            <tr><td colspan="6" class="text-center text-muted py-3">Loading items…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Totals -->
            <div class="d-flex justify-content-end mb-6">
                <table class="table table-borderless w-auto mb-0" id="pfDrawerTotals">
                    <tr>
                        <th class="ps-0 text-muted" style="min-width:200px;">Subtotal</th>
                        <td class="px-0 text-end" id="pfDSubtotal">-</td>
                    </tr>
                    <tr class="d-none" id="pfDDiscountRow">
                        <th class="ps-0 text-muted">Discount</th>
                        <td class="px-0 text-end" id="pfDDiscount">-</td>
                    </tr>
                    <tr>
                        <th class="ps-0 text-muted">Tax</th>
                        <td class="px-0 text-end" id="pfDTax">-</td>
                    </tr>
                    <tr class="d-none" id="pfDRoundOffRow">
                        <th class="ps-0 text-muted">Round Off</th>
                        <td class="px-0 text-end" id="pfDRoundOff">-</td>
                    </tr>
                    <tr class="border-top">
                        <th class="ps-0">Total</th>
                        <td class="px-0 text-end fw-bold" id="pfDGrandTotal">-</td>
                    </tr>
                </table>
            </div>

        </form>
    </div>

    <div class="offcanvas-footer border-top p-3 d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="offcanvas">Cancel</button>
        <button type="button" class="btn btn-sm btn-primary" id="pfSaveBtn">Create Proforma Invoice</button>
    </div>

</div>
