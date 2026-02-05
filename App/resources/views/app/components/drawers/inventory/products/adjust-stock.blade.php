<style>
#addEditProductStock tags.tagify {padding: 0;}
#addEditProductStock tags.tagify .tagify__input {
    line-height: 1.375;
    padding: 0.543rem 0.9375rem;
    min-height: 2.125rem;
    margin: 0;
}
</style>
<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditProductStock" aria-labelledby="addEditProductStockDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="offcanvas-header">
        <h5 id="addEditProductStockDrawerTitle" class="offcanvas-title">Adjust stock</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="addEditProductStockForm">
            <div>
                <input type="hidden" name="product_id" value="" />
            </div>
            <div class="form-glob-feedback"></div>
            <div class="pb-5 mb-5 border-bottom">
                <h6 class="mb-2" id="productName"></h6>
                <div class="d-flex justify-content-between">                    
                    <small class="d-inline-flex align-items-center">Total stock<i class="bx bx-info-circle text-muted ms-1 cursor-pointer text-dark" data-bs-toggle="tooltip"  title="On-hand stock (Available + Reserved) across all locations."></i>:<span id="totalStock" class="ms-1 text-black fw-medium">0</span></small>
                    <small>Tracking: <span id="trackingMethod" class="text-black fw-medium">Serial</span></small>
                </div>
            </div>            
            <div class="mb-4">
                <label class="form-label required">Location</label>
                <select class="form-select" name="location_id" placeholder="Location">
                    <option></option>
                </select>
            </div>
            <div class="d-flex gap-4 mb-4">
                <div>
                    <label class="form-label d-inline-flex align-items-center">Qty. available <i class="bx bx-info-circle text-muted ms-1 cursor-pointer text-dark" data-bs-toggle="tooltip"  title="Available stock at the selected location (excluding reserved stock)."></i></label>
                    <input type="text" class="form-control" value="0" id="qtyAvailable" readonly disabled/>
                </div>
                <div>
                    <label class="form-label">New qty.</label>
                    <input type="text" class="form-control" value="0" id="newQty" readonly disabled/>
                </div>
            </div>

            <div class="mb-4" id="qtyAdjustedWrapper">
                <label class="form-label required">Qty. adjusted</label>
                <input type="text" name="quantity" class="form-control" placeholder="Ex: +10, -10"/>
                <small class="text-tiny">Positive to add stock and negative to reduce it.</small>
            </div>

            <div class="mb-4">
                <label class="form-label required">Note</label>
                <textarea class="form-control" name="notes"></textarea>                
            </div>

        </form>
    </div>
    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveAddEditProductStock" class="btn btn-primary btn-sm w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>
@push('scripts')
<script>
let serialLotTagify = null;
const initSerialLotTagify = function(mode='free', whitelist=[]) {

    console.log(whitelist);

    const input = document.querySelector("#addEditProductStockForm [name='serial_or_lot_numbers']");
    if (!input) return;

    // Destroy existing instance
    if (serialLotTagify) {
        serialLotTagify.destroy();
        serialLotTagify = null;
    }

    // reset values
    input.value = "";

    if (mode === 'whitelistonly') {
        
        input.setAttribute("placeholder","Select from the list");
        
        serialLotTagify = new Tagify(input, {
            whitelist: whitelist,
            userInput: false,            
        });
    } else {

        // free mode
        input.setAttribute("placeholder","Scan or enter numbers separated by comma");
        serialLotTagify = new Tagify(input);
    }
}

const toggleGenerateButton = function (show) {
    const btn = document.querySelector("#addEditProductStockForm #generateSerialOrLot");    
    if (!btn) return;
    
    btn.style.display = show ? '' : 'none';
};

