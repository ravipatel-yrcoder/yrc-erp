<style>
#addEditProduct #product_image {width: 150px;height: 180px;}
#addEditProduct #product_image .dz-preview {max-width: 100%;}
#addEditProduct #product_image .dz-thumbnail {max-width: 138px;height: 137px}
#addEditProduct #product_image .dz-message::before {top: 0;bottom: 0;margin: auto;}
</style>
<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditProduct" aria-labelledby="addEditProductDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 40%;">
    <div class="offcanvas-header">
        <h5 id="addEditProductDrawerTitle" class="offcanvas-title">Add product</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="addEditProductForm">
            <div>
                <input type="hidden" id="id" value="" />
            </div>
            <div class="mb-4">
                <div class="row">
                    <div class="col-md">
                        <div class="mb-4">
                            <label class="form-label required">Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g: Half Sleeve T-Shirt" />
                        </div>
                        <div>
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="col-md-auto">
                        <div class="dropzone needsclick image_url" id="product_image">
                            <div class="dz-message needsclick"><small>Upload Image</small></div>
                            <div class="fallback"><input name="file" type="file" /></div>                            
                        </div>

                    </div>
                </div>                
            </div>
            <div>
                <div class="nav-align-top">
                    <ul class="nav nav-tabs shadow" role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-general" aria-controls="navs-top-general" aria-selected="true">General Information</button>
                        </li>
                        <!--
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-inventory" aria-controls="navs-top-inventory" aria-selected="false">Inventory</button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-sales" aria-controls="navs-top-sales" aria-selected="false">Sales</button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-prices" aria-controls="navs-top-prices" aria-selected="false">Prices</button>
                        </li>
                        -->
                    </ul>
                    <div class="tab-content px-0">
                        <div class="tab-pane fade show active" id="navs-top-general" role="tabpanel">
                            <div class="mb-4">
                                <label class="form-label d-block mb-3 required">Product Type</label>
                                <div class="row">
                                    <div class="col-md mb-md-0 mb-5">
                                        <div class="form-check custom-option custom-option-basic">
                                            <label class="form-check-label custom-option-content py-3" for="type_goods">
                                                <input name="type" class="form-check-input" type="radio" value="goods" id="type_goods" checked />
                                                <span class="custom-option-header pb-0"><span class="h6 mb-0">Goods</span></span>
                                                <span class="custom-option-body">
                                                    <small>Goods are tangible materials and merchandise you provide.</small>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md">
                                        <div class="form-check custom-option custom-option-basic">
                                            <label class="form-check-label custom-option-content py-3" for="type_service">
                                                <input name="type" class="form-check-input" type="radio" value="service" id="type_service" />
                                                <span class="custom-option-header pb-0"><span class="h6 mb-0">Service</span></span>
                                                <span class="custom-option-body">
                                                    <small>A service is a non-material product you provide.</small>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label">Sku</label>
                                        <input type="text" class="form-control" name="sku" />
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Category</label>
                                        <select class="form-select" name="category_id" placeholder="Category">
                                            <option></option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Track Inventory?</label>
                                        <div class="form-control ps-0 border-0">
                                            <input class="form-check-input mt-0" type="checkbox" value="yes" name="track_inventory">
                                        </div>
                                    </div>
                                    <div id="stock_tracking_method_wrapper" class="mb-4 d-none">
                                        <label class="form-label">Stock Tracking Method</label>
                                        <select class="form-select" name="stock_tracking_method">
                                            <option value="inherit">Inherit</option>    
                                            <option value="quantity">By Quantity</option>
                                            <option value="lot">By Lots</option>   
                                            <option value="serial">By Unique Serial Number</option>   
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label">Sales Price</label>
                                        <input type="text" class="form-control" name="sale_price" />
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Sales Tax</label>
                                        <select class="select2 form-select" name="sales_tax" placeholder="Sales Tax">
                                            <option></option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Cost</label>
                                        <input type="text" class="form-control" name="cost_price" />
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Purchase Tax</label>
                                        <select class="select2 form-select" name="purchase_tax" placeholder="Purchase Tax">
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                            </div>                            
                        </div>
                        <div class="tab-pane fade px-0" id="navs-top-inventory" role="tabpanel"><p>Yet to implement</p></div>
                      <div class="tab-pane fade px-0" id="navs-top-sales" role="tabpanel"><p>Yet to implement</p></div>
                      <div class="tab-pane fade px-0" id="navs-top-prices" role="tabpanel"><p>Yet to implement</p></div>
                    </div>
                  </div>
            </div>
            
            <div class="form-check pt-4">
                <input class="form-check-input" type="checkbox" value="active" name="status" checked />
                <label class="form-check-label"> Active</label>
            </div>

        </form>
    </div>
    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveAddEditProduct" class="btn btn-primary btn-sm w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>
