<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditLead" aria-labelledby="addEditLeadDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 45%;">

    <div class="offcanvas-header">
        <h5 id="addEditLeadDrawerTitle" class="offcanvas-title">Add Lead</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="addEditLeadForm">
            <input type="hidden" id="lead_id" value="" />

            {{-- Contact name --}}
            <div class="mb-4">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label">Salutation</label>
                        <select class="select2 form-select" name="salutation" id="lead_salutation">
                            <option value=""></option>
                            <option value="Mr.">Mr.</option>
                            <option value="Mrs.">Mrs.</option>
                            <option value="Ms.">Ms.</option>
                            <option value="Miss">Miss</option>
                            <option value="Dr.">Dr.</option>
                            <option value="Prof.">Prof.</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label required">First Name</label>
                        <input type="text" name="first_name" id="lead_first_name" class="form-control" placeholder="First name" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" id="lead_last_name" class="form-control" placeholder="Last name" />
                    </div>
                </div>
            </div>

            {{-- Company name --}}
            <div class="mb-4">
                <label class="form-label">Company Name</label>
                <input type="text" name="company_name" id="lead_company_name" class="form-control" placeholder="Company or organisation" />
            </div>

            {{-- Display name --}}
            <div class="mb-4">
                <label class="form-label required">Display Name</label>
                <select class="select2 form-select" id="lead_display_name_select">
                    <option value=""></option>
                </select>
                <input type="text" id="lead_display_name_manual" class="form-control mt-2" style="display:none;" placeholder="Enter display name" />
                <input type="hidden" name="display_name" id="lead_display_name_hidden" />
            </div>

            {{-- Job title --}}
            <div class="mb-4">
                <label class="form-label">Job Title</label>
                <input type="text" name="job_title" class="form-control" placeholder="e.g. Marketing Manager" />
            </div>

            {{-- Email & Phone --}}
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

            {{-- Stage & Priority --}}
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

            {{-- Revenue & Close Date --}}
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
                        <input type="text" name="expected_close_date" class="form-control" placeholder="DD/MM/YYYY" autocomplete="off" />
                    </div>
                </div>
            </div>

            {{-- Assigned To --}}
            <div class="mb-4">
                <label class="form-label">Assigned To</label>
                <select class="select2 form-select" name="assigned_to">
                    <option></option>
                </select>
            </div>

            {{-- Source --}}
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

            {{-- Notes --}}
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
const LEAD_DN_MANUAL = '__manual__';
const leadDnDebounce = function(fn, delay) {
    
    let timer;
    return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), delay); };
};

const buildLeadDisplayNameOptions = function() {
    
    const salutation = jQuery('#lead_salutation').val()?.trim() || '';
    const firstName = document.getElementById('lead_first_name').value.trim();
    const lastName = document.getElementById('lead_last_name').value.trim();
    const companyName = document.getElementById('lead_company_name').value.trim();

    const seen = new Set();
    const options = [];
    const add = function(val) { if (val && !seen.has(val)) { seen.add(val); options.push({ id: val, text: val }); } };

    const fullName = [firstName, lastName].filter(Boolean).join(' ');
    const formalName = [salutation, firstName, lastName].filter(Boolean).join(' ');

    add(fullName);
    add(formalName);
    if (firstName && lastName) add(`${lastName}, ${firstName}`);
    add(companyName);
    if (fullName && companyName) add(`${fullName} (${companyName})`);

    options.push({ id: LEAD_DN_MANUAL, text: '— Enter manually...' });
    return options;
};

const refreshLeadDisplayNameSelect = function(forceSelect = null) {
    
    const drawerEl = document.getElementById('addEditLead');
    const $select = jQuery('#lead_display_name_select');
    const prevVal = $select.val();
    const options = buildLeadDisplayNameOptions();
    const realOpts = options.filter(o => o.id !== LEAD_DN_MANUAL);

    let newVal;
    if (forceSelect !== null) {
        
        const exists = options.some(o => o.id === forceSelect);
        newVal = exists ? forceSelect : (forceSelect ? LEAD_DN_MANUAL : '');

    } else {

        const prevStillValid = prevVal && prevVal !== LEAD_DN_MANUAL && options.some(o => o.id === prevVal);
        if (prevVal === LEAD_DN_MANUAL)  newVal = LEAD_DN_MANUAL;
        else if (prevStillValid) newVal = prevVal;
        else newVal = realOpts.length > 0 ? realOpts[0].id : '';
    }

    initSelect2('#lead_display_name_select', {
        dropdownParent: drawerEl,
        minimumResultsForSearch: Infinity,
        placeholder: '— Select display name —',
        allowClear: false,
        data: options,
        resetVal: false,
        onChange: function(_this) {
            const val = _this.value;
            const isManual = val === LEAD_DN_MANUAL;
            const manualEl = document.getElementById('lead_display_name_manual');
            if (isManual) {
                if (!manualEl.value) {
                    const first = buildLeadDisplayNameOptions().find(o => o.id !== LEAD_DN_MANUAL);
                    manualEl.value = first?.id || '';
                    document.getElementById('lead_display_name_hidden').value = manualEl.value;
                }
                manualEl.style.display = '';
            } else {
                manualEl.style.display = 'none';
                document.getElementById('lead_display_name_hidden').value = val || '';
            }
        },
    });
    jQuery('#lead_display_name_select').val(newVal || null).trigger('change');

    // Stored value not in generated options → show manual input pre-filled
    if (forceSelect && forceSelect !== LEAD_DN_MANUAL && newVal === LEAD_DN_MANUAL) {
        
        const manualEl = document.getElementById('lead_display_name_manual');
        manualEl.style.display = '';
        manualEl.value = forceSelect;
        document.getElementById('lead_display_name_hidden').value = forceSelect;
    }
};

