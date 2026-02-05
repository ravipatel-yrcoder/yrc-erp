<div class="offcanvas offcanvas-end" tabindex="-1" id="receivePurchaseOrder" aria-labelledby="receivePurchaseOrderDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 50%;">

    <div class="offcanvas-header">
        <h5 id="receivePurchaseOrderDrawerTitle" class="offcanvas-title">Create purchase receive</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <!-- BODY -->
    <div class="offcanvas-body">
        <form id="receivePurchaseOrderForm">

            <input type="hidden" id="id" value="" />
            <input type="hidden" name="purchase_order_id" value="" />
            
            <div class="form-glob-feedback"></div>

            <div class="row g-3 mb-10">
                <div class="col-md-6">
                    <label class="form-label required">Vendor Name</label>
                    <input type="text" class="form-control" id="vendorName" value="" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Purchase Order #</label>
                    <input type="text" class="form-control" id="poNumber" value="" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Purchase Receipt #</label>
                    <input type="text" class="form-control" name="grn_number" value="" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Received Date</label>
                    <input type="text" class="form-control" name="posted_date" placeholder="DD/MM/YYYY">
                </div>
            </div>

            <div class="receive_items-section-feedback form-section-feedback"></div>
            <div class="mb-7">
                <h6 class="text-uppercase text-muted mb-3">Line Items</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="p-2" width="35%">Items & Description</th>
                                <th class="p-2 text-end">Ordered</th>
                                <th class="p-2 text-end">Received</th>
                                <th class="p-2 text-end">In Transit</th>
                                <th class="p-2 text-end">Qty. to Receive</th>
                                <th class="p-2" width="40"></th>
                            </tr>
                        </thead>
                        <tbody id="receiveItemsBody"></tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 d-none" id="addReceiveItemBtn"> <i class="bx bx-plus"></i> Add Item</button>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="3"></textarea>
            </div>

        </form>

    </div>

    <!-- FOOTER -->
     <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveReceivePurchaseOrderDraft" class="btn btn-primary btn-sm min-w-px-100">Save Draft</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>

</div>

@push('scripts')
<script>
const toggleAddReceiveItemButton = function() {
    
    const btn = document.querySelector('#receivePurchaseOrder #addReceiveItemBtn');
    if (selectedReceiveItemIds.size < receivableItems.length) {
        btn.classList.remove('d-none');
    } else {
        btn.classList.add('d-none');
    }
}


const getReceivableItemHtml = function(item) {
    const html = `<tr data-po-item-id=${item.po_item_id}>
        <td class="px-2">
            <input type="hidden" name="receive_items[${item.po_item_id}][po_item_id]" value="${item.po_item_id}">
            <div class="fw-semibold">${item.product_name}</div>
            <small class="text-muted">${item.description || '-'}</small>
        </td>

        <td class="px-2 text-end">${item.ordered_qty}</td>
        <td class="px-2 text-end">${item.received_qty}</td>
        <td class="px-2 text-end">${item.in_transit_qty}</td>

        <td class="px-2">
            <div class="d-flex justify-content-end">
                <input type="number" class="form-control text-end w-px-100" name="receive_items[${item.po_item_id}][receive_qty]" value="${item.remaining_qty}" min="0" max="${item.remaining_qty}">
            </div>
        </td>
        <td class="px-2 text-center">
            <button type="button" class="btn btn-sm btn-icon btn-text-danger remove-receivable-item"><i class="bx bx-trash text-danger cursor-pointer"></i></button>
        </td>
    </tr>`;

    return html;
}


const selectReceiveItem = function(_this) {

    const poItemId = parseInt(_this.value);
    if (!poItemId) {
        return;
    }

    const item = receivableItems.find(i => i.po_item_id === poItemId);
    if (!item) {
        jQuery(_this).val(null).trigger("change.select2");
        return;
    }

    
    if( selectedReceiveItemIds.has(item.po_item_id) ) {
        jQuery(_this).val(null).trigger("change.select2");
        return;
    }

    selectedReceiveItemIds.add(poItemId);

    const tr = jQuery(_this).closest('tr');
    tr.after(getReceivableItemHtml(item));
    tr.remove();

    toggleAddReceiveItemButton();
}


