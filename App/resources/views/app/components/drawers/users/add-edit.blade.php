<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditUser" aria-labelledby="addEditUserDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="offcanvas-header">
        <h5 id="addEditUserDrawerTitle" class="offcanvas-title">Add User</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="addEditUserForm">
            <div class="form-glob-feedback"></div>
            <input type="hidden" id="userId" value="" />

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label required">First Name</label>
                    <input type="text" name="first_name" class="form-control" placeholder="First name" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" placeholder="Last name" />
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label required">Email</label>
                <input type="email" name="email" class="form-control" placeholder="user@example.com" />
            </div>

            <div class="mb-4">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" placeholder="+91 98765 43210" />
            </div>

            <div class="mb-4">
                <label class="form-label required">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Min. 8 characters" autocomplete="new-password" />
                <div class="form-text text-muted" id="passwordHint"></div>
            </div>

            <div class="mb-4">
                <label class="form-label required">Role</label>
                <select name="role_id" id="userRoleSelect" class="form-select" placeholder="Select role"></select>
            </div>

            <div class="mb-4">
                <label class="form-label">Teams</label>
                <select name="team_ids[]" id="userTeamsSelect" class="form-select" placeholder="Select teams" multiple></select>
                <div class="form-text text-muted">Optional. Assign this user to one or more teams.</div>
            </div>

        </form>
    </div>
    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveUserBtn" class="btn btn-primary btn-sm min-w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const populateUserForm = function(details) {
    if (!details || Object.keys(details).length === 0) return;

    const { id, first_name, last_name, email, phone, role_id, is_company, team_ids } = details;

    document.getElementById('userId').value = id || '';
    jQuery('#addEditUser input[name="first_name"]').val(first_name || '');
    jQuery('#addEditUser input[name="last_name"]').val(last_name || '');
    jQuery('#addEditUser input[name="email"]').val(email || '');
    jQuery('#addEditUser input[name="phone"]').val(phone || '');
    jQuery('#addEditUser input[name="password"]').val('');

    const $roleSelect = jQuery('#userRoleSelect');
    $roleSelect.val(role_id || null).trigger('change');

    // Lock role dropdown for the company owner
    if (parseInt(is_company) === 1) {
        $roleSelect.prop('disabled', true).trigger('change');
    }

    jQuery('#userTeamsSelect').val((team_ids || []).map(String)).trigger('change');
};

const openUserFormDrawer = async function(id = 0) {

    const isEdit = id > 0;
    document.getElementById('addEditUserDrawerTitle').textContent = isEdit ? 'Edit User' : 'Add User';
    document.getElementById('passwordHint').textContent = isEdit ? 'Leave blank to keep the current password.' : '';

    const drawerEl = document.getElementById('addEditUser');
    const formEl   = document.getElementById('addEditUserForm');

    cleanFormInputFeedback(formEl);
    formEl.reset();
    document.getElementById('userId').value = '';
    jQuery('#userRoleSelect').prop('disabled', false);

    try {
        const [userCtxRes, teamsRes] = await Promise.all([
            api.get('/users/form-context', { params: { id } }),
            api.get('/company/teams'),
        ]);

        const { roles, user_details } = userCtxRes.data.data;
        const teams = teamsRes.data.data || [];

        initSelect2('#userRoleSelect', {
            dropdownParent: drawerEl,
            placeholder: 'Select role',
            allowClear: false,
            data: buildSelect2Options(roles),
        });

        initSelect2('#userTeamsSelect', {
            dropdownParent: drawerEl,
            placeholder: 'Select teams',
            data: buildSelect2Options(teams),
        });

        if (isEdit && user_details && user_details.id) {
            populateUserForm(user_details);
        }

        new bootstrap.Offcanvas(drawerEl).show();

    } catch (error) {
        handleApiError(error);
    }
};

document.getElementById('saveUserBtn').addEventListener('click', async function() {
    var btn = this;

    const formEl = document.getElementById('addEditUserForm');
    const id = document.getElementById('userId').value || '';

    cleanFormInputFeedback(formEl);

    const formData = new FormData(formEl);
    const payload  = formDataToObject(formData);

    // Read directly from elements to handle disabled state (company owner role) and multi-select
    payload.role_id  = document.getElementById('userRoleSelect').value;
    payload.team_ids = jQuery('#userTeamsSelect').val() || [];

    setButtonLoading(btn, true);
    try {

        const url = id ? `/users/${id}` : `/users`;
        const response = await api.post(url, payload);

        notyf.success(response.data.message);

        if (typeof usersDt !== 'undefined') {
            usersDt.ajax.reload();
        }

        bootstrap.Offcanvas.getInstance(document.getElementById('addEditUser')).hide();
        formEl.reset();

    } catch (error) {
        handleApiError(error, formEl);
    } finally {
        setButtonLoading(btn, false);
    }
});
</script>
@endpush
