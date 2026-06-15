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
            <input type="hidden" name="product_id" value="" />
            <div class="form-glob-feedback"></div>

            {{-- Product selector — shown only when opened without a product ID --}}
            <div id="adjProductSelectWrapper" class="d-none mb-4">
                <label class="form-label required">Product</label>
                <select class="form-select" id="adjProductSelect"></select>
            </div>

            {{-- Form body — hidden until a product is loaded --}}
            <div id="adjFormBody" class="d-none">
                <div class="pb-5 mb-5 border-bottom">
                    <h6 class="mb-3" id="productName"></h6>
                    <div class="d-flex justify-content-between mb-1">
                        <small class="d-inline-flex align-items-center">Total stock<i class="bx bx-info-circle text-muted ms-1 cursor-pointer text-dark" data-bs-toggle="tooltip" title="On-hand stock (Available + Reserved) across all locations."></i>:<span id="totalStock" class="ms-1 text-black fw-medium">0</span></small>
                        <small>Tracking: <span id="trackingMethod" class="text-black fw-medium">Serial</span></small>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="d-inline-flex align-items-center">UOM<i class="bx bx-info-circle text-muted ms-1 cursor-pointer text-dark" data-bs-toggle="tooltip" title="Unit of measurement"></i>:<span id="uom" class="ms-1 text-black fw-medium">-</span></small>
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
                        <label class="form-label d-inline-flex align-items-center">On-hand qty. <i class="bx bx-info-circle text-muted ms-1 cursor-pointer text-dark" data-bs-toggle="tooltip" title="Physical stock at the selected location (includes reserved stock)."></i></label>
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
            </div>

        </form>
    </div>
    <div class="offcanvas-footer d-none" id="addEditProductStockFooter">
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
    const input = document.querySelector("#addEditProductStockForm [name='serial_or_lot_numbers']");
    if (!input) return;
    if (serialLotTagify) {
        serialLotTagify.destroy();
        serialLotTagify = null;
    }
    input.value = "";
    if (mode === 'whitelistonly') {
        input.setAttribute("placeholder", "Select from the list");
        serialLotTagify = new Tagify(input, { whitelist: whitelist, userInput: false });
    } else {
        input.setAttribute("placeholder", "Scan or enter numbers separated by comma");
        serialLotTagify = new Tagify(input);
    }
};

const toggleGenerateButton = function(show) {
    const btn = document.querySelector("#addEditProductStockForm #generateSerialOrLot");
    if (!btn) return;
    btn.style.display = show ? '' : 'none';
};

const renderSerialOrLotNumbersSection = function() {
    const formEl = document.getElementById('addEditProductStockForm');
    const serialOrLotHtml = `
    <div class="mb-4 mt-6" id="serialOrLotWrapper">
        <div class="d-flex justify-content-between align-items-end mb-1">
            <small>
                Count: <span id="serialOrLotCount">0</span> |
                <a href="javascript:void(0);" id="clearSerialOrLotNumbers">Clear All</a>
            </small>
            <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary py-1" id="generateSerialOrLot">
                <span class="icon-base bx bx-refresh icon-xs me-1"></span>
                Generate serial numbers
            </a>
        </div>
        <input class="form-control" name="serial_or_lot_numbers" placeholder="Scan or enter numbers separated by comma" />
    </div>`;
    const qtyAdjustedEl = formEl.querySelector("#qtyAdjustedWrapper");
    qtyAdjustedEl.insertAdjacentHTML("afterend", serialOrLotHtml);
    initSerialLotTagify();

    formEl.querySelector('#clearSerialOrLotNumbers').addEventListener('click', function() {
        const tagify = document.querySelector("#addEditProductStockForm [name='serial_or_lot_numbers']").__tagify;
        if (tagify) tagify.removeAllTags();
    });

    formEl.querySelector('#generateSerialOrLot').addEventListener('click', async function() {
        const formEl = document.getElementById('addEditProductStockForm');
        try {
            cleanFormInputFeedback(formEl);
            const productId = formEl.querySelector('[name="product_id"]').value || '';
            const qty     = parseInt(formEl.querySelector('[name="quantity"]').value) || 0;
            const already = serialLotTagify ? serialLotTagify.value.length : 0;
            const needed  = Math.max(0, qty - already);
            if (needed === 0) return;
            const response = await api.post(`/inv/sequence/generate/`, { product_id: productId, count: needed });
            const { data } = response.data;
            const tagify = document.querySelector("[name='serial_or_lot_numbers']").__tagify;
            if (tagify) data.forEach(item => tagify.addTags([item]));
        } catch(error) {
            handleApiError(error, formEl);
        }
    });
};

