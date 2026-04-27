<?php
/**
 * Service_AccessControl — runtime feature access checks.
 *
 * Answers two questions per request:
 *   1. Does the company's subscription include this feature? (companyCanAccess)
 *   2. Does this user's roles grant access to this feature?  (userCanAccess)
 *
 * The combined canAccess() is the single gate called from middleware.
 * getUserAccessibleFeatureKeys() is used by the sidebar to filter visible menu items.
 *
 * All tables queried here live in platform_db — inherited via Service_PlatformBase.
 * Service_Subscription is composed internally for subscription queries.
 */
class Service_AccessControl extends Service_Base
{
    //private Service_Subscription $subscription;

    private function serviceSubscription(): Service_Subscription {
        return new Service_Subscription();
    }

    
    /**
     * Full gate check: company subscription AND user role access.
     *
     * This is the single method called from middleware on every
     * authenticated request. Returns true only when both pass.
     *
     * Note: subscription liveness (trial/active/pilot vs expired) is
     * checked separately via Service_Subscription::isAccessible() in
     * middleware BEFORE this method — so canAccess only needs to verify
     * that the feature is included in the subscription and the user's
     * roles grant it.
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
     *
     * Delegates to Service_Subscription::getAccessibleFeatureKeys() which
     * walks module_feature_map for all active subscribed modules.
     */
    public function companyCanAccess(int $companyId, string $featureKey): bool
    {
        $keys = $this->serviceSubscription()->getAccessibleFeatureKeys($companyId);
        return in_array($featureKey, $keys, true);
    }


    /**
     * Check if a user has a super role (bypasses all feature access checks).
     *
     * Super roles are system-seeded at company signup (is_super = 1) and
     * cannot be deleted. A user holding a super role can access everything
     * the company's subscription allows.
     */
    public function userIsSuperAdmin(int $companyId, int $userId): bool
    {
        $row = $this->db->fetchOne("SELECT 1 FROM user_roles ur
                JOIN company_roles cr ON cr.id = ur.role_id
                WHERE
                    ur.user_id = ? AND
                    ur.company_id = ? AND
                    cr.is_super = ? AND
                    cr.status = ?
                LIMIT  1", [$userId, $companyId, 1, 'active']);

        return (bool) $row;
    }


    /**
     * Check if a user has role-based access to a specific feature.
     *
     * Three ways a user can have access:
     *   1. They hold a super role (is_super = 1) — access to everything
     *   2. Their role has a direct feature grant (access_type = 'feature')
     *   3. Their role has a module grant (access_type = 'module') that
     *      owns the requested feature
     *
     * All three are evaluated in a single query using OR + EXISTS.
     */
    public function userCanAccess(int $companyId, int $userId, string $featureKey): bool
    {
        //$platformDb = DB("platform_db");
        $platformDb = DB();

        // super_admin features bypass role grants entirely — only super roles can reach them
        $feature = $platformDb->fetchOne("SELECT access_level FROM features WHERE `key` = ? LIMIT 1", [$featureKey]);
        if ($feature && $feature->access_level === 'super_admin') {
            return $this->userIsSuperAdmin($companyId, $userId);
        }

        $row = $this->db->fetchOne(
            "SELECT 1 FROM user_roles ur
             JOIN company_roles cr ON cr.id = ur.role_id AND cr.status = 'active'
             WHERE 
                ur.user_id = ? AND
                ur.company_id = ? AND (
                     cr.is_super = 1
                     OR EXISTS (
                       SELECT 1
                       FROM   role_access_grants rag
                       JOIN   features f ON f.id = rag.access_id
                       WHERE  rag.role_id     = ur.role_id
                         AND  rag.access_type = 'feature'
                         AND  f.key           = ?
                     )

                     OR EXISTS (
                       SELECT 1
                       FROM   role_access_grants rag
                       JOIN   modules  m ON m.id       = rag.access_id
                       JOIN   features f ON f.module_id = m.id
                       WHERE  rag.role_id     = ur.role_id
                         AND  rag.access_type = 'module'
                         AND  f.key           = ?
                     )
                   )
             LIMIT  1",
            [$userId, $companyId, $featureKey, $featureKey]
        );

        return (bool) $row;
    }


    /**
     * Returns all feature keys this user can access via their roles,
     * intersected with what the company's subscription allows.
     *
     * Used by the sidebar to filter visible menu items — only features the user
     * can actually reach are rendered.
     *
     * Super admins receive all company-accessible features.
     * Regular users receive the union of their role grants intersected
     * with the company subscription to prevent grants exceeding the plan.
     */
    public function getUserAccessibleFeatureKeys(int $companyId, int $userId): array
    {
        $companyKeys = $this->serviceSubscription()->getAccessibleFeatureKeys($companyId);

        if (empty($companyKeys)) {
            return [];
        }

        // Super admins get everything the subscription allows
        if ($this->userIsSuperAdmin($companyId, $userId)) {
            return $companyKeys;
        }

        // Regular users: union of direct feature grants + module grants
        // super_admin features are excluded — they cannot be granted via roles
        $sql = "SELECT DISTINCT f.key
                FROM   user_roles    ur
                JOIN   company_roles cr  ON cr.id = ur.role_id AND cr.status = 'active'
                JOIN   role_access_grants rag ON rag.role_id = ur.role_id
                JOIN   features f ON (
                         (rag.access_type = 'feature' AND f.id        = rag.access_id)
                      OR (rag.access_type = 'module'  AND f.module_id = rag.access_id)
                       )
                WHERE  ur.user_id      = ?
                  AND  ur.company_id   = ?
                  AND  f.is_active     = 1
                  AND  f.access_level != 'super_admin'";

        $rows     = $this->db->fetchAll($sql, [$userId, $companyId]);
        $userKeys = array_column(array_map('get_object_vars', $rows), 'key');

        // Intersect with subscription — role grants can never exceed the plan
        return array_values(array_intersect($userKeys, $companyKeys));
    }
}
?>
