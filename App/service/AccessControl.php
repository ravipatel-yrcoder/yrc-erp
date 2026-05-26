<?php
/**
 * Service_AccessControl — runtime feature access checks.
 *
 * Access tiers:
 *   super_admin features → is_company=1 user only
 *   admin features       → Admin role (is_admin=1) OR is_company=1 user
 *   public features      → any role with an explicit grant; Admin role auto-granted all
 */
class Service_AccessControl extends Service_Base
{
    private function serviceSubscription(): Service_Subscription {
        return new Service_Subscription();
    }


    /**
     * Full gate: company subscription AND user role access.
     */
    public function canAccess(int $companyId, int $userId, string $featureKey): bool
    {
        if (!$this->companyCanAccess($companyId, $featureKey)) {
            return false;
        }

        return $this->userCanAccess($companyId, $userId, $featureKey);
    }


    /**
     * Check if the company's active subscription includes a feature.
     */
    public function companyCanAccess(int $companyId, string $featureKey): bool
    {
        $keys = $this->serviceSubscription()->getAccessibleFeatureKeys($companyId);
        return in_array($featureKey, $keys, true);
    }


    /**
     * Check if this user is the company owner (is_company=1).
     * Company owners bypass all feature checks.
     */
    public function isCompanyUser(int $companyId, int $userId): bool
    {
        $row = $this->db->fetchOne(
            "SELECT 1 FROM users WHERE id = ? AND company_id = ? AND is_company = 1 LIMIT 1",
            [$userId, $companyId]
        );

        return (bool) $row;
    }


    /**
     * Check if the user holds the Admin role (is_admin=1).
     * Admin role users have access to all non-super_admin features.
     */
    public function isAdminRole(int $companyId, int $userId): bool
    {
        $row = $this->db->fetchOne(
            "SELECT 1 FROM user_roles ur
             JOIN company_roles cr ON cr.id = ur.role_id AND cr.status = 'active'
             WHERE ur.user_id = ? AND ur.company_id = ? AND cr.is_admin = 1
             LIMIT 1",
            [$userId, $companyId]
        );

        return (bool) $row;
    }


    /**
     * Check if a user has role-based access to a specific feature.
     *
     *   super_admin → is_company=1 only
     *   admin       → Admin role OR is_company=1
     *   public      → Admin role (auto-granted all) OR explicit role grant
     */
    public function userCanAccess(int $companyId, int $userId, string $featureKey): bool
    {
        $feature = $this->db->fetchOne(
            "SELECT access_level FROM features WHERE `key` = ? LIMIT 1",
            [$featureKey]
        );

        $accessLevel = $feature ? $feature->access_level : 'public';

        if ($accessLevel === 'super_admin') {
            return $this->isCompanyUser($companyId, $userId);
        }

        if ($this->isCompanyUser($companyId, $userId)) {
            return true;
        }

        if ($this->isAdminRole($companyId, $userId)) {
            return true;
        }

        if ($accessLevel === 'admin') {
            return false;
        }

        // public feature — check explicit role grant
        $row = $this->db->fetchOne(
            "SELECT 1 FROM user_roles ur
             JOIN company_roles cr ON cr.id = ur.role_id AND cr.status = 'active'
             JOIN role_permissions rp ON rp.role_id = ur.role_id
             JOIN permissions p ON p.id = rp.permission_id
             JOIN features f ON f.id = p.feature_id
             WHERE ur.user_id = ? AND ur.company_id = ? AND f.key = ?
             LIMIT 1",
            [$userId, $companyId, $featureKey]
        );

        return (bool) $row;
    }


