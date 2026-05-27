<?php
class Service_Scope {

    private Service_TenantContext $context;

    public function __construct(Service_TenantContext $context) {
        $this->context = $context;
    }

    /**
     * Returns a SQL WHERE fragment + bindings for data scope filtering.
     *
     * Reads the scope from TenantContext for the given feature key:
     *   all  → no filter (empty sql, empty bindings)
     *   own  → (col1 = userId OR col2 = userId)
     *   team → fetch team members → (col1 IN (...) OR col2 IN (...))
     *          falls back to 'own' if user is in no teams
     *
     * @param string $featureKey    e.g. 'crm_leads'
     * @param array  $ownerColumns  SQL column refs, e.g. ['l.created_by', 'l.assigned_to']
     * @return array{sql: string, bindings: array}
     */
    public function getCondition(string $featureKey, array $ownerColumns): array {
        $scope  = $this->context->scopeFor($featureKey);
        $userId = $this->context->userId;

        if ($scope === 'all') {
            return ['sql' => '', 'bindings' => []];
        }

        if ($scope === 'team') {
            $teamService = new Service_Team($this->context);
            $memberIds   = $teamService->getTeamMemberIds($userId, $this->context->companyId);

            if (!empty($memberIds)) {
                $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
                $parts    = array_map(fn($col) => "{$col} IN ({$placeholders})", $ownerColumns);
                $bindings = [];
                foreach ($ownerColumns as $_) {
                    $bindings = array_merge($bindings, $memberIds);
                }
                return ['sql' => '(' . implode(' OR ', $parts) . ')', 'bindings' => $bindings];
            }
            // No teams — fall through to 'own'
        }

        // own scope (or team with no teams configured)
        $parts    = array_map(fn($col) => "{$col} = ?", $ownerColumns);
        $bindings = array_fill(0, count($ownerColumns), $userId);
        return ['sql' => '(' . implode(' OR ', $parts) . ')', 'bindings' => $bindings];
    }
}
