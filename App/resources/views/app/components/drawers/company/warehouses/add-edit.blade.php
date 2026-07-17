@php
$warehouseTypes = config("constants.company.warehouse_types");
@endphp
<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditWarehouse" aria-labelledby="addEditWarehouseDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="offcanvas-header">
        <h5 id="addEditWarehouseDrawerTitle" class="offcanvas-title">Add warehouse</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="addEditWarehouseForm">
            <div>
                <input type="hidden" id="id" value="" />
            </div>
            <div class="mb-4">
                <label class="form-label required">Name</label>
                <input type="text" name="name" class="form-control" placeholder="Warehouse #1" />
            </div>
            <div class="mb-4">
                <label class="form-label required">Type</label>
                <select class="form-select" name="type" placeholder="Choose type">
                    <option></option>
                    @foreach($warehouseTypes as $slug => $label)
                    <option value='{{$slug}}'>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label">Code</label>
                <input type="text" name="code" class="form-control" placeholder="WH1" />
            </div>
            <div class="mb-4">
                <label class="form-label">Address line1</label>
                <input type="text" name="address_line1" class="form-control" placeholder="Address line 1" />
            </div>
            <div class="mb-4">
                <label class="form-label">Address line2</label>
                <input type="text" name="address_line2" class="form-control" placeholder="Address line 2" />
            </div>
            <div class="mb-4">
                <div class="row">
                    <div class="col-md">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" placeholder="City" />
                    </div>
                    <div class="col-md">
                        <label class="form-label">State</label>
                        <input type="text" name="state" class="form-control" placeholder="State" />
                    </div>
                </div>
            </div>
            <div class="mb-4">
                <div class="row">
                    <div class="col-md">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" class="form-control" placeholder="Country" />
                    </div>
                    <div class="col-md">
                        <label class="form-label">Zipcode</label>
                        <input type="text" name="zip" class="form-control" placeholder="Zipcode" />
                    </div>
                </div>
            </div>

            <div class="form-check pt-4">
                <input class="form-check-input" type="checkbox" value=1 name="is_default" />
                <label class="form-check-label"> Default warehouse?</label>
            </div>

            <div class="form-check pt-4">
                <input class="form-check-input" type="checkbox" value="active" name="status" checked />
                <label class="form-check-label"> Active</label>
            </div>

        </form>
    </div>
    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveAddEditWarehouse" class="btn btn-primary btn-sm min-w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>
@push('scripts')
<script>
const populateWarehouseForm = function(warehouseDetails) {

    if (Object.keys(warehouseDetails).length === 0) return;

    const { id, name, code, type, address_line1, address_line2, city, state, country, zip, is_default, status } = warehouseDetails;

    jQuery("#addEditWarehouse input#id").val(id);
    jQuery("#addEditWarehouse input[name='name']").val(name);
    jQuery("#addEditWarehouse input[name='code']").val(code);
    jQuery("#addEditWarehouse select[name='type']").val(type).trigger("change");
    jQuery("#addEditWarehouse input[name='address_line1']").val(address_line1);
    jQuery("#addEditWarehouse input[name='address_line2']").val(address_line2);
    jQuery("#addEditWarehouse input[name='city']").val(city);
    jQuery("#addEditWarehouse input[name='state']").val(state);
    jQuery("#addEditWarehouse input[name='country']").val(country);
    jQuery("#addEditWarehouse input[name='zip']").val(zip);

    const isDefaultChecked = is_default == "1" ? true : false;
    jQuery("#addEditWarehouse input[name='is_default']").prop("checked", isDefaultChecked);

    const statusChecked = status == "active" ? true : false;
    jQuery("#addEditWarehouse input[name='status']").prop("checked", statusChecked);
}

const openWarehouseFormDrawer = async function(id = 0) {

    const title = id > 0 ? "Edit warehouse" : "Add warehouse";
    document.getElementById("addEditWarehouseDrawerTitle").innerHTML = title;

    const drawerEl = document.getElementById('addEditWarehouse');
    const formEl   = document.getElementById('addEditWarehouseForm');

    cleanFormInputFeedback(formEl);
    formEl.reset();
    formEl.querySelector("input#id").value = '';

    try {

        const payload = {params: {id}};
        const response = await api.get('/inv/warehouses/form-context', payload);

        const { data } = response.data;
        const warehouseDetails = data.warehouse_details || {};

        initSelect2("#addEditWarehouse select[name='type']", {
            dropdownParent: drawerEl,
            placeholder: 'Warehouse type',
            allowClear: true,
        });

        populateWarehouseForm(warehouseDetails);

        new bootstrap.Offcanvas(drawerEl).show();

    } catch(error) {
        handleApiError(error);
    }
}

const saveAddEditWarehouseButton = document.getElementById('saveAddEditWarehouse');
saveAddEditWarehouseButton.addEventListener('click', async function(e) {

    var btn = this;
    const formEl = document.getElementById('addEditWarehouseForm');

    const id = formEl.querySelector('input#id').value || '';

    let apiPostfix = `/inv/warehouses`;
    if( id ) {
        apiPostfix += `/${id}`;
    }

    cleanFormInputFeedback(formEl);

    const formData = new FormData(formEl);
    const payload = Object.fromEntries(formData.entries());
    payload.id = parseInt(id) || 0;

    setButtonLoading(btn, true);
    try {

        const response = await api.post(apiPostfix, payload);
        const { code, message } = response.data;

        notyf.success(message);

        if( code == 201 || code == 200 ) {

            if( typeof(warehousesDt) != "undefined" ) {
                warehousesDt.ajax.reload()
            }

            const drawer = bootstrap.Offcanvas.getInstance(document.getElementById('addEditWarehouse'));
            drawer.hide();

            formEl.reset();
        }

    } catch(error) {
        handleApiError(error, formEl);
    } finally {
        setButtonLoading(btn, false);
    }

});

</script>
@endpush