let selectedReceiveItemIds = new Set();
let receivableItems = [];
const openReceivePurchaseOrderFormDrawer = async function(poId) {
    
    const drawerEl = document.getElementById('receivePurchaseOrder');
    const formEl = document.getElementById('receivePurchaseOrderForm');

    let title = "Create purchase receive";
    if( id > 0 ) {
        title = "Edit purchase receive";
    }

    drawerEl.querySelector("#receivePurchaseOrderDrawerTitle").innerHTML = title;    

    // reset receivable items
    selectedReceiveItemIds.clear();
    receivableItems = [];

    // clean form feedback
    cleanFormInputFeedback(formEl);

    // reset receivable items table
    const receiveItemsTbodyEl = formEl.querySelector("#receiveItemsBody");
    receiveItemsTbodyEl.innerHTML = "";

    try {

        formEl.reset();        
        formEl.querySelector("input#id").value='';
        formEl.querySelector("input[name='purchase_order_id']").value = poId;

        const response = await api.get(`/purchase-orders/${poId}/receive/form-context`);

        const { data } = response.data;
        receivableItems = data.receivable_items || [];
        const vendorName = data.vendor_name || "";
        const poNumber = data.po_number || "";
        const receiptNumber = data.grn_number_preview || "";

        formEl.querySelector("input#vendorName").value = vendorName;
        formEl.querySelector("input#poNumber").value = poNumber;
        formEl.querySelector("input[name='grn_number']").value = receiptNumber;
        initDatePicker("#receivePurchaseOrder [name='posted_date']", {defaultDate: 'today'});
    
        // Render receivable items
        if (Array.isArray(receivableItems) && receivableItems.length > 0) {
            receivableItems.forEach(item => {
                const itemHtml = getReceivableItemHtml(item);
                receiveItemsTbodyEl.insertAdjacentHTML("beforeend", itemHtml);
                selectedReceiveItemIds.add(item.po_item_id);
            });
        }

        toggleAddReceiveItemButton();

        new bootstrap.Offcanvas(drawerEl).show();

    } catch(error) {
        handleApiError(error);
    }

}

document.addEventListener('click', function (e) {

    if (e.target.closest('.remove-receivable-item')) {
        
        const tr = e.target.closest('tr');
        const poItemId = parseInt(tr.dataset.poItemId);

        selectedReceiveItemIds.delete(poItemId);

        tr.remove();
        toggleAddReceiveItemButton();
    }

    if (e.target.closest('.remove-receivable-select-row')) {
        e.target.closest('tr').remove();
        toggleAddReceiveItemButton();
    }    
});

document.querySelector('#receivePurchaseOrder #addReceiveItemBtn').addEventListener('click', function () {
    
    const drawerEl = document.getElementById('receivePurchaseOrder');
    const tbody = drawerEl.querySelector('#receivePurchaseOrder #receiveItemsBody');

    const tr = document.createElement('tr');
    tr.classList.add('select-row');

    const options = receivableItems.map(item =>
        `<option value="${item.po_item_id}">${item.product_name}</option>`
    ).join('');

    tr.innerHTML = `
    <td class="px-2">
        <select class="form-select select-receive-item">
            <option value="">Select item</option>
            ${options}
        </select>
    </td>
    <td colspan="4"></td>
    <td class="px-2 text-center">
        <button type="button" class="btn btn-sm btn-icon btn-text-danger remove-receivable-item"><i class="bx bx-trash text-danger cursor-pointer"></i></button>
    </td>`;

    tbody.appendChild(tr);

    initSelect2(tr.querySelector(".select-receive-item"), {dropdownParent: drawerEl, onChange: selectReceiveItem});

});



const saveReceivePODraftBtn = document.getElementById('saveReceivePurchaseOrderDraft');
saveReceivePODraftBtn.addEventListener('click', async function(e) {
    
    const formEl = document.getElementById('receivePurchaseOrderForm');

    try {

        const id = formEl.querySelector('input#id').value || '';
        let apiPostfix = `/purchase-receipts`;
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


</script>
@endpush