@push('scripts')
<script>
const populateProductForm = function(productDetails) {
    
    if (Object.keys(productDetails).length === 0) return;    

    const { id, name, description, image_url, type, sku, category_id, stock_tracking_method, sale_price, cost_price, status } = productDetails;
    
    const trackInventory = stock_tracking_method != 'none' ? true : false;
    const stockInventoryMethodValue = stock_tracking_method == 'none' ?  null : stock_tracking_method;
    
    
    jQuery("#addEditProduct input#id").val(id);
    jQuery("#addEditProduct input[name='name']").val(name);
    jQuery("#addEditProduct textarea[name='description']").val(description);
    if( image_url ) {
        const prodImgDz = getDropzoneInstance("#addEditProductForm #product_image");
        if( prodImgDz ) {
            populateDropzoneImage(prodImgDz, image_url);
        }
    }
    jQuery("#addEditProduct input[name='type'][value='"+type+"']").prop("checked", true);
    jQuery("#addEditProduct input[name='sku']").val(sku);
    jQuery("#addEditProduct select[name='category_id']").val(category_id).trigger("change");        
    jQuery("#addEditProduct input[name='track_inventory']").prop("checked", trackInventory).trigger("change");
    jQuery("#addEditProduct select[name='stock_tracking_method']").val(stockInventoryMethodValue).trigger("change");
    jQuery("#addEditProduct input[name='sale_price']").val(sale_price);
    jQuery("#addEditProduct input[name='cost_price']").val(cost_price);
    const statusChecked = status == "active" ? true : false;    
    jQuery("#addEditProduct input[name='status']").prop("checked", statusChecked);
}

const openProductFormDrawer = async function(id=0) {
    
    let title = "Add product";
    if( id > 0 ) title = "Edit product";
    document.getElementById("addEditProductDrawerTitle").innerHTML = title;

    const drawerEl = document.getElementById('addEditProduct');
    const formEl = document.getElementById('addEditProductForm');

    // clean form feedback
    cleanFormInputFeedback(formEl);

    try {

        formEl.reset();        
        formEl.querySelector("input#id").value='';
        // reset dropzone
        const prodImgDz = getDropzoneInstance("#addEditProductForm #product_image");
        if( prodImgDz ) {
            prodImgDz.removeAllFiles(true);
        }

        const payload = {params: {id}};
        const response = await api.get('/products/form-context', payload);

        const { data } = response.data;
        const productDetails = data.product_details || {};
        const categoryList = data.categories || [];        
        
        // init parent category select2
        const categorySelect2 = jQuery("#addEditProduct select[name='category_id']");
        if (categorySelect2.data("select2")) {
            categorySelect2.empty().select2("destroy");
        }

        const categoryOptions = buildCategorySelect2Options(categoryList);
        categorySelect2.select2({
            placeholder: 'Choose category',
            data: categoryOptions,
            width: '100%',
            dropdownParent: drawerEl,
            allowClear: true
        });
        categorySelect2.val(null).trigger('change');

        populateProductForm(productDetails);

        new bootstrap.Offcanvas(drawerEl).show();        

    } catch(error) {
        
        handleApiError(error);        
    }

    
}

