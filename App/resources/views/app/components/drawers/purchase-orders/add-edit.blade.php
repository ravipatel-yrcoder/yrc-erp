<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditPurchaseOrders" aria-labelledby="addEditPurchaseOrdersDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 60%;">

    <div class="offcanvas-header">
        <h5 id="addEditPurchaseOrdersDrawerTitle" class="offcanvas-title">Add purchase order</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <!-- BODY -->
    <div class="offcanvas-body">
        <form id="addEditPurchaseOrdersForm">

            <input type="hidden" id="id" value="" />
            <input type="hidden" id="status" name="status" value="draft" />
            <input type="hidden" name="order_discount_info" id="poOrderDiscInfo" value="">
            <input type="hidden" name="adjustment_label" value="">
            <input type="hidden" name="adjustment_amount" value="0">
            <input type="hidden" name="round_off_amount" id="poRoundOff" value="0">

            <div class="form-glob-feedback"></div>

            <!-- ===================== -->
            <!-- GENERAL INFORMATION -->
            <!-- ===================== -->
            <div class="mb-7">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label required">Vendor</label>
                        <select class="form-select" name="vendor_id"></select>
                        <input type="hidden" name="currency_code" id="po_currency_code" value="" />
                        @if(tenantContext()->canDo('vendors', 'write'))
                        <div id="poCreateVendorLink" class="mt-1">
                            <a href="javascript:void(0);" class="fs-13" onclick="poOpenCreateVendor()"><i class="bx bx-plus me-1"></i>Create new vendor</a>
                        </div>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <label class="form-label required">Location</label>
                        <select class="form-select" name="location_id"></select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label required">PO Number</label>
                        <input type="text" class="form-control" name="po_number" placeholder="PO Number" />
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Reference #</label>
                        <input type="text" class="form-control" name="reference" placeholder="Reference" />
                    </div>

                    <div class="col-md-3">
                        <label class="form-label required">Order Date</label>
                        <input type="text" class="form-control" name="order_date" placeholder="Order Date" />
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Expected Delivery</label>
                        <input type="text" class="form-control" name="expected_delivery_date" placeholder="Expected Delivery Date" />
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Payment Terms</label>
                        <select class="form-select" name="payment_term_id"></select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Notes for the vendor (printed on PO)"></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Internal Notes</label>
                        <textarea class="form-control" name="internal_notes" rows="2" placeholder="Internal notes (not printed)"></textarea>
                    </div>
                </div>
            </div>

            <!-- ===================== -->
            <!-- LINE ITEMS -->
            <!-- ===================== -->
            <div class="items-section-feedback form-section-feedback"></div>
            <div class="mb-7">
                <h6 class="text-uppercase text-muted mb-3">Line Items</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="po_line_items">
                        <thead class="table-light">
                            <tr>
                                <th class="p-2" style="width: 32%">Items & Description</th>
                                <th class="p-2 text-end" style="width: 8%">Qty</th>
                                <th class="p-2 text-end" style="width: 11%">Unit Cost</th>
                                <th class="p-2" style="width: 26%">Tax</th>
                                <th class="p-2 text-end" style="width: 10%">Discount</th>
                                <th class="p-2 text-end" style="width: 10%">Amount</th>
                                <th class="p-2" style="width: 40px"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add_po_item">+ Add Item</button>
            </div>

            <!-- ===================== -->
            <!-- TOTALS SUMMARY -->
            <!-- ===================== -->
            <div class="row justify-content-end mt-2 mb-4">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-0" id="poTotalsTable">
                        <tbody>
                            <tr>
                                <td class="text-muted ps-0 text-uppercase">Subtotal</td>
                                <td class="text-end pe-0 fw-medium" id="poFormSubtotal">-</td>
                            </tr>
                            <tr id="rowItemDisc" class="d-none">
                                <td class="text-muted ps-0 text-uppercase">Item Discounts</td>
                                <td class="text-end pe-0 text-danger" id="poFormItemDisc">-</td>
                            </tr>
                            <tr id="poOrderDiscountRow" class="d-none">
                                <td class="ps-0 text-uppercase">
                                    <span class="text-muted" id="poOrderDiscLabel">Order Discount</span>
                                    <a href="javascript:void(0);" id="clearPOOrderDiscount" class="ms-1" title="Remove order discount"><i class="bx bx-trash text-danger" style="font-size:13px;vertical-align:middle;"></i></a>
                                </td>
                                <td class="text-end pe-0 text-danger" id="poFormOrderDiscAmt"></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0 text-uppercase">Tax</td>
                                <td class="text-end pe-0" id="poFormTax">-</td>
                            </tr>
                            <tr id="poRoundOffRow" class="d-none">
                                <td class="text-muted ps-0 text-uppercase">Round-off</td>
                                <td class="text-end pe-0" id="poFormRoundOffAmt"></td>
                            </tr>
                            <tr class="border-top">
                                <td class="ps-0 fw-semibold text-uppercase">Grand Total</td>
                                <td class="text-end pe-0 fw-bold fs-5" id="poFormGrandTotal">-</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-end gap-2 mt-1">
                        <button type="button" class="d-flex justify-content-center btn btn-sm btn-outline-secondary d-none" id="togglePORoundOffBtn">
                            <i class="bx bx-rotate-right me-1"></i><span id="togglePORoundOffLabel">Apply Round Off</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="addPOOrderDiscountBtn">
                            <i class="bx bx-purchase-tag me-1"></i>Add Order Discount
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>

    <!-- FOOTER -->
    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveAddEditPurchaseOrders" class="btn btn-primary btn-sm min-w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>