const computeNewStock = function() {
    const availStockEl     = document.querySelector("#addEditProductStock #qtyAvailable");
    const stockChangeQtyEl = document.querySelector("#addEditProductStock input[name='quantity']");
    if (availStockEl && stockChangeQtyEl) {
        const availStock     = parseFloat(availStockEl.value || 0) || 0;
        const stockChangeQty = parseFloat(stockChangeQtyEl.value || 0) || 0;
        const newStockEl     = document.querySelector("#addEditProductStock #newQty");
        if (newStockEl) newStockEl.value = availStock + stockChangeQty;
    }
};

let stockByLocation = {};

const loadAdjFormContext = async function(prodId, drawerEl, formEl, showProductName = true) {
    try {
        const response       = await api.get(`/inv/products/${prodId}/stock/adjust/form-context`, {});
        const { data }       = response.data;
        const locations      = data.locations || [];
        const product        = data.product || {};
        const stockDetails   = data.stock_details || {};
        const uomCode        = product.uom_code || "";
        const uomName        = product.uom_name || "-";
        const stockTrackingMethod = product.stock_tracking_method || "-";

        stockByLocation = {};
        (stockDetails.stock_by_location || []).forEach(item => { stockByLocation[item.location_id] = item; });

        if (stockTrackingMethod === "lot" || stockTrackingMethod === "serial") {
            renderSerialOrLotNumbersSection();
        }

        formEl.querySelector("input[name='product_id']").value = prodId;
        formEl.querySelector("#totalStock").innerHTML          = (stockDetails.total_stock || 0) + " " + uomCode;
        formEl.querySelector("#trackingMethod").innerHTML      = ucFirst(stockTrackingMethod);
        formEl.querySelector("#uom").innerHTML                 = ucFirst(uomName);

        const productNameEl = formEl.querySelector("#productName");
        if (showProductName) {
            productNameEl.innerHTML = product.name;
            productNameEl.classList.remove('d-none');
        } else {
            productNameEl.innerHTML = '';
            productNameEl.classList.add('d-none');
        }

        const locationsOptions = locations.map(item => ({ id: item.id, text: item.code ? `${item.name} - (${item.code})` : item.name }));
        const locationChange = function(_this) {
            const locationId = _this.value;
            let availStock = 0;
            if (locationId) {
                const stock = stockByLocation[locationId] || {};
                availStock = parseFloat(stock.unrestricted_qty || 0) || 0;
            }
            const availStockEl = document.querySelector("#addEditProductStock #qtyAvailable");
            if (availStockEl) availStockEl.value = availStock;
            computeNewStock();
        };
        initSelect2("#addEditProductStock select[name='location_id']", {
            dropdownParent: drawerEl,
            allowClear: false,
            data: locationsOptions,
            autoSelectSingle: true,
            onChange: locationChange,
        });

        document.getElementById('adjFormBody').classList.remove('d-none');
        document.getElementById('addEditProductStockFooter').classList.remove('d-none');

    } catch(error) {
        handleApiError(error);
    }
};