document.getElementById('lead_display_name_manual').addEventListener('input', function() {
    document.getElementById('lead_display_name_hidden').value = this.value.trim();
});

// Debounced refresh on name field changes
const leadDnRefresh = leadDnDebounce(() => refreshLeadDisplayNameSelect(), 300);
document.getElementById('lead_first_name').addEventListener('input', leadDnRefresh);
document.getElementById('lead_last_name').addEventListener('input', leadDnRefresh);
document.getElementById('lead_company_name').addEventListener('input', leadDnRefresh);


const populateLeadForm = function(details) {
    
    if (!details || Object.keys(details).length === 0) return;

    const setField = function(sel, val) {
        const el = document.querySelector(sel);
        if (!el) return;
        el.value = val ?? '';
        el.dispatchEvent(new Event('change', { bubbles: true }));
    };

    document.querySelector('#addEditLead input#lead_id').value = details.id || '';

    jQuery('#addEditLead select[name="salutation"]').val(details.salutation || '').trigger('change');
    setField('#addEditLead input[name="first_name"]', details.first_name);
    setField('#addEditLead input[name="last_name"]',  details.last_name);
    setField('#addEditLead input[name="company_name"]', details.company_name);
    setField('#addEditLead input[name="job_title"]', details.job_title);
    setField('#addEditLead input[name="email"]', details.email);
    setField('#addEditLead input[name="phone"]', details.phone);
    setField('#addEditLead select[name="priority"]', details.priority || 'medium');
    setField('#addEditLead input[name="expected_revenue"]', details.expected_revenue);
    datePickerSetDate('#addEditLead input[name="expected_close_date"]', details.expected_close_date);
    setField('#addEditLead select[name="source"]', details.source);
    setField('#addEditLead textarea[name="notes"]', details.notes);

    // Select2 selects — set value then trigger change
    jQuery('#addEditLead select[name="stage_id"]').val(details.stage_id || '').trigger('change');
    jQuery('#addEditLead select[name="assigned_to"]').val(details.assigned_to || '').trigger('change');

    // Display name — must be last (depends on first/last/company/salutation being set)
    refreshLeadDisplayNameSelect(details.display_name || null);
};

const openLeadFormDrawer = async function(id = 0) {

    const title = id > 0 ? 'Edit Lead' : 'Add Lead';
    document.getElementById('addEditLeadDrawerTitle').innerHTML = title;

    const drawerEl = document.getElementById('addEditLead');
    const formEl   = document.getElementById('addEditLeadForm');

    cleanFormInputFeedback(formEl);
    formEl.reset();
    formEl.querySelector('input#lead_id').value = '';

    // Reset display name section
    document.getElementById('lead_display_name_manual').style.display = 'none';
    document.getElementById('lead_display_name_manual').value = '';
    document.getElementById('lead_display_name_hidden').value = '';

    try {

        const res = await api.get('/crm/leads/form-context', { params: { id } });
        const { stages = [], users = [], leadDetails = {} } = res.data.data;

        // Init salutation select2
        initSelect2('#addEditLead select[name="salutation"]', {
            dropdownParent: drawerEl,
            allowClear: true,
            minimumResultsForSearch: Infinity,
            placeholder: '—',
            onChange: () => refreshLeadDisplayNameSelect(),
        });

        // Init stage select2
        initSelect2('#addEditLead select[name="stage_id"]', {
            dropdownParent: drawerEl,
            placeholder: 'Select stage',
            allowClear: true,
            data: buildSelect2Options(stages, { idKey: 'id', textKey: 'name' }),
        });

        // Init assigned_to select2
        initSelect2('#addEditLead select[name="assigned_to"]', {
            dropdownParent: drawerEl,
            placeholder: 'Assign to...',
            allowClear: true,
            data: buildSelect2Options(users, { idKey: 'id', textKey: 'name' }),
        });

        // Init date picker
        initDatePicker('#addEditLead input[name="expected_close_date"]');

        if (id > 0 && leadDetails && Object.keys(leadDetails).length > 0) {
            populateLeadForm(leadDetails);
        } else {
            refreshLeadDisplayNameSelect(); // init empty state
        }

        new bootstrap.Offcanvas(drawerEl).show();

    } catch(error) {
        handleApiError(error);
    }
}


document.getElementById('saveAddEditLead').addEventListener('click', async function() {

    const formEl = document.getElementById('addEditLeadForm');
    cleanFormInputFeedback(formEl);

    try {

        const id = formEl.querySelector('input#lead_id').value || '';
        const apiUrl = id ? `/crm/leads/${id}` : '/crm/leads';

        const payload = formDataToObject(new FormData(formEl));
        const res = await api.post(apiUrl, payload);
        const { code, message } = res.data;

        notyf.success(message);

        if( code === 201 || code === 200 ) {
            
            if( typeof leadsDt !== 'undefined' ) leadsDt.ajax.reload();
            bootstrap.Offcanvas.getInstance(document.getElementById('addEditLead')).hide();
            formEl.reset();
            document.dispatchEvent(new CustomEvent('leadFormSaved', { detail: { id: res.data.data?.id } }));
        }

    } catch(error) {
        handleApiError(error, formEl);
    }
});
</script>
@endpush