</div>


<!-- PO Discount Modal — shared for item + order discount -->
<div class="modal fade stacked-modal" id="poDiscountModal" tabindex="-1" aria-labelledby="poDiscountModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-3">
                <h6 class="modal-title mb-0" id="poDiscountModalLabel">Apply Discount</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="poDiscountModalTarget" value="" />
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="poDiscountType" id="poDiscountTypePercent" value="percent">
                            <label class="form-check-label" for="poDiscountTypePercent">Percent (%)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="poDiscountType" id="poDiscountTypeFixed" value="fixed" checked>
                            <label class="form-check-label" for="poDiscountTypeFixed">Fixed (₹)</label>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount</label>
                    <input type="number" class="form-control" id="poDiscountValueInput" placeholder="0.00" min="0" step="1" />
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="poApplyDiscountBtn">Apply</button>
            </div>
        </div>
    </div>
</div>


<style>
#addEditPurchaseOrders #po_line_items td.qty {
    position: relative;
}
#addEditPurchaseOrders #po_line_items .uom-label {
    position: absolute;
    right: 10px;
}
</style>

@push('scripts')
<script>
let poItemIndx           = 0;
let poOrderDiscountInfo  = {};
let poRoundOffEnabled    = false;

const refreshPurchaseOrderForm = async function(id = 0) {

    const drawerEl    = document.getElementById('addEditPurchaseOrders');
    const formEl      = document.getElementById('addEditPurchaseOrdersForm');

    drawerEl.querySelector("#addEditPurchaseOrdersDrawerTitle").innerHTML = id > 0 ? "Edit purchase order" : "Add purchase order";
    drawerEl.querySelector("#saveAddEditPurchaseOrders").innerHTML         = id > 0 ? "Save" : "Save as draft";

    cleanFormInputFeedback(formEl);

    // Reset discount + round-off state
    poOrderDiscountInfo = {};
    poRoundOffEnabled   = false;
    document.getElementById('poRoundOff').value = '0';
    document.getElementById('poRoundOffRow')?.classList.add('d-none');
    document.getElementById('poOrderDiscountRow')?.classList.add('d-none');
    const roBtn = document.getElementById('togglePORoundOffBtn');
    if (roBtn) {
        roBtn.classList.replace('btn-secondary', 'btn-outline-secondary');
        document.getElementById('togglePORoundOffLabel').textContent = 'Apply Round Off';
    }
    initRoundOffToggle();

    try {

        formEl.reset();
        formEl.querySelector("input#id").value = '';
        formEl.querySelector("input[name='status']").value = "draft";

        const response = await api.get('/purchase/orders/form-context', { params: { id } });
        const { data } = response.data;

        const poDetails                 = data.po_details || {};
        const recentVendors             = data.recent_vendors || [];
        const locations                 = data.locations || [];
        const payment_terms             = data.payment_terms || [];
        purchaseOrderAvailableProducts  = data.products || [];
        purchaseOrderApplicableTaxes    = data.taxes || [];
        const suggestedPoNumber         = data.suggested_po_number ?? "";

        // Vendor select2 (AJAX)
        poVendorsData = recentVendors;
        jQuery("#addEditPurchaseOrders select[name='vendor_id']").empty();
        initPOVendorSelect2(recentVendors);

        initSelect2("#addEditPurchaseOrders select[name='location_id']",    { dropdownParent: drawerEl, placeholder: "Choose location", data: buildSelect2Options(locations) });
        initSelect2("#addEditPurchaseOrders select[name='payment_term_id']", { dropdownParent: drawerEl, placeholder: "Choose terms",    data: buildSelect2Options(payment_terms, { idKey: 'id', textKey: 'name' }) });

        // Auto-select location when only one exists (new PO only)
        if (!(id > 0) && locations.length === 1) {
            jQuery("#addEditPurchaseOrders select[name='location_id']").val(locations[0].id).trigger("change");
        }

        const poItemsTbodyEl = formEl.querySelector("#po_line_items tbody");
        poItemsTbodyEl.innerHTML = "";

        if (!(id > 0)) {
            const poNumberInput = formEl.querySelector("input[name='po_number']");
            if (poNumberInput) {
                poNumberInput.value         = suggestedPoNumber;
                poNumberInput.dataset.value = suggestedPoNumber;
            }
            // Auto-set today's date
            datePickerSetDate("#addEditPurchaseOrders [name='order_date']", new Date().toISOString().split('T')[0]);
            const itemHtml = getPOLineItemHtml();
            poItemsTbodyEl.insertAdjacentHTML("beforeend", itemHtml);
            initRowSelect2(poItemsTbodyEl.lastElementChild);
        }

        populatePurchaseOrderForm(poDetails);

    } catch(err) {
        handleApiError(err);
    }
}


