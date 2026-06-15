<style>
#noteAttachmentsDropzone .dz-message::before {top: 25px;}
#noteAttachmentsDropzone .dz-message {margin: 75px 25px 25px;}
</style>
<div class="offcanvas offcanvas-end" tabindex="-1" id="leadNoteDrawer" aria-labelledby="leadNoteDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 400px;">

    <div class="offcanvas-header">
        <h5 id="leadNoteDrawerTitle" class="offcanvas-title">Add Note</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="leadNoteForm">
            <div class="hidden">
                <input type="hidden" id="leadId" value="" />
            </div>
            <div class="mb-4">
                <label class="form-label required">Note</label>
                <textarea name="note" id="leadNoteText" class="form-control" rows="6" placeholder="Write a note..."></textarea>
            </div>
            <div class="mb-2">
                <label class="form-label">Attachments <span class="text-muted small">(optional)</span></label>
                <div class="dropzone" id="noteAttachmentsDropzone">
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
            <button type="button" id="saveLeadNoteBtn" class="btn btn-sm btn-primary">Save Note</button>
            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>

</div>

@push('scripts')
<script>
const openLeadNoteDrawer = function(leadId) {

    const formEl = document.getElementById('leadNoteForm');
    cleanFormInputFeedback(formEl);
    formEl.reset();

    formEl.querySelector('#leadId').value = leadId;

    const noteDz = getDropzoneInstance('#noteAttachmentsDropzone');
    if (noteDz) noteDz.removeAllFiles(true);

    new bootstrap.Offcanvas(document.getElementById('leadNoteDrawer')).show();
};

document.getElementById('saveLeadNoteBtn').addEventListener('click', async function() {
    var btn = this;

    const leadId = document.querySelector('#leadNoteDrawer #leadId')?.value;
    const note = document.querySelector('#leadNoteDrawer #leadNoteText').value.trim();
    if (!note) { notyf.error("Please enter a note"); return; }

    const payload = { note };

    const noteDz = getDropzoneInstance('#noteAttachmentsDropzone');
    const newFiles = noteDz ? await readDropzoneFilesAsBase64(noteDz) : [];
    if (newFiles.length > 0) {
        payload.attachments = newFiles;
    }

    setButtonLoading(btn, true);
    try {

        const res = await api.post(`/crm/leads/${leadId}/note`, payload);
        notyf.success(res.data?.message || 'Note added');
        bootstrap.Offcanvas.getInstance(document.getElementById('leadNoteDrawer')).hide();
        document.getElementById('leadNoteForm').reset();
        document.dispatchEvent(new CustomEvent('leadNoteAdded', {detail: {lead_id: leadId}}));

    } catch (e) {
        handleApiError(e);
    } finally {
        setButtonLoading(btn, false);
    }
});

jQuery(document).ready(function() {

    const dzEl = document.querySelector('#noteAttachmentsDropzone');
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