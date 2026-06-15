<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditLead" aria-labelledby="addEditLeadDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 45%;">

    <div class="offcanvas-header">
        <h5 id="addEditLeadDrawerTitle" class="offcanvas-title">Add Lead</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        <form id="addEditLeadForm">
            <input type="hidden" id="lead_id" value="" />

            <div class="nav-align-top">
                <ul class="nav nav-tabs shadow" id="leadFormTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="leadTabOverviewBtn" data-bs-toggle="tab" data-bs-target="#leadTabOverview" type="button" role="tab">Overview</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="leadTabDetailsBtn" data-bs-toggle="tab" data-bs-target="#leadTabDetails" type="button" role="tab">Extra Details</button>
                    </li>
                </ul>

                <div class="tab-content px-0">

                {{-- TAB 1: OVERVIEW --}}
                <div class="tab-pane fade show active" id="leadTabOverview" role="tabpanel">

                    {{-- Title & Notes --}}
                    <div class="mb-4">
                        <div class="mb-3">
                            <label class="form-label required">Title / Inquiry</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Solar Panel Quote Request, Enterprise License Inquiry" />
                        </div>
                        <div>
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="5" placeholder="Initial notes, context, or background about this lead..."></textarea>
                        </div>
                    </div>

                    {{-- Section: Contact --}}
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="text-muted small fw-semibold text-uppercase">Contact</span>
                            <hr class="flex-grow-1 my-0" />
                        </div>

                        {{-- Salutation, First Name, Last Name --}}
                        <div class="row g-2 mb-3">
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

                        {{-- Phone & Email --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="+91 98765 43210" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="email@example.com" />
                            </div>
                        </div>

                        {{-- Company Name & Job Title --}}
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" id="lead_company_name" class="form-control" placeholder="Company or organisation" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Job Title</label>
                                <input type="text" name="job_title" class="form-control" placeholder="e.g. Marketing Manager" />
                            </div>
                        </div>
                    </div>

                    {{-- Section: Lead Qualification --}}
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="text-muted small fw-semibold text-uppercase">Lead Qualification</span>
                            <hr class="flex-grow-1 my-0" />
                        </div>

                        {{-- Stage, Priority, Assigned To --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Stage</label>
                                <select class="select2 form-select" name="stage_id">
                                    <option></option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Priority</label>
                                <select class="select2 form-select" name="priority">
                                    @foreach(config('constants.crm.lead_priorities') as $p)
                                        <option value="{{ $p['key'] }}">{{ $p['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Assigned To</label>
                                <select class="select2 form-select" name="assigned_to">
                                    <option></option>
                                </select>
                            </div>
                        </div>

                        {{-- Probability, Expected Revenue, Expected Close Date --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Probability (%)</label>
                                <input type="number" name="probability" class="form-control" placeholder="0" min="0" max="100" step="1" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Expected Revenue</label>
                                <input type="number" name="expected_revenue" class="form-control" placeholder="0" min="0" step="1" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Expected Close Date</label>
                                <input type="text" name="expected_close_date" class="form-control" placeholder="DD/MM/YYYY" autocomplete="off" />
                            </div>
                        </div>

                        {{-- Source & Tags --}}
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Source</label>
                                <select class="select2 form-select" name="source">
                                    <option value="">— Select source —</option>
                                    @foreach(config('constants.crm.lead_sources') as $s)
                                        <option value="{{ $s['key'] }}">{{ $s['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tags</label>
                                <input type="text" name="tags" id="lead_tags" class="form-control" placeholder="Add tags..." />
                            </div>
                        </div>
                    </div>

                </div>
                {{-- END TAB 1 --}}

                {{-- TAB 2: EXTRA DETAILS --}}
                <div class="tab-pane fade px-0" id="leadTabDetails" role="tabpanel">

                    {{-- Website --}}
                    <div class="mb-4">
                        <label class="form-label">Website</label>
                        <input type="text" name="website" class="form-control" placeholder="https://example.com" />
                    </div>

                    {{-- Address --}}
                    <div class="mb-3">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="text-muted small fw-semibold text-uppercase">Address</span>
                            <hr class="flex-grow-1 my-0" />
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Address Line 1</label>
                                <textarea name="address_line1" class="form-control" rows="2" placeholder="Street address, building..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address Line 2</label>
                                <textarea name="address_line2" class="form-control" rows="2" placeholder="Apartment, suite, unit..."></textarea>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" placeholder="City" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">State</label>
                                <input type="text" name="state" class="form-control" placeholder="State / Province" />
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal_code" class="form-control" placeholder="Postal / ZIP code" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Country</label>
                                <select class="select2 form-select" name="country">
                                    <option value=""></option>
                                    @foreach (getCountries() as $countryCode => $countryName)
                                        <option value="{{ $countryCode }}">{{ $countryName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                    </div>

                </div>
                {{-- END TAB 2 --}}

            </div>{{-- end tab-content --}}
            </div>{{-- end nav-align-top --}}

        </form>
    </div>

    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveAddEditLead" class="btn btn-primary btn-sm min-w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>


@push('scripts')
<script>
let leadTagify = null;

const populateLeadForm = function(details) {

    if (!details || Object.keys(details).length === 0) return;

    const setField = function(sel, val) {
        const el = document.querySelector(sel);
        if (!el) return;
        el.value = val ?? '';
        el.dispatchEvent(new Event('change', { bubbles: true }));
    };

    document.querySelector('#addEditLead input#lead_id').value = details.id || '';

    // Contact section
    jQuery('#addEditLead select[name="salutation"]').val(details.salutation || '').trigger('change');
    setField('#addEditLead input[name="first_name"]', details.first_name);
    setField('#addEditLead input[name="last_name"]', details.last_name);
    setField('#addEditLead input[name="company_name"]', details.company_name);
    setField('#addEditLead input[name="phone"]', details.phone);
    setField('#addEditLead input[name="email"]', details.email);

    // Lead section
    setField('#addEditLead input[name="title"]', details.title);
    jQuery('#addEditLead select[name="stage_id"]').val(details.stage_id || '').trigger('change');
    jQuery('#addEditLead select[name="priority"]').val(details.priority || 'medium').trigger('change');
    jQuery('#addEditLead select[name="assigned_to"]').val(details.assigned_to || '').trigger('change');
    setField('#addEditLead input[name="job_title"]', details.job_title);
    jQuery('#addEditLead select[name="source"]').val(details.source || '').trigger('change');
    setField('#addEditLead input[name="probability"]', details.probability);
    setField('#addEditLead input[name="expected_revenue"]', details.expected_revenue);
    datePickerSetDate('#addEditLead input[name="expected_close_date"]', details.expected_close_date);

    // Notes
    setField('#addEditLead textarea[name="notes"]', details.notes);

    // Contact Details tab
    setField('#addEditLead input[name="website"]', details.website);
    setField('#addEditLead textarea[name="address_line1"]', details.address_line1);
    setField('#addEditLead textarea[name="address_line2"]', details.address_line2);
    setField('#addEditLead input[name="city"]', details.city);
    setField('#addEditLead input[name="state"]', details.state);
    setField('#addEditLead input[name="postal_code"]', details.postal_code);
    jQuery('#addEditLead select[name="country"]').val(details.country || '').trigger('change');

    // Tags via Tagify
    if (leadTagify) {
        leadTagify.removeAllTags();
        if (Array.isArray(details.tags) && details.tags.length) {
            leadTagify.addTags(details.tags);
        }
    }
};


const openLeadFormDrawer = async function(id = 0, defaultStageId = null) {

    const title = id > 0 ? 'Edit Lead' : 'Add Lead';
    document.getElementById('addEditLeadDrawerTitle').innerHTML = title;

    const drawerEl = document.getElementById('addEditLead');
    const formEl   = document.getElementById('addEditLeadForm');

    cleanFormInputFeedback(formEl);
    formEl.reset();
    formEl.querySelector('input#lead_id').value = '';

    // Reset to Overview tab
    bootstrap.Tab.getOrCreateInstance(document.getElementById('leadTabOverviewBtn')).show();

    // Destroy stale Tagify instance before reinit
    if (leadTagify) { leadTagify.destroy(); leadTagify = null; }

    try {

        const res = await api.get('/crm/leads/form-context', { params: { id } });
        const { stages = [], users = [], leadDetails = {} } = res.data.data;

        const firstStage   = stages[0] || {};
        const defaultStage = firstStage.id || 0;

        // Salutation
        initSelect2('#addEditLead select[name="salutation"]', {
            dropdownParent: drawerEl,
            allowClear: true,
            minimumResultsForSearch: Infinity,
            placeholder: '—',
        });

        // Stage
        initSelect2('#addEditLead select[name="stage_id"]', {
            dropdownParent: drawerEl,
            placeholder: 'Select stage',
            allowClear: true,
            data: buildSelect2Options(stages, { idKey: 'id', textKey: 'name' }),
        });

        // Priority
        initSelect2('#addEditLead select[name="priority"]', {
            dropdownParent: drawerEl,
            minimumResultsForSearch: Infinity,
            allowClear: false,
        });
        jQuery('#addEditLead select[name="priority"]').val('medium').trigger('change');

        // Assigned To
        initSelect2('#addEditLead select[name="assigned_to"]', {
            dropdownParent: drawerEl,
            placeholder: 'Assign to...',
            allowClear: true,
            data: buildSelect2Options(users, { idKey: 'id', textKey: 'name' }),
        });

        // Source
        initSelect2('#addEditLead select[name="source"]', {
            dropdownParent: drawerEl,
            placeholder: '— Select source —',
            allowClear: true,
            minimumResultsForSearch: Infinity,
        });

        // Country
        initSelect2('#addEditLead select[name="country"]', {
            dropdownParent: drawerEl,
            placeholder: 'Select country',
            allowClear: true,
        });

        // Date picker
        initDatePicker('#addEditLead input[name="expected_close_date"]');

        // Tagify for tags
        leadTagify = new Tagify(formEl.querySelector('input[name="tags"]'), {
            originalInputValueFormat: valuesArr => JSON.stringify(valuesArr.map(item => item.value)),
        });

        if (id > 0 && leadDetails && Object.keys(leadDetails).length > 0) {
            populateLeadForm(leadDetails);
        } else {
            if (!defaultStageId) defaultStageId = defaultStage;
            if (defaultStageId) {
                jQuery('#addEditLead select[name="stage_id"]').val(defaultStageId).trigger('change');
            }
        }

        new bootstrap.Offcanvas(drawerEl).show();

    } catch(error) {
        handleApiError(error);
    }
}


document.getElementById('saveAddEditLead').addEventListener('click', async function() {

    const btn = this;
    const formEl = document.getElementById('addEditLeadForm');
    cleanFormInputFeedback(formEl);

    const leadId = formEl.querySelector('input#lead_id').value || '';
    const apiUrl = leadId ? `/crm/leads/${leadId}` : '/crm/leads';

    const payload = formDataToObject(new FormData(formEl));

    setButtonLoading(btn, true);
    try {

        const res = await api.post(apiUrl, payload);
        const { code, message, data } = res.data;

        notyf.success(message);

        if( code === 201 || code === 200 ) {
            if( typeof leadsDt !== 'undefined' ) leadsDt.ajax.reload();
            bootstrap.Offcanvas.getInstance(document.getElementById('addEditLead')).hide();
            formEl.reset();
            document.dispatchEvent(new CustomEvent('leadFormSaved', { detail: { id: leadId } }));
        }

    } catch(error) {
        handleApiError(error, formEl);
    } finally {
        setButtonLoading(btn, false);
    }
});
</script>
@endpush
