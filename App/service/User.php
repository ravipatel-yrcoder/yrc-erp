<?php
class Service_User extends Service_PlatformBase
{
    public function getFormContext(int $companyId, int $userId = 0): array
    {
        $roles = $this->db->fetchAll(
            "SELECT id, name FROM company_roles WHERE company_id = ? AND status = 'active' ORDER BY name ASC",
            [$companyId]
        );

        $userDetails = [];
        if ($userId > 0) {
            $row = $this->db->fetchOne(
                "SELECT u.id, u.first_name, u.last_name, u.name, u.email, u.phone,
                        ur.role_id
                 FROM   users u
                 LEFT   JOIN user_roles ur ON ur.user_id = u.id AND ur.company_id = ?
                 WHERE  u.id = ? AND u.company_id = ?
                 LIMIT  1",
                [$companyId, $userId, $companyId]
            );
            $userDetails = $row ? (array) $row : [];
        }

        return [
            'roles'        => $roles,
            'user_details' => $userDetails,
        ];
    }


    public function invite(int $companyId, int $createdBy, array $data): array
    {
        $this->validateUser($data, true);

        if ($this->hasErrors()) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $subService = new Service_Subscription();
        $sub = $subService->getCurrent($companyId);
        $seats = $subService->getSeatCounts($companyId);
        if ($seats['available_seats'] <= 0) {
            $this->addError('No seats available. Please upgrade your subscription to add more users.', 'general');
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $email = strtolower(trim($data['email']));
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE company_id = ? AND email = ? LIMIT 1",
            [$companyId, $email]
        );
        if ($existing) {
            $this->addError(validationErrMsg('duplicate', 'Email'), 'email');
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        try {
            
            $this->db->startTransaction();

            $firstName = trim($data['first_name']);
            $lastName  = trim($data['last_name'] ?? '');
            $fullName  = $lastName !== '' ? "{$firstName} {$lastName}" : $firstName;

            $user = new Models_User();
            
            $user->company_id = $companyId;
            $user->first_name = $firstName;
            $user->last_name = $lastName ?: null;
            $user->name = $fullName;
            $user->email = $email;
            $user->phone = trim($data['phone'] ?? '') ?: null;
            $user->password = hashPassword($data['password']);
            $user->status = 'active';
            $user->email_verified_at = date('Y-m-d H:i:s');
            $user->created_by = $createdBy;

            $userId = $user->create();

            if ( !$userId ) {
                throw new Exception('Failed to create user: ' . implode(', ', $user->getErrors()));
            }

            echo "User created: $userId";

            $userRole = new Models_UserRole();
            $userRole->company_id = $companyId;
            $userRole->user_id    = $userId;
            $userRole->role_id    = (int) $data['role_id'];
            $userRole->created_by = $createdBy;

            if (!$userRole->create()) {
                throw new Exception('Failed to assign role');
            }

            // If the new user consumes a paid seat, record the event
            if ($sub && ($seats['used_seats'] + 1) > (int) $sub->free_users_included) {
                $seatsBefore = (int) $sub->purchased_extra_seats;
                $seatsAfter  = $seatsBefore + 1;

                $this->db->query(
                    "UPDATE company_subscriptions SET purchased_extra_seats = ? WHERE id = ?",
                    [$seatsAfter, $sub->id]
                );

                $seatEvent                  = new Models_SubscriptionSeatEvent();
                $seatEvent->company_id      = $companyId;
                $seatEvent->subscription_id = (int) $sub->id;
                $seatEvent->event_type      = 'add';
                $seatEvent->seats_before    = $seatsBefore;
                $seatEvent->seats_after     = $seatsAfter;
                $seatEvent->effective_at    = date('Y-m-d H:i:s');
                $seatEvent->period_start    = $sub->current_period_start;
                $seatEvent->period_end      = $sub->current_period_end;
                $seatEvent->prorated_amount = null;
                $seatEvent->triggered_by    = $createdBy;
                $seatEvent->create();
            }

            $this->db->commit();

            return ['success' => true, 'data' => ['user_id' => $userId]];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }


    public function update(int $companyId, int $currentUserId, int $targetId, array $data): array
    {
        $this->validateUser($data, false);

        if ($this->hasErrors()) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $existingUser = $this->db->fetchOne(
            "SELECT id FROM users WHERE id = ? AND company_id = ? LIMIT 1",
            [$targetId, $companyId]
        );
        if (!$existingUser) {
            throw new Service_Exception('User not found', 404);
        }

        $email = strtolower(trim($data['email']));
        $duplicate = $this->db->fetchOne(
            "SELECT id FROM users WHERE company_id = ? AND email = ? AND id != ? LIMIT 1",
            [$companyId, $email, $targetId]
        );
        if ($duplicate) {
            $this->addError(validationErrMsg('duplicate', 'Email'), 'email');
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $newRoleId   = (int) $data['role_id'];
        $currentRole = $this->db->fetchOne(
            "SELECT cr.is_super
             FROM   user_roles ur
             JOIN   company_roles cr ON cr.id = ur.role_id
             WHERE  ur.user_id = ? AND ur.company_id = ?
             LIMIT  1",
            [$targetId, $companyId]
        );

        if ($currentRole && (int) $currentRole->is_super === 1) {
            $newRole = $this->db->fetchOne(
                "SELECT is_super FROM company_roles WHERE id = ? AND company_id = ? LIMIT 1",
                [$newRoleId, $companyId]
            );
            if ($newRole && (int) $newRole->is_super === 0) {
                $otherAdminCount = (int) $this->db->fetchVar(
                    "SELECT COUNT(*)
                     FROM   user_roles ur
                     JOIN   company_roles cr ON cr.id = ur.role_id
                     JOIN   users u ON u.id = ur.user_id
                     WHERE  ur.company_id = ? AND cr.is_super = 1
                       AND  u.status = 'active' AND ur.user_id != ?",
                    [$companyId, $targetId]
                );
                if ($otherAdminCount === 0) {
                    $this->addError('Cannot remove the admin role from the last administrator.', 'role_id');
                    return ['success' => false, 'errors' => $this->getErrors()];
                }
            }
        }

        try {
            $this->db->startTransaction();

            $firstName = trim($data['first_name']);
            $lastName  = trim($data['last_name'] ?? '');
            $fullName  = $lastName !== '' ? "{$firstName} {$lastName}" : $firstName;

            $user           = new Models_User($targetId);
            $user->first_name = $firstName;
            $user->last_name  = $lastName ?: null;
            $user->name       = $fullName;
            $user->email      = $email;
            $user->phone      = trim($data['phone'] ?? '') ?: null;

            if (!empty($data['password'])) {
                $user->password = hashPassword($data['password']);
            }

            if (!$user->update()) {
                throw new Exception('Failed to update user: ' . implode(', ', $user->getErrors()));
            }

            $this->db->query(
                "DELETE FROM user_roles WHERE user_id = ? AND company_id = ?",
                [$targetId, $companyId]
            );

            $userRole = new Models_UserRole();
            $userRole->company_id = $companyId;
            $userRole->user_id    = $targetId;
            $userRole->role_id    = $newRoleId;
            $userRole->created_by = $currentUserId;

            if (!$userRole->create()) {
                throw new Exception('Failed to update role');
            }

            $this->db->commit();

            return ['success' => true, 'data' => []];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }


    public function toggleStatus(int $companyId, int $currentUserId, int $targetId): array
    {
        if ($targetId === $currentUserId) {
            throw new Service_Exception('You cannot deactivate your own account.', 422);
        }

        $user = $this->db->fetchOne(
            "SELECT id, status FROM users WHERE id = ? AND company_id = ? LIMIT 1",
            [$targetId, $companyId]
        );
        if (!$user) {
            throw new Service_Exception('User not found', 404);
        }

        $newStatus      = $user->status === 'active' ? 'inactive' : 'active';
        $isDeactivating = $newStatus === 'inactive';

        $subService = new Service_Subscription();
        $sub        = $subService->getCurrent($companyId);
        $seats      = $subService->getSeatCounts($companyId);

        if ($isDeactivating) {
            $isSuper = $this->db->fetchOne(
                "SELECT cr.is_super FROM user_roles ur
                 JOIN   company_roles cr ON cr.id = ur.role_id
                 WHERE  ur.user_id = ? AND ur.company_id = ? LIMIT 1",
                [$targetId, $companyId]
            );
            if ($isSuper && (int) $isSuper->is_super === 1) {
                $otherAdmins = (int) $this->db->fetchVar(
                    "SELECT COUNT(*) FROM user_roles ur
                     JOIN   company_roles cr ON cr.id = ur.role_id
                     JOIN   users u ON u.id = ur.user_id
                     WHERE  ur.company_id = ? AND cr.is_super = 1
                       AND  u.status = 'active' AND ur.user_id != ?",
                    [$companyId, $targetId]
                );
                if ($otherAdmins === 0) {
                    throw new Service_Exception('Cannot deactivate the last administrator.', 422);
                }
            }
        } else {
            if ($seats['available_seats'] <= 0) {
                throw new Service_Exception('No seats available. Please upgrade your subscription to add more users.', 422);
            }
        }

        try {
            $this->db->startTransaction();

            $userModel         = new Models_User($targetId);
            $userModel->status = $newStatus;
            $userModel->update();

            if ($isDeactivating) {
                if ($sub && (int) $sub->purchased_extra_seats > 0) {
                    $activeCount = (int) $this->db->fetchVar(
                        "SELECT COUNT(*) FROM users WHERE company_id = ? AND status = 'active'",
                        [$companyId]
                    );
                    // activeCount still includes the target user at this point (same transaction read)
                    if ($activeCount > (int) $sub->free_users_included) {
                        $seatsBefore = (int) $sub->purchased_extra_seats;
                        $seatsAfter  = max(0, $seatsBefore - 1);

                        $this->db->query(
                            "UPDATE company_subscriptions SET purchased_extra_seats = ? WHERE id = ?",
                            [$seatsAfter, $sub->id]
                        );

                        $seatEvent                  = new Models_SubscriptionSeatEvent();
                        $seatEvent->company_id      = $companyId;
                        $seatEvent->subscription_id = (int) $sub->id;
                        $seatEvent->event_type      = 'remove';
                        $seatEvent->seats_before    = $seatsBefore;
                        $seatEvent->seats_after     = $seatsAfter;
                        $seatEvent->effective_at    = null;
                        $seatEvent->period_start    = $sub->current_period_start;
                        $seatEvent->period_end      = $sub->current_period_end;
                        $seatEvent->prorated_amount = null;
                        $seatEvent->triggered_by    = $currentUserId;
                        $seatEvent->create();
                    }
                }
            } else {
                if ($sub && ($seats['used_seats'] + 1) > (int) $sub->free_users_included) {
                    $seatsBefore = (int) $sub->purchased_extra_seats;
                    $seatsAfter  = $seatsBefore + 1;

                    $this->db->query(
                        "UPDATE company_subscriptions SET purchased_extra_seats = ? WHERE id = ?",
                        [$seatsAfter, $sub->id]
                    );

                    $seatEvent                  = new Models_SubscriptionSeatEvent();
                    $seatEvent->company_id      = $companyId;
                    $seatEvent->subscription_id = (int) $sub->id;
                    $seatEvent->event_type      = 'add';
                    $seatEvent->seats_before    = $seatsBefore;
                    $seatEvent->seats_after     = $seatsAfter;
                    $seatEvent->effective_at    = date('Y-m-d H:i:s');
                    $seatEvent->period_start    = $sub->current_period_start;
                    $seatEvent->period_end      = $sub->current_period_end;
                    $seatEvent->prorated_amount = null;
                    $seatEvent->triggered_by    = $currentUserId;
                    $seatEvent->create();
                }
            }

            $this->db->commit();

            return ['success' => true, 'data' => ['status' => $newStatus]];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }


    // -------------------------------------------------------------------------
    // Role management
    // -------------------------------------------------------------------------

    public function getRoleFormContext(int $companyId, int $roleId = 0): array
    {
        $roleDetails = [];
        if ($roleId > 0) {
            $row = $this->db->fetchOne(
                "SELECT id, name, slug, description, is_system, is_super, status
                 FROM   company_roles
                 WHERE  id = ? AND company_id = ?
                 LIMIT  1",
                [$roleId, $companyId]
            );
            $roleDetails = $row ? (array) $row : [];
        }
        return ['role_details' => $roleDetails];
    }


    public function saveRole(int $companyId, int $createdBy, array $data): array
    {
        $roleId = (int) ($data['id'] ?? 0);
        $isNew  = $roleId === 0;

        $this->validateRole($data, $companyId, $roleId);
        if ($this->hasErrors()) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        if ($isNew) {
            $role              = new Models_CompanyRole();
            $role->company_id  = $companyId;
            $role->name        = trim($data['name']);
            $role->slug        = $this->makeSlug(trim($data['name']));
            $role->description = trim($data['description'] ?? '') ?: null;
            $role->is_system   = 0;
            $role->is_super    = 0;
            $role->status      = 'active';
            $role->created_by  = $createdBy;
            $role->updated_by  = $createdBy;

            if (!$role->create()) {
                throw new Exception('Failed to create role');
            }

            return ['success' => true, 'data' => ['role_id' => (int) $role->id]];
        }

        $existing = $this->db->fetchOne(
            "SELECT id, is_system FROM company_roles WHERE id = ? AND company_id = ? LIMIT 1",
            [$roleId, $companyId]
        );
        if (!$existing) {
            throw new Service_Exception('Role not found', 404);
        }

        $role              = new Models_CompanyRole($roleId);
        $role->description = trim($data['description'] ?? '') ?: null;
        $role->updated_by  = $createdBy;

        if (!(int) $existing->is_system) {
            $role->name = trim($data['name']);
            $role->slug = $this->makeSlug(trim($data['name']));
        }

        if (!$role->update()) {
            throw new Exception('Failed to update role');
        }

        return ['success' => true, 'data' => []];
    }


    // -------------------------------------------------------------------------
    // My profile (logged-in user)
    // -------------------------------------------------------------------------

    public function getMyProfile(int $companyId, int $userId): array
    {
        $row = $this->db->fetchOne(
            "SELECT id, first_name, last_name, name, email, phone
             FROM   users
             WHERE  id = ? AND company_id = ?
             LIMIT  1",
            [$userId, $companyId]
        );
        return $row ? (array) $row : [];
    }


    public function updateMyProfile(int $companyId, int $userId, array $data): array
    {
        if (empty(trim($data['first_name'] ?? ''))) {
            $this->addError(validationErrMsg('required', 'First name'), 'first_name');
        }

        $email = trim($data['email'] ?? '');
        if ($email === '') {
            $this->addError(validationErrMsg('required', 'Email'), 'email');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError(validationErrMsg('invalid', 'Email'), 'email');
        }

        if (!$this->hasErrors()) {
            $dup = $this->db->fetchOne(
                "SELECT id FROM users WHERE company_id = ? AND email = ? AND id != ? LIMIT 1",
                [$companyId, strtolower($email), $userId]
            );
            if ($dup) {
                $this->addError(validationErrMsg('duplicate', 'Email'), 'email');
            }
        }

        if ($this->hasErrors()) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $firstName = trim($data['first_name']);
        $lastName  = trim($data['last_name'] ?? '');
        $fullName  = $lastName !== '' ? "{$firstName} {$lastName}" : $firstName;

        $user             = new Models_User($userId);
        $user->first_name = $firstName;
        $user->last_name  = $lastName ?: null;
        $user->name       = $fullName;
        $user->email      = strtolower($email);
        $user->phone      = trim($data['phone'] ?? '') ?: null;

        if (!$user->update()) {
            throw new Exception('Failed to update profile: ' . implode(', ', $user->getErrors()));
        }

        return ['success' => true, 'data' => []];
    }


    public function changeMyPassword(int $companyId, int $userId, array $data): array
    {
        $currentPassword = $data['current_password'] ?? '';
        $newPassword     = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        if ($currentPassword === '') {
            $this->addError(validationErrMsg('required', 'Current password'), 'current_password');
        }
        if ($newPassword === '') {
            $this->addError(validationErrMsg('required', 'New password'), 'password');
        } elseif (strlen($newPassword) < 8) {
            $this->addError(validationErrMsg('password_too_short', ''), 'password');
        }
        if ($newPassword !== $confirmPassword) {
            $this->addError(validationErrMsg('password_mismatch', ''), 'confirm_password');
        }

        if ($this->hasErrors()) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $row = $this->db->fetchOne(
            "SELECT password FROM users WHERE id = ? AND company_id = ? LIMIT 1",
            [$userId, $companyId]
        );
        if (!$row || !verifyPassword($currentPassword, $row->password)) {
            $this->addError('Current password is incorrect.', 'current_password');
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $user           = new Models_User($userId);
        $user->password = hashPassword($newPassword);

        if (!$user->update()) {
            throw new Exception('Failed to update password');
        }

        return ['success' => true, 'data' => []];
    }


    // -------------------------------------------------------------------------
    // Role permissions
    // -------------------------------------------------------------------------

    public function getRolePermissions(int $companyId, int $roleId): array
    {
        $sql = "SELECT id, name, is_super, is_system
                FROM company_roles
                WHERE id = ? AND company_id = ?
                LIMIT 1";

        $role = $this->db->fetchOne($sql, [$roleId, $companyId]);

        if (!$role) {
            throw new Service_Exception('Role not found', 404);
        }

        // Features accessible to this company's subscription, grouped by owning module
        $sql = "SELECT DISTINCT 
                    m.id AS module_id,
                    m.key AS module_key,
                    m.name AS module_name,
                    m.sort_order AS module_sort_order,
                    f.id AS feature_id,
                    f.key AS feature_key,
                    f.name AS feature_name                 
                FROM company_subscriptions cs
                JOIN company_subscription_modules csm ON csm.subscription_id = cs.id
                JOIN module_feature_map mfi ON mfi.module_id = csm.module_id
                JOIN features f ON f.id = mfi.feature_id AND f.is_active = 1
                JOIN modules m ON m.id = f.module_id AND m.is_active = 1
                WHERE cs.company_id = ? AND cs.is_current = 1
                ORDER BY m.sort_order ASC";
        $rows = $this->db->fetchAll($sql, [$companyId]);

        $modules = [];
        foreach ($rows as $row) {
            $mk = (int) $row->module_id;
            if (!isset($modules[$mk])) {
                $modules[$mk] = [
                    'id'       => $mk,
                    'key'      => $row->module_key,
                    'name'     => $row->module_name,
                    'features' => [],
                ];
            }
            $modules[$mk]['features'][] = [
                'id'         => (int) $row->feature_id,
                'key'        => $row->feature_key,
                'name'       => $row->feature_name,                
            ];
        }

        // Current grants for this role
        $grantRows = $this->db->fetchAll(
            "SELECT access_type, access_id
             FROM   role_access_grants
             WHERE  role_id = ? AND company_id = ?",
            [$roleId, $companyId]
        );

        $grants = ['modules' => [], 'features' => []];
        foreach ($grantRows as $g) {
            if ($g->access_type === 'module') {
                $grants['modules'][] = (int) $g->access_id;
            } else {
                $grants['features'][] = (int) $g->access_id;
            }
        }

        return [
            'role'    => (array) $role,
            'modules' => array_values($modules),
            'grants'  => $grants,
        ];
    }


    public function saveRolePermissions(int $companyId, int $userId, int $roleId, array $grants): void
    {
        $role = $this->db->fetchOne(
            "SELECT id FROM company_roles WHERE id = ? AND company_id = ? LIMIT 1",
            [$roleId, $companyId]
        );
        if (!$role) {
            throw new Service_Exception('Role not found', 404);
        }

        $this->db->startTransaction();
        try {
            $this->db->query(
                "DELETE FROM role_access_grants WHERE role_id = ? AND company_id = ?",
                [$roleId, $companyId]
            );

            $seen = [];
            foreach ($grants as $g) {
                $type = $g['type'] ?? '';
                $id   = (int) ($g['id'] ?? 0);

                if (!$id || !in_array($type, ['module', 'feature'], true)) continue;

                $key = $type . '_' . $id;
                if (isset($seen[$key])) continue;
                $seen[$key] = true;

                $grant              = new Models_RoleAccessGrant();
                $grant->company_id  = $companyId;
                $grant->role_id     = $roleId;
                $grant->access_type = $type;
                $grant->access_id   = $id;
                $grant->created_by  = $userId;
                $grant->create();
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }


    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function makeSlug(string $name): string
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name));
        return trim($slug, '_');
    }


    private function validateRole(array $data, int $companyId, int $roleId = 0): void
    {
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            $this->addError(validationErrMsg('required', 'Role name'), 'name');
            return;
        }

        $sql      = "SELECT id FROM company_roles WHERE company_id = ? AND name = ?";
        $bindings = [$companyId, $name];
        if ($roleId > 0) {
            $sql      .= " AND id != ?";
            $bindings[] = $roleId;
        }
        $sql .= " LIMIT 1";

        if ($this->db->fetchOne($sql, $bindings)) {
            $this->addError(validationErrMsg('duplicate', 'Role name'), 'name');
        }
    }


    private function validateUser(array $data, bool $isNew): void
    {
        if (empty(trim($data['first_name'] ?? ''))) {
            $this->addError(validationErrMsg('required', 'First name'), 'first_name');
        }

        $email = trim($data['email'] ?? '');
        if ($email === '') {
            $this->addError(validationErrMsg('required', 'Email'), 'email');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError(validationErrMsg('invalid', 'Email'), 'email');
        }

        if (empty($data['role_id'])) {
            $this->addError(validationErrMsg('required', 'Role'), 'role_id');
        }

        $password = $data['password'] ?? '';
        if ($isNew && $password === '') {
            $this->addError(validationErrMsg('required', 'Password'), 'password');
        } elseif ($password !== '' && strlen($password) < 8) {
            $this->addError(validationErrMsg('password_too_short', ''), 'password');
        }
    }
}
?>
