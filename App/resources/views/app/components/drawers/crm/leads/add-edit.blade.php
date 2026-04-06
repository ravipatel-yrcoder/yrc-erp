{{-- CRM Lead add/edit drawer — implemented in Step 7 --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditLead" aria-labelledby="addEditLeadDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 45%;">

    <div class="offcanvas-header">
        <h5 id="addEditLeadDrawerTitle" class="offcanvas-title">Add Lead</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="addEditLeadForm">
            <input type="hidden" id="lead_id" value="" />

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label class="form-label required">First Name</label>
                        <input type="text" name="first_name" class="form-control" placeholder="First name" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" placeholder="Last name" />
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Company Name</label>
                <input type="text" name="company_name" class="form-control" placeholder="Company or organisation" />
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="email@example.com" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="+91 98765 43210" />
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label class="form-label">Stage</label>
                        <select class="select2 form-select" name="stage_id">
                            <option></option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <label class="form-label">Priority</label>
                        <select class="form-select" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label class="form-label">Expected Revenue</label>
                        <input type="number" name="expected_revenue" class="form-control" placeholder="0.00" min="0" step="0.01" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <label class="form-label">Expected Close Date</label>
                        <input type="text" name="expected_close_date" class="form-control flatpickr-date" placeholder="YYYY-MM-DD" />
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Assigned To</label>
                <select class="select2 form-select" name="assigned_to">
                    <option></option>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label">Source</label>
                <select class="form-select" name="source">
                    <option value="">— Select source —</option>
                    <option value="website">Website</option>
                    <option value="referral">Referral</option>
                    <option value="cold_call">Cold Call</option>
                    <option value="email_campaign">Email Campaign</option>
                    <option value="social_media">Social Media</option>
                    <option value="trade_show">Trade Show</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Initial notes about this lead..."></textarea>
            </div>

        </form>
    </div>

    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveAddEditLead" class="btn btn-primary btn-sm w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>


@push('scripts')
<script>
const populateLeadForm = function(details) {
    
    if( !details || Object.keys(details).length === 0 ) return;

    const setVal = (sel, val) => {
        const el = document.querySelector(sel);
        if( !el ) return;
        el.value = val ?? '';
        el.dispatchEvent(new Event('change', { bubbles: true }));
    };

    document.querySelector('#addEditLead input#lead_id').value = details.id || '';
    setVal('#addEditLead input[name="first_name"]', details.first_name);
    setVal('#addEditLead input[name="last_name"]', details.last_name);
    setVal('#addEditLead input[name="company_name"]', details.company_name);
    setVal('#addEditLead input[name="email"]', details.email);
    setVal('#addEditLead input[name="phone"]', details.phone);
    setVal('#addEditLead select[name="priority"]', details.priority || 'medium');
    setVal('#addEditLead input[name="expected_revenue"]', details.expected_revenue);
    datePickerSetDate('#addEditLead input[name="expected_close_date"]', details.expected_close_date);
    setVal('#addEditLead select[name="source"]', details.source);
    setVal('#addEditLead textarea[name="notes"]', details.notes);
    setVal('#addEditLead select[name="stage_id"]', details.stage_id);
    setVal('#addEditLead select[name="assigned_to"]', details.assigned_to);
}

const openLeadFormDrawer = async function(id = 0) {

    const title = id > 0 ? 'Edit Lead' : 'Add Lead';
    document.getElementById('addEditLeadDrawerTitle').textContent = title;

    const drawerEl = document.getElementById('addEditLead');
    const formEl = document.getElementById('addEditLeadForm');

    cleanFormInputFeedback(formEl);
    formEl.reset();
    formEl.querySelector('input#lead_id').value = '';

    try {

        const res = await api.get('/crm/leads/form-context', { params: { id } });
        const { stages = [], users = [], leadDetails = {} } = res.data.data;

        // Init Stage select2
        initSelect2('#addEditLead select[name="stage_id"]', {
            dropdownParent: drawerEl,
            placeholder: 'Select stage',
            allowClear: true,
            data: buildSelect2Options(stages, { idKey: 'id', textKey: 'name' }),
        });

        // Init Assigned To select2
        initSelect2('#addEditLead select[name="assigned_to"]', {
            dropdownParent: drawerEl,
            placeholder: 'Assign to...',
            allowClear: true,
            data: buildSelect2Options(users, { idKey: 'id', textKey: 'name' }),
        });

        // Init date picker
        initDatePicker('#addEditLead input[name="expected_close_date"]');

        populateLeadForm(leadDetails);
        new bootstrap.Offcanvas(drawerEl).show();

    } catch(error) {
        handleApiError(error);
    }
}

document.getElementById('saveAddEditLead').addEventListener('click', async function() {

    const formEl = document.getElementById('addEditLeadForm');

    try {

        const id = formEl.querySelector('input#lead_id').value || '';
        const apiUrl = id ? `/crm/leads/${id}` : '/crm/leads';

        cleanFormInputFeedback(formEl);

        const payload = formDataToObject(new FormData(formEl));
        const res = await api.post(apiUrl, payload);
        const { code, message } = res.data;

        notyf.success(message);

        if( code === 201 || code === 200 ) {
            if( typeof leadsDt !== 'undefined' ) leadsDt.ajax.reload();
            bootstrap.Offcanvas.getInstance(document.getElementById('addEditLead')).hide();
            formEl.reset();
        }

    } catch(error) {
        handleApiError(error, formEl);
    }
});
</script>
@endpush
