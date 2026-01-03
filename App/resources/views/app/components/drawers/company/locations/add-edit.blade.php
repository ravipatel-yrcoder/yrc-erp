@php
$locationTypes = config("constants.company.location_types");
@endphp
<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditLocation" aria-labelledby="addEditLocationDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="offcanvas-header">
        <h5 id="addEditLocationDrawerTitle" class="offcanvas-title">Add location</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="addEditLocationForm">
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
                    @foreach($locationTypes as $slug => $label)
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
                        <label class="form-label">Zipecode</label>
                        <input type="text" name="zip" class="form-control" placeholder="Zipcode" />
                    </div>
                </div>
            </div>
            
            <div class="form-check pt-4">
                <input class="form-check-input" type="checkbox" value=1 name="is_main" />
                <label class="form-check-label"> Main location?</label>
            </div>

            <div class="form-check pt-4">
                <input class="form-check-input" type="checkbox" value="active" name="status" checked />
                <label class="form-check-label"> Active</label>
            </div>

        </form>
    </div>
    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveAddEditLocation" class="btn btn-primary btn-sm w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>
@push('scripts')
<script>
const populateLocationForm = function(locationDetails) {
    
    if (Object.keys(locationDetails).length === 0) return;

    const { id, name, code, type, address_line1, address_line2, city, state, country, zip, is_main, status } = locationDetails;
    
    jQuery("#addEditLocation input#id").val(id);
    jQuery("#addEditLocation input[name='name']").val(name);
    jQuery("#addEditLocation input[name='code']").val(code);
    jQuery("#addEditLocation select[name='type']").val(type).trigger("change");
    jQuery("#addEditLocation input[name='address_line1']").val(address_line1);
    jQuery("#addEditLocation input[name='address_line2']").val(address_line2);
    jQuery("#addEditLocation input[name='city']").val(city);
    jQuery("#addEditLocation input[name='state']").val(state);
    jQuery("#addEditLocation input[name='country']").val(country);
    jQuery("#addEditLocation input[name='zip']").val(zip);
    
    const isMainChecked = is_main == "1" ? true : false;    
    jQuery("#addEditLocation input[name='is_main']").prop("checked", isMainChecked);

    const statusChecked = status == "active" ? true : false;    
    jQuery("#addEditLocation input[name='status']").prop("checked", statusChecked);
}

const openLocationFormDrawer = async function(id=0) {
    
    let title = "Add location";
    if( id > 0 ) title = "Edit location";
    document.getElementById("addEditLocationDrawerTitle").innerHTML = title;

    const drawerEl = document.getElementById('addEditLocation');
    const formEl = document.getElementById('addEditLocationForm');

    // clean form feedback
    cleanFormInputFeedback(formEl);

    try {

        formEl.reset();        
        formEl.querySelector("input#id").value='';
        
        const payload = {params: {id}};
        const response = await api.get('/company/locations/form-context', payload);

        const { data } = response.data;
        const locationDetails = data.location_details || {};
        
        populateLocationForm(locationDetails);

        new bootstrap.Offcanvas(drawerEl).show();

    } catch(error) {
        /*console.log(error);
        alert("Unable to load form");
        return false;*/

        handleApiError(error);
    }
}

const saveAddEditLocationButton = document.getElementById('saveAddEditLocation');
saveAddEditLocationButton.addEventListener('click', async function(e) {
    
    const formEl = document.getElementById('addEditLocationForm');

    try {

        const id = formEl.querySelector('input#id').value || '';

        let apiPostfix = `/company/locations`;
        if( id ) {
            apiPostfix += `/${id}`;
        }
        // clean form input feedback
        cleanFormInputFeedback(formEl);

        const formData = new FormData(formEl);
        const payload = Object.fromEntries(formData.entries());


        const response = await api.post(apiPostfix, payload);
        const { code, message } = response.data;

        notyf.success(message);

        if( code == 201 || code == 200 ) {

            if( typeof(locationsDt) != "undefined" ) {
                locationsDt.ajax.reload()
            }

            const drawer = bootstrap.Offcanvas.getInstance(document.getElementById('addEditLocation'));
            drawer.hide();

            formEl.reset();
        }        

    } catch(error) {

        handleApiError(error, formEl);

        /*if (error.response) {
            
            const { code, message, errors={} } = error.response.data;
            
            if( code == 401 ) {
                notyf.error(UNAUTHORIZED_MESSAGE);
                return;
            }

            if( message ) {
                notyf.error(message);
            }

            for(const [key, value] of Object.entries(errors)) {
                let inputEl = formEl.querySelector(`[name="${key}"], .${key}.dropzone`);
                showFormInputFeedback(inputEl, value);
            }

        } else {
                        
            alert("Unable to save");
            console.log(error);
        }*/
    }

});

jQuery(document).ready(function(){

    jQuery("#addEditLocation select[name='type']").select2({
        placeholder: 'Location type',
        width: '100%',
        dropdownParent: jQuery("#addEditLocation"),
        allowClear: true
    });
})
</script>
@endpush