<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditFeature" aria-labelledby="addEditFeatureTitle"
     data-bs-backdrop="static" data-bs-keyboard="false" style="width:460px;">

    <div class="offcanvas-header">
        <h5 id="addEditFeatureTitle" class="offcanvas-title">Add Feature</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="addEditFeatureForm" autocomplete="off">
            <input type="hidden" id="feature_id" value="" />

            <div class="mb-4">
                <label class="form-label required">Name</label>
                <input type="text" name="name" id="featureName" class="form-control" placeholder="e.g. CRM Leads" />
            </div>

            <div class="mb-4">
                <label class="form-label required">Key</label>
                <input type="text" name="key" id="featureKey" class="form-control font-monospace" placeholder="e.g. crm.leads" />
                <div class="form-text">Unique slug. Auto-generated from name.</div>
            </div>

            <div class="mb-4">
                <label class="form-label required">Access Level</label>
                <select name="access_level" id="featureAccessLevel" class="form-select">
                    <option value="subscription">Subscription — gated by subscribed module</option>
                    <option value="core">Core — always accessible, role-controlled</option>
                    <option value="super_admin">Super Admin Only — always accessible, super admins only</option>
                </select>
            </div>

            <div id="featureModuleSection" class="mb-4">
                <label class="form-label" id="featureModuleLabel">Primary Module</label>
                <select name="module_id" id="featureModuleId" class="form-select"></select>
                <div class="form-text" id="featureModuleHint">The module this feature belongs to for display grouping.</div>
            </div>

            <div id="featureExtraModulesSection" class="mb-4">
                <label class="form-label">Also Accessible Via</label>
                <select id="featureExtraModules" class="form-select" multiple></select>
                <div class="form-text">Additional modules whose subscription also unlocks this feature (e.g. Quotations in both CRM &amp; Sales).</div>
            </div>

            <div class="mb-4">
                <label class="form-label">Route</label>
                <input type="text" name="route" class="form-control font-monospace" placeholder="e.g. /crm/leads" />
            </div>

            <div class="mb-4">
                <label class="form-label">Route Type</label>
                <select name="route_type" id="featureRouteType" class="form-select">
                    <option value="front">Front (web page)</option>
                    <option value="api">API only</option>
                    <option value="both">Both</option>
                </select>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="featureIsActive" value="1" checked />
                <label class="form-check-label" for="featureIsActive">Active</label>
            </div>
        </form>
    </div>

    <div class="offcanvas-footer border-top p-3">
        <div class="d-flex gap-2">
            <button type="button" id="saveFeatureBtn" class="btn btn-primary btn-sm px-4">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const featureDrawerEl  = document.getElementById('addEditFeature');
const featureFormEl    = document.getElementById('addEditFeatureForm');

// Auto-generate key from name (only when key field is empty / untouched)
let featureKeyManual = false;
document.getElementById('featureName').addEventListener('input', function() {
    if (featureKeyManual) return;
    document.getElementById('featureKey').value = this.value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s.]/g, '')
        .replace(/\s+/g, '.');
});
document.getElementById('featureKey').addEventListener('input', function() {
    featureKeyManual = this.value.length > 0;
});

// Show/hide module fields based on access level
const applyAccessLevelUI = function(level) {
    const moduleSection = document.getElementById('featureModuleSection');
    const extraSection  = document.getElementById('featureExtraModulesSection');
    const moduleLabel   = document.getElementById('featureModuleLabel');
    const moduleHint    = document.getElementById('featureModuleHint');

    if (level === 'subscription') {
        moduleSection.style.display = '';
        extraSection.style.display  = '';
        moduleLabel.classList.add('required');
        moduleHint.textContent = 'The module this feature belongs to for display grouping.';
    } else {
        // core / super_admin: module is optional display grouping only
        moduleSection.style.display = '';
        extraSection.style.display  = 'none';
        moduleLabel.classList.remove('required');
        moduleHint.textContent = 'Optional — used for display grouping in the admin table only.';
    }
};