const saveAddEditProductButton = document.getElementById('saveAddEditProduct');
saveAddEditProductButton.addEventListener('click', async function(e) {
    
    const formEl = document.getElementById('addEditProductForm');

    try {

        const id = formEl.querySelector('input#id').value || '';

        let apiPostfix = `/products`;
        if( id ) {
            apiPostfix += `/${id}`;
        }
        // clean form input feedback
        cleanFormInputFeedback(formEl);

        const formData = new FormData(formEl);
        const payload = Object.fromEntries(formData.entries());

        const trackInventory = formEl.elements["track_inventory"]?.checked || false;
        if( !trackInventory ) {
            payload["stock_tracking_method"] = "none";
        }

        const trackingMethod = formEl.elements["stock_tracking_method"]?.value || "";
        if( trackingMethod == "inherit" ) {
            payload["stock_tracking_method"] = null;
        }

        // product image
        const prodImgDz = getDropzoneInstance("#addEditProductForm #product_image");
        if( prodImgDz )
        {
            if( prodImgDz.files.length > 0 ) {
                
                const file = prodImgDz.files[0];
                const existing = file.existing || false;
                if( existing === false ) {
                    const base64 = file.dataURL;
                    payload["image"] = {
                        name: file.name,
                        extension: file.name.split(".").pop().toLowerCase(),
                        mime_type: file.type, 
                        content: base64.split(",")[1],
                    };
                }
            }
        }        
        
        const response = await api.post(apiPostfix, payload);
        const { code, message } = response.data;

        notyf.success(message);

        if( code == 201 || code == 200 ) {

            if( typeof(productsDt) != "undefined" ) {
                productsDt.ajax.reload()
            } else if( typeof(productMastersDt) != "undefined" ) {
                productMastersDt.ajax.reload()
            }

            const drawer = bootstrap.Offcanvas.getInstance(document.getElementById('addEditProduct'));
            drawer.hide();

            formEl.reset();
        }        

    } catch(error) {

        handleApiError(error, formEl);
    }

});

jQuery(document).ready(function(){

    const imgDzEl = document.querySelector("#addEditProductForm #product_image");
    if( imgDzEl )
    {
        const previewTemplate = `<div class="dz-preview dz-file-preview m-0" style="max-width: 100%;">
            <div class="dz-details">
                <div class="dz-thumbnail p-1">
                    <img data-dz-thumbnail>
                    <span class="dz-nopreview">No preview</span>
                    <div class="dz-success-mark"></div>
                    <div class="dz-error-mark"></div>
                    <div class="dz-error-message"><span data-dz-errormessage></span></div>
                    <div class="progress">
                    <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
                    </div>
                </div>  
            </div>
        </div>`;

        new Dropzone(imgDzEl, {
            previewTemplate: previewTemplate,
            parallelUploads: 1,
            maxFilesize: 1, // 1 MB
            acceptedFiles: '.jpg,.jpeg,.png',
            addRemoveLinks: true,
            dictRemoveFile: "Remove",
            maxFiles: 1,
            url: "#",
        });
    }    

    jQuery("#addEditProduct select[name='stock_tracking_method']").select2({
        placeholder: 'Tracking method',
        width: '100%',
        dropdownParent: jQuery("#addEditProduct"),
        allowClear: true
    });


    jQuery("#addEditProduct input[name='track_inventory']").change(function(){

        if(jQuery(this).is(":checked")) {
            jQuery("#addEditProduct #stock_tracking_method_wrapper").removeClass("d-none");
        } else {
            jQuery("#addEditProduct #stock_tracking_method_wrapper").addClass("d-none");
        }
    });
})
</script>
@endpush