<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditPurchaseOrders" aria-labelledby="addEditPurchaseOrdersDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 50%;">

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
                                <th class="p-2" style="width: 40%">Items & Description</th>                                
                                <th class="p-2 text-end" style="width: 10%">Qty</th>
                                <th class="p-2 text-end" style="width: 12%">Unit cost</th>
                                <th class="p-2" style="width: 20%">Tax</th>
                                <th class="p-2 text-end" style="width: 15%">Amount</th>
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
        const response = await api.get('/purchase-orders/form-context', payload);

        const { data } = response.data;
        const poDetails = data.po_details || {};
        const vendors = data.vendors || [];
        const locations = data.locations || [];        
        const payment_terms = data.payment_terms || [];
        purchaseOrderAvailableProducts = data.products || [];

        // init vendors select2
        const vendorOptions = buildSelect2Options(vendors, {idKey: 'id', textKey: ['vendor_code', 'display_name']});
        initSelect2("#addEditPurchaseOrders select[name='vendor_id']", {dropdownParent: drawerEl, placeholder:"Choose vendor", data: vendorOptions});
        
        // init locations select2
        initSelect2("#addEditPurchaseOrders select[name='location_id']", {dropdownParent: drawerEl, placeholder:"Choose location", data: buildSelect2Options(locations)});
        
        // init locations select2
        initSelect2("#addEditPurchaseOrders select[name='payment_terms']", {dropdownParent: drawerEl, placeholder:"Choose terms", data: buildSelect2Options(payment_terms, {idKey: 'name'})});

        const poItemsTbodyEl = formEl.querySelector("#po_line_items tbody");
        poItemsTbodyEl.innerHTML = "";

        if( !(id > 0) ) {
            
            // populate one item default
            const itemHtml = getPOLineItemHtml();            
            poItemsTbodyEl.insertAdjacentHTML("beforeend", itemHtml);
        }
        
        populatePurchaseOrderForm(poDetails);        

    } catch(err) {

        //console.log(err);
        handleApiError(error);
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
        });
    }
}

const getPOLineItemHtml = function(savedItem={}) {

    const {
        id = "",
        product_id = "",
        description = "",
        ordered_qty = "",
        unit_price = "",
        line_total = "0.00",
        tax_id = ""
    } = savedItem;

    const orderQty = formatQty(ordered_qty);
    const unitPrice = parseFloat(unit_price) || 0;
    const unitPriceFormatted = formatPrice(unitPrice);
    const lineTotal = formatCurrency(line_total);
    
    const productOptions = purchaseOrderAvailableProducts.map(product => {
        const selected = product.id == product_id ? "selected" : "";
        return `<option value="${product.id}" ${selected}>${product.name}</option>`;
    }).join("");

    const html = `<tr data-index="${poItemIndx}">
        <td class="ps-0 pe-2">
            <select class="form-select item-details" name="po_items[${poItemIndx}][product_id]">
                <option value="">Select item</option>
                ${productOptions}
            </select>
            <textarea class="mt-1 form-control" name="po_items[${poItemIndx}][description]">${description || ""}</textarea>
            <input type="hidden" name="po_items[${poItemIndx}][id]" value="${id}" />
        </td>
        <td class="px-2">
            <input type="text" class="px-1 form-control text-end po-item-qty" name="po_items[${poItemIndx}][qty]" placeholder="1" value="${orderQty}">
        </td>
        <td class="px-2">
            <input type="text" class="px-1 form-control text-end po-item-price" placeholder="0.00" value="${unitPriceFormatted}">
            <input type="hidden" class="unit-cost-hidden" name="po_items[${poItemIndx}][unit_cost]" value="${unitPrice}">
        </td>
        <td class="px-2">
            <select class="form-select item-details" name="po_items[${poItemIndx}][tax]">
                <option value="">Select tax</option>                
            </select>            
        </td>        
        <td class="px-2 text-end fw-semibold">${lineTotal}</td>
        <td class="px-2 text-center">
            <button type="button" class="btn btn-sm btn-icon btn-text-danger po-remove-item"><i class="bx bx-trash text-danger cursor-pointer"></i></button>
        </td>
    </tr>`;

    poItemIndx++;

    return html;
}


let purchaseOrderAvailableProducts = [];
const openPurchaseOrderFormDrawer = async function(id=0) {
    
    refreshPurchaseOrderForm(id);
        
    const drawerEl = document.getElementById('addEditPurchaseOrders');
    new bootstrap.Offcanvas(drawerEl).show();
}

const saveAddEditPurchaseOrdersButton = document.getElementById('saveAddEditPurchaseOrders');
saveAddEditPurchaseOrdersButton.addEventListener('click', async function(e) {
    
    const formEl = document.getElementById('addEditPurchaseOrdersForm');

    try {

        const id = formEl.querySelector('input#id').value || '';

        let apiPostfix = `/purchase-orders`;
        if( id ) {
            apiPostfix += `/${id}`;
        }
        // clean form input feedback
        cleanFormInputFeedback(formEl);

        const formData = new FormData(formEl);
        const payload = formDataToObject(formData)

        const response = await api.post(apiPostfix, payload);
        const { code, message, data } = response.data;

        notyf.success(message);

        if( code == 201 || code == 200 ) {

            if( id ) {

                refreshPurchaseOrderDetails(id);

                const drawer = bootstrap.Offcanvas.getInstance(document.getElementById('addEditPurchaseOrders'));
                drawer.hide();

                formEl.reset();

            } else {
                window.location.href = `/purchase-orders/${data.po_id}/`;
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
    }

});

// Add PO Item
const addPOItemBtn = document.getElementById('add_po_item');
addPOItemBtn.addEventListener('click', async function(e) {
    
    const poItemsTbodyEl = document.querySelector("#addEditPurchaseOrdersForm #po_line_items tbody");
    const itemHtml = getPOLineItemHtml();    
    poItemsTbodyEl.insertAdjacentHTML("beforeend", itemHtml);
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
});

jQuery(document).ready(function(){

    initDatePicker("#addEditPurchaseOrders input[name='order_date']", {
        defaultDate: new Date()
    });

    initDatePicker("#addEditPurchaseOrders input[name='expected_delivery_date']");    
});
</script>
@endpush