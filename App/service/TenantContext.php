<?php
class Service_TenantContext
{
    // Core identity — always set at construction
    public int $companyId;
    public int $userId;

    // Subscription state — populated by hydrate()
    public ?object $subscription = null;
    public array $activeModuleKeys = [];
    public array $activatedRoleModuleKeys = [];
    public bool $isCompanyUser = false;   // is_company=1 — account owner, all access
    public bool $isAdminRole   = false;   // is_admin=1 role — full access except super_admin

    // Access state — populated by hydrate()
    public array $accessibleFeatureKeys = [];
    public array $permissionMap = [];

    private bool $hydrated = false;


    public function __construct(int $companyId, int $userId) {
        $this->companyId = $companyId;
        $this->userId = $userId;
    }


    /**
     * Load subscription and access state into this context.
     * Called once by middleware after authentication succeeds.
     */
    public function hydrate(): void {
        if ($this->hydrated) {
            return;
        }

        $subscription = new Service_Subscription();
        $this->subscription = $subscription->getCurrent($this->companyId);
        $this->activeModuleKeys = $subscription->getActiveModuleKeys($this->companyId);

        $accessService = new Service_AccessControl($this);
        $this->isCompanyUser = $accessService->isCompanyUser($this->companyId, $this->userId);
        $this->isAdminRole   = $accessService->isAdminRole($this->companyId, $this->userId);
        $this->permissionMap = $accessService->getUserPermissionMap($this->companyId, $this->userId);
        $this->activatedRoleModuleKeys = $accessService->getUserActivatedModuleKeys($this->companyId, $this->userId);

        $this->accessibleFeatureKeys = array_keys($this->permissionMap);

        // Dashboard is always accessible to every authenticated user.
        if (!in_array('dashboard', $this->accessibleFeatureKeys, true)) {
            $this->accessibleFeatureKeys[] = 'dashboard';
        }

        $this->hydrated = true;
    }


    public function isHydrated(): bool {
        return $this->hydrated;
    }


    /**
     * Check if a feature key is accessible to this user.
     */
    public function canAccess(string $featureKey): bool {
        if (empty($featureKey)) {
            return true;
        }
        return in_array($featureKey, $this->accessibleFeatureKeys, true);
    }


    /**
     * Check if the user has a specific action granted on a feature.
     */
    public function canDo(string $featureKey, string $action): bool {
        if ($this->isCompanyUser) {
            return true;
        }
        return isset($this->permissionMap[$featureKey][$action]);
    }


    /**
     * Get the data scope for a feature ('own' | 'team' | 'all').
     */
    public function scopeFor(string $featureKey): string {
        if ($this->isCompanyUser) {
            return 'all';
        }

        return $this->permissionMap[$featureKey]['read'] ?? 'own';
    }


    /**
     * Check if a module key is active on the company's subscription.
     */
    public function hasModule(string $moduleKey): bool {
        return in_array($moduleKey, $this->activeModuleKeys, true);
    }


    /**
     * Check if this role has explicitly activated a module.
     */
    public function hasRoleModule(string $moduleKey): bool {
        return in_array($moduleKey, $this->activatedRoleModuleKeys, true);
    }
}
?>
