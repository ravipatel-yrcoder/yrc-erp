<div class="modal fade" id="importProductsModal" tabindex="-1" aria-labelledby="importProductsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="importProductsModalLabel">Import Products</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

                {{-- State: Upload --}}
                <div id="importUploadState">
                    <p class="text-muted small mb-3">
                        <a href="/assets/files/products-import-template.csv" download>Download the template</a>, fill in your products, then upload the completed CSV file.
                    </p>
                    <div>
                        <label class="form-label required">CSV File</label>
                        <input type="file" class="form-control" id="importProductsFile" accept=".csv" />
                    </div>
                </div>

                {{-- State: Errors --}}
                <div id="importErrorState" class="d-none">
                    <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                        <i class="icon-base bx bx-error-circle me-2 fs-5"></i>
                        <span id="importErrorSummary"></span>
                    </div>
                    <div style="max-height:300px;overflow-y:auto;">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px;">Row</th>
                                    <th style="width:130px;">Column</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody id="importErrorTableBody"></tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <div id="importUploadFooter" class="d-flex gap-2">
                    <button type="button" id="importProductsBtn" class="btn btn-primary btn-sm">Import</button>
                    <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                </div>
                <div id="importErrorFooter" class="d-none d-flex gap-2">
                    <button type="button" id="importTryAgainBtn" class="btn btn-outline-primary btn-sm">Try Again</button>
                    <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
const openImportProductsModal = function() {
    resetImportProductsModal();
    new bootstrap.Modal(document.getElementById('importProductsModal')).show();
};

const resetImportProductsModal = function() {
    document.getElementById('importUploadState').classList.remove('d-none');
    document.getElementById('importErrorState').classList.add('d-none');
    document.getElementById('importUploadFooter').classList.remove('d-none');
    document.getElementById('importErrorFooter').classList.add('d-none');
    document.getElementById('importProductsFile').value = '';
    const btn = document.getElementById('importProductsBtn');
    setButtonLoading(btn, false);
};

const showImportProductErrors = function(errors) {
    document.getElementById('importUploadState').classList.add('d-none');
    document.getElementById('importUploadFooter').classList.add('d-none');
    document.getElementById('importErrorState').classList.remove('d-none');
    document.getElementById('importErrorFooter').classList.remove('d-none');

    document.getElementById('importErrorSummary').textContent =
        `${errors.length} error(s) found. Please fix your file and try again.`;

    document.getElementById('importErrorTableBody').innerHTML = errors.map(e =>
        `<tr>
            <td class="text-center">${e.row}</td>
            <td>${e.column}</td>
            <td class="text-danger">${e.message}</td>
        </tr>`
    ).join('');
};

document.getElementById('importProductsBtn').addEventListener('click', async function() {

    const fileInput = document.getElementById('importProductsFile');
    if (!fileInput.files.length) {
        notyf.error('Please select a CSV file to import');
        return;
    }

    const btn = this;
    setButtonLoading(btn, true);

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    try {
        const response = await api.post('/products/import', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        const { data, message } = response.data;

        bootstrap.Modal.getInstance(document.getElementById('importProductsModal')).hide();
        notyf.success(`${data.imported} product(s) imported successfully`);

        if (typeof productsDt !== 'undefined') {
            productsDt.ajax.reload();
        }

    } catch(error) {
        const errors = error.response?.data?.errors;
        if (errors && errors.length) {
            showImportProductErrors(errors);
        } else {
            setButtonLoading(btn, false);
            handleApiError(error);
        }
    }
});

document.getElementById('importTryAgainBtn').addEventListener('click', function() {
    resetImportProductsModal();
});
</script>
@endpush