const populatePurchaseOrderForm = function(poDetails) {

    if (Object.keys(poDetails).length === 0) return;

    const drawerEl = document.getElementById('addEditPurchaseOrders');
    const formEl   = drawerEl.querySelector('#addEditPurchaseOrdersForm');

    const {
        id, status, vendor_id, currency_code, location_id, po_number, reference,
        order_date, expected_delivery_date, payment_term_id, notes, internal_notes,
        discount_info, round_off_amount, line_items = []
    } = poDetails;

    jQuery("#addEditPurchaseOrders input#id").val(id);
    jQuery("#addEditPurchaseOrders [name='status']").val(status || 'draft');

    // AJAX Select2 — pre-select vendor by appending the option (vendor is in recentVendors/poVendorsData)
    const editingVendor = poVendorsData.find(v => Number(v.id) === Number(vendor_id));
    if (editingVendor) {
        jQuery("#addEditPurchaseOrders [name='vendor_id']")
            .append(new Option(editingVendor.display_name, editingVendor.id, true, true))
            .trigger('change');
    }

    if (currency_code) {
        poActiveCurrency = currency_code;
        document.getElementById('po_currency_code').value = currency_code;
    }

    jQuery("#addEditPurchaseOrders [name='location_id']").val(location_id).trigger("change");
    jQuery("#addEditPurchaseOrders [name='po_number']").val(po_number || "");
    jQuery("#addEditPurchaseOrders [name='reference']").val(reference || "");
    jQuery("#addEditPurchaseOrders [name='payment_term_id']").val(payment_term_id || "").trigger("change");
    jQuery("#addEditPurchaseOrders [name='notes']").val(notes || "");
    jQuery("#addEditPurchaseOrders [name='internal_notes']").val(internal_notes || "");

    datePickerSetDate("#addEditPurchaseOrders [name='order_date']", order_date || "");
    datePickerSetDate("#addEditPurchaseOrders [name='expected_delivery_date']", expected_delivery_date || "");

    // Order discount
    poOrderDiscountInfo = (discount_info && typeof discount_info === 'object') ? discount_info : {};
    renderPOOrderDiscountRow();

    // Round-off: restore toggle state when editing with an existing round-off value
    const existingRoundOff = parseFloat(round_off_amount || 0);
    const roMode = window.sysDefaultConfig?.roundOff?.mode || 'off';
    if (roMode === 'manual' && existingRoundOff !== 0) {
        poRoundOffEnabled = true;
        const roBtn = document.getElementById('togglePORoundOffBtn');
        if (roBtn) {
            roBtn.classList.replace('btn-outline-secondary', 'btn-secondary');
            document.getElementById('togglePORoundOffLabel').textContent = 'Remove Round Off';
        }
    }

    // Line items
    const tbodyEl = drawerEl.querySelector("#po_line_items tbody");
    tbodyEl.innerHTML = "";
    poItemIndx = 0;

    if (Array.isArray(line_items) && line_items.length > 0) {
        line_items.forEach(item => {
            tbodyEl.insertAdjacentHTML("beforeend", getPOLineItemHtml(item));
            const newRow  = tbodyEl.lastElementChild;
            initRowSelect2(newRow);
            const taxIds  = (item.tax_info || []).map(t => Number(t.id));
            jQuery(newRow).find("select.items").val(item.product_id || null).trigger("change");
            jQuery(newRow).find("select.taxes").val(taxIds).trigger("change");
        });
    }
}


