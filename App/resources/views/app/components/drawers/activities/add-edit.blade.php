<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditActivity" aria-labelledby="addEditActivityDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 480px;">

    <div class="offcanvas-header">
        <h5 id="addEditActivityDrawerTitle" class="offcanvas-title">Schedule Activity</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="addEditActivityForm">
            <input type="hidden" id="activity_id" name="activity_id" value="" />
            <input type="hidden" id="activity_related_type" name="related_type" value="" />
            <input type="hidden" id="activity_related_id" name="related_id" value="" />

            <div class="mb-4">
                <label class="form-label required">Activity Type</label>
                <select class="select2 form-select" name="type" id="activity_type">
                    <option value="">Select type</option>
                    <option value="call">Phone Call</option>
                    <option value="email">Email</option>
                    <option value="meeting">Meeting</option>
                    <option value="todo">To-Do</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label required">Summary</label>
                <input type="text" name="summary" id="activity_summary" class="form-control" placeholder="What needs to be done?" />
            </div>

            <div class="row">
                <div class="col-md-7">
                    <div class="mb-4">
                        <label class="form-label required">Due Date</label>
                        <input type="text" name="due_date" id="activity_due_date" class="form-control" placeholder="DD/MM/YYYY" autocomplete="off" />
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="mb-4">
                        <label class="form-label">Due Time <span class="text-muted small">(optional)</span></label>
                        <input type="time" name="due_time" id="activity_due_time" class="form-control" />
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Assigned To</label>
                <select class="select2 form-select" name="assigned_to" id="activity_assigned_to">
                    <option value="">Unassigned</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label">Note <span class="text-muted small">(optional)</span></label>
                <textarea name="note" id="activity_note" class="form-control" rows="3" placeholder="Additional details..."></textarea>
            </div>

        </form>
    </div>

    <div class="offcanvas-footer border-top p-4">
        <div class="d-flex gap-2">
            <button type="button" id="saveActivityBtn" class="btn btn-sm btn-primary">Save Activity</button>
            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>

</div>

<script>
const openActivityFormDrawer = async function(activityId = 0, relatedType = '', relatedId = 0) {

    const title = activityId > 0 ? 'Edit Activity' : 'Schedule Activity';
    document.getElementById('addEditActivityDrawerTitle').innerHTML = title;

    const drawerEl = document.getElementById('addEditActivity');
    const formEl   = document.getElementById('addEditActivityForm');

    cleanFormInputFeedback(formEl);
    formEl.reset();
    formEl.querySelector('#activity_id').value           = activityId || '';
    formEl.querySelector('#activity_related_type').value = relatedType;
    formEl.querySelector('#activity_related_id').value   = relatedId || '';

    // Init type Select2 (static options)
    initSelect2('#addEditActivity select[name="type"]', { dropdownParent: drawerEl, placeholder: 'Select type' });

    // Init date picker
    initDatePicker('#addEditActivity input[name="due_date"]');

    try {

        const response = await api.get('/activities/form-context', { params: { id: activityId } });
        const { users = [], activityDetails } = response.data.data;

        // Init assigned_to Select2 with users from API
        const userOpts = buildSelect2Options(users, { idKey: 'id', textKey: 'name' });
        initSelect2('#addEditActivity select[name="assigned_to"]', {
            dropdownParent: drawerEl,
            placeholder: 'Unassigned',
            allowClear: true,
            data: userOpts,
        });

        // Populate for edit
        if( activityDetails ) {
            const setField = function(sel, val) {
                const el = document.querySelector(sel);
                if (el) el.value = val ?? '';
            };

            setField('#addEditActivity select[name="type"]', activityDetails.type);
            jQuery('#addEditActivity select[name="type"]').trigger('change');
            setField('#addEditActivity input[name="summary"]', activityDetails.summary);
            datePickerSetDate('#addEditActivity input[name="due_date"]', activityDetails.due_date);
            setField('#addEditActivity input[name="due_time"]', activityDetails.due_time || '');
            setField('#addEditActivity select[name="assigned_to"]', activityDetails.assigned_to);
            jQuery('#addEditActivity select[name="assigned_to"]').trigger('change');
            setField('#addEditActivity textarea[name="note"]', activityDetails.note || '');
        }

        new bootstrap.Offcanvas(drawerEl).show();

    } catch(error) {
        handleApiError(error);
    }
};


document.getElementById('saveActivityBtn').addEventListener('click', async function() {

    const formEl = document.getElementById('addEditActivityForm');
    cleanFormInputFeedback(formEl);

    const activityId = document.getElementById('activity_id').value;
    const formData = new FormData(formEl);
    const payload = Object.fromEntries(formData.entries());

    // Remove activity_id from payload; it's the route param, not a body field
    delete payload.activity_id;

    try {

        const apiUrl = activityId ? `/activities/${activityId}` : `/activities`;
        const res = await api.post(apiUrl, payload);
        const { code } = res.data;

        if( code === 201 || code === 200 ) {

            bootstrap.Offcanvas.getInstance(document.getElementById('addEditActivity')).hide();
            notyf.success(activityId ? 'Activity updated' : 'Activity scheduled');
            document.dispatchEvent(new CustomEvent('activityFormSaved'));
        }

    } catch (err) {
        if( err.response?.data?.errors ) {
            showFormErrors(formEl, err.response.data.errors);
        } else {
            notyf.error(err.response?.data?.message || 'Failed to save activity');
        }
    }
});
</script>