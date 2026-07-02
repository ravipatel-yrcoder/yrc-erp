<?php
/**
 * Service_Subscription — read-only subscription queries.
 *
 * Does not extend Service_Base: this service is called from middleware
 * before a TenantContext has been established, so it takes companyId
 * directly on each method.
 *
 * All tables queried here (subscriptions, modules, features, roles) live in
 * the platform DB — the shared database that is never split per-company.
 * Extends Service_PlatformBase which provides $this->db (platform_db) and
 * the standard error helpers.
 */
class Service_Subscription extends Service_PlatformBase
{

    /**
     * Load the current active subscription row for a company.
     *
     * Joins subscription_plans so callers get plan_name, plan_slug,
     * and max_modules without a second query.
     *
     * @return stdClass|null  null when no current subscription exists
     */
    public function getCurrent(int $companyId): ?object
    {
        $sql = "SELECT cs.*, sp.name AS plan_name, sp.slug AS plan_slug, sp.max_modules
                FROM company_subscriptions cs
                JOIN subscription_plans sp ON sp.id = cs.plan_id
                WHERE cs.company_id = ? AND cs.is_current = ?
                LIMIT  1";

        $row = $this->db->fetchOne($sql, [$companyId, 1]);

        return $row ?: null;
    }


    /**
     * Returns true when the company is allowed to use the application.
     *
     * Allowed statuses : trial, pilot, active
     * Blocked statuses : past_due, cancelled, suspended
     *
     * Extra expiry checks:
     *  - trial  : blocked when trial_ends_at is set and has passed
     *  - pilot  : blocked when pilot_until  is set and has passed
     */
    public function isAccessible(int $companyId): bool
    {
        $sub = $this->getCurrent($companyId);

        if (!$sub) {
            return false;
        }

        $allowed = ['trial', 'pilot', 'active'];
        if (!in_array($sub->status, $allowed, true)) {
            return false;
        }

        $now = time();
        if ($sub->status === 'trial' && !empty($sub->trial_ends_at)) {
            if (strtotime($sub->trial_ends_at) < $now) {
                return false;
            }
        }

        if ($sub->status === 'pilot' && !empty($sub->pilot_until)) {
            if (strtotime($sub->pilot_until) < $now) {
                return false;
            }
        }

        return true;
    }


    /**
     * Returns the module keys active for the company under the current subscription.
     *
     * Only includes modules that are also marked is_active in the modules table,
     * so deactivating a platform module immediately revokes access without a
     * data-migration.
     *
     * @return string[]  e.g. ['crm', 'sales']
     */
    public function getActiveModuleKeys(int $companyId): array
    {
        $sql = "SELECT m.key
                FROM   company_subscription_modules csm
                JOIN   company_subscriptions cs ON cs.id = csm.subscription_id
                JOIN   modules              m  ON m.id  = csm.module_id
                WHERE  csm.company_id = ?
                  AND  cs.is_current  = 1
                  AND  csm.is_active  = 1
                  AND  m.is_active    = 1

                UNION

                SELECT m.key
                FROM   modules m
                WHERE  m.is_system = 1 AND m.is_active = 1";

        $rows = $this->db->fetchAll($sql, [$companyId]);

        return array_column(array_map('get_object_vars', $rows), 'key');
    }


    /**
     * Returns feature keys that belong to deactivated subscription modules and are NOT
     * also mapped to any currently active module. Used to subtract inaccessible features
     * from elevated users' (company owner / admin role) permission maps without removing
     * platform features (company_users, company_roles_mgmt, etc.) that live outside any
     * module_feature_map entry.
     *
     * @return string[]
     */
    public function getDeactivatedModuleFeatureKeys(int $companyId): array
    {
        // Use features.module_id (the feature's primary home module) rather than
        // module_feature_map, which is a cross-module display index not an
        // authoritative source of feature→module ownership. This prevents platform
        // features like company_settings from being incorrectly blocked when their
        // primary module (e.g. system) is active but they also appear in deactivated
        // module feature maps for display purposes.
        $sql = "SELECT f.key
                FROM   features f
                JOIN   modules  m ON m.id = f.module_id
                WHERE  f.is_active  = 1
                  AND  m.is_active  = 1
                  AND  m.is_system  = 0
                  AND  EXISTS (
                      SELECT 1
                      FROM   company_subscription_modules csm
                      JOIN   company_subscriptions cs ON cs.id = csm.subscription_id
                      WHERE  csm.company_id = ?
                        AND  cs.is_current  = 1
                        AND  csm.module_id  = m.id
                        AND  csm.is_active  = 0
                  )";

        $rows = $this->db->fetchAll($sql, [$companyId]);

        return array_column(array_map('get_object_vars', $rows), 'key');
    }