const renderPOOrderDiscountRow = function() {
    const row     = document.getElementById('poOrderDiscountRow');
    const labelEl = document.getElementById('poOrderDiscLabel');
    const hasDisc = poOrderDiscountInfo && parseFloat(poOrderDiscountInfo.value || 0) > 0;

    if (row) {
        if (hasDisc) {
            const typeLabel = poOrderDiscountInfo.type === 'percent' ? ` (${poOrderDiscountInfo.value}%)` : '';
            if (labelEl) labelEl.textContent = `Order Discount${typeLabel}`;
            row.classList.remove('d-none');
        } else {
            row.classList.add('d-none');
        }
    }
    recalcTotals();
}


const getPOLineItemHtml = function(savedItem = {}) {

    const {
        id = "", description = "", ordered_qty = "", unit_price = "",
        line_total = "0.00", uom_id = "", discount_info = null,
    } = savedItem;

    const unitPrice          = parseFloat(unit_price) || 0;
    const discInfo           = discount_info && typeof discount_info === 'object' ? discount_info : {};
    const discType           = discInfo.type  || 'percent';
    const discValue          = discInfo.value ? String(discInfo.value) : '';
    const discJson           = discValue ? JSON.stringify({ type: discType, value: parseFloat(discValue) }) : '';
    const discLabel = discValue
        ? (discType === 'percent' ? `${discValue}%` : `₹${parseFloat(discValue).toFixed(2)}`)
        : '<i class="bx bx-edit-alt"></i>';

    const productOptions = purchaseOrderAvailableProducts.map(p =>
        `<option value="${p.id}" data-price="${p.cost_price}">${p.name}</option>`
    ).join("");

    const taxOptions = purchaseOrderApplicableTaxes.map(t =>
        `<option value="${t.id}" data-rate="${t.rate}">${t.name}</option>`
    ).join("");

    const html = `<tr data-index="${poItemIndx}">
        <td class="ps-0 pe-2">
            <select class="form-select items select2-field" name="po_items[${poItemIndx}][product_id]">${productOptions}</select>
            <textarea class="mt-1 form-control" name="po_items[${poItemIndx}][description]">${description || ""}</textarea>
            <input type="hidden" name="po_items[${poItemIndx}][id]" value="${id}" />
        </td>
        <td class="px-2 qty">
            <input type="text" class="px-1 form-control text-end po-item-qty" name="po_items[${poItemIndx}][qty]" placeholder="1" value="${formatQty(ordered_qty)}">
            <input type="hidden" class="uom-id" name="po_items[${poItemIndx}][uom_id]" value="${uom_id}" />
        </td>
        <td class="px-2">
            <input type="text" class="px-1 form-control text-end po-item-price" placeholder="0.00" value="${formatPrice(unitPrice)}">
            <input type="hidden" class="unit-cost-hidden" name="po_items[${poItemIndx}][unit_cost]" value="${unitPrice}">
        </td>
        <td class="px-2">
            <select class="form-select taxes select2-field" name="po_items[${poItemIndx}][tax][]">${taxOptions}</select>
        </td>
        <td class="px-2 text-center">
            <button type="button" class="btn btn-sm btn-text-secondary po-item-discount-btn text-nowrap" title="Apply discount">
                <span class="d-flex align-items-center discount-label">${discLabel}</span>
            </button>
            <input type="hidden" class="po-disc-hidden" name="po_items[${poItemIndx}][discount_info]" value='${discJson}'>
        </td>
        <td class="px-2 text-end fw-semibold line-total">${formatCurrency(line_total, { currency: poActiveCurrency })}</td>
        <td class="px-2 text-center">
            <button type="button" class="btn btn-sm btn-icon btn-text-danger po-remove-item"><i class="bx bx-trash text-danger cursor-pointer"></i></button>
        </td>
    </tr>`;

    poItemIndx++;
    return html;
}


