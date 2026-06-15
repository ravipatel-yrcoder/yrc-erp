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

            <div class="form-glob-feedback"></div>

            <!-- ===================== -->
            <!-- GENERAL INFORMATION -->
            <!-- ===================== -->
            <div class="mb-7">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label required">Vendor</label>
                        <select class="form-select" name="vendor_id"></select>
                        <div class="mt-1 small text-muted">Currency: <span id="poCurrencyDisplay" class="fw-semibold text-body">-</span></div>
                        <input type="hidden" name="currency_code" id="po_currency_code" value="" />
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
                        <select class="form-select" name="payment_terms"></select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Internal notes or instructions"></textarea>
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
                                <th class="p-2" style="width: 35%">Items & Description</th>                                
                                <th class="p-2 text-end" style="width: 10%">Qty</th>
                                <th class="p-2 text-end" style="width: 12%">Unit cost</th>
                                <th class="p-2" style="width: 30%">Tax</th>
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
            <!-- TOTAL SUMMARY (OPTIONAL) -->
            <!-- ===================== -->
            <div class="row justify-content-end d-none">
                <div class="col-md-4">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th class="text-muted">Subtotal</th>
                            <td class="text-end">$0.00</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Tax</th>
                            <td class="text-end">$0.00</td>
                        </tr>
                        <tr class="border-top">
                            <th>Total</th>
                            <td class="text-end fw-bold">$0.00</td>
                        </tr>
                    </table>
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
let poItemIndx = 0;
const refreshPurchaseOrderForm = async function(id=0) {

    const drawerEl = document.getElementById('addEditPurchaseOrders');
    const formEl = document.getElementById('addEditPurchaseOrdersForm');

    let title = "Add purchase order";
    let saveBtnLabel = "Save as draft";
    if( id > 0 ) {
        title = "Edit purchase order";
        saveBtnLabel = "Save";
    }

    drawerEl.querySelector("#addEditPurchaseOrdersDrawerTitle").innerHTML = title;
    drawerEl.querySelector("#saveAddEditPurchaseOrders").innerHTML = saveBtnLabel;

    // clean form feedback
    cleanFormInputFeedback(formEl);

    try {

        formEl.reset();        
        formEl.querySelector("input#id").value='';
        formEl.querySelector("input[name='status']").value = "draft";
        
        const payload = {params: {id}};
        const response = await api.get('/purchase/orders/form-context', payload);

        const { data } = response.data;
        const poDetails = data.po_details || {};
        const vendors = data.vendors || [];
        const locations = data.locations || [];        
        const payment_terms = data.payment_terms || [];
        purchaseOrderAvailableProducts = data.products || [];
        purchaseOrderApplicableTaxes = data.taxes || [];
        const suggestedPoNumber = data.suggested_po_number ?? "";

        //console.log(purchaseOrderAvailableProducts);

        // init vendors select2
        poVendorsData = vendors;
        const vendorOptions = buildSelect2Options(vendors, {idKey: 'id', textKey: ['vendor_code', 'display_name']});
        initSelect2("#addEditPurchaseOrders select[name='vendor_id']", {
            dropdownParent: drawerEl,
            placeholder: "Choose vendor",
            data: vendorOptions,
            onChange: function(el) {
                const vendorId = Number(jQuery(el).val() || 0);
                const vendor = poVendorsData.find(v => Number(v.id) === vendorId);
                const currency = vendor?.currency_code || window.sysDefaultConfig?.currency || 'INR';
                poActiveCurrency = currency;
                document.getElementById('po_currency_code').value = currency;
                document.getElementById('poCurrencyDisplay').textContent = currency;
            }
        });
        
        // init locations select2
        initSelect2("#addEditPurchaseOrders select[name='location_id']", {dropdownParent: drawerEl, placeholder:"Choose location", data: buildSelect2Options(locations)});
        
        // init locations select2
        initSelect2("#addEditPurchaseOrders select[name='payment_terms']", {dropdownParent: drawerEl, placeholder:"Choose terms", data: buildSelect2Options(payment_terms, {idKey: 'name'})});

        const poItemsTbodyEl = formEl.querySelector("#po_line_items tbody");
        poItemsTbodyEl.innerHTML = "";

        if( !(id > 0) ) {

            // populate suggested po number
            const poNumberInput = formEl.querySelector("input[name='po_number']");
            if( poNumberInput ) {
                poNumberInput.value= suggestedPoNumber;
                poNumberInput.dataset.value = suggestedPoNumber;
            }
            
            // populate one item default
            const itemHtml = getPOLineItemHtml();            
            poItemsTbodyEl.insertAdjacentHTML("beforeend", itemHtml);

            const newRow = poItemsTbodyEl.lastElementChild;
            initRowSelect2(newRow);
        }
        
        populatePurchaseOrderForm(poDetails);        

    } catch(err) {

        //console.log(err);
        handleApiError(err);
    }
}