const renderSerialOrLotNumbersSection = function() {

    const formEl = document.getElementById('addEditProductStockForm');

    // 1. HTML block
    const serialOrLotHtml = `
    <div class="mb-4 mt-6" id="serialOrLotWrapper">
        <div class="d-flex justify-content-between align-items-end mb-1">
            <small>
                Count: <span id="serialOrLotCount">0</span> | 
                <a href="javascript:void(0);" id="clearSerialOrLotNumbers">Clear All</a>
            </small>
            <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary py-1" id="generateSerialOrLot">
                <span class="icon-base bx bx-pie-chart-alt icon-xs me-1"></span>
                Generate serial numbers
            </a>
        </div>
        <input class="form-control" name="serial_or_lot_numbers" placeholder="Scan or enter numbers separated by comma" />
    </div>`;
    
    // 2. Insert after qtyAdjusted div
    const qtyAdjustedEl = formEl.querySelector("#qtyAdjustedWrapper");
    qtyAdjustedEl.insertAdjacentHTML("afterend", serialOrLotHtml);

    // 3. Init Tagify
    initSerialLotTagify();
    //const input = formEl.querySelector("[name='serial_or_lot_numbers']");
    //const tagify = new Tagify(input);


    // Bind click event on actions

    // clear serial or lot
    const clearSerialOrLotNumbersBtn = formEl.querySelector('#clearSerialOrLotNumbers');
    clearSerialOrLotNumbersBtn.addEventListener('click', async function(e) {

        const tagifyInput = document.querySelector("#addEditProductStockForm [name='serial_or_lot_numbers']");
        const tagify = tagifyInput.__tagify;
        if( tagify ) {
            tagify.removeAllTags();
        }

    });


    // generate serial or lot
    const generateSerialOrLotBtn = formEl.querySelector('#generateSerialOrLot');
    generateSerialOrLotBtn.addEventListener('click', async function(e) {
        
        const formEl = document.getElementById('addEditProductStockForm');
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
}

const computeNewStock = function() {

    let newStock = 0;

    const availStockEl = document.querySelector("#addEditProductStock #qtyAvailable");
    const stockChangeQtyEl = document.querySelector("#addEditProductStock input[name='quantity']");
    if( availStockEl && stockChangeQtyEl ) {
        
        const availStock = parseFloat(availStockEl.value || 0) || 0;
        const stockChangeQty = parseFloat(stockChangeQtyEl.value || 0) || 0;

        newStock = (availStock + stockChangeQty)
    }

    const newStockEl = document.querySelector("#addEditProductStock #newQty");
    if(newStockEl) {
        newStockEl.value = newStock;
    }
}

let stockByLocation = {};
const openAddEditProdStockDrawer = async function(prodId) {

    const drawerEl = document.getElementById('addEditProductStock');
    const formEl = document.getElementById('addEditProductStockForm');

    // clean form feedback
    cleanFormInputFeedback(formEl);

    // remove serialOrLotWrapper
    const serialOrLotWrapper = formEl.querySelector("#serialOrLotWrapper");
    if (serialOrLotWrapper) serialOrLotWrapper.remove();

    try {

        formEl.reset();    
        
        //const payload = {params: {id}};
        const payload = {};
        const response = await api.get(`/inv/products/${prodId}/stock/adjust/form-context`, payload);

        const { data } = response.data;
        const locations = data.locations || [];
        const product = data.product || {};
        const stockDetails = data.stock_details || {};
                
        (stockDetails.stock_by_location || []).forEach(item => { stockByLocation[item["location_id"]] = item;})


        const stockTrackingMethod = product.stock_tracking_method || "";
        if( stockTrackingMethod === "lot" || stockTrackingMethod === "serial" ) {
            renderSerialOrLotNumbersSection();
        }

        formEl.querySelector("input[name='product_id']").value=prodId;
        formEl.querySelector("#productName").innerHTML = product.name;
        formEl.querySelector("#totalStock").innerHTML = stockDetails.total_stock || 0;

        const locationsOptions = locations.map(item => {
            return {
                id: item.id,
                text: `${item.name} - (${item.code})`,
            };
        });

        
        // init locations select2
        const locationChange = function(_this) {
            
            const locationId = _this.value
            let availStock = 0;
            if( locationId ) {
                const stock = stockByLocation[locationId] || {};
                availStock = parseFloat(stock.available_qty || 0) || 0;
            }

            const availStockEl = document.querySelector("#addEditProductStock #qtyAvailable");
            if(availStockEl) {
                availStockEl.value = availStock;
            }

            computeNewStock();
        }
        const locationSelect2Selector = "#addEditProductStock select[name='location_id']";
        initSelect2(locationSelect2Selector, {dropdownParent: drawerEl, allowClear: false, data: locationsOptions, autoSelectSingle: true, onChange: locationChange});        


        /*
        // bind change event on location select2
        const locationSelect = document.querySelector(locationSelect2Selector);
        locationSelect.addEventListener("change", function () {

            alert("change");

            const locationId = this.value;
            if (!locationId) return;

            const stock = stockByLocation[locationId];
            
            if (!stock) return;

            const availableStock = parseFloat(stock.available_qty || 0) || 0;
            document.querySelector("#addEditProductStock #qtyAvailable").value = availableStock;
        });*/
        
        new bootstrap.Offcanvas(drawerEl).show();

    } catch(error) {

        handleApiError(error);
    }    
}

const saveAddEditProductStockButton = document.getElementById('saveAddEditProductStock');
saveAddEditProductStockButton.addEventListener('click', async function(e) {
    
    const formEl = document.getElementById('addEditProductStockForm');
    
    try {

        const productId = formEl.querySelector('[name="product_id"]').value || '';

        // clean form input feedback
        cleanFormInputFeedback(formEl);

        const formData = new FormData(formEl);
        const payload = Object.fromEntries(formData.entries());

        // Convert Tagify field "serial_or_lot_numbers" into array of values
        if (payload.serial_or_lot_numbers) {
            try {
                const parsed = JSON.parse(payload.serial_or_lot_numbers); // Tagify JSON string
                payload.serial_or_lot_numbers = Array.isArray(parsed)
                    ? parsed.map(item => item.value) // only values
                    : [];
            } catch (e) {
                payload.serial_or_lot_numbers = [];
            }
        }
        
        const response = await api.post(`/inv/products/${productId}/stock/adjust`, payload);
        const { code, message } = response.data;

        notyf.success(message);

        if( code == 201 || code == 200 ) {

            if( typeof(prodStockDt) != "undefined" ) {
                prodStockDt.ajax.reload()
            }

            const drawer = bootstrap.Offcanvas.getInstance(document.getElementById('addEditProductStock'));
            drawer.hide();

            formEl.reset();
        }

    } catch(error) {

        handleApiError(error, formEl);
    }

});


const qtyInput = document.querySelector("#addEditProductStock input[name='quantity']");
qtyInput.addEventListener("keydown", async function (e) {
    
    const val = e.target.value;
    const key = e.key;

    // Always allow: backspace, delete, arrows, tab
    if (["Backspace", "Delete", "ArrowLeft", "ArrowRight", "Tab"].includes(key)) {
        return;
    }

    // Allow digits always
    if (/^[0-9]$/.test(key)) {
        return;
    }

    // Allow + or - ONLY if input is empty
    if ((key === "+" || key === "-") && val.length === 0) {
        return;
    }

    // Block everything else
    e.preventDefault();
});

qtyInput.addEventListener("input", async function (e) {
    
    let val = e.target.value;

    // If starts with + → remove it
    if (val.startsWith("+")) {
        val = val.substring(1);
    }

    // Keep single leading -
    if (val.startsWith("-")) {
        val = "-" + val.substring(1).replace(/[^0-9]/g, "");
    } else {
        val = val.replace(/[^0-9]/g, "");
    }

    // POSITIVE → Free tagify
    if( val > 0 ) {
        toggleGenerateButton(true);
        initSerialLotTagify();
    }


    // NEGATIVE → Whitelist only tagify
    if( val < 0 ) {
        
        toggleGenerateButton(false);

        try {
            const productId = document.querySelector('#addEditProductStock [name="product_id"]').value;

            // Fetch predefined list
            const response = await api.get(`/inv/products/${productId}/serial-or-lot-numbers`);
            const { data } = response.data;

            const whitelist = data;
            
            /*(data || []).map(item => ({
                value: item
            }));*/

            initSerialLotTagify('whitelistonly', whitelist);

        } catch (error) {
            console.error(error);
        }

    }

    e.target.value = val;

    computeNewStock();
});


jQuery(document).ready(function(){
});
</script>
@endpush