const initRowSelect2 = function(rowElement) {

    const drawerEl = rowElement.closest('#addEditPurchaseOrders');
    if (!drawerEl) return;

    const itemsSelect2El = rowElement.querySelector("select.items");
    const taxesSelect2El = rowElement.querySelector("select.taxes");

    if (itemsSelect2El) {
        initSelect2(itemsSelect2El, {
            dropdownParent: drawerEl,
            placeholder: "Choose item",
            onChange: function(_this) {
                const row       = _this.closest('tr');
                const prodId    = _this.value || "";
                const qtyTdEl   = row.querySelector("td.qty");
                qtyTdEl.querySelector("span.uom-label")?.remove();

                let itemUom = "";
                if (prodId) {
                    const selectedProduct = new Map(purchaseOrderAvailableProducts.map(p => [Number(p.id), p])).get(Number(prodId));
                    const baseUom         = selectedProduct?.uoms.find(u => Number(u.is_base_uom) === 1);
                    if (baseUom) {
                        itemUom = baseUom.uom_id || "";
                        if (baseUom.code) {
                            qtyTdEl.insertAdjacentHTML('beforeend', `<span class="uom-label fs-tiny mt-1 text-primary fw-semibold">UOM: ${baseUom.code}</span>`);
                        }
                    }
                }
                qtyTdEl.querySelector("input.uom-id").value = itemUom;
            }
        });
    }

    if (taxesSelect2El) {
        initSelect2(taxesSelect2El, {
            dropdownParent: drawerEl,
            placeholder: "Choose taxes",
            multiple: true,
            onChange: function(_this) {
                calculateLineAmount(_this.closest('tr'));
            }
        });
    }
}


const calculateLineAmount = function(rowEl) {

    const qtyEl        = rowEl.querySelector('.po-item-qty');
    const unitCostEl   = rowEl.querySelector('.po-item-price');
    const taxSelectEl  = rowEl.querySelector('.taxes');
    const lineTotalEl  = rowEl.querySelector('.line-total');
    const discHiddenEl = rowEl.querySelector('.po-disc-hidden');

    const qty          = parseFloat(qtyEl.value) || 0;
    const unitCost     = parseFloat(unformatNumber(unitCostEl.value)) || 0;
    const lineSubtotal = qty * unitCost;

    const discInfo     = discHiddenEl?.value ? JSON.parse(discHiddenEl.value) : null;
    const discType     = discInfo?.type  || 'percent';
    const discValue    = discInfo ? (parseFloat(discInfo.value) || 0) : 0;
    let itemDiscAmt    = 0;
    if (discValue > 0) {
        itemDiscAmt = discType === 'percent'
            ? lineSubtotal * (discValue / 100)
            : Math.min(discValue, lineSubtotal);
    }

    const taxableAmount = lineSubtotal - itemDiscAmt;
    let totalTaxRate    = 0;
    Array.from(taxSelectEl.selectedOptions).forEach(opt => {
        totalTaxRate += parseFloat(opt.dataset.rate) || 0;
    });

    lineTotalEl.innerHTML = formatCurrency(taxableAmount + taxableAmount * (totalTaxRate / 100), { currency: poActiveCurrency });
    recalcTotals();
}


