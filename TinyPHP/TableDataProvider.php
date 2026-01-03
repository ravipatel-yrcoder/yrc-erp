<?php
class TinyPHP_TableDataProvider
{
    protected $db;
    protected $request;
    protected $baseQuery;
    protected $columns = [];
    protected $wheres = [];
    protected $bindings = [];
    protected $orderBy = '';
    protected $limit = null;
    protected $offset = null;
    protected $filtersApplied = false;

    public function __construct($db)
    {
		$this->db = $db;
        $this->request = TinyPHP_Request::getInstance();
    }

    public function query(string $sql)
    {
        $this->baseQuery = $sql;
        return $this;
    }

    public function columns(array $map)
    {
        $this->columns = $map;
        return $this;
    }

    /**
     * Supports flexible where conditions:
     * ->where([['id', '=', 1], ['status', 'IN', ['active', 'pending']]])
     */
    public function where(array $conditions)
    {
        foreach ($conditions as $cond) {
            
			if (count($cond) < 2) continue;

            [$column, $operator, $value] = array_pad($cond, 3, null);

            $operator = strtoupper($operator ?: '=');

            switch ($operator) {
				case 'IN':
				case 'NOT IN':
					if (!is_array($value) || empty($value)) continue;
					$placeholders = implode(',', array_fill(0, count($value), '?'));
					$this->wheres[] = "$column $operator ($placeholders)";
					$this->bindings = array_merge($this->bindings, $value);
					break;

				// --- Range ---
				case 'BETWEEN':
				case 'NOT BETWEEN':
					if (!is_array($value) || count($value) < 2) continue;
					$this->wheres[] = "$column $operator ? AND ?";
					$this->bindings[] = $value[0];
					$this->bindings[] = $value[1];
					break;

				// --- Null checks ---
				case 'IS NULL':
				case 'IS NOT NULL':
					$this->wheres[] = "$column $operator";
					break;

				// --- LIKE patterns ---
				case 'LIKE':
				case 'NOT LIKE':
					$this->wheres[] = "$column $operator ?";
					$this->bindings[] = $value;
					break;

				// --- Comparison / equality operators ---
				case '=':
				case '!=':
				case '<>':
				case '>':
				case '<':
				case '>=':
				case '<=':
					$this->wheres[] = "$column $operator ?";
					$this->bindings[] = $value;
					break;

				// --- RAW SQL or custom expression ---
				case 'RAW':
					// value is a raw string condition (unsafe if user-provided!)
					$this->wheres[] = $value;
					break;

				default:
					$this->wheres[] = "$column $operator ?"; 
					if ($value !== null) { $this->bindings[] = $value; }
            }
        }
        return $this;
    }

    public function applyFilters(Closure $callback)
    {
        if (!$this->filtersApplied) {
            $callback($this, $this->request);
            $this->filtersApplied = true;
        }
        return $this;
    }

    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBy = "ORDER BY $column $direction";
        return $this;
    }

    public function orderByFromRequest()
    {
        if ($this->request && $this->request->input('order_column')) {
            $col = $this->request->input('order_column');
            $dir = strtoupper($this->request->input('order_dir') ?? 'ASC');
            $this->orderBy($col, $dir);
        }
        return $this;
    }

    public function paginateFromRequest()
    {
        if ($this->request) {
            $start = (int)($this->request->input('start') ?? 0);
            $length = (int)($this->request->input('length') ?? 0);
            $page = (int)($this->request->ginputet('page') ?? 0);
            $perPage = (int)($this->request->input('per_page') ?? 0);

            if ($length) {
                $this->limit = $length;
                $this->offset = $start;
            } elseif ($perPage) {
                $this->limit = $perPage;
                $this->offset = ($page - 1) * $perPage;
            }
        }
        return $this;
    }

    protected function buildFinalQuery()
    {
        $sql = $this->baseQuery;

        if ($this->wheres) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        if ($this->orderBy) {
            $sql .= ' ' . $this->orderBy;
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
            if ($this->offset !== null) {
                $sql .= " OFFSET {$this->offset}";
            }
        }

        return $sql;
    }

    public function getData()
    {
        $sql = $this->buildFinalQuery();
        $rows = $this->db->select($sql, $this->bindings);

        // If DataTable request
        if ($this->request && $this->request->input('draw')) {
            $totalCount = $this->getTotalCount();
            return [
                'draw' => (int)$this->request->input('draw'),
                'recordsTotal' => $totalCount,
                'recordsFiltered' => $totalCount,
                'data' => $rows,
            ];
        }

        // Regular API/mobile
        return $rows;
    }

    protected function getTotalCount()
    {
        $sql = "SELECT COUNT(*) as total FROM ({$this->baseQuery}) as base";
        $result = $this->db->selectOne($sql, $this->bindings);
        return $result['total'] ?? 0;
    }
}