const openAddEditProdStockDrawer = async function(prodId = null) {
    const drawerEl = document.getElementById('addEditProductStock');
    const formEl   = document.getElementById('addEditProductStockForm');

    cleanFormInputFeedback(formEl);
    formEl.reset();
    stockByLocation = {};

    const serialOrLotWrapper = formEl.querySelector("#serialOrLotWrapper");
    if (serialOrLotWrapper) serialOrLotWrapper.remove();

    document.getElementById('adjFormBody').classList.add('d-none');
    document.getElementById('addEditProductStockFooter').classList.add('d-none');

    if (prodId) {
        // Called from stock-locations page — skip product selector, load form directly
        document.getElementById('adjProductSelectWrapper').classList.add('d-none');
        await loadAdjFormContext(prodId, drawerEl, formEl, true);
    } else {
        // Called from adjustments page — show product search Select2
        document.getElementById('adjProductSelectWrapper').classList.remove('d-none');

        initSelect2('#adjProductSelect', {
            dropdownParent: $(drawerEl),
            placeholder: 'Search products...',
            minimumInputLength: 2,
            ajax: {
                url: '/api/products/search',
                dataType: 'json',
                delay: 300,
                data: params => ({ q: params.term || '' }),
                processResults: data => ({
                    results: (data.data || []).map(p => ({ id: p.id, text: p.name }))
                }),
            },
        });

        $('#adjProductSelect').on('select2:select', async function(e) {
            const selectedId = parseInt(e.params.data.id);
            if (!selectedId) return;
            await loadAdjFormContext(selectedId, drawerEl, formEl, false);
        });
    }

    new bootstrap.Offcanvas(drawerEl).show();
};

document.getElementById('saveAddEditProductStock').addEventListener('click', async function() {
    const formEl = document.getElementById('addEditProductStockForm');
    try {
        const productId = formEl.querySelector('[name="product_id"]').value || '';
        cleanFormInputFeedback(formEl);

        const formData = new FormData(formEl);
        const payload  = Object.fromEntries(formData.entries());

        if (payload.serial_or_lot_numbers) {
            try {
                const parsed = JSON.parse(payload.serial_or_lot_numbers);
                payload.serial_or_lot_numbers = Array.isArray(parsed) ? parsed.map(i => i.value) : [];
            } catch(e) {
                payload.serial_or_lot_numbers = [];
            }
        }

        const response = await api.post(`/inv/products/${productId}/stock/adjust`, payload);
        const { code, message } = response.data;

        notyf.success(message);

        if (code == 201 || code == 200) {
            if (typeof prodStockDt      !== 'undefined') prodStockDt.ajax.reload();
            if (typeof invAdjustmentsDt !== 'undefined') invAdjustmentsDt.ajax.reload();
            if (typeof invItemsDt       !== 'undefined') invItemsDt.ajax.reload();

            bootstrap.Offcanvas.getInstance(document.getElementById('addEditProductStock')).hide();
            formEl.reset();
        }

    } catch(error) {
        handleApiError(error, formEl);
    }
});

const qtyInput = document.querySelector("#addEditProductStock input[name='quantity']");
qtyInput.addEventListener("keydown", function(e) {
    const val = e.target.value;
    const key = e.key;
    if (["Backspace", "Delete", "ArrowLeft", "ArrowRight", "Tab"].includes(key)) return;
    if (/^[0-9]$/.test(key)) return;
    if ((key === "+" || key === "-") && val.length === 0) return;
    e.preventDefault();
});

qtyInput.addEventListener("input", async function(e) {
    let val = e.target.value;
    if (val.startsWith("+")) val = val.substring(1);
    if (val.startsWith("-")) {
        val = "-" + val.substring(1).replace(/[^0-9]/g, "");
    } else {
        val = val.replace(/[^0-9]/g, "");
    }

    if (val > 0) {
        toggleGenerateButton(true);
        initSerialLotTagify();
    }

    if (val < 0) {
        toggleGenerateButton(false);
        try {
            const productId = document.querySelector('#addEditProductStock [name="product_id"]').value;
            const response  = await api.get(`/inv/products/${productId}/serial-or-lot-numbers`);
            initSerialLotTagify('whitelistonly', response.data.data || []);
        } catch(error) {
            console.error(error);
        }
    }

    e.target.value = val;
    computeNewStock();
});
</script>
@endpush
