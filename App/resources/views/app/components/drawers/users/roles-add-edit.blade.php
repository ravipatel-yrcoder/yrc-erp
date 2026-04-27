<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditRole" aria-labelledby="addEditRoleDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="offcanvas-header">
        <h5 id="addEditRoleDrawerTitle" class="offcanvas-title">Add Role</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="addEditRoleForm">
            <input type="hidden" id="roleId" value="" />

            <div class="mb-4">
                <label class="form-label required">Role Name</label>
                <input type="text" name="name" id="roleNameInput" class="form-control" placeholder="e.g. Sales Manager" />
                <div class="form-text text-muted d-none" id="roleNameHint">System role names cannot be changed.</div>
            </div>

            <div class="mb-4">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Optional description"></textarea>
            </div>

        </form>
    </div>
    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveRoleBtn" class="btn btn-primary btn-sm w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const populateRoleForm = function(details) {
    if (!details || Object.keys(details).length === 0) return;

    const { id, name, description, is_system } = details;

    document.getElementById('roleId').value = id || '';
    jQuery('#addEditRole input[name="name"]').val(name || '');
    jQuery('#addEditRole textarea[name="description"]').val(description || '');

    if (is_system == 1) {
        document.getElementById('roleNameInput').setAttribute('readonly', true);
        document.getElementById('roleNameHint').classList.remove('d-none');
    } else {
        document.getElementById('roleNameInput').removeAttribute('readonly');
        document.getElementById('roleNameHint').classList.add('d-none');
    }
};

const openRoleFormDrawer = async function(id = 0) {

    const isEdit = id > 0;
    document.getElementById('addEditRoleDrawerTitle').textContent = isEdit ? 'Edit Role' : 'Add Role';

    const drawerEl = document.getElementById('addEditRole');
    const formEl   = document.getElementById('addEditRoleForm');

    cleanFormInputFeedback(formEl);
    formEl.reset();
    document.getElementById('roleId').value = '';
    document.getElementById('roleNameInput').removeAttribute('readonly');
    document.getElementById('roleNameHint').classList.add('d-none');

    try {
        const response = await api.get('/users/roles/form-context', { params: { id } });
        const { role_details } = response.data.data;

        if (isEdit) {
            populateRoleForm(role_details);
        }

        new bootstrap.Offcanvas(drawerEl).show();

    } catch (error) {
        handleApiError(error);
    }
};

document.getElementById('saveRoleBtn').addEventListener('click', async function() {

    const formEl = document.getElementById('addEditRoleForm');
    const id     = document.getElementById('roleId').value || '';

    cleanFormInputFeedback(formEl);

    const formData = new FormData(formEl);
    const payload  = formDataToObject(formData);

    try {
        let response;
        if (id) {
            response = await api.post(`/users/roles/${id}`, payload);
        } else {
            response = await api.post('/users/roles', payload);
        }

        notyf.success(response.data.message);

        if (typeof loadRoles === 'function') {
            loadRoles();
        }

        bootstrap.Offcanvas.getInstance(document.getElementById('addEditRole')).hide();
        formEl.reset();

    } catch (error) {
        handleApiError(error, formEl);
    }
});
</script>
@endpush
