<!-- Add Serial Numbers Modal -->
<div class="modal stacked-modal fade" data-bs-backdrop="static" id="addLotSerialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title">Add Serial Numbers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">

                <form id="addSerialLotForm">
                    
                    <input type="hidden" name="source" />
                    <input type="hidden" name="product_id" />
                    <input type="hidden" name="quantity" />

                    <div class="form-glob-feedback"></div>

                    <!-- Location + Option -->
                    <div class="d-flex justify-content-between align-items-center mb-3 d-none">
                        <div class="text-muted">
                            <i class="bx bx-map me-1"></i>
                            Location : <strong>-</strong>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="overwriteZeroQty">
                            <label class="form-check-label" for="overwriteZeroQty">
                                Overwrite the line item with 0 quantities
                                <i class="bx bx-info-circle ms-1 text-muted" data-bs-toggle="tooltip" title="If enabled, remaining quantity will be set to zero"></i>
                            </label>
                        </div>
                    </div>

                    <hr class="my-2 d-none">

                    <!-- Product Info -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>Prod: <div class="fw-semibold" id="prodName">-</div></div>
                        <div class="text-end">Qty: <div class="fw-semibold" id="quantity">0</div></div>
                    </div>

                    <!-- Count + Generate -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small> Count: <span id="serialLotCount">0</span> | <a href="javascript:void(0);" id="clearSerialOrLotNumbers">Clear All</a></small>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="generateSerialOrLot"><i class="icon-base bx bx-refresh icon-xs me-1"></i> Generate serial numbers</button>
                    </div>

                    <!-- Serial Input -->
                    <div class="mb-3">
                        <input class="form-control" name="serial_or_lot_numbers" placeholder="Scan or enter numbers separated by comma" />
                        <small class="text-muted">Each number must be unique. Press comma or scan barcode.</small>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-primary" id="saveSerialLotsBtn">Save</button>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>

        </div>
    </div>
</div>


@push('scripts')
<script>
let addSerialLotTagify = null;
const initAddSerialLotTagify = function(numbers) {

    const input = document.querySelector("#addLotSerialModal [name='serial_or_lot_numbers']");
    if (!input) return;

    // Destroy existing instance
    if (addSerialLotTagify) {
        addSerialLotTagify.destroy();
        addSerialLotTagify = null;
    }

    // reset values
    input.value = "";
    addSerialLotTagify = new Tagify(input);

    addSerialLotTagify.on('add', updateAddSerialLotCount);
    addSerialLotTagify.on('remove', updateAddSerialLotCount);
    addSerialLotTagify.on('input', updateAddSerialLotCount);

    // Populate existing numbers
    if (Array.isArray(numbers) && numbers.length) {
        addSerialLotTagify.addTags(numbers);
    }    
}

const updateAddSerialLotCount = function () {
    
    const countEl = document.querySelector('#addLotSerialModal #serialLotCount');
    if (!addSerialLotTagify || !countEl) return;

    countEl.textContent = addSerialLotTagify.value.length;
};

const openAddLotSerialModal = function(source, prod_id, prod_name, qty, trackingMethod, numbers=[]) {

    const modalEl = document.getElementById('addLotSerialModal');
    const formEl = document.getElementById('addSerialLotForm');

    
    // clean form feedback
    cleanFormInputFeedback(formEl);

    try {

        formEl.reset(); 
        formEl.querySelector("input[name='source']").value = source;
        formEl.querySelector("input[name='product_id']").value = prod_id;
        formEl.querySelector("input[name='quantity']").value = qty;
        formEl.querySelector("#prodName").innerHTML = prod_name;
        formEl.querySelector("#quantity").innerHTML = qty;
        formEl.querySelector("#serialLotCount").innerHTML = 0;

        initAddSerialLotTagify(numbers);
        
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
        
    } catch(err) {
        //console.log(err);
    }    

}

// clear serial or lot
const clearSerialOrLotNumbersBtn1 = document.querySelector('#addLotSerialModal #clearSerialOrLotNumbers');
clearSerialOrLotNumbersBtn1.addEventListener('click', async function(e) {

    const tagifyInput = document.querySelector("#addLotSerialModal [name='serial_or_lot_numbers']");
    const tagify = tagifyInput.__tagify;
    if( tagify ) {
        tagify.removeAllTags();
        updateAddSerialLotCount();        
    }
});


// generate serial or lot
const generateSerialOrLotBtn1 = document.querySelector('#addLotSerialModal  #generateSerialOrLot');
generateSerialOrLotBtn1.addEventListener('click', async function(e) {
    
    const formEl = document.getElementById('addSerialLotForm');
    try {

        // clean form input feedback
        cleanFormInputFeedback(formEl);
        
        const productId = formEl.querySelector('[name="product_id"]').value || '';
        const count = formEl.querySelector('[name="quantity"]').value || '';
        
        
        const payload = {product_id: productId, count};
        const response = await api.post(`/inv/sequence/generate/`, payload);
        const { code, message, data } = response.data;

        const tagifyInput = document.querySelector("[name='serial_or_lot_numbers']");
        const tagify = tagifyInput.__tagify;
        if( tagify ) {
            data.forEach(function(item){
                tagify.addTags([item]);
            });
        }

    } catch(error) {
        handleApiError(error, formEl);
    }
});

const addSerialLotSaveBtn = document.querySelector('#addLotSerialModal  #saveSerialLotsBtn');
addSerialLotSaveBtn.addEventListener('click', async function(e) {

    const formEl = document.getElementById('addSerialLotForm');
    const source = formEl.querySelector('[name="source"]').value || 0;
    const prodId = formEl.querySelector('[name="product_id"]').value || 0;
    let numbers = formEl.querySelector('[name="serial_or_lot_numbers"]').value || [];

    // Convert Tagify field "serial_or_lot_numbers" into array of values
    if (numbers) {
        try {
            const parsed = JSON.parse(numbers); // Tagify JSON string
            numbers = Array.isArray(parsed) ? parsed.map(item => item.value) : [];
        } catch (e) {
            numbers = [];
        }
    }

    if( source === "receive_purchase" ) {

        const receivePurchaseForm = document.getElementById(`receivePurchaseOrderForm`);
        const receiveItemTr = receivePurchaseForm.querySelector(`tr[data-po-item-prod-id="${prodId}"]`);
        if( !receiveItemTr ) return;

        const poItemId = receiveItemTr.dataset.poItemId || 0;

        let serialLotNumberInputs = '';    
        numbers.forEach(number => {
            serialLotNumberInputs +=`<input type='hidden' name='receive_items[${poItemId}][serial_or_lot_numbers][]' value='${number}' />`;
        });

        const receieveItemActionBtn = receiveItemTr.querySelector("a.add-serial-lot");
        if( !receieveItemActionBtn ) return;

        const tracking = receieveItemActionBtn.dataset.tracking || '';
        const actionBtnHtml = numbers.length === 0 ? `<i class="bx bx-error-circle text-warning fs-6" data-bs-toggle="tooltip" title="${tracking} numbers required before receiving"></i> Add ${tracking}` : `<i class="bx bx-show text-info fs-6" data-bs-toggle="tooltip" title="View/Edit ${tracking} numbers"></i> View/Edit ${tracking}`;
        receieveItemActionBtn.innerHTML = actionBtnHtml;

        receiveItemTr.querySelector(".serial-lot-numbers").innerHTML = serialLotNumberInputs;
    }    


    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addLotSerialModal'));
    modal.hide();

});
</script>
@endpush