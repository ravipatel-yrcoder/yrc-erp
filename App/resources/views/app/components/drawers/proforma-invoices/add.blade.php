
<div class="offcanvas offcanvas-end" tabindex="-1" id="addProformaInvoice" aria-labelledby="addProformaInvoiceTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 65%;">

    <div class="offcanvas-header">
        <h5 id="addProformaInvoiceTitle" class="offcanvas-title">New Proforma Invoice</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="addProformaForm">

            <input type="hidden" id="pfFormSoId" name="sales_order_id" value="" />
            <input type="hidden" id="pfNumberSuggested" name="proforma_number_suggested" value="" />

            <div class="form-glob-feedback"></div>

            <div class="mb-5">

                <!-- Row 1: Proforma Number | Proforma Date | Payment Terms | Place of Supply -->
                <div class="row g-12">
                    <div class="col-md-8">
                        <div class="row gy-2 gx-5">
                            <div class="col-md-4">
                                <label class="form-label required">Proforma Number</label>
                                <input type="text" class="form-control" name="proforma_number" id="pfNumberPreview" placeholder="Proforma Number" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Proforma Date</label>
                                <input type="text" class="form-control" name="proforma_date" id="pfProformaDate" placeholder="Proforma Date" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Valid Until</label>
                                <input type="text" class="form-control" name="valid_until" id="pfValidUntilDate" placeholder="Select date" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Terms</label>
                                <input type="text" class="form-control bg-lighter" id="pfPaymentTermsDisplay" readonly placeholder="—" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Place of Supply</label>
                                <div id="pfPlaceOfSupply" class="form-control bg-lighter">—</div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" id="pfNotes" rows="3" placeholder="Optional notes for customer"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-5">
                            <label class="form-label">Billing Address</label>
                            {{-- Display text shown once an address is selected --}}
                            <div id="pfBillAddrDisplayWrap" class="d-none border rounded p-2 bg-lighter" style="min-height:2.5rem;">
                                <div id="pfBillAddrText" class="small" style="line-height:1.7;"></div>
                            </div>

                            {{-- Select dropdown shown when choosing --}}
                            <div id="pfBillAddrSelectWrap">
                                <select class="form-select" id="pfBillingAddressId">
                                    <option value="">Select address...</option>
                                </select>
                            </div>

                            <div class="mt-1 d-flex gap-3">
                                <a href="javascript:void(0);" id="pfAddNewBillingAddressBtn" class="fs-13">+ Add New</a>
                                <a href="javascript:void(0);" id="pfChangeBillingAddressBtn" class="fs-13 d-none text-muted">Change</a>
                                <a href="javascript:void(0);" id="pfEditBillingAddressBtn" class="fs-13 d-none">Edit</a>
                                <a href="javascript:void(0);" id="pfCancelBillingAddressBtn" class="fs-13 d-none text-muted">Cancel</a>
                            </div>
                            <input type="hidden" name="billing_address_json" id="pfBillingAddressJson" />
                        </div>
                        <div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="pfReverseCharge" name="reverse_charge" value="1">
                                <label class="form-check-label" for="pfReverseCharge">
                                    Reverse Charge Applicable
                                    <small class="text-muted d-block">GST amounts shown on document but payable by recipient directly to government</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label mb-0">Terms &amp; Conditions</label>
                            <a href="javascript:void(0);" class="fs-13" id="pfTermsToggle" onclick="togglePfTermsEditor()">Show</a>
                        </div>
                        <div class="d-none mt-2" id="pfTermsEditorWrap">
                            <textarea id="pfTermsInput" name="invoice_terms"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items table -->
            <div class="mb-4">
                <h6 class="text-uppercase text-muted mb-2">Items</h6>
                <div class="items-section-feedback form-section-feedback"></div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="pfDrawerItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="p-2" style="width:4%">#</th>
                                <th class="p-2" style="width:28%">Items &amp; Description</th>
                                <th class="p-2" style="width:9%">HSN/SAC</th>
                                <th class="p-2 text-end" style="width:10%">Qty</th>
                                <th class="p-2 text-end" style="width:11%">Unit Price</th>
                                <th class="p-2 text-end" style="width:9%">Discount</th>
                                <th class="p-2" style="width:19%">Tax</th>
                                <th class="p-2 text-end" style="width:10%">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="pfDrawerItemsBody">
                            <tr><td colspan="8" class="text-center text-muted py-3">Loading items…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Totals -->
            <div class="row justify-content-end">
                <div class="col-md-5">
                    <table class="table table-sm table-borderless mb-0" id="pfDrawerTotals">
                        <tr>
                            <th class="ps-0 text-muted fw-normal">Subtotal</th>
                            <td class="text-end" id="pfDSubtotal">-</td>
                        </tr>
                        <tr class="d-none" id="pfDItemDiscRow">
                            <th class="ps-0 text-muted fw-normal">Item Discounts</th>
                            <td class="text-end text-danger" id="pfDItemDisc">-</td>
                        </tr>
                        <tr class="d-none" id="pfDOrderDiscRow">
                            <th class="ps-0 text-muted fw-normal">Order Discount</th>
                            <td class="text-end text-danger" id="pfDOrderDisc">-</td>
                        </tr>
                        <tbody id="pfDGstRowsGroup">
                            <tr>
                                <th class="ps-0 text-muted fw-normal">Tax</th>
                                <td class="text-end" id="pfDTax">-</td>
                            </tr>
                        </tbody>
                        <tr class="d-none" id="pfDRoundOffRow">
                            <th class="ps-0 text-muted fw-normal">Round Off</th>
                            <td class="text-end" id="pfDRoundOff">-</td>
                        </tr>
                        <tr class="border-top">
                            <th class="ps-0">Grand Total</th>
                            <td class="text-end fw-bold" id="pfDGrandTotal">-</td>
                        </tr>
                    </table>
                </div>
            </div>

        </form>
    </div>

    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" class="btn btn-primary btn-sm min-w-px-140" id="pfSaveBtn">Create Proforma Invoice</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>

</div>
