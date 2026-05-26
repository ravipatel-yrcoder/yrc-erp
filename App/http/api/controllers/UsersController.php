<?php
class Api_UsersController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function serviceUser(): Service_User {
        return new Service_User();
    }


    // GET/POST /api/users
    public function indexAction(TinyPHP_Request $request)
    {
        if ($request->isMethod('get')) {
            return $this->handleList($request);
        } elseif ($request->isMethod('post')) {
            return $this->handleSave($request);
        }        
    }


    // GET /api/users/form-context
    public function formContextAction(TinyPHP_Request $request)
    {
        $tenantContext = tenantContext();
        $targetId = $request->getInput('id', 'int', 0);

        $service = new Service_User();
        $data = $service->getFormContext($tenantContext->companyId, $targetId);

        return response($data)->sendJson();
    }


    // POST /api/users/:id/status — toggle active/inactive
    public function statusAction(TinyPHP_Request $request)
    {
        $tenantContext = tenantContext();

        $targetId = $request->getInput('id');
        
        $service = new Service_User();
        $result = $service->toggleStatus($tenantContext->companyId, $tenantContext->userId, $targetId);

        $label = $result['data']['status'] === 'active' ? 'activated' : 'deactivated';
        
        return response($result['data'], "User {$label} successfully.")->sendJson();
    }


    // POST /api/users/:id
    public function entityAction(TinyPHP_Request $request)
    {
        $tenantContext = tenantContext();

        $targetId = $request->getInput('id');
        
        $service = new Service_User();
        $result = $service->update($tenantContext->companyId, $tenantContext->userId, $targetId, $request->getInputs());

        if ($result['success']) {
            return response([], 'User updated successfully.')->sendJson();
        }

        return response([], 'Failed to update user', 422)->errors($result['errors'])->sendJson();        
    }


    // GET /api/users/me — my profile
    // POST /api/users/me — update personal info
    public function meAction(TinyPHP_Request $request)
    {
        if ($request->isMethod('get')) {
            return $this->handleMeGet();
        } elseif ($request->isMethod('post')) {
            return $this->handleMePut($request);
        }        
    }


    // PUT /api/users/me/password
    public function mePasswordAction(TinyPHP_Request $request)
    {
        $tenantContext = tenantContext();

        $service = new Service_User();
        $result = $service->changeMyPassword($tenantContext->companyId, $tenantContext->userId, $request->getInputs());

        if ($result['success']) {
            return response([], 'Password updated successfully.')->sendJson();
        }

        return response([], 'Failed to update password', 422)->errors($result['errors'])->sendJson();        
    }


    // GET/POST /api/users/roles — list + create
    public function rolesAction(TinyPHP_Request $request)
    {
        if ($request->isMethod('get')) {
            return $this->handleRolesList($request);
        } elseif ($request->isMethod('post')) {
            return $this->handleRolesSave($request);
        }        
    }


    // GET /api/users/roles/form-context
    public function rolesFormContextAction(TinyPHP_Request $request)
    {
        $companyId = tenantContext()->companyId;
        $roleId = $request->getInput('id', 'int', 0);
        
        $service = new Service_User();
        $data = $service->getRoleFormContext($companyId, $roleId);

        return response($data)->sendJson();
    }


    // GET  /api/users/roles/:id/permissions — load permissions for a role
    // POST /api/users/roles/:id/permissions — save permissions for a role
    public function rolesPermissionsAction(TinyPHP_Request $request)
    {
        $roleId = $request->getInput('id', 'int', 0);

        $tenantContext = tenantContext();

        $companyId = $tenantContext->companyId;
        $userId = $tenantContext->userId;

        if ($request->isMethod('get')) {

            $service = $this->serviceUser();                        
            $data = $service->getRolePermissions($companyId, $roleId);

            return response($data)->sendJson();

        } elseif ($request->isMethod('post')) {

            $grants           = $request->getInput('grants') ?? [];
            $activatedModules = $request->getInput('activated_modules') ?? [];
            if (!is_array($grants))           $grants = [];
            if (!is_array($activatedModules)) $activatedModules = [];

            $service = $this->serviceUser();
            $service->saveRolePermissions($companyId, $userId, $roleId, $grants, $activatedModules);

            return response([], 'Permissions saved successfully.')->sendJson();
        }                
    }


    // POST   /api/users/roles/:id — update role
    // DELETE /api/users/roles/:id — delete role
    public function rolesEntityAction(TinyPHP_Request $request)
    {
        $tenantContext = tenantContext();
        $roleId    = $request->getInput('id', 'int', 0);
        $companyId = $tenantContext->companyId;
        $userId    = $tenantContext->userId;
        $service   = new Service_User();

        if ($request->isMethod('delete')) {
            $service->deleteRole($companyId, $roleId);
            return response([], 'Role deleted successfully.')->sendJson();
        }

        $inputs = array_merge($request->getInputs(), ['id' => $roleId]);
        $result = $service->saveRole($companyId, $userId, $inputs);

        if (!$result['success']) {
            return response([], 'Failed to update role', 422)->errors($result['errors'])->sendJson();
        }

        return response([], 'Role updated successfully.')->sendJson();
    }


    // POST /api/users/roles/:id/status — toggle role active/inactive
    public function rolesStatusAction(TinyPHP_Request $request)
    {
        $companyId = tenantContext()->companyId;
        $roleId    = $request->getInput('id', 'int', 0);
        $service   = new Service_User();
        $data      = $service->toggleRoleStatus($companyId, $roleId);

        return response($data, 'Role status updated.')->sendJson();
    }


    private function handleRolesList(TinyPHP_Request $request)
    {
        $companyId = tenantContext()->companyId;

        $columns = [
            'id'                => 'cr.id',
            'name'              => 'cr.name',
            'slug'              => 'cr.slug',
            'description'       => 'cr.description',
            'is_admin'          => 'cr.is_admin',
            'status'            => 'cr.status',
            'created_at'        => 'cr.created_at',
            'user_count'        => 'COUNT(DISTINCT u.id)',
            'activated_modules' => '(SELECT GROUP_CONCAT(m.name ORDER BY m.sort_order SEPARATOR \',\')
                                     FROM role_module_activations rma
                                     JOIN modules m ON m.id = rma.module_id AND m.is_active = 1
                                     WHERE rma.role_id = cr.id)',
        ];

        $dataFetch = new TinyPHP_DataFetch($request);
        $results = $dataFetch
            ->table('company_roles AS cr')
            ->joins("LEFT JOIN user_roles AS ur ON ur.role_id = cr.id AND ur.company_id = cr.company_id LEFT JOIN users AS u ON u.id = ur.user_id AND u.status = 'active'")
            ->columns($columns)
            ->where('cr.company_id = ?', [$companyId])
            ->groupBy('cr.id')
            ->orderBy('cr.name ASC')
            ->fetch();

        return response($results)->sendJson();
    }


    private function handleRolesSave(TinyPHP_Request $request)
    {
        $tenantContext = tenantContext();

        $companyId = $tenantContext->companyId;
        $userId = $tenantContext->userId;
        
        $service = new Service_User();
        $result = $service->saveRole($companyId, $userId, $request->getInputs());

        if (!$result['success']) {
            return response([], 'Failed to create role', 422)->errors($result['errors'])->sendJson();
        }

        return response($result['data'], 'Role created successfully.', 201)->sendJson();
    }


    private function handleMeGet()
    {
        $tenantContext = tenantContext();

        $companyId = $tenantContext->companyId;
        $userId = $tenantContext->userId;

        $service = new Service_User();
        $data = $service->getMyProfile($companyId, $userId);

        return response($data)->sendJson();
    }


    private function handleMePut(TinyPHP_Request $request)
    {
        $tenantContext = tenantContext();

        $companyId = $tenantContext->companyId;
        $userId = $tenantContext->userId;

        $service = new Service_User();
        $result = $service->updateMyProfile($companyId, $userId, $request->getInputs());

        if (!$result['success']) {
            return response([], 'Failed to update profile', 422)->errors($result['errors'])->sendJson();
        }

        return response([], 'Profile updated successfully.')->sendJson();
    }


    private function handleList(TinyPHP_Request $request)
    {
        $companyId = tenantContext()->companyId;

        $columns = [
            'id'              => 'u.id',
            'name'            => 'u.name',
            'first_name'      => 'u.first_name',
            'last_name'       => 'u.last_name',
            'email'           => 'u.email',
            'phone'           => 'u.phone',
            'role_id'         => 'ur.role_id',
            'role_name'       => 'cr.name',
            'status'          => 'u.status',
            'is_company'      => 'u.is_company',
            'created_at'      => 'u.created_at',
            'created_by_name' => 'cb.name',
        ];

        $dataFetch = new TinyPHP_DataFetch($request);
        $fetch = $dataFetch
            ->table('users AS u')
            ->joins(
                'LEFT JOIN user_roles AS ur ON ur.user_id = u.id AND ur.company_id = ' . (int) $companyId .
                ' LEFT JOIN company_roles AS cr ON cr.id = ur.role_id' .
                ' LEFT JOIN users AS cb ON cb.id = u.created_by'
            )
            ->columns($columns)
            ->where('u.company_id = ?', [$companyId]);

        $filterRoleId = $request->getInput('filter_role', 'Int', 0);
        $filterStatus = $request->getInput('filter_status', 'String', '');

        if ($filterRoleId > 0) {
            $fetch->where('ur.role_id = ?', [$filterRoleId]);
        }
        if (in_array($filterStatus, ['active', 'inactive'], true)) {
            $fetch->where('u.status = ?', [$filterStatus]);
        }

        return response($fetch->fetch())->sendJson();
    }


    private function handleSave(TinyPHP_Request $request)
    {
        $tenantContext = tenantContext();

        $companyId = $tenantContext->companyId;
        $userId = $tenantContext->userId;
        
        $service = new Service_User();
        $result = $service->invite($companyId, $userId, $request->getInputs());

        if (!$result['success']) {
            return response([], 'Failed to add user', 422)->errors($result['errors'])->sendJson();
        }

        return response($result['data'], 'User added successfully.', 201)->sendJson();
    }
}
?>