const recalcTotals = function() {

    const currency = poActiveCurrency;
    let poSubtotal = 0, poItemDiscounts = 0, poTaxTotal = 0;

    document.querySelectorAll('#addEditPurchaseOrdersForm #po_line_items tbody tr').forEach(row => {
        const qtyEl        = row.querySelector('.po-item-qty');
        const unitCostEl   = row.querySelector('.po-item-price');
        const taxSelectEl  = row.querySelector('.taxes');
        const discHiddenEl = row.querySelector('.po-disc-hidden');

        if (!qtyEl || !unitCostEl) return;

        const qty          = parseFloat(qtyEl.value) || 0;
        const unitCost     = parseFloat(unformatNumber(unitCostEl.value)) || 0;
        const lineSubtotal = qty * unitCost;

        const discInfo     = discHiddenEl?.value ? JSON.parse(discHiddenEl.value) : null;
        const discType     = discInfo?.type  || 'percent';
        const discValue    = discInfo ? (parseFloat(discInfo.value) || 0) : 0;
        let itemDiscAmt    = 0;
        if (discValue > 0) {
            itemDiscAmt = discType === 'percent'
                ? lineSubtotal * (discValue / 100)
                : Math.min(discValue, lineSubtotal);
        }

        const taxableAmount = lineSubtotal - itemDiscAmt;
        let totalTaxRate    = 0;
        if (taxSelectEl) {
            Array.from(taxSelectEl.selectedOptions).forEach(opt => {
                totalTaxRate += parseFloat(opt.dataset.rate) || 0;
            });
        }

        poSubtotal      += lineSubtotal;
        poItemDiscounts += itemDiscAmt;
        poTaxTotal      += taxableAmount * (totalTaxRate / 100);
    });

    const netSubtotal    = poSubtotal - poItemDiscounts;
    const orderDiscType  = poOrderDiscountInfo?.type  || 'percent';
    const orderDiscValue = parseFloat(poOrderDiscountInfo?.value || 0) || 0;
    let orderDiscAmt     = 0;
    if (orderDiscValue > 0) {
        orderDiscAmt = orderDiscType === 'percent'
            ? netSubtotal * (orderDiscValue / 100)
            : orderDiscValue;
    }

    document.getElementById('poOrderDiscInfo').value = orderDiscValue > 0
        ? JSON.stringify({ type: orderDiscType, value: orderDiscValue })
        : '';

    const discRatio      = netSubtotal > 0 ? orderDiscAmt / netSubtotal : 0;
    const adjustedTax    = Math.max(0, poTaxTotal * (1 - discRatio));
    const preRoundTotal  = (netSubtotal - orderDiscAmt) + adjustedTax;
    const roCfg          = window.sysDefaultConfig?.roundOff || {};
    const roMode         = roCfg.mode || 'off';
    let roundOff         = 0;
    if (roMode === 'auto' || (roMode === 'manual' && poRoundOffEnabled)) {
        roundOff = computeRoundOff(preRoundTotal, parseFloat(roCfg.roundTo || 1), roCfg.method || 'nearest');
    }
    const grandTotal = preRoundTotal + roundOff;

    // Sync hidden round_off_amount field
    document.getElementById('poRoundOff').value = roundOff;

    document.getElementById('poFormSubtotal').textContent   = formatCurrency(poSubtotal,  { currency });
    document.getElementById('poFormTax').textContent        = formatCurrency(adjustedTax, { currency });
    document.getElementById('poFormGrandTotal').textContent = formatCurrency(grandTotal,  { currency });

    const rowItemDisc = document.getElementById('rowItemDisc');
    if (poItemDiscounts > 0) {
        document.getElementById('poFormItemDisc').textContent = '−' + formatCurrency(poItemDiscounts, { currency });
        rowItemDisc?.classList.remove('d-none');
    } else {
        rowItemDisc?.classList.add('d-none');
    }

    const orderDiscAmtEl = document.getElementById('poFormOrderDiscAmt');
    if (orderDiscAmtEl) {
        orderDiscAmtEl.textContent = orderDiscAmt > 0 ? '−' + formatCurrency(orderDiscAmt, { currency }) : '';
    }

    const roRow       = document.getElementById('poRoundOffRow');
    const roAmtEl     = document.getElementById('poFormRoundOffAmt');
    if (roundOff !== 0) {
        roRow?.classList.remove('d-none');
        if (roAmtEl) {
            roAmtEl.innerHTML  = (roundOff < 0 ? '−' : '+') + formatCurrency(Math.abs(roundOff), { currency });
            roAmtEl.className  = 'text-end pe-0 ' + (roundOff < 0 ? 'text-danger' : 'text-success');
        }
    } else {
        roRow?.classList.add('d-none');
    }
}


function computeRoundOff(amount, roundTo, method) {
    if (!roundTo || roundTo <= 0) return 0;
    let rounded;
    switch (method) {
        case 'floor':   rounded = Math.floor(amount / roundTo) * roundTo; break;
        case 'ceiling': rounded = Math.ceil(amount  / roundTo) * roundTo; break;
        default:        rounded = Math.round(amount / roundTo) * roundTo;
    }
    return parseFloat((rounded - amount).toFixed(4));
}

const initRoundOffToggle = function() {
    const btn    = document.getElementById('togglePORoundOffBtn');
    const roMode = window.sysDefaultConfig?.roundOff?.mode || 'off';
    if (roMode === 'manual') {
        btn.classList.remove('d-none');
    } else {
        btn.classList.add('d-none');
    }
};

let purchaseOrderAvailableProducts = [];
let purchaseOrderApplicableTaxes   = [];
let poVendorsData                  = [];
let poActiveCurrency               = window.sysDefaultConfig?.currency || 'INR';

