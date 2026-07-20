<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditIntegration" aria-labelledby="addEditIntegrationDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 35%;">

    <div class="offcanvas-header">
        <h5 id="addEditIntegrationDrawerTitle" class="offcanvas-title">Add Integration</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="addEditIntegrationForm">
            <input type="hidden" id="integration_id" value="" />

            <div class="mb-4">
                <label class="form-label required">Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g: India Mart - Main" />
            </div>

            <div class="mb-4" id="integration_source_field">
                <label class="form-label required">Source</label>
                <select name="source" id="integration_source" class="form-select">
                    <option value="">-- Select Source --</option>
                    <option value="indiamart">India Mart</option>
                </select>
            </div>

            {{-- Webhook URL - shown read-only when editing --}}
            <div class="mb-4 d-none" id="integration_webhook_url_field">
                <label class="form-label">Webhook URL</label>
                <div class="input-group">
                    <input type="text" id="integration_webhook_url" class="form-control font-monospace" readonly />
                    <button type="button" class="btn btn-outline-secondary" id="copyWebhookUrlBtn" title="Copy URL">
                        <i class="bx bx-copy"></i>
                    </button>
                </div>
                <div class="form-text">Paste this URL in your third-party application's webhook settings.</div>
            </div>

            <div class="form-check pt-2">
                <input class="form-check-input" type="checkbox" name="is_active" id="integration_is_active" value="1" checked />
                <label class="form-check-label" for="integration_is_active">Active</label>
            </div>

        </form>
    </div>

    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveAddEditIntegration" class="btn btn-primary btn-sm min-w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>


@push('scripts')
<script>
const populateIntegrationForm = function(details) {

    if( !details || !Object.keys(details).length ) return;

    const drawerEl = document.getElementById('addEditIntegration');

    drawerEl.querySelector('input#integration_id').value   = details.id || '';
    drawerEl.querySelector('input[name="name"]').value     = details.name || '';

    // Source is locked after creation - hide the field and show the webhook URL
    document.getElementById('integration_source_field').classList.add('d-none');
    document.getElementById('integration_webhook_url_field').classList.remove('d-none');

    const url = WEBHOOK_BASE_URL + '/api/webhooks/' + details.source + '/' + details.token;
    document.getElementById('integration_webhook_url').value = url;

    jQuery('#addEditIntegration input[name="is_active"]').prop('checked', details.is_active == 1);
};

const openIntegrationFormDrawer = async function(id = 0) {

    const title   = id > 0 ? 'Edit Integration' : 'Add Integration';
    document.getElementById('addEditIntegrationDrawerTitle').innerHTML = title;

    const drawerEl = document.getElementById('addEditIntegration');
    const formEl   = document.getElementById('addEditIntegrationForm');

    cleanFormInputFeedback(formEl);
    formEl.reset();
    formEl.querySelector('input#integration_id').value = '';

    // Reset visibility of conditional fields
    document.getElementById('integration_source_field').classList.remove('d-none');
    document.getElementById('integration_webhook_url_field').classList.add('d-none');

    // Re-initialize source Select2
    initSelect2('#integration_source', {
        dropdownParent: '#addEditIntegration',
        resetVal: true,
    });

    try {

        if( id > 0 ) {
            const response = await api.get('/crm/integrations/form-context', { params: { id } });
            const { data } = response.data;
            populateIntegrationForm(data.integrationDetails || {});
        }

        new bootstrap.Offcanvas(drawerEl).show();

    } catch(error) {
        handleApiError(error);
    }
};

document.getElementById('copyWebhookUrlBtn').addEventListener('click', function() {
    const url = document.getElementById('integration_webhook_url').value;
    if( url ) copyWebhookUrl(url);
});

document.getElementById('saveAddEditIntegration').addEventListener('click', async function() {

    const btn = this;
    const formEl = document.getElementById('addEditIntegrationForm');

    const id = formEl.querySelector('input#integration_id').value || '';

    const endpoint = id ? '/crm/integrations/' + id : '/crm/integrations';

    cleanFormInputFeedback(formEl);

    const formData = new FormData(formEl);
    const payload  = formDataToObject(formData);

    // Checkbox — absent from FormData when unchecked
    payload.is_active = formEl.querySelector('input[name="is_active"]').checked ? 1 : 0;

    setButtonLoading(btn, true);
    try {

        const response        = await api.post(endpoint, payload);
        const { code, message } = response.data;

        notyf.success(message);

        if( code == 201 || code == 200 ) {
            if( typeof integrationsDt !== 'undefined' ) {
                integrationsDt.ajax.reload();
            }
            bootstrap.Offcanvas.getInstance(document.getElementById('addEditIntegration')).hide();
            formEl.reset();
        }

    } catch(error) {
        handleApiError(error, formEl);
    } finally {
        setButtonLoading(btn, false);
    }
});
</script>
@endpush
