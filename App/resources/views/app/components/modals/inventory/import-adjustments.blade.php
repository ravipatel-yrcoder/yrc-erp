<div class="modal fade" id="importAdjustmentsModal" tabindex="-1" aria-labelledby="importAdjustmentsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="importAdjustmentsModalLabel">Import Stock Adjustments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

                {{-- State: Upload --}}
                <div id="importAdjUploadState">
                    <p class="text-muted small mb-3">
                        <a href="javascript:void(0);" id="downloadAdjTemplate">Download the pre-filled template</a>, fill in your adjustments, then upload the completed CSV file.
                    </p>
                    <div>
                        <label class="form-label required">CSV File</label>
                        <input type="file" class="form-control" id="importAdjFile" accept=".csv" />
                    </div>
                </div>

                {{-- State: Errors --}}
                <div id="importAdjErrorState" class="d-none">
                    <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                        <i class="icon-base bx bx-error-circle me-2 fs-5"></i>
                        <span id="importAdjErrorSummary"></span>
                    </div>
                    <div style="max-height:300px;overflow-y:auto;">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px;">Row</th>
                                    <th style="width:150px;">Column</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody id="importAdjErrorTableBody"></tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <div id="importAdjUploadFooter" class="d-flex gap-2">
                    <button type="button" id="importAdjBtn" class="btn btn-primary btn-sm">Import</button>
                    <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                </div>
                <div id="importAdjErrorFooter" class="d-none d-flex gap-2">
                    <button type="button" id="importAdjTryAgainBtn" class="btn btn-outline-primary btn-sm">Try Again</button>
                    <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
const openImportAdjustmentsModal = function() {
    resetImportAdjustmentsModal();
    new bootstrap.Modal(document.getElementById('importAdjustmentsModal')).show();
};

const resetImportAdjustmentsModal = function() {
    document.getElementById('importAdjUploadState').classList.remove('d-none');
    document.getElementById('importAdjErrorState').classList.add('d-none');
    document.getElementById('importAdjUploadFooter').classList.remove('d-none');
    document.getElementById('importAdjErrorFooter').classList.add('d-none');
    document.getElementById('importAdjFile').value = '';
    const btn = document.getElementById('importAdjBtn');
    btn.disabled = false;
    btn.innerHTML = 'Import';
};

const showImportAdjErrors = function(errors) {
    document.getElementById('importAdjUploadState').classList.add('d-none');
    document.getElementById('importAdjUploadFooter').classList.add('d-none');
    document.getElementById('importAdjErrorState').classList.remove('d-none');
    document.getElementById('importAdjErrorFooter').classList.remove('d-none');

    document.getElementById('importAdjErrorSummary').textContent =
        `${errors.length} error(s) found. Please fix your file and try again.`;

    document.getElementById('importAdjErrorTableBody').innerHTML = errors.map(e =>
        `<tr>
            <td class="text-center">${e.row}</td>
            <td>${e.column}</td>
            <td class="text-danger">${e.message}</td>
        </tr>`
    ).join('');
};

document.getElementById('downloadAdjTemplate').addEventListener('click', async function() {
    try {
        const response = await api.get('/inv/adjustments/import-template', { responseType: 'blob' });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const a = document.createElement('a');
        a.href = url;
        a.download = 'stock-adjustments-template.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    } catch(e) {
        notyf.error('Failed to download template');
    }
});

document.getElementById('importAdjBtn').addEventListener('click', async function() {

    const fileInput = document.getElementById('importAdjFile');
    if (!fileInput.files.length) {
        notyf.error('Please select a CSV file to import');
        return;
    }

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Importing...';

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    try {
        const response = await api.post('/inv/adjustments/import', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        const { data } = response.data;

        bootstrap.Modal.getInstance(document.getElementById('importAdjustmentsModal')).hide();
        notyf.success(`${data.imported} adjustment(s) imported successfully`);

        if (typeof invAdjustmentsDt !== 'undefined') {
            invAdjustmentsDt.ajax.reload();
        }

    } catch(error) {
        const errors = error.response?.data?.errors;
        if (errors && errors.length && typeof errors[0] === 'object') {
            showImportAdjErrors(errors);
        } else {
            btn.disabled = false;
            btn.innerHTML = 'Import';
            handleApiError(error);
        }
    }
});

document.getElementById('importAdjTryAgainBtn').addEventListener('click', function() {
    resetImportAdjustmentsModal();
});
</script>
@endpush