document.getElementById('featureAccessLevel').addEventListener('change', function() {
    applyAccessLevelUI(this.value);
});

const openFeatureFormDrawer = async function(id = 0) {
    const title = id > 0 ? 'Edit Feature' : 'Add Feature';
    document.getElementById('addEditFeatureTitle').textContent = title;

    cleanFormInputFeedback(featureFormEl);
    featureFormEl.reset();
    featureFormEl.querySelector('#feature_id').value = '';
    featureKeyManual = false;

    // Reset access level to default and apply UI
    document.getElementById('featureAccessLevel').value = 'subscription';
    applyAccessLevelUI('subscription');

    // Clear selects
    const moduleSelect = document.getElementById('featureModuleId');
    moduleSelect.innerHTML = '<option value="">— Select module —</option>';
    const extraSelect = document.getElementById('featureExtraModules');
    extraSelect.innerHTML = '';

    try {
        const params  = id > 0 ? `?id=${id}` : '';
        const resp    = await fetch(`/admin/features/form-context${params}`);
        const json    = await resp.json();
        const { data } = json;

        // Populate primary module select
        (data.modules || []).forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.name;
            moduleSelect.appendChild(opt);
        });
        initSelect2('#featureModuleId', { dropdownParent: $('#addEditFeature') });

        // Populate extra modules multi-select (all modules)
        (data.modules || []).forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.name;
            extraSelect.appendChild(opt);
        });
        initSelect2('#featureExtraModules', {
            dropdownParent: $('#addEditFeature'),
            placeholder: '— None —',
            allowClear: true,
        });

        // Populate form if editing
        if (id > 0 && data.feature) {
            const f = data.feature;
            featureFormEl.querySelector('#feature_id').value         = f.id;
            featureFormEl.querySelector('[name="name"]').value       = f.name      || '';
            featureFormEl.querySelector('[name="key"]').value        = f.key       || '';
            featureFormEl.querySelector('[name="route"]').value      = f.route     || '';
            featureFormEl.querySelector('#featureIsActive').checked  = f.is_active == 1;
            featureKeyManual = true;

            // access level
            const level = f.access_level || 'subscription';
            document.getElementById('featureAccessLevel').value = level;
            applyAccessLevelUI(level);

            // route_type select
            featureFormEl.querySelector('[name="route_type"]').value = f.route_type || 'front';

            // primary module
            $('#featureModuleId').val(f.module_id).trigger('change');

            // extra modules (only relevant for subscription features)
            if (level === 'subscription') {
                const extraIds = (data.extra_module_ids || []).map(String);
                $('#featureExtraModules').val(extraIds).trigger('change');
            }
        }

        new bootstrap.Offcanvas(featureDrawerEl).show();

    } catch(err) {
        notyf.error('Failed to load form');
    }
};

document.getElementById('saveFeatureBtn').addEventListener('click', async function() {
    var btn = this;
    cleanFormInputFeedback(featureFormEl);

    const id      = featureFormEl.querySelector('#feature_id').value;
    const url     = id ? `/admin/features/${id}/update` : '/admin/features/store';
    const payload = formDataToObject(new FormData(featureFormEl));

    // Checkbox sends nothing when unchecked — normalise
    payload.is_active = featureFormEl.querySelector('#featureIsActive').checked ? '1' : '0';

    // Collect extra module IDs from multi-select
    payload.extra_module_ids = Array.from(document.getElementById('featureExtraModules').selectedOptions)
        .map(o => o.value)
        .filter(v => v !== '');

    setButtonLoading(btn, true);
    try {
        const resp = await fetch(url, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        });
        const json = await resp.json();

        if (!resp.ok) {
            handleApiError({ response: { data: json } }, featureFormEl);
            return;
        }

        notyf.success(json.message || 'Saved');
        bootstrap.Offcanvas.getInstance(featureDrawerEl)?.hide();
        setTimeout(() => window.location.reload(), 300);

    } catch(err) {
        notyf.error('Save failed');
    } finally {
        setButtonLoading(btn, false);
    }
});
</script>
@endpush
