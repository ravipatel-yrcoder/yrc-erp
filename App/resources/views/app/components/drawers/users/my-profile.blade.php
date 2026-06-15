<div class="offcanvas offcanvas-end" tabindex="-1" id="myProfileDrawer" aria-labelledby="myProfileDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width:420px;">
    <div class="offcanvas-header">
        <h5 id="myProfileDrawerTitle" class="offcanvas-title">My Profile</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">

        {{-- Personal Info --}}
        <form id="myProfileInfoForm">
            <p class="text-uppercase text-muted small fw-semibold mb-3">Personal Information</p>

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
                <input type="email" name="email" class="form-control" placeholder="you@example.com" />
            </div>

            <div class="mb-4">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" placeholder="+91 98765 43210" />
            </div>

            <div class="d-flex gap-2 mb-5">
                <button type="button" id="saveProfileInfoBtn" class="btn btn-primary btn-sm">Save Changes</button>
            </div>
        </form>

        <hr class="my-2">

        {{-- Change Password --}}
        <form id="myProfilePasswordForm">
            <p class="text-uppercase text-muted small fw-semibold mb-3 mt-4">Change Password</p>

            <div class="mb-4">
                <label class="form-label required">Current Password</label>
                <input type="password" name="current_password" class="form-control" placeholder="Enter current password" autocomplete="current-password" />
            </div>

            <div class="mb-4">
                <label class="form-label required">New Password</label>
                <input type="password" name="password" class="form-control" placeholder="Min. 8 characters" autocomplete="new-password" />
            </div>

            <div class="mb-4">
                <label class="form-label required">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" autocomplete="new-password" />
            </div>

            <div class="d-flex gap-2">
                <button type="button" id="saveProfilePasswordBtn" class="btn btn-primary btn-sm">Update Password</button>
            </div>
        </form>

    </div>
</div>

<script>
const openMyProfileDrawer = async function() {

    const drawerEl    = document.getElementById('myProfileDrawer');
    const infoFormEl  = document.getElementById('myProfileInfoForm');
    const passFormEl  = document.getElementById('myProfilePasswordForm');

    cleanFormInputFeedback(infoFormEl);
    cleanFormInputFeedback(passFormEl);
    infoFormEl.reset();
    passFormEl.reset();

    try {
        const response = await api.get('/users/me');
        const data = response.data.data || {};

        jQuery('#myProfileDrawer input[name="first_name"]').val(data.first_name || '');
        jQuery('#myProfileDrawer input[name="last_name"]').val(data.last_name || '');
        jQuery('#myProfileDrawer input[name="email"]').val(data.email || '');
        jQuery('#myProfileDrawer input[name="phone"]').val(data.phone || '');

        new bootstrap.Offcanvas(drawerEl).show();

    } catch (error) {
        handleApiError(error);
    }
};

document.getElementById('saveProfileInfoBtn').addEventListener('click', async function() {
    var btn = this;
    const formEl = document.getElementById('myProfileInfoForm');
    cleanFormInputFeedback(formEl);

    const payload = formDataToObject(new FormData(formEl));

    setButtonLoading(btn, true);
    try {
        const response = await api.post('/users/me', payload);
        notyf.success(response.data.message);
    } catch (error) {
        handleApiError(error, formEl);
    } finally {
        setButtonLoading(btn, false);
    }
});

document.getElementById('saveProfilePasswordBtn').addEventListener('click', async function() {
    var btn = this;
    const formEl = document.getElementById('myProfilePasswordForm');
    cleanFormInputFeedback(formEl);

    const payload = formDataToObject(new FormData(formEl));

    setButtonLoading(btn, true);
    try {
        const response = await api.post('/users/me/password', payload);
        notyf.success(response.data.message);
        formEl.reset();
    } catch (error) {
        handleApiError(error, formEl);
    } finally {
        setButtonLoading(btn, false);
    }
});
</script>
