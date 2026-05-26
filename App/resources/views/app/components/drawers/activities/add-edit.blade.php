<style>
#activityAttachmentsDropzone .dz-message::before {top: 25px;}
#activityAttachmentsDropzone .dz-message {margin: 75px 25px 25px;}
</style>
<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditActivity" aria-labelledby="addEditActivityDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 480px;">

    <div class="offcanvas-header">
        <h5 id="addEditActivityDrawerTitle" class="offcanvas-title">Schedule Activity</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="addEditActivityForm">
            <input type="hidden" id="activity_id" name="activity_id" value="" />
            <input type="hidden" id="activity_entity_type" name="entity_type" value="" />
            <input type="hidden" id="activity_entity_id" name="entity_id" value="" />

            <div class="mb-4">
                <label class="form-label required">Activity Type</label>
                <select class="select2 form-select" name="activity_type" id="activity_type">
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
                        <input type="text" name="due_time" id="activity_due_time" class="form-control" autocomplete="off" />
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
                <label class="form-label">Description <span class="text-muted small">(optional)</span></label>
                <textarea name="description" id="activity_description" class="form-control" rows="3" placeholder="Additional details..."></textarea>
            </div>

            <div class="mb-2">
                <label class="form-label">Attachments <span class="text-muted small">(optional)</span></label>
                <div id="activityExistingAttachments" class="mb-2"></div>
                <div class="dropzone" id="activityAttachmentsDropzone">
                    <div class="dz-message">
                        <span class="fs-tiny">Drop files here or <strong>click to upload</strong></span>
                        <small class="d-block text-muted mt-1">Max 5 files · 10 MB each</small>
                    </div>
                </div>
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

@push('scripts')
<script>
let _activityPendingDeleteIds = [];

const renderActivityExistingAttachments = function(attachments) {
    const container = document.getElementById('activityExistingAttachments');
    if (!attachments || !attachments.length) { container.innerHTML = ''; return; }
    container.innerHTML = attachments.map(a => `
        <div class="d-flex align-items-center gap-2 py-1 border-bottom activity-att-row" data-id="${a.id}">
            <i class="bx ${a.is_image ? 'bx-image' : 'bx-file'} text-muted fs-6 flex-shrink-0"></i>
            <a href="javascript:void(0);" onclick="downloadAttachment('${a.download_url}', '${a.original_name.replace(/'/g, "\\'")}')"
               class="text-truncate small flex-grow-1" style="max-width:220px;" title="${a.original_name}">${a.original_name}</a>
            <button type="button" class="btn btn-sm btn-link text-danger p-0 flex-shrink-0 remove-att-btn"
                    data-id="${a.id}" title="Will be removed on save">
                <i class="bx bx-x fs-5"></i>
            </button>
        </div>
    `).join('');
};

// Click delegate — marks for deletion but does NOT call the API yet.
// Actual deletion happens inside the save handler (delete_attachment_ids payload).
document.getElementById('activityExistingAttachments').addEventListener('click', function(e) {
    const btn = e.target.closest('.remove-att-btn');
    if (!btn) return;
    _activityPendingDeleteIds.push(btn.dataset.id);
    const row = btn.closest('.activity-att-row');
    row.classList.add('opacity-25', 'text-decoration-line-through');
    btn.remove();
});

const openActivityFormDrawer = async function(activityId = 0, relatedType = '', relatedId = 0) {

    const title = activityId > 0 ? 'Edit Activity' : 'Schedule Activity';
    document.getElementById('addEditActivityDrawerTitle').innerHTML = title;

    const drawerEl = document.getElementById('addEditActivity');
    const formEl   = document.getElementById('addEditActivityForm');

    cleanFormInputFeedback(formEl);
    formEl.reset();
    formEl.querySelector('#activity_id').value           = activityId || '';
    formEl.querySelector('#activity_entity_type').value = relatedType;
    formEl.querySelector('#activity_entity_id').value   = relatedId || '';

    // Reset attachment state
    _activityPendingDeleteIds = [];
    document.getElementById('activityExistingAttachments').innerHTML = '';
    const actDz = getDropzoneInstance('#activityAttachmentsDropzone');
    if (actDz) actDz.removeAllFiles(true);

    // Init type Select2 (static options)
    initSelect2('#addEditActivity select[name="activity_type"]', { dropdownParent: drawerEl, placeholder: 'Select type' });

    // Init date picker
    initDatePicker('#addEditActivity input[name="due_date"]');
    initTimePicker('#addEditActivity input[name="due_time"]');

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
        if (activityDetails) {
            const setField = function(sel, val) {
                const el = document.querySelector(sel);
                if (el) el.value = val ?? '';
            };

            setField('#addEditActivity select[name="activity_type"]', activityDetails.activity_type);
            jQuery('#addEditActivity select[name="activity_type"]').trigger('change');
            setField('#addEditActivity input[name="summary"]', activityDetails.summary);
            datePickerSetDate('#addEditActivity input[name="due_date"]', activityDetails.due_date);
            timePickerSetTime('#addEditActivity input[name="due_time"]', activityDetails.due_time || '');
            setField('#addEditActivity select[name="assigned_to"]', activityDetails.assigned_to);
            jQuery('#addEditActivity select[name="assigned_to"]').trigger('change');
            setField('#addEditActivity textarea[name="description"]', activityDetails.description || '');

            if (activityDetails.attachments && activityDetails.attachments.length > 0) {
                renderActivityExistingAttachments(activityDetails.attachments);
            }
        }

        new bootstrap.Offcanvas(drawerEl).show();

    } catch(error) {
        handleApiError(error);
    }
};


document.getElementById('saveActivityBtn').addEventListener('click', async function() {

    const formEl    = document.getElementById('addEditActivityForm');
    const activityId = document.getElementById('activity_id').value;

    cleanFormInputFeedback(formEl);

    const formData = new FormData(formEl);
    const payload  = formDataToObject(formData);
    delete payload.activity_id;

    // New attachments from Dropzone
    const actDz    = getDropzoneInstance('#activityAttachmentsDropzone');
    const newFiles = actDz ? await readDropzoneFilesAsBase64(actDz) : [];
    if (newFiles.length > 0) {
        payload.attachments = newFiles;
    }

    // IDs marked for removal — deferred until save
    if (_activityPendingDeleteIds.length > 0) {
        payload.delete_attachment_ids = _activityPendingDeleteIds;
    }

    try {

        const apiUrl = activityId ? `/activities/${activityId}` : `/activities`;
        const res    = await api.post(apiUrl, payload);
        const { code, message } = res.data;

        if (code === 201 || code === 200) {
            bootstrap.Offcanvas.getInstance(document.getElementById('addEditActivity')).hide();
            notyf.success(message);
            document.dispatchEvent(new CustomEvent('activityFormSaved'));
        }

    } catch (err) {
        handleApiError(err, formEl);
    }
});

jQuery(document).ready(function() {
    const dzEl = document.querySelector('#activityAttachmentsDropzone');
    if (dzEl) {
        new Dropzone(dzEl, {
            url: '#',
            autoProcessQueue: false,
            maxFiles: 5,
            maxFilesize: 10,
            acceptedFiles: '.jpg,.jpeg,.png,.gif,.webp,.svg,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv,.txt,.rtf,.zip,.xml',
            addRemoveLinks: true,
            dictRemoveFile: 'Remove',
        });
    }
});
</script>
@endpush