    /**
     * Returns all feature keys the company can access given their subscribed modules.
     *
     * Walks module_feature_map for every active subscribed module and returns
     * the union of all included feature keys. Handles cross-module access
     * (e.g. CRM subscription includes sales.quotations) automatically because
     * those cross-module rows live in module_feature_map.
     *
     * @return string[]  e.g. ['crm.pipeline', 'crm.leads', 'sales.quotations', ...]
     */
    public function getAccessibleFeatureKeys(int $companyId): array
    {
        $sql = "SELECT DISTINCT f.key
                FROM   company_subscription_modules csm
                JOIN   company_subscriptions   cs  ON cs.id  = csm.subscription_id
                JOIN   module_feature_map mfi ON mfi.module_id = csm.module_id
                JOIN   features                f   ON f.id   = mfi.feature_id
                WHERE  csm.company_id = ?
                  AND  cs.is_current  = 1
                  AND  csm.is_active  = 1
                  AND  f.is_active    = 1

                UNION

                SELECT f.key
                FROM   features f
                WHERE  f.access_level IN ('core', 'super_admin')
                  AND  f.is_active    = 1

                UNION

                SELECT f.key
                FROM   features f
                JOIN   modules m ON m.id = f.module_id AND m.is_system = 1 AND m.is_active = 1
                WHERE  f.is_active = 1";

        $rows = $this->db->fetchAll($sql, [$companyId]);

        return array_column(array_map('get_object_vars', $rows), 'key');
    }


    /**
     * Returns a full summary of the current subscription for the overview page.
     * Aggregates plan, modules, and seat data in one call.
     */
    public function getSummary(int $companyId): array
    {
        $sub = $this->getCurrent($companyId);

        if (!$sub) {
            return [];
        }

        $seats = $this->getSeatCounts($companyId);

        $activeModules = $this->db->fetchAll(
            "SELECT m.key, m.name, m.icon
             FROM   company_subscription_modules csm
             JOIN   company_subscriptions cs ON cs.id  = csm.subscription_id
             JOIN   modules              m  ON m.id   = csm.module_id
             WHERE  csm.company_id = ? AND cs.is_current = 1
               AND  csm.is_active  = 1 AND m.is_active   = 1
             ORDER  BY m.sort_order ASC",
            [$companyId]
        );

        $trialDaysRemaining = null;
        if ($sub->status === 'trial' && !empty($sub->trial_ends_at)) {
            $diff = strtotime($sub->trial_ends_at) - time();
            $trialDaysRemaining = max(0, (int) ceil($diff / 86400));
        }

        $allModules = [];
        if ((int) $sub->max_modules === 1) {
            $allModules = $this->db->fetchAll(
                "SELECT id, `key`, name, icon FROM modules WHERE is_active = 1 ORDER BY sort_order ASC",
                []
            );
        }

        return [
            'plan_name'            => $sub->plan_name,
            'plan_slug'            => $sub->plan_slug,
            'max_modules'          => $sub->max_modules,
            'status'               => $sub->status,
            'billing_cycle'        => $sub->billing_cycle,
            'agreed_base_price'    => $sub->agreed_base_price,
            'trial_ends_at'        => $sub->trial_ends_at,
            'trial_days_remaining' => $trialDaysRemaining,
            'current_period_end'   => $sub->current_period_end,
            'seats'                => $seats,
            'active_modules'       => $activeModules,
            'all_modules'          => $allModules,
        ];
    }


