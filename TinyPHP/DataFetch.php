<?php
class TinyPHP_DataFetch {

	protected $db;
	protected $request;
	protected $table = '';
	protected $columns = [];
	protected $virtualColumns = [];
	protected $joins = "";
	protected $where = [];
	protected $bindings = [];
	protected $disabledSearchColumns = [];
	protected $orderBy = '';
	protected $groupBy = '';
	protected $having = '';
	
	public function __construct(TinyPHP_Request $request) {
		
		global $db;
        
		$this->db = $db;
		$this->request = $request;
    }


	public function table(string $tableFrom): self {		
		
		$this->table = $tableFrom;
		return $this;
	}

	public function columns(array $columns): self {

		$this->columns = $columns;
		return $this;
	}


	public function virtualColumns(array $columns): self {

		$this->virtualColumns = $columns;
		return $this;
	}


	public function joins(string $joinsStr, $bind=[]): self {		
		
		$this->joins = $joinsStr;
		if( $bind ) {$this->bindings = array_merge($this->bindings, $bind);}

		return $this;
	}


	public function where(string $condition, $bind=[]): self {
		
		$this->where[] = $condition;
        if( $bind ) {$this->bindings = array_merge($this->bindings, $bind);}
        
		return $this;		
	}


	public function ignoreSearch(array $columns): self {
		
		$this->disabledSearchColumns = $columns;		
		return $this;
	}

	public function orderBy(string $orderByStr): self {

		$this->orderBy = $orderByStr;
		return $this;
	}

	public function groupBy(string $groupByStr): self {

		$this->groupBy = $groupByStr;
		return $this;
	}


	public function having(string $havingStr): self {

		$this->having = $havingStr;
		return $this;
	}


	public function fetch(): array
    {
		$request = $this->request;

        $isDataTable = $request->hasInput("draw") ? true : false;
		$dtColumns = $isDataTable === true ? $request->getInput("columns", "array", []) : [];
		$fetchColumns = $this->columns;

		$whereClause = "";
		if( $this->where ) {
			$whereClause = "WHERE ".implode(" AND ", $this->where);
		}

		// Search where
		$searchWhereClause = [];
		$searchWhereBinding = [];
		if( $isDataTable === true )
		{
			if( $request->hasInput("search") ) {
			
				$search = $request->getInput("search", "array", []);
				$searchVal = $search["value"] ?? "";
				if( $searchVal )
				{
					foreach($dtColumns as $dtColumn)
					{
						$isSearchable = $dtColumn["searchable"] ?? false;
						if( $isSearchable == true )
						{
							$dtColName = $dtColumn["name"] ? $dtColumn["name"] : $dtColumn["data"];
							$finalCols = (array) ($this->virtualColumns[$dtColName] ?? [$dtColName]);
							foreach($finalCols as $finalCol)
							{
								if( $fetchColumns[$finalCol] ?? false )
								{
									$searchWhereClause[] = $fetchColumns[$finalCol]." LIKE ?";
									$searchWhereBinding[] = "%{$searchVal}%";
								}								
							}
						}
					}
				}
			}
		}

		if( $searchWhereClause && $searchWhereBinding )
		{
			if( $whereClause == "" ) {
				$whereClause .= "WHERE ";
			} else {
				$whereClause .= " AND ";
			}

			$whereClause .= "(".implode(" OR ", $searchWhereClause).")";
			$this->bindings = array_merge($this->bindings, $searchWhereBinding);
		}



		$groupByClause = "";
		if( $this->groupBy ) {
			$groupByClause = "GROUP BY {$this->groupBy}";
		}

		$havingClause = "";
		if( $this->groupBy && $this->having ) {
			$havingClause = "HAVING {$this->having}";
		}

		
		$orderByClauseItems = [];
		if( $isDataTable === true )
		{
			if( $this->request->hasInput("order") ) {
				
				$orderByColumns = $this->request->getInput("order", "array", []);
				foreach($orderByColumns as $orderByCol)
				{
					$orderByColIndex = $orderByCol["column"] ?? false;
					if( $orderByColIndex !== false )
					{
						$dtCol = $dtColumns[$orderByColIndex] ?? [];
						$idDtColOrderable = $dtCol["orderable"] ?? false;
						if( $idDtColOrderable == true )
						{
							$dtColName = $dtCol["name"] ? $dtCol["name"] : $dtCol["data"];
							$finalCols = (array) ($this->virtualColumns[$dtColName] ?? [$dtColName]);
							foreach($finalCols as $finalCol)
							{
								if( $fetchColumns[$finalCol] ?? false )
								{
									$orderByClauseItems[] = $fetchColumns[$finalCol]." ". $orderByCol['dir'];
								}								
							}
						}
					}					
				}
			}
		}
		else
		{
			if( $this->request->hasInput("order") ) {
				
				$orderByColumns = $this->request->getInput("orderBy", "array", []);
				foreach($orderByColumns as $orderByCol)
				{
					$orderByColName = $orderByCol["name"] ?? "";
					$orderByColDir = $orderByCol["dir"] ?? "ASC";
					if( $orderByColName )
					{
						$orderByClauseItems[] = $orderByColName." ". strtoupper($orderByColDir);
					}					
				}
			}
		}

		$orderByClause = "";
		if( $orderByClauseItems ) {
			$orderByClause = "ORDER BY ".implode(", ", $orderByClauseItems);
		}


		$limitClause = '';
		if( $request->hasInput('start') && $request->hasInput('length')) {
			
			$start = $request->getInput('start', 'int');
			$length = $request->getInput('length', 'int');

			if( $length != -1 ) {
				$limitClause = "LIMIT ?, ?";
				$this->bindings = array_merge($this->bindings, [$start, $length]);
			}
		}		
		



		$selectCols = ["*"];
		if( $fetchColumns ) {
			$selectCols = [];
			foreach($fetchColumns as $alias => $col) {
				$selectCols[] = "$col AS $alias";
			}			
		}

		$sql = "SELECT ".implode(",", $selectCols)." FROM {$this->table} {$this->joins} {$whereClause} {$groupByClause} {$havingClause} {$orderByClause} {$limitClause}";
		$results = $this->db->fetchAll($sql, $this->bindings);

		// Response
        if ($isDataTable) {
            return [
                'draw' => (int) $request->getInput('draw'),
                'recordsTotal' => count($results),
                'recordsFiltered' => count($results),
                'data' => $results
            ];
        }

		return[];

        return [
            'total' => (int) $recordsTotal,
            'items' => $rows,
            'limit' => $length,
            'offset' => $start
        ];
    }

	
}
?>