const initPOVendorSelect2 = function(recentVendors) {

    const drawerEl   = document.getElementById('addEditPurchaseOrders');
    const initialData = recentVendors.map(v => ({
        id:              v.id,
        text:            v.display_name,
        email:           v.email || '',
        phone:           v.phone || '',
        currency_code:   v.currency_code || '',
        payment_term_id: v.payment_term_id || '',
    }));

    initSelect2("#addEditPurchaseOrders select[name='vendor_id']", {
        dropdownParent:     drawerEl,
        placeholder:        'Search or select vendor...',
        minimumInputLength: 0,
        resetVal:           false,
        ajax: {
            url:      '/api/purchase/orders/vendors/search',
            dataType: 'json',
            delay:    300,
            data:     params => ({ q: params.term || '' }),
            transport: function(params, success, failure) {
                if (!params.data.q) {
                    success({ data: initialData });
                    return;
                }
                jQuery.ajax(params).then(success).fail(failure);
            },
            processResults: function(response) {
                return {
                    results: (response.data || []).map(v => ({
                        id:              v.id,
                        text:            v.display_name,
                        email:           v.email || '',
                        phone:           v.phone || '',
                        currency_code:   v.currency_code || '',
                        payment_term_id: v.payment_term_id || '',
                    }))
                };
            },
        },
        templateResult: function(item) {
            if (item.loading) return item.text;
            return jQuery('<div>')
                .append(jQuery('<div>').addClass('fw-semibold').text(item.text))
                .append(jQuery('<small>').addClass('text-muted').text([item.email, item.phone].filter(Boolean).join(' · ')));
        },
        templateSelection: item => item.text || item.id,
        onChange: function(el) {
            const selected   = jQuery(el).select2('data')[0];
            const vendorId   = Number(jQuery(el).val() || 0);
            // select2('data') has extra props for AJAX-selected items; fall back to local cache for pre-populated/DOM-appended items
            const currency   = selected?.currency_code   || poVendorsData.find(v => Number(v.id) === vendorId)?.currency_code   || window.sysDefaultConfig?.currency || 'INR';
            const payTermId  = selected?.payment_term_id || poVendorsData.find(v => Number(v.id) === vendorId)?.payment_term_id  || '';
            poActiveCurrency = currency;
            document.getElementById('po_currency_code').value = currency;
            if (payTermId) {
                jQuery("#addEditPurchaseOrders [name='payment_term_id']").val(payTermId).trigger('change');
            }
        },
    });
};

const poOpenCreateVendor = function() {
    if (typeof openVendorFormDrawer !== 'function') return;
    openVendorFormDrawer(0, {
        onSaved: function(vendor) {
            poVendorsData.push(vendor);
            jQuery("#addEditPurchaseOrders select[name='vendor_id']")
                .append(new Option(vendor.display_name, vendor.id, true, true))
                .trigger('change');
            // Explicitly set currency — DOM-appended options don't carry extra data for Select2's onChange
            const currency = vendor.currency_code || window.sysDefaultConfig?.currency || 'INR';
            poActiveCurrency = currency;
            document.getElementById('po_currency_code').value = currency;
        },
    });
};

const openPurchaseOrderFormDrawer = async function(id = 0) {
    refreshPurchaseOrderForm(id);
    new bootstrap.Offcanvas(document.getElementById('addEditPurchaseOrders')).show();
}


// ── Save ──────────────────────────────────────────────────────────────────────
document.getElementById('saveAddEditPurchaseOrders').addEventListener('click', async function() {

    const btn   = this;
    const formEl = document.getElementById('addEditPurchaseOrdersForm');
    const id     = formEl.querySelector('input#id').value || '';

    cleanFormInputFeedback(formEl);
    setButtonLoading(btn, true);

    try {
        const response = await api.post(id ? `/purchase/orders/${id}` : '/purchase/orders', formDataToObject(new FormData(formEl)));
        const { code, message, data } = response.data;

        notyf.success(message);

        if (code == 201 || code == 200) {
            if (id) {
                if (typeof refreshPurchaseOrderDetails === 'function') refreshPurchaseOrderDetails(id);
                if (typeof refreshPurchaseOrderHistory === 'function') refreshPurchaseOrderHistory(id);
                if (typeof purchaseOrdersDt !== 'undefined') purchaseOrdersDt.ajax.reload();
                bootstrap.Offcanvas.getInstance(document.getElementById('addEditPurchaseOrders')).hide();
                formEl.reset();
            } else {
                window.location.href = `/purchase/orders/${data.po_id}/`;
            }
        }

    } catch(error) {
        handleApiError(error, formEl);
    } finally {
        setButtonLoading(btn, false);
    }
});


// ── Add / Remove items ────────────────────────────────────────────────────────
document.getElementById('add_po_item').addEventListener('click', function() {
    const tbody = document.querySelector("#addEditPurchaseOrdersForm #po_line_items tbody");
    tbody.insertAdjacentHTML("beforeend", getPOLineItemHtml());
    initRowSelect2(tbody.lastElementChild);
});

document.querySelector("#addEditPurchaseOrdersForm #po_line_items").addEventListener("click", function(e) {
    const btn = e.target.closest(".po-remove-item");
    if (!btn) return;
    btn.closest("tr")?.remove();
    recalcTotals();
});