const populatePurchaseOrderForm = function(poDetails) {
    
    if (Object.keys(poDetails).length === 0) return;

    const drawerEl = document.getElementById('addEditPurchaseOrders');
    const formEl = drawerEl.querySelector('#addEditPurchaseOrdersForm');

    const {
        id,
        status,
        vendor_id,
        currency_code,
        location_id,
        po_number,
        reference,
        order_date,
        expected_delivery_date,
        payment_terms,
        notes,
        line_items=[]
    } = poDetails;

    jQuery("#addEditPurchaseOrders input#id").val(id);
    jQuery("#addEditPurchaseOrders [name='vendor_id']").val(vendor_id).trigger("change");

    if (currency_code) {
        poActiveCurrency = currency_code;
        document.getElementById('po_currency_code').value = currency_code;
        document.getElementById('poCurrencyDisplay').textContent = currency_code;
    }
    jQuery("#addEditPurchaseOrders [name='location_id']").val(location_id).trigger("change");
    jQuery("#addEditPurchaseOrders [name='po_number']").val(po_number || "");
    jQuery("#addEditPurchaseOrders [name='reference']").val(reference || "");
    jQuery("#addEditPurchaseOrders [name='payment_terms']").val(payment_terms).trigger("change");
    jQuery("#addEditPurchaseOrders [name='notes']").val(notes || "");

    datePickerSetDate("#addEditPurchaseOrders [name='order_date']", order_date || "");
    datePickerSetDate("#addEditPurchaseOrders [name='expected_delivery_date']", expected_delivery_date || "");

    // populate line items
    const tbodyEl = drawerEl.querySelector("#po_line_items tbody");
    tbodyEl.innerHTML = "";
    poItemIndx = 0;

    // Edit mode → render saved items
    if (Array.isArray(line_items) && line_items.length > 0) {
        line_items.forEach(item => {
            
            const itemHtml = getPOLineItemHtml(item);
            tbodyEl.insertAdjacentHTML("beforeend", itemHtml);

            const newRow = tbodyEl.lastElementChild;
            initRowSelect2(newRow);

            const prodId = item.product_id || null;
            const taxInfo = item.tax_info || [];
            //console.log(taxInfo);
            const taxIds = taxInfo.map(taxItem => Number(taxItem.id));

            jQuery(newRow).find("select.items").val(prodId).trigger("change");
            jQuery(newRow).find("select.taxes").val(taxIds).trigger("change");

        });
    }
}


