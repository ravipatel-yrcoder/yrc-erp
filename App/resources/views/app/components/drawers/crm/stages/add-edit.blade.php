<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditStage" aria-labelledby="addEditStageDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 35%;">

    <div class="offcanvas-header">
        <h5 id="addEditStageDrawerTitle" class="offcanvas-title">Add Stage</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="addEditStageForm">
            <input type="hidden" id="stage_id" value="" />

            <div class="mb-4">
                <label class="form-label required">Stage Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g: Qualification" />
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label class="form-label">Probability (%)</label>
                        <input type="number" name="probability" class="form-control" min="0" max="100" value="0" placeholder="0-100" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" min="0" value="0" placeholder="Display order" />
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-4">
                    <div class="mb-4">
                        <label class="form-label">Color</label>
                        <input type="color" name="color" class="form-control form-control-color w-100" value="#4880ff" title="Choose stage color" />
                    </div>
                </div>
                <div class="col-8">
                    <div class="mb-4">
                        <label class="form-label d-block">Stage Type</label>
                        <div class="form-check-group">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="stage_type" id="stage_type_normal" value="normal" checked />
                                <label class="form-check-label" for="stage_type_normal">Normal</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="stage_type" id="stage_type_won" value="won" />
                                <label class="form-check-label" for="stage_type_won">Won</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="stage_type" id="stage_type_lost" value="lost" />
                                <label class="form-check-label" for="stage_type_lost">Lost</label>
                            </div>
                            <div class="form-text">Mark as "Won" or "Lost" to auto-close deals.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-check pt-2">
                <input class="form-check-input" type="checkbox" value="active" name="status" id="stage_status" checked />
                <label class="form-check-label" for="stage_status">Active</label>
            </div>

        </form>
    </div>

    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveAddEditStage" class="btn btn-primary btn-sm min-w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>


@push('scripts')
<script>
const setStageFormFieldValue = function(selector, value) {
    const el = document.querySelector(selector);
    if (!el) return;
    const tag = el.tagName.toLowerCase();
    if (tag === "select") {
        el.value = value ?? "";
        el.dispatchEvent(new Event("change", { bubbles: true }));
    } else {
        el.value = value ?? "";
    }
}

const populateStageForm = function(details) {
    
    if (!details || Object.keys(details).length === 0) return;

    setStageFormFieldValue("#addEditStage input#stage_id", details.id);
    setStageFormFieldValue("#addEditStage input[name='name']", details.name);
    setStageFormFieldValue("#addEditStage input[name='probability']", details.probability);
    setStageFormFieldValue("#addEditStage input[name='sort_order']", details.sort_order);
    setStageFormFieldValue("#addEditStage input[name='color']", details.color || '#4880ff');

    // Set stage type radio
    if( details.is_won == 1 ) {
        document.getElementById('stage_type_won').checked = true;
    } else if( details.is_lost == 1 ) {
        document.getElementById('stage_type_lost').checked = true;
    } else {
        document.getElementById('stage_type_normal').checked = true;
    }

    // Status checkbox
    jQuery("#addEditStage input[name='status']").prop("checked", details.status === "active");
}

const openStageFormDrawer = async function(id = 0) {

    const title = id > 0 ? "Edit Stage" : "Add Stage";
    document.getElementById("addEditStageDrawerTitle").innerHTML = title;

    const drawerEl = document.getElementById('addEditStage');
    const formEl   = document.getElementById('addEditStageForm');

    cleanFormInputFeedback(formEl);
    formEl.reset();
    formEl.querySelector("input#stage_id").value = '';

    try {

        if( id > 0 ) {
            const payload = { params: { id } };
            const response = await api.get('/crm/stages/form-context', payload);
            const { data } = response.data;
            populateStageForm(data.stageDetails || {});
        }

        new bootstrap.Offcanvas(drawerEl).show();

    } catch(error) {
        handleApiError(error);
    }
}

const saveAddEditStageButton = document.getElementById('saveAddEditStage');
saveAddEditStageButton.addEventListener('click', async function(e) {

    var btn = this;
    const formEl = document.getElementById('addEditStageForm');

    const id = formEl.querySelector('input#stage_id').value || '';

    let apiPostfix = '/crm/stages';
    if( id ) {
        apiPostfix += `/${id}`;
    }

    cleanFormInputFeedback(formEl);

    const formData = new FormData(formEl);
    const payload = formDataToObject(formData);

    // Map stage_type radio to is_won / is_lost
    const stageType = formEl.querySelector('input[name="stage_type"]:checked')?.value || 'normal';
    payload.is_won = stageType === 'won' ? 1 : 0;
    payload.is_lost = stageType === 'lost' ? 1 : 0;
    delete payload.stage_type;

    setButtonLoading(btn, true);
    try {

        const response = await api.post(apiPostfix, payload);
        const { code, message } = response.data;

        notyf.success(message);

        if( code == 201 || code == 200 ) {

            if( typeof(stagesDt) != "undefined" ) {
                stagesDt.ajax.reload();
            }

            const drawer = bootstrap.Offcanvas.getInstance(document.getElementById('addEditStage'));
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
