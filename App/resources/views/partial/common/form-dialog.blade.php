{{-- Global Form Dialog — populated dynamically by showFormDialog() in app-custom.js --}}
<div class="modal fade" id="appFormDialog" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header py-3">
                <h5 class="modal-title fw-semibold" id="appFormDialogTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <div id="appFormDialogDescription" class="mb-3" style="display:none;"></div>
                <div id="appFormDialogBody"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary btn-sm" id="appFormDialogCancelBtn" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="appFormDialogSaveBtn">Save</button>
            </div>
        </div>
    </div>
</div>
