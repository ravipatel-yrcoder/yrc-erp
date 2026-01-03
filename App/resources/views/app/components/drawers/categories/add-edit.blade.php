<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditProdCategory" aria-labelledby="addEditProdCategoryDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="offcanvas-header">
        <h5 id="addEditProdCategoryDrawerTitle" class="offcanvas-title">Add category</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="addEditProdCategoryForm">
            <div>
                <input type="hidden" id="id" value="" />
            </div>
            <div class="mb-4">
                <label class="form-label required">Name</label>
                <input type="text" name="name" class="form-control" placeholder="Electornics" />                
            </div>
            <div class="mb-4">
                <label class="form-label">Code</label>
                <input type="text" name="code" class="form-control" placeholder="EL" />
            </div>
            <div class="mb-4">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3"></textarea>
            </div>
            <!--<div class="mb-4">
                <label class="form-label">Icon</label>
                <div class="dz-message needsclick">
                    Drop files here or click to upload
                    <span class="note needsclick">(This is just a demo dropzone. Selected files are <span class="fw-medium">not</span> actually uploaded.)</span>
                </div>
                <div class="fallback">
                    <input name="icon" type="file" />
                </div>
            </div>-->
            <div class="mb-4">
                <label class="form-label">Parent category</label>
                <select class="form-select" name="parent_id">
                    <option></option>
                </select>
            </div>
            <div class="form-check pt-4">
                <input class="form-check-input" type="checkbox" value="active" name="status" checked />
                <label class="form-check-label"> Active</label>
            </div>
        </form>
    </div>
    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveAddEditProdCategory" class="btn btn-primary btn-sm w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>
@push('scripts')
<script>

const populateProdCategoryForm = function(categoryDetails) {
    
    if (Object.keys(categoryDetails).length === 0) return;    

    const { id, parent_id, name, code, description, status } = categoryDetails;
    const parent_cat_id = parent_id ? parent_id : null;

    jQuery("#addEditProdCategory input#id").val(id);
    jQuery("#addEditProdCategory input[name='name']").val(name);
    jQuery("#addEditProdCategory input[name='code']").val(code);
    jQuery("#addEditProdCategory textarea[name='description']").val(description);
    jQuery("#addEditProdCategory select[name='parent_id']").val(parent_cat_id).trigger("change");
    const statusChecked = status == "active" ? true : false;
    jQuery("#addEditProdCategory input[name='status']").prop("checked", statusChecked);

}

const openProdCategoryFormDrawer = async function(id=0) {
    
    let title = "Add category";
    if( id > 0 ) title = "Edit category";
    document.getElementById("addEditProdCategoryDrawerTitle").innerHTML = title;

    const drawerEl = document.getElementById('addEditProdCategory');
    const formEl = document.getElementById('addEditProdCategoryForm');
    _formEl = formEl;
    
    
    // clean form feedback
    cleanFormInputFeedback(formEl);

    try {

        formEl.reset();        
        formEl.querySelector("input#id").value='';        

        const payload = {params: {id}};
        const response = await api.get('/product-categories/form-context', payload);

        const { data } = response.data;
        const categoryDetails = data.category_details || {};
        const categoryList = data.categories || [];

        
        
        // init parent category select2
        const parentCatSelect2 = jQuery("#addEditProdCategoryForm select[name='parent_id']");
        if (parentCatSelect2.data("select2")) {
            parentCatSelect2.empty().select2("destroy");
        }

        const parentCatOptions = buildCategorySelect2Options(categoryList);

        parentCatSelect2.select2({
            placeholder: '—None—',
            data: parentCatOptions,
            width: '100%',
            dropdownParent: drawerEl,
            allowClear: true
        });
        parentCatSelect2.val(null).trigger('change');


        populateProdCategoryForm(categoryDetails);

        new bootstrap.Offcanvas(drawerEl).show();

    } catch(error) {        
        handleApiError(error);        
    }

    
}

const saveAddEditProdCategoryButton = document.getElementById('saveAddEditProdCategory');
saveAddEditProdCategoryButton.addEventListener('click', async function(e) {
    
    const formEl = document.getElementById('addEditProdCategoryForm');

    try {

        const id = formEl.querySelector('input#id').value || '';

        let apiPostfix = `/product-categories`;
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

            if( typeof(categoriesDt) != "undefined" ) {
                categoriesDt.ajax.reload()
            }

            const drawer = bootstrap.Offcanvas.getInstance(document.getElementById('addEditProdCategory'));
            drawer.hide();

            formEl.reset();
        }        

    } catch(error) {
        handleApiError(error, formEl);
    }

});
</script>
@endpush