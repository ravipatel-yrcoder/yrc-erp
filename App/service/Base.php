<?php
abstract  class Service_Base {

	protected $db;
    protected Service_TenantContext $context;
    private $errors = [];

    public function __construct(Service_TenantContext $context) {
        $this->context = $context;
        $this->db = Service_TenantDBResolver::resolve($context->companyId);
    }
    
    public function addError($err, $idx=null)
    {
        if(is_array($err))
        {
            foreach($err as $key => $msg)
            {
                if( is_numeric($key) ) {
                    $this->errors[] = $msg;
                } else {
                    $this->errors[$key] = $msg;
                }
            }
        }
        else
        {
            if (empty($idx)) {
                $this->errors[] = $err;
            } else {
                $this->errors[$idx] = $err;
            } 
        }
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
	
    public function hasErrors()
    {
        $hasErrors = false;
        if( count($this->errors) > 0 )
        {
            $hasErrors = true;
        }
        
        return $hasErrors;
    }
    
    public function resetErrors()
    {
        $this->errors = [];
    }


    /**
     * Maps a related_type string to the feature key that gates access to that object.
     * Returns null for unknown types — callers should deny access on null.
     */
    protected function relatedTypeToFeatureKey(string $relatedType): ?string
    {
        $map = [
            'lead'            => 'crm_leads',
            'sales_order'     => 'sales_orders',
            'sales_delivery'  => 'sales_deliveries',
            'purchase_order'  => 'purchase_orders',
            'purchase_receipt'=> 'purchase_receipts',
            'customer'        => 'customers',
            'vendor'          => 'vendors',
            'product'         => 'products',
        ];
        return $map[$relatedType] ?? null;
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
     * @param string $featureKey     e.g. 'crm_leads'
     * @param array  $ownerColumns   SQL column refs, e.g. ['l.created_by', 'l.assigned_to']
     * @return array{sql: string, bindings: array}
     */
    public function getScopeCondition(string $featureKey, array $ownerColumns): array
    {
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