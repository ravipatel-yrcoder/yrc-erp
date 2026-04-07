<div class="offcanvas offcanvas-end" tabindex="-1" id="leadNoteDrawer" aria-labelledby="leadNoteDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 400px;">

    <div class="offcanvas-header">
        <h5 id="leadNoteDrawerTitle" class="offcanvas-title">Add Note</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="leadNoteForm">
            <div class="mb-4">
                <label class="form-label required">Note</label>
                <textarea name="note" id="leadNoteText" class="form-control" rows="6" placeholder="Write a note..."></textarea>
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

<script>
const openLeadNoteDrawer = function() {

    const formEl = document.getElementById('leadNoteForm');
    cleanFormInputFeedback(formEl);
    formEl.reset();
    new bootstrap.Offcanvas(document.getElementById('leadNoteDrawer')).show();
};

document.getElementById('saveLeadNoteBtn').addEventListener('click', async function() {

    const note = document.getElementById('leadNoteText').value.trim();
    if (!note) { notyf.error("Please enter a note"); return; }

    try {

        await api.post(`/crm/leads/${leadId}/note`, { note });
        notyf.success("Note added");
        bootstrap.Offcanvas.getInstance(document.getElementById('leadNoteDrawer')).hide();
        document.getElementById('leadNoteForm').reset();
        document.dispatchEvent(new CustomEvent('leadNoteAdded'));

    } catch (e) {
        handleApiError(e);
    }
});
</script>