const getPOLineItemHtml = function(savedItem={}) {

    const {
        id = "",
        description = "",
        ordered_qty = "",
        unit_price = "",
        line_total = "0.00",        
        uom_id = "",        
    } = savedItem;

    const orderQty = formatQty(ordered_qty);
    const unitPrice = parseFloat(unit_price) || 0;
    const unitPriceFormatted = formatPrice(unitPrice);
    const lineTotal = formatCurrency(line_total, { currency: poActiveCurrency });
    
    const productOptions = purchaseOrderAvailableProducts.map(product => {
        return `<option value="${product.id}" data-price="${product.cost_price}">${product.name}</option>`;
    }).join("");

    const taxOptions = purchaseOrderApplicableTaxes.map(tax => {
        return `<option value="${tax.id}" data-rate="${tax.rate}">${tax.name}</option>`;
    }).join("");

    const html = `<tr data-index="${poItemIndx}">
        <td class="ps-0 pe-2">
            <select class="form-select items select2-field" name="po_items[${poItemIndx}][product_id]">
                ${productOptions}
            </select>
            <textarea class="mt-1 form-control" name="po_items[${poItemIndx}][description]">${description || ""}</textarea>
            <input type="hidden" name="po_items[${poItemIndx}][id]" value="${id}" />
        </td>
        <td class="px-2 qty">
            <input type="text" class="px-1 form-control text-end po-item-qty" name="po_items[${poItemIndx}][qty]" placeholder="1" value="${orderQty}">
            <input type="hidden" class="uom-id" name="po_items[${poItemIndx}][uom_id]" value="${uom_id}" />
        </td>
        <td class="px-2">
            <input type="text" class="px-1 form-control text-end po-item-price" placeholder="0.00" value="${unitPriceFormatted}">
            <input type="hidden" class="unit-cost-hidden" name="po_items[${poItemIndx}][unit_cost]" value="${unitPrice}">
        </td>
        <td class="px-2">
            <select class="form-select taxes select2-field" name="po_items[${poItemIndx}][tax][]">
                ${taxOptions}
            </select>            
        </td>        
        <td class="px-2 text-end fw-semibold line-total">${lineTotal}</td>
        <td class="px-2 text-center">
            <button type="button" class="btn btn-sm btn-icon btn-text-danger po-remove-item"><i class="bx bx-trash text-danger cursor-pointer"></i></button>
        </td>
    </tr>`;

    poItemIndx++;

    return html;
}


const initRowSelect2 = function(rowElement) {

    const drawerEl = rowElement.closest('#addEditPurchaseOrders');
    if( !drawerEl ) return;

    const itemsSelect2El = rowElement.querySelector("select.items");
    const taxesSelect2El = rowElement.querySelector("select.taxes");

    if( itemsSelect2El ) {
        const prodChange = function(_this) {
            const row = _this.closest('tr');
            const prodId = _this.value || "";
            
            const qtyTdEl = row.querySelector("td.qty");
            const uomLabelEl = qtyTdEl.querySelector("span.uom-label");
            if( uomLabelEl ) {
                uomLabelEl.remove();
            }

            let itemUom = "";
            if( prodId ) {
                
                const productsMap = new Map(purchaseOrderAvailableProducts.map(product => [Number(product.id), product]));
                const selectedProduct = productsMap.get(Number(prodId));
                const itemBaseUom = selectedProduct?.uoms.find(
                    uom => Number(uom.is_base_uom) === 1
                );

                if( itemBaseUom ) {
                    itemUom = itemBaseUom.uom_id || "";
                    const itemUomLabel = itemBaseUom.code || "";
                    if( itemUomLabel ) {
                        qtyTdEl.insertAdjacentHTML('beforeend', `<span class="uom-label fs-tiny mt-1 text-primary fw-semibold">UOM: ${itemUomLabel}</span>`);
                    }
                }
            }

            qtyTdEl.querySelector("input.uom-id").value = itemUom;
            
        }
        initSelect2(itemsSelect2El, {dropdownParent: drawerEl, placeholder: "Choose item", onChange: prodChange});
    }

    if( taxesSelect2El ) {
        const taxChange = function(_this) {
            const row = _this.closest('tr');
            calculateLineAmount(row); 
        }
        initSelect2(taxesSelect2El, {dropdownParent: drawerEl, placeholder: "Choose taxes", multiple: true, onChange: taxChange});
    }
}


const calculateLineAmount = function(rowEl) {
    
    const qtyEl = rowEl.querySelector('.po-item-qty');
    const unitCostEl = rowEl.querySelector('.po-item-price');
    const taxSelectEl = rowEl.querySelector('.taxes');
    const lineTotalEl = rowEl.querySelector('.line-total');

    const qty = parseFloat(qtyEl.value) || 0;
    const unitCost = parseFloat(unformatNumber(unitCostEl.value)) || 0;

    const subTotal = qty * unitCost;
    
    let totalTaxRate = 0;
    Array.from(taxSelectEl.selectedOptions).forEach(option => {
        totalTaxRate += parseFloat(option.dataset.rate) || 0;
    });

    const taxAmount = subTotal * (totalTaxRate / 100);    
    const lineTotal = subTotal + taxAmount;

    lineTotalEl.innerHTML = formatCurrency(lineTotal, { currency: poActiveCurrency });
}


