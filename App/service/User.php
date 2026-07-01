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
                        u.is_company, ur.role_id
                 FROM   users u
                 LEFT   JOIN user_roles ur ON ur.user_id = u.id AND ur.company_id = ?
                 WHERE  u.id = ? AND u.company_id = ?
                 LIMIT  1",
                [$companyId, $userId, $companyId]
            );
            if ($row) {
                $userDetails = (array) $row;
                $teamRows = $this->db->fetchAll(
                    "SELECT tm.team_id FROM team_members tm
                     JOIN teams t ON t.id = tm.team_id AND t.company_id = ?
                     WHERE tm.user_id = ?",
                    [$companyId, $userId]
                );
                $userDetails['team_ids'] = array_column(array_map('get_object_vars', $teamRows), 'team_id');
            }
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

            $userRole = new Models_UserRole();
            $userRole->company_id = $companyId;
            $userRole->user_id    = $userId;
            $userRole->role_id    = (int) $data['role_id'];
            $userRole->created_by = $createdBy;

            if (!$userRole->create()) {
                throw new Exception('Failed to assign role');
            }

            // Sync team memberships
            $teamIds = $this->resolveValidTeamIds($data['team_ids'] ?? [], $companyId);
            foreach ($teamIds as $teamId) {
                $member             = new Models_TeamMember();
                $member->team_id    = $teamId;
                $member->user_id    = $userId;
                $member->created_by = $createdBy;
                $member->create();
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

        $targetUser = $this->db->fetchOne(
            "SELECT is_company FROM users WHERE id = ? AND company_id = ? LIMIT 1",
            [$targetId, $companyId]
        );

        $newRoleId   = (int) $data['role_id'];

        // Company owner's role assignment is locked — block role changes only
        if ($targetUser && (int) $targetUser->is_company === 1) {
            $currentRoleRow = $this->db->fetchOne(
                "SELECT role_id FROM user_roles WHERE user_id = ? AND company_id = ? LIMIT 1",
                [$targetId, $companyId]
            );
            if ($currentRoleRow && (int) $currentRoleRow->role_id !== $newRoleId) {
                throw new Service_Exception('The company owner role assignment cannot be changed.', 403);
            }
        }

        $currentRole = $this->db->fetchOne(
            "SELECT cr.is_admin AS is_admin
             FROM   user_roles ur
             JOIN   company_roles cr ON cr.id = ur.role_id
             WHERE  ur.user_id = ? AND ur.company_id = ?
             LIMIT  1",
            [$targetId, $companyId]
        );

        if ($currentRole && (int) $currentRole->is_admin === 1) {
            $newRole = $this->db->fetchOne(
                "SELECT is_admin FROM company_roles WHERE id = ? AND company_id = ? LIMIT 1",
                [$newRoleId, $companyId]
            );
            if ($newRole && (int) $newRole->is_admin === 0) {
                $otherAdminCount = (int) $this->db->fetchVar(
                    "SELECT COUNT(*)
                     FROM   user_roles ur
                     JOIN   company_roles cr ON cr.id = ur.role_id
                     JOIN   users u ON u.id = ur.user_id
                     WHERE  ur.company_id = ? AND cr.is_admin = 1
                       AND  u.status = 'active' AND ur.user_id != ?",
                    [$companyId, $targetId]
                );
                if ($otherAdminCount === 0) {
                    $this->addError('Cannot remove the last administrator from the Admin role.', 'role_id');
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

            // Sync team memberships
            $this->db->query("DELETE FROM team_members WHERE user_id = ?", [$targetId]);
            $teamIds = $this->resolveValidTeamIds($data['team_ids'] ?? [], $companyId);
            foreach ($teamIds as $teamId) {
                $member             = new Models_TeamMember();
                $member->team_id    = $teamId;
                $member->user_id    = $targetId;
                $member->created_by = $currentUserId;
                $member->create();
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
            "SELECT id, status, is_company FROM users WHERE id = ? AND company_id = ? LIMIT 1",
            [$targetId, $companyId]
        );
        if (!$user) {
            throw new Service_Exception('User not found', 404);
        }

        if ((int) $user->is_company === 1) {
            throw new Service_Exception('The company owner account cannot be deactivated.', 422);
        }

        $newStatus      = $user->status === 'active' ? 'inactive' : 'active';
        $isDeactivating = $newStatus === 'inactive';

        $subService = new Service_Subscription();
        $sub        = $subService->getCurrent($companyId);
        $seats      = $subService->getSeatCounts($companyId);

        if ($isDeactivating) {
            $isAdmin = $this->db->fetchOne(
                "SELECT cr.is_admin AS is_admin FROM user_roles ur
                 JOIN   company_roles cr ON cr.id = ur.role_id
                 WHERE  ur.user_id = ? AND ur.company_id = ? LIMIT 1",
                [$targetId, $companyId]
            );
            if ($isAdmin && (int) $isAdmin->is_admin === 1) {
                $otherAdmins = (int) $this->db->fetchVar(
                    "SELECT COUNT(*) FROM user_roles ur
                     JOIN   company_roles cr ON cr.id = ur.role_id
                     JOIN   users u ON u.id = ur.user_id
                     WHERE  ur.company_id = ? AND cr.is_admin = 1
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
                "SELECT id, name, slug, description, is_admin, status
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
            $role->is_admin    = 0;
            $role->status      = 'active';
            $role->created_by  = $createdBy;
            $role->updated_by  = $createdBy;

            if (!$role->create()) {
                throw new Exception('Failed to create role');
            }

            return ['success' => true, 'data' => ['role_id' => (int) $role->id]];
        }

        $existing = $this->db->fetchOne(
            "SELECT id, is_admin FROM company_roles WHERE id = ? AND company_id = ? LIMIT 1",
            [$roleId, $companyId]
        );
        if (!$existing) {
            throw new Service_Exception('Role not found', 404);
        }

        if ((int) $existing->is_admin === 1) {
            throw new Service_Exception('The Admin role cannot be modified.', 403);
        }

        $role              = new Models_CompanyRole($roleId);
        $role->name        = trim($data['name']);
        $role->slug        = $this->makeSlug(trim($data['name']));
        $role->description = trim($data['description'] ?? '') ?: null;
        $role->updated_by  = $createdBy;

        if (!$role->update()) {
            throw new Exception('Failed to update role');
        }

        return ['success' => true, 'data' => []];
    }


    public function toggleRoleStatus(int $companyId, int $roleId): array
    {
        $role = $this->db->fetchOne("SELECT id, status, is_admin FROM company_roles WHERE id = ? AND company_id = ? LIMIT 1", [$roleId, $companyId]);

        if (!$role) {
            throw new Service_Exception('Role not found', 404);
        }

        if ((int) $role->is_admin === 1) {
            throw new Service_Exception('The Admin role cannot be deactivated.', 403);
        }

        $isDeactivating = $role->status === 'active';

        if ($isDeactivating) {

            $activeUserCount = (int) $this->db->fetchVar(
                "SELECT COUNT(*) FROM user_roles ur
                 JOIN users u ON u.id = ur.user_id AND u.status = 'active'
                 WHERE ur.role_id = ?",
                [$roleId]
            );

            if ($activeUserCount > 0) {                
                throw new Service_Exception("Cannot deactivate this role — {$activeUserCount} active user(s) are assigned to it. Reassign them first.", 422);
            }
        }

        $newStatus = $isDeactivating ? 'inactive' : 'active';
        $this->db->query("UPDATE company_roles SET status = ?, updated_at = ? WHERE id = ? AND company_id = ?", [$newStatus, date('Y-m-d H:i:s'), $roleId, $companyId]);

        return ['status' => $newStatus];
    }


    public function deleteRole(int $companyId, int $roleId): void
    {
        $role = $this->db->fetchOne(
            "SELECT id, is_admin FROM company_roles WHERE id = ? AND company_id = ? LIMIT 1",
            [$roleId, $companyId]
        );
        if (!$role) {
            throw new Service_Exception('Role not found', 404);
        }

        if ((int) $role->is_admin === 1) {
            throw new Service_Exception('The Admin role cannot be deleted.', 403);
        }

        $activeUserCount = (int) $this->db->fetchVar(
            "SELECT COUNT(*) FROM user_roles ur
             JOIN users u ON u.id = ur.user_id AND u.status = 'active'
             WHERE ur.role_id = ?",
            [$roleId]
        );
        if ($activeUserCount > 0) {
            throw new Service_Exception(
                "Cannot delete this role — {$activeUserCount} active user(s) are assigned to it. Reassign them first.",
                422
            );
        }

        $this->db->startTransaction();
        try {
            $this->db->query("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);
            $this->db->query("DELETE FROM role_module_activations WHERE role_id = ?", [$roleId]);
            $this->db->query("DELETE FROM user_roles WHERE role_id = ?", [$roleId]);
            $this->db->query("DELETE FROM company_roles WHERE id = ? AND company_id = ?", [$roleId, $companyId]);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
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
        $role = $this->db->fetchOne(
            "SELECT id, name, is_admin FROM company_roles WHERE id = ? AND company_id = ? LIMIT 1",
            [$roleId, $companyId]
        );
        if (!$role) {
            throw new Service_Exception('Role not found', 404);
        }

        // Admin role has full access — no permission grid needed
        if ((int) $role->is_admin === 1) {
            return [
                'role'            => (array) $role,
                'is_admin_role'   => true,
                'modules'         => [],
                'features'        => [],
                'shared_features' => [],
                'admin_features'  => [],
            ];
        }

        $isElevated = false;

        // 1. All modules for this company's subscription (includes system modules via CSM)
        $subModuleRows = $this->db->fetchAll(
            "SELECT m.id, m.key, m.name, m.sort_order, m.is_system
             FROM company_subscriptions cs
             JOIN company_subscription_modules csm ON csm.subscription_id = cs.id
             JOIN modules m ON m.id = csm.module_id AND m.is_active = 1
             WHERE cs.company_id = ? AND cs.is_current = 1
             ORDER BY m.is_system DESC, m.sort_order ASC",
            [$companyId]
        );
        $subscriptionModuleKeys = array_column(array_map('get_object_vars', $subModuleRows), 'key');

        // 2. Modules this role has activated (intersected with subscription for downgrade safety)
        $activatedRows = $this->db->fetchAll(
            "SELECT m.key FROM role_module_activations rma
             JOIN modules m ON m.id = rma.module_id
             WHERE rma.role_id = ?",
            [$roleId]
        );
        $activatedModuleKeys = array_values(array_intersect(
            array_column(array_map('get_object_vars', $activatedRows), 'key'),
            $subscriptionModuleKeys
        ));

        // 3. All module-specific features accessible via subscription, with cross-module mapping.
        //    Returns one row per (feature × mapped_module) combination — deduplicated per feature
        //    in PHP to build shared_modules[] and display_names{} arrays.
        $mappingRows = $this->db->fetchAll(
            "SELECT DISTINCT
                f.id AS feature_id, f.key AS feature_key, f.name AS feature_name,
                f.is_scopeable, f.feature_category, f.sort_order AS feature_sort,
                pm.key AS primary_module_key, pm.sort_order AS primary_module_sort,
                mm.key AS mapped_module_key,
                COALESCE(mfi.display_name, f.name) AS mapped_display_name
             FROM company_subscriptions cs
             JOIN company_subscription_modules csm ON csm.subscription_id = cs.id
             JOIN modules sm ON sm.id = csm.module_id AND sm.is_active = 1
             JOIN module_feature_map mfi ON mfi.module_id = sm.id
             JOIN features f ON f.id = mfi.feature_id AND f.is_active = 1
               AND f.module_id IS NOT NULL AND f.access_level = 'public'
             JOIN modules pm ON pm.id = f.module_id AND pm.is_active = 1
             JOIN modules mm ON mm.id = mfi.module_id AND mm.is_active = 1
             WHERE cs.company_id = ? AND cs.is_current = 1
             ORDER BY pm.sort_order ASC, f.sort_order ASC",
            [$companyId]
        );

        // 4. Permissions for all module-specific features (separate query avoids row explosion)
        $permRows = $this->db->fetchAll(
            "SELECT DISTINCT p.id, p.feature_id, p.action
             FROM company_subscriptions cs
             JOIN company_subscription_modules csm ON csm.subscription_id = cs.id
             JOIN module_feature_map mfi ON mfi.module_id = csm.module_id
             JOIN features f ON f.id = mfi.feature_id AND f.is_active = 1
               AND f.module_id IS NOT NULL AND f.access_level = 'public'
             JOIN permissions p ON p.feature_id = f.id
             WHERE cs.company_id = ? AND cs.is_current = 1
             ORDER BY p.feature_id ASC, p.action ASC",
            [$companyId]
        );

        // 5. Shared features (system module features — shown in their own locked section)
        $sharedRows = $this->db->fetchAll(
            "SELECT f.id, f.key, f.name, f.is_scopeable, f.sort_order,
                    p.id AS permission_id, p.action
             FROM features f
             JOIN permissions p ON p.feature_id = f.id
             JOIN modules m ON m.id = f.module_id AND m.is_system = 1 AND m.is_active = 1
             WHERE f.is_active = 1
             ORDER BY f.sort_order ASC, p.action ASC",
            []
        );

        // Admin-level features are not configurable on custom roles.
        $adminRows = [];

        // 6. Current grants for this role
        $grantRows = $this->db->fetchAll(
            "SELECT permission_id, data_scope FROM role_permissions WHERE role_id = ?",
            [$roleId]
        );
        $grantedMap = [];
        foreach ($grantRows as $g) {
            $grantedMap[(int) $g->permission_id] = $g->data_scope;
        }

        $actionLabels = [
            'read'          => 'Read',   'write'      => 'Write',
            'delete'        => 'Delete', 'cancel'     => 'Cancel',
            'convert'       => 'Convert','mark_complete' => 'Mark Complete',
            'send_email'    => 'Send Email', 'receive' => 'Receive',
        ];

        // 7. Build feature map indexed by feature_id with placement metadata
        $featureMap = [];
        foreach ($mappingRows as $row) {
            $fid = (int) $row->feature_id;
            if (!isset($featureMap[$fid])) {
                $featureMap[$fid] = [
                    'key'              => $row->feature_key,
                    'name'             => $row->feature_name,
                    'is_scopeable'     => (bool) $row->is_scopeable,
                    'feature_category' => $row->feature_category,
                    'primary_module'   => $row->primary_module_key,
                    'primary_sort'     => (int) $row->primary_module_sort,
                    'feature_sort'     => (int) $row->feature_sort,
                    'shared_modules'   => [],
                    'display_names'    => [$row->primary_module_key => $row->feature_name],
                    'permissions'      => [],
                ];
            }
            if ($row->mapped_module_key !== $row->primary_module_key
                && !in_array($row->mapped_module_key, $featureMap[$fid]['shared_modules'], true)) {
                $featureMap[$fid]['shared_modules'][]                       = $row->mapped_module_key;
                $featureMap[$fid]['display_names'][$row->mapped_module_key] = $row->mapped_display_name;
            }
        }

        // Attach permissions to each feature
        foreach ($permRows as $row) {
            $fid = (int) $row->feature_id;
            $pid = (int) $row->id;
            if (!isset($featureMap[$fid])) continue;
            $granted = isset($grantedMap[$pid]);
            $featureMap[$fid]['permissions'][] = [
                'permission_id' => $pid,
                'action'        => $row->action,
                'label'         => $actionLabels[$row->action] ?? ucfirst($row->action),
                'granted'       => $granted,
                'data_scope'    => $granted ? $grantedMap[$pid] : null,
            ];
        }

        // Sort features: primary module sort order, then feature sort order
        uasort($featureMap, fn($a, $b) =>
            $a['primary_sort'] <=> $b['primary_sort'] ?: $a['feature_sort'] <=> $b['feature_sort']
        );

        // Separate reporting features — rendered in their own section in the permissions UI,
        // not mixed into their parent module's operational feature list
        $reportingFeatureMap = [];
        foreach ($featureMap as $fid => $f) {
            if (($f['feature_category'] ?? null) === 'reporting') {
                $reportingFeatureMap[$fid] = $f;
                unset($featureMap[$fid]);
            }
        }

        // 8. Build shared_features list
        $sharedData = [];
        foreach ($sharedRows as $row) {
            $fid = (int) $row->id;
            $pid = (int) $row->permission_id;
            if (!isset($sharedData[$fid])) {
                $sharedData[$fid] = [
                    'key'          => $row->key,
                    'name'         => $row->name,
                    'is_scopeable' => (bool) $row->is_scopeable,
                    'permissions'  => [],
                ];
            }
            $granted = isset($grantedMap[$pid]);
            $sharedData[$fid]['permissions'][] = [
                'permission_id' => $pid,
                'action'        => $row->action,
                'label'         => $actionLabels[$row->action] ?? ucfirst($row->action),
                'granted'       => $granted,
                'data_scope'    => $granted ? $grantedMap[$pid] : null,
            ];
        }

        // 8b. Build admin_features list
        $adminData = [];
        foreach ($adminRows as $row) {
            $fid = (int) $row->id;
            $pid = (int) $row->permission_id;
            if (!isset($adminData[$fid])) {
                $adminData[$fid] = [
                    'key'          => $row->key,
                    'name'         => $row->name,
                    'is_scopeable' => (bool) $row->is_scopeable,
                    'permissions'  => [],
                ];
            }
            $granted = isset($grantedMap[$pid]);
            $adminData[$fid]['permissions'][] = [
                'permission_id' => $pid,
                'action'        => $row->action,
                'label'         => $actionLabels[$row->action] ?? ucfirst($row->action),
                'granted'       => $granted,
                'data_scope'    => $granted ? $grantedMap[$pid] : null,
            ];
        }

        // 9. Build module list (subscription modules + system modules with activated state)
        $modules = [];
        foreach ($subModuleRows as $sm) {
            $isSystem = (bool) $sm->is_system;
            $modules[] = [
                'id'        => (int) $sm->id,
                'key'       => $sm->key,
                'name'      => $sm->name,
                'is_system' => $isSystem,
                'activated' => $isSystem || in_array($sm->key, $activatedModuleKeys, true),
            ];
        }

        return [
            'role'               => (array) $role,
            'modules'            => $modules,
            'features'           => array_values($featureMap),
            'shared_features'    => array_values($sharedData),
            'admin_features'     => array_values($adminData),
            'reporting_features' => array_values($reportingFeatureMap),
        ];
    }


    public function saveRolePermissions(int $companyId, int $userId, int $roleId, array $grants, array $activatedModules = []): void
    {
        $role = $this->db->fetchOne(
            "SELECT id, is_admin FROM company_roles WHERE id = ? AND company_id = ? LIMIT 1",
            [$roleId, $companyId]
        );
        if (!$role) {
            throw new Service_Exception('Role not found', 404);
        }

        if ((int) $role->is_admin === 1) {
            throw new Service_Exception('Permissions on the Admin role cannot be modified.', 403);
        }

        // Valid permission ids: public features within subscribed modules OR system modules
        $validPermissionIds = array_column(
            array_map('get_object_vars', $this->db->fetchAll(
                "SELECT DISTINCT p.id
                 FROM permissions p
                 JOIN features f ON f.id = p.feature_id AND f.is_active = 1 AND f.access_level = 'public'
                 WHERE EXISTS (
                   SELECT 1 FROM company_subscriptions cs
                   JOIN company_subscription_modules csm ON csm.subscription_id = cs.id
                   JOIN module_feature_map mfi ON mfi.module_id = csm.module_id
                   WHERE cs.company_id = ? AND cs.is_current = 1
                     AND mfi.feature_id = f.id
                 )

                 UNION

                 SELECT DISTINCT p2.id
                 FROM permissions p2
                 JOIN features f2 ON f2.id = p2.feature_id AND f2.is_active = 1 AND f2.access_level = 'public'
                 JOIN modules m ON m.id = f2.module_id AND m.is_system = 1 AND m.is_active = 1",
                [$companyId]
            )),
            'id'
        );
        $validSet = array_flip($validPermissionIds);

        // Valid module ids for this company's subscription
        $validModuleRows = $this->db->fetchAll(
            "SELECT m.id, m.key FROM company_subscriptions cs
             JOIN company_subscription_modules csm ON csm.subscription_id = cs.id
             JOIN modules m ON m.id = csm.module_id AND m.is_active = 1
             WHERE cs.company_id = ? AND cs.is_current = 1",
            [$companyId]
        );
        $validModuleByKey = [];
        foreach ($validModuleRows as $m) {
            $validModuleByKey[$m->key] = (int) $m->id;
        }

        $this->db->startTransaction();
        try {
            // Save feature permission grants
            $this->db->query("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);

            $seen = [];
            foreach ($grants as $g) {
                $permissionId = (int) ($g['permission_id'] ?? 0);
                $granted      = !empty($g['granted']);
                $dataScope    = in_array($g['data_scope'] ?? '', ['own', 'team', 'all'], true)
                                ? $g['data_scope'] : 'all';

                if (!$granted || !$permissionId || !isset($validSet[$permissionId])) continue;
                if (isset($seen[$permissionId])) continue;
                $seen[$permissionId] = true;

                $rp                = new Models_RolePermission();
                $rp->role_id       = $roleId;
                $rp->permission_id = $permissionId;
                $rp->data_scope    = $dataScope;
                $rp->created_by    = $userId;
                $rp->create();
            }

            // Save module activations (replace existing)
            $this->db->query("DELETE FROM role_module_activations WHERE role_id = ?", [$roleId]);

            foreach ($activatedModules as $moduleKey) {
                $moduleKey = trim((string) $moduleKey);
                if (!isset($validModuleByKey[$moduleKey])) continue;

                $this->db->insert('role_module_activations', [
                    'role_id'   => $roleId,
                    'module_id' => $validModuleByKey[$moduleKey],
                ]);
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

    private function resolveValidTeamIds(mixed $raw, int $companyId): array
    {
        $ids = array_filter(array_map('intval', is_array($raw) ? $raw : []));
        if (empty($ids)) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->db->fetchAll(
            "SELECT id FROM teams WHERE id IN ($placeholders) AND company_id = ?",
            array_merge(array_values($ids), [$companyId])
        );
        return array_column(array_map('get_object_vars', $rows), 'id');
    }


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