    /**
     * Returns all feature keys this user can access (intersected with subscription).
     *
     *   Company user  → all company-accessible feature keys
     *   Admin role    → all except super_admin features
     *   Custom role   → only explicitly granted public features
     */
    public function getUserAccessibleFeatureKeys(int $companyId, int $userId): array
    {
        $companyKeys = $this->serviceSubscription()->getAccessibleFeatureKeys($companyId);

        if (empty($companyKeys)) {
            return [];
        }

        if ($this->isCompanyUser($companyId, $userId)) {
            return $companyKeys;
        }

        if ($this->isAdminRole($companyId, $userId)) {
            // All features except super_admin
            $rows = $this->db->fetchAll(
                "SELECT f.key FROM features f WHERE f.is_active = 1 AND f.access_level != 'super_admin'",
                []
            );
            $adminKeys = array_column(array_map('get_object_vars', $rows), 'key');
            return array_values(array_intersect($adminKeys, $companyKeys));
        }

        // Custom role — explicit grants of public features only
        $rows = $this->db->fetchAll(
            "SELECT DISTINCT f.key
             FROM user_roles ur
             JOIN company_roles cr ON cr.id = ur.role_id AND cr.status = 'active'
             JOIN role_permissions rp ON rp.role_id = ur.role_id
             JOIN permissions p ON p.id = rp.permission_id
             JOIN features f ON f.id = p.feature_id
             WHERE ur.user_id = ? AND ur.company_id = ?
               AND f.is_active = 1 AND f.access_level = 'public'",
            [$userId, $companyId]
        );
        $userKeys = array_column(array_map('get_object_vars', $rows), 'key');

        return array_values(array_intersect($userKeys, $companyKeys));
    }


    /**
     * Returns the full permission map: [feature_key => [action => data_scope]]
     *
     *   Company user  → all features+actions with scope 'all'
     *   Admin role    → all non-super_admin features+actions with scope 'all'
     *   Custom role   → explicit grants only
     */
    public function getUserPermissionMap(int $companyId, int $userId): array
    {
        if ($this->isCompanyUser($companyId, $userId)) {
            return $this->buildElevatedPermissionMap(true);
        }

        if ($this->isAdminRole($companyId, $userId)) {
            return $this->buildElevatedPermissionMap(false);
        }

        $rows = $this->db->fetchAll(
            "SELECT f.key AS feature_key, p.action, rp.data_scope
             FROM user_roles ur
             JOIN company_roles cr ON cr.id = ur.role_id AND cr.status = 'active'
             JOIN role_permissions rp ON rp.role_id = ur.role_id
             JOIN permissions p ON p.id = rp.permission_id
             JOIN features f ON f.id = p.feature_id
             WHERE ur.user_id = ? AND ur.company_id = ?
               AND f.is_active = 1 AND f.access_level = 'public'",
            [$userId, $companyId]
        );

        $map = [];
        foreach ($rows as $row) {
            $map[$row->feature_key][$row->action] = $row->data_scope;
        }

        return $map;
    }


    /**
     * Returns module keys the user can access, intersected with company subscription.
     *
     *   Company user or Admin role → all subscription modules
     *   Custom role                → only role-activated modules
     */
    public function getUserActivatedModuleKeys(int $companyId, int $userId): array
    {
        if ($this->isCompanyUser($companyId, $userId) || $this->isAdminRole($companyId, $userId)) {
            return $this->serviceSubscription()->getActiveModuleKeys($companyId);
        }

        $rows = $this->db->fetchAll(
            "SELECT DISTINCT m.key
             FROM user_roles ur
             JOIN company_roles cr ON cr.id = ur.role_id AND cr.status = 'active'
             JOIN role_module_activations rma ON rma.role_id = ur.role_id
             JOIN modules m ON m.id = rma.module_id AND m.is_active = 1
             JOIN company_subscriptions cs ON cs.company_id = ur.company_id AND cs.is_current = 1
             JOIN company_subscription_modules csm ON csm.subscription_id = cs.id
               AND csm.module_id = rma.module_id
             WHERE ur.user_id = ? AND ur.company_id = ?",
            [$userId, $companyId]
        );
        $roleKeys = array_column(array_map('get_object_vars', $rows), 'key');

        return array_values(array_unique($roleKeys));
    }


    /**
     * Build a permission map granting all features with scope 'all'.
     * Used for company users (all features) and Admin role (all except super_admin).
     */
    private function buildElevatedPermissionMap(bool $includeSuperAdmin): array
    {
        $filter = $includeSuperAdmin ? '' : "AND f.access_level != 'super_admin'";

        $rows = $this->db->fetchAll(
            "SELECT f.key AS feature_key, p.action
             FROM features f
             JOIN permissions p ON p.feature_id = f.id
             WHERE f.is_active = 1 {$filter}",
            []
        );

        $map = [];
        foreach ($rows as $row) {
            $map[$row->feature_key][$row->action] = 'all';
        }

        return $map;
    }
}
?>