// ── Line item field changes ───────────────────────────────────────────────────
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('po-item-qty')) {
        calculateLineAmount(e.target.closest('tr'));
    }
});

document.addEventListener('change', function(e) {
    if (!e.target.classList.contains('po-item-price')) return;
    const raw = unformatNumber(e.target.value);
    e.target.closest('tr').querySelector('.unit-cost-hidden').value = raw;
    e.target.value = formatPrice(raw);
    calculateLineAmount(e.target.closest('tr'));
});



// ── Item discount button → open modal ────────────────────────────────────────
document.querySelector('#po_line_items').addEventListener('click', function(e) {
    const btn = e.target.closest('.po-item-discount-btn');
    if (!btn) return;

    const row      = btn.closest('tr');
    const hiddenEl = row.querySelector('.po-disc-hidden');
    let discInfo   = {};
    try { discInfo = JSON.parse(hiddenEl?.value || '{}'); } catch(e) {}

    document.getElementById('poDiscountModalTarget').value = row.dataset.index;
    document.getElementById('poDiscountValueInput').value  = discInfo.value || '';
    document.querySelector(`input[name="poDiscountType"][value="${discInfo.type || 'fixed'}"]`).checked = true;
    document.getElementById('poDiscountModalLabel').textContent = 'Item Discount';

    new bootstrap.Modal(document.getElementById('poDiscountModal')).show();
});


// ── Apply discount (modal) ───────────────────────────────────────────────────
document.getElementById('poApplyDiscountBtn').addEventListener('click', function() {

    const target     = document.getElementById('poDiscountModalTarget').value;
    const discType   = document.querySelector('input[name="poDiscountType"]:checked').value;
    const discValue  = parseFloat(document.getElementById('poDiscountValueInput').value) || 0;
    const discJson  = discValue > 0 ? JSON.stringify({ type: discType, value: discValue }) : '';
    const discLabel = discValue > 0
        ? (discType === 'percent' ? `${discValue}%` : `₹${discValue.toFixed(2)}`)
        : '<i class="bx bx-edit-alt"></i>';

    if (target === 'order') {
        poOrderDiscountInfo = discValue > 0 ? { type: discType, value: discValue } : {};
        renderPOOrderDiscountRow();
    } else {
        const row = document.querySelector(`#po_line_items tbody tr[data-index="${target}"]`);
        if (row) {
            const hiddenEl  = row.querySelector('.po-disc-hidden');
            const labelEl   = row.querySelector('.discount-label');
            if (hiddenEl) hiddenEl.value    = discJson;
            if (labelEl)  labelEl.innerHTML = discLabel;
            calculateLineAmount(row);
        }
    }

    bootstrap.Modal.getInstance(document.getElementById('poDiscountModal')).hide();
});


// ── Clear order discount (trash icon — delegated, row is dynamic) ─────────────
document.addEventListener('click', function(e) {
    if (e.target.closest('#clearPOOrderDiscount')) {
        poOrderDiscountInfo = {};
        renderPOOrderDiscountRow();
    }
});


// ── Add Order Discount button ─────────────────────────────────────────────────
document.getElementById('addPOOrderDiscountBtn').addEventListener('click', function() {
    document.getElementById('poDiscountModalTarget').value = 'order';
    document.getElementById('poDiscountValueInput').value  = poOrderDiscountInfo?.value ?? '';
    document.querySelector(`input[name="poDiscountType"][value="${poOrderDiscountInfo?.type || 'percent'}"]`).checked = true;
    document.getElementById('poDiscountModalLabel').textContent = 'Order Discount';

    new bootstrap.Modal(document.getElementById('poDiscountModal')).show();
});


// ── Round-off toggle ──────────────────────────────────────────────────────────
document.getElementById('togglePORoundOffBtn').addEventListener('click', function() {
    poRoundOffEnabled = !poRoundOffEnabled;
    const label = document.getElementById('togglePORoundOffLabel');
    label.textContent = poRoundOffEnabled ? 'Remove Round Off' : 'Apply Round Off';
    this.classList.toggle('btn-outline-secondary', !poRoundOffEnabled);
    this.classList.toggle('btn-secondary', poRoundOffEnabled);
    recalcTotals();
});


jQuery(document).ready(function() {
    initDatePicker("#addEditPurchaseOrders input[name='order_date']", { defaultDate: new Date() });
    initDatePicker("#addEditPurchaseOrders input[name='expected_delivery_date']");
});
</script>
@endpush