    /**
     * Switches the active module for a One App subscription.
     * Deactivates the current module and activates the requested one.
     */
    public function changeModule(int $companyId, int $userId, string $newModuleKey): array
    {
        $sub = $this->getCurrent($companyId);
        if (!$sub) {
            throw new Service_Exception('No active subscription found', 404);
        }

        if ((int) $sub->max_modules !== 1) {
            throw new Service_Exception('Module switching is only available on the One App plan', 422);
        }

        $newModule = $this->db->fetchOne(
            "SELECT id, `key` FROM modules WHERE `key` = ? AND is_active = 1 LIMIT 1",
            [$newModuleKey]
        );
        if (!$newModule) {
            throw new Service_Exception('Invalid module selected', 422);
        }

        $alreadyActive = $this->db->fetchOne(
            "SELECT csm.id FROM company_subscription_modules csm
             JOIN   modules m ON m.id = csm.module_id
             WHERE  csm.company_id = ? AND csm.subscription_id = ?
               AND  csm.is_active  = 1 AND m.key = ?
             LIMIT  1",
            [$companyId, $sub->id, $newModuleKey]
        );
        if ($alreadyActive) {
            throw new Service_Exception('This module is already active', 422);
        }

        try {
            $this->db->startTransaction();

            $subId = (int) $sub->id;
            $moduleId = (int) $newModule->id;
            $now = date('Y-m-d H:i:s');

            $this->db->query(
                "UPDATE company_subscription_modules SET is_active = 0 WHERE company_id = ? AND subscription_id = ?",
                [$companyId, $subId]
            );

            $existingRow = $this->db->fetchOne(
                "SELECT id FROM company_subscription_modules WHERE company_id = ? AND subscription_id = ? AND module_id = ? LIMIT 1",
                [$companyId, $subId, $moduleId]
            );

            if ($existingRow) {
                $this->db->query(
                    "UPDATE company_subscription_modules SET is_active = 1, activated_at = ? WHERE id = ?",
                    [$now, $existingRow->id]
                );
            } else {
                $csm                  = new Models_CompanySubscriptionModule();
                $csm->company_id      = $companyId;
                $csm->subscription_id = $subId;
                $csm->module_id       = $moduleId;
                $csm->is_active       = 1;
                $csm->create();
            }

            $this->db->commit();

            return ['success' => true, 'data' => []];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }


    /**
     * Upgrades a One App subscription to All Apps.
     * Creates a new subscription row and activates all platform modules.
     * Billing charge is handled separately in Phase 8.
     */
    public function upgradePlan(int $companyId, int $userId): array
    {
        $sub = $this->getCurrent($companyId);
        if (!$sub) {
            throw new Service_Exception('No active subscription found', 404);
        }

        if ($sub->plan_slug === 'all_apps') {
            throw new Service_Exception('Already on the All Apps plan', 422);
        }

        $allAppsPlan = $this->db->fetchOne(
            "SELECT id, free_users_included, base_price_monthly, extra_user_price_monthly
             FROM   subscription_plans
             WHERE  slug = 'all_apps' AND is_active = 1
             LIMIT  1",
            []
        );
        if (!$allAppsPlan) {
            throw new Service_Exception('Upgrade plan not available', 500);
        }

        $allModules = $this->db->fetchAll(
            "SELECT id FROM modules WHERE is_active = 1",
            []
        );

        try {
            $this->db->startTransaction();

            $oldSub             = new Models_CompanySubscription((int) $sub->id);
            $oldSub->is_current = 0;
            $oldSub->updated_by = $userId;
            $oldSub->update();

            $newSub                       = new Models_CompanySubscription();
            $newSub->company_id           = $companyId;
            $newSub->plan_id              = (int) $allAppsPlan->id;
            $newSub->is_current           = 1;
            $newSub->status               = $sub->status;
            $newSub->billing_cycle        = $sub->billing_cycle;
            $newSub->agreed_base_price    = $allAppsPlan->base_price_monthly;
            $newSub->agreed_extra_user_price = $allAppsPlan->extra_user_price_monthly;
            $newSub->free_users_included  = (int) $allAppsPlan->free_users_included;
            $newSub->purchased_extra_seats = (int) $sub->purchased_extra_seats;
            $newSub->trial_ends_at        = $sub->trial_ends_at;
            $newSub->current_period_start = $sub->current_period_start;
            $newSub->current_period_end   = $sub->current_period_end;
            $newSub->created_by           = $userId;
            $newSub->updated_by           = $userId;

            if (!$newSub->create()) {
                throw new Exception('Failed to create upgraded subscription');
            }

            $newSubId = (int) $newSub->id;

            foreach ($allModules as $module) {
                $csm                  = new Models_CompanySubscriptionModule();
                $csm->company_id      = $companyId;
                $csm->subscription_id = $newSubId;
                $csm->module_id       = (int) $module->id;
                $csm->is_active       = 1;
                $csm->create();
            }

            $this->db->commit();

            return ['success' => true, 'data' => []];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }


    /**
     * Cancels the current subscription immediately.
     * Sets status = cancelled on the current subscription row.
     */
    public function cancelSubscription(int $companyId, int $userId): array
    {
        $sub = $this->getCurrent($companyId);
        if (!$sub) {
            throw new Service_Exception('No active subscription found', 404);
        }

        if ($sub->status === 'cancelled') {
            throw new Service_Exception('Subscription is already cancelled', 422);
        }

        $model             = new Models_CompanySubscription((int) $sub->id);
        $model->status     = 'cancelled';
        $model->updated_by = $userId;

        if (!$model->update()) {
            throw new Exception('Failed to cancel subscription');
        }

        return ['success' => true, 'data' => []];
    }


    /**
     * Downgrades from All Apps to One App, keeping only the specified module.
     * Creates a new subscription row for audit history (same pattern as upgradePlan).
     */
    public function downgradePlan(int $companyId, int $userId, string $moduleKey): array
    {
        $sub = $this->getCurrent($companyId);
        if (!$sub) {
            throw new Service_Exception('No active subscription found', 404);
        }

        if ($sub->plan_slug !== 'all_apps') {
            throw new Service_Exception('Downgrade is only available on the All Apps plan', 422);
        }

        $newModule = $this->db->fetchOne(
            "SELECT id, `key` FROM modules WHERE `key` = ? AND is_active = 1 LIMIT 1",
            [$moduleKey]
        );
        if (!$newModule) {
            throw new Service_Exception('Invalid module selected', 422);
        }

        $oneAppPlan = $this->db->fetchOne(
            "SELECT id, free_users_included, base_price_monthly, extra_user_price_monthly
             FROM   subscription_plans
             WHERE  slug = 'one_app' AND is_active = 1
             LIMIT  1",
            []
        );
        if (!$oneAppPlan) {
            throw new Service_Exception('Target plan not available', 500);
        }

        try {
            $this->db->startTransaction();

            $oldSub             = new Models_CompanySubscription((int) $sub->id);
            $oldSub->is_current = 0;
            $oldSub->updated_by = $userId;
            $oldSub->update();

            $newSub                           = new Models_CompanySubscription();
            $newSub->company_id               = $companyId;
            $newSub->plan_id                  = (int) $oneAppPlan->id;
            $newSub->is_current               = 1;
            $newSub->status                   = $sub->status;
            $newSub->billing_cycle            = $sub->billing_cycle;
            $newSub->agreed_base_price        = $oneAppPlan->base_price_monthly;
            $newSub->agreed_extra_user_price  = $oneAppPlan->extra_user_price_monthly;
            $newSub->free_users_included      = (int) $oneAppPlan->free_users_included;
            $newSub->purchased_extra_seats    = (int) $sub->purchased_extra_seats;
            $newSub->razorpay_customer_id     = $sub->razorpay_customer_id;
            $newSub->razorpay_subscription_id = $sub->razorpay_subscription_id;
            $newSub->trial_ends_at            = $sub->trial_ends_at;
            $newSub->current_period_start     = $sub->current_period_start;
            $newSub->current_period_end       = $sub->current_period_end;
            $newSub->created_by               = $userId;
            $newSub->updated_by               = $userId;

            if (!$newSub->create()) {
                throw new Exception('Failed to create downgraded subscription');
            }

            $newSubId = (int) $newSub->id;

            $csm                  = new Models_CompanySubscriptionModule();
            $csm->company_id      = $companyId;
            $csm->subscription_id = $newSubId;
            $csm->module_id       = (int) $newModule->id;
            $csm->is_active       = 1;
            $csm->create();

            $this->db->commit();

            return ['success' => true, 'data' => []];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }


    /**
     * Returns seat counts for the company's current subscription.
     *
     * total_seats     = free_users_included + purchased_extra_seats
     * used_seats      = active users in the company
     * available_seats = total_seats - used_seats  (can be negative if seats were removed)
     *
     * @return array{total_seats: int, used_seats: int, available_seats: int}
     */
    public function getSeatCounts(int $companyId): array
    {
        $sub = $this->getCurrent($companyId);

        $total = 0;
        if ($sub) {
            $total = (int) $sub->free_users_included + (int) $sub->purchased_extra_seats;
        }

        $usedRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM users WHERE company_id = ? AND status = 'active'",
            [$companyId]
        );
        $used = $usedRow ? (int) $usedRow->cnt : 0;

        return [
            'total_seats'     => $total,
            'used_seats'      => $used,
            'available_seats' => $total - $used,
        ];
    }
}
?>
