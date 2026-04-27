<?php
class Service_TenantContext
{
    // Core identity — always set at construction
    public int $companyId;
    public int $userId;

    // Subscription state — populated by hydrate()
    public ?object $subscription = null;  // current subscription row + plan fields
    public array $activeModuleKeys = [];    // e.g. ['crm', 'sales']
    public bool $isSuperAdmin = false; // true if user holds a super role

    // Access state — populated by hydrate()
    // Feature keys this user can reach: role grants ∩ company subscription
    public array $accessibleFeatureKeys = [];

    // Internal hydration guard — prevents double-loading
    private bool $hydrated = false;


    public function __construct(int $companyId, int $userId) {
        $this->companyId = $companyId;
        $this->userId = $userId;
    }


    /**
     * Load subscription and access state into this context.
     *
     * Called once by middleware after authentication succeeds.
     * Idempotent — safe to call multiple times, only runs once.
     */
    public function hydrate(): void {
        
        if ($this->hydrated) {
            return;
        }

        $subscription = new Service_Subscription();
        $this->subscription = $subscription->getCurrent($this->companyId);
        $this->activeModuleKeys = $subscription->getActiveModuleKeys($this->companyId);

        $accessService = new Service_AccessControl($this);
        $this->isSuperAdmin = $accessService->userIsSuperAdmin($this->companyId, $this->userId);
        $this->accessibleFeatureKeys = $accessService->getUserAccessibleFeatureKeys($this->companyId, $this->userId);

        $this->hydrated = true;
    }


    /**
     * Returns true if hydrate() has been called.
     *
     * Useful for assertions in services that expect a hydrated context.
     */
    public function isHydrated(): bool {
        return $this->hydrated;
    }


    /**
     * Check if a feature key is accessible to this user.
     *
     * Convenience method — avoids re-instantiating Service_AccessControl
     * in every controller or service that already holds a hydrated context.
     *
     * Returns false if context has not been hydrated yet.
     */
    public function canAccess(string $featureKey): bool {
        return in_array($featureKey, $this->accessibleFeatureKeys, true);
    }


    /**
     * Check if a module key is active on the company's subscription.
     *
     * Useful for conditional logic in controllers and sidebar rendering.
     * Returns false if context has not been hydrated yet.
     */
    public function hasModule(string $moduleKey): bool {
        return in_array($moduleKey, $this->activeModuleKeys, true);
    }
}
?>