let purchaseOrderAvailableProducts = [];
let purchaseOrderApplicableTaxes = [];
let poVendorsData = [];
let poActiveCurrency = window.sysDefaultConfig?.currency || 'INR';
const openPurchaseOrderFormDrawer = async function(id = 0) {
    refreshPurchaseOrderForm(id);
    const drawerEl = document.getElementById('addEditPurchaseOrders');
    new bootstrap.Offcanvas(drawerEl).show();
}


const saveAddEditPurchaseOrdersButton = document.getElementById('saveAddEditPurchaseOrders');
saveAddEditPurchaseOrdersButton.addEventListener('click', async function(e) {

    var btn = this;
    const formEl = document.getElementById('addEditPurchaseOrdersForm');

    const id = formEl.querySelector('input#id').value || '';

    let apiPostfix = `/purchase/orders`;
    if( id ) {
        apiPostfix += `/${id}`;
    }
    // clean form input feedback
    cleanFormInputFeedback(formEl);

    const formData = new FormData(formEl);
    const payload = formDataToObject(formData);

    setButtonLoading(btn, true);
    try {

        const response = await api.post(apiPostfix, payload);
        const { code, message, data } = response.data;

        notyf.success(message);

        if( code == 201 || code == 200 ) {

            if( id ) {

                if (typeof refreshPurchaseOrderDetails === 'function') refreshPurchaseOrderDetails(id);
                if (typeof refreshPurchaseOrderHistory === 'function') refreshPurchaseOrderHistory(id);
                if (typeof purchaseOrdersDt !== 'undefined') purchaseOrdersDt.ajax.reload();

                const drawer = bootstrap.Offcanvas.getInstance(document.getElementById('addEditPurchaseOrders'));
                drawer.hide();

                formEl.reset();

            } else {
                window.location.href = `/purchase/orders/${data.po_id}/`;
            }

            /*
            if( typeof(purchaseOrdersDt) != "undefined" ) {
                purchaseOrdersDt.ajax.reload()
            }

            refreshPurchaseOrderForm(data.po_id);
            */

            /*
            const drawer = bootstrap.Offcanvas.getInstance(document.getElementById('addEditPurchaseOrders'));
            drawer.hide();

            formEl.reset();
            */
        }

    } catch(error) {

        handleApiError(error, formEl);
    } finally {
        setButtonLoading(btn, false);
    }

});


// Add PO Item
const addPOItemBtn = document.getElementById('add_po_item');
addPOItemBtn.addEventListener('click', async function(e) {
    
    const poItemsTbodyEl = document.querySelector("#addEditPurchaseOrdersForm #po_line_items tbody");
    const itemHtml = getPOLineItemHtml();    
    poItemsTbodyEl.insertAdjacentHTML("beforeend", itemHtml);

    const newRow = poItemsTbodyEl.lastElementChild;
    initRowSelect2(newRow);
});


// Remove PO Item
const poLineItemsTableEl = document.querySelector("#addEditPurchaseOrdersForm #po_line_items");
poLineItemsTableEl.addEventListener("click", function(event) {
  
    const removeBtn = event.target.closest(".po-remove-item");
    if (!removeBtn) return;

    const rowEl = removeBtn.closest("tr");
    if (!rowEl) return;

    rowEl.remove();
});


// quantity change
document.addEventListener('change', function (e) {

    if ( !e.target.classList.contains('po-item-qty') ) return;

    const row = e.target.closest('tr');
    calculateLineAmount(row); 
});


// cost change
document.addEventListener('change', function (e) {

    if (!e.target.classList.contains('po-item-price')) return;

    const input = e.target;
    const rawValue = unformatNumber(input.value);

    // update hidden field
    const hidden = input.closest('tr').querySelector('.unit-cost-hidden');
    if (hidden) {
        hidden.value = rawValue;
    }

    // re-format display
    input.value = formatPrice(rawValue);

    calculateLineAmount(input.closest('tr'));
});

jQuery(document).ready(function(){

    initDatePicker("#addEditPurchaseOrders input[name='order_date']", {
        defaultDate: new Date()
    });

    initDatePicker("#addEditPurchaseOrders input[name='expected_delivery_date']");    
});
</script>
@endpush