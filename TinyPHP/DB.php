<?php
use Illuminate\Database\Capsule\Manager as Capsule;
final class TinyPHP_DB
{	
	/**
     * @var Illuminate\Database\Connection[]
     */
    private static $instances = [];

    /**
     * Flag to boot Capsule globally only once
     * @var bool
     */
    private static $booted = false;


    /** @var \Illuminate\Database\Connection */
    private $connection;


    /** @var string */
    private $connectionName;


    // Prevent direct construction
    private function __construct(\Illuminate\Database\Connection $connection, string $connectionName)
    {
        $this->connection = $connection;
        $this->connectionName = $connectionName;
    }


    /**
     * Get a database connection instance
     *
     * @param string|null $connectionName
     * @return \Illuminate\Database\Connection
     * @throws \Exception
     */
	public static function getInstance(string|null $connectionName=null): self {
        
		// Load database config
		$dbConfig = Config('database');

		if (!$dbConfig) {
            throw new \Exception("Database config not found.");
        }

		// Default connection
		$defaultConnection = $dbConfig["default"] ?? "default";
		$requestedConnection = $connectionName ?: $defaultConnection;


		// Return existing instance if already created
		if (isset(self::$instances[$requestedConnection])) {
			return self::$instances[$requestedConnection];
		}


		 // Check if connection exists in config
        $connections = $dbConfig['connections'] ?? [];

        if (!isset($connections[$requestedConnection])) {
            throw new \Exception("Database connection '$requestedConnection' not defined in config.");
        }

		$config = $connections[$requestedConnection];

		// Build connection array for Capsule
        $capsuleConfig = [
            'driver' => $config['driver'] ?? 'mysql',
            'host' => $config['host'] ?? '127.0.0.1',
            'database' => $config['database'] ?? '',
            'username' => $config['username'] ?? '',
            'password' => $config['password'] ?? '',
            'charset' => $config['charset'] ?? 'utf8mb4',
            'collation' => $config['collation'] ?? 'utf8mb4_unicode_ci',
            'prefix' => $config['prefix'] ?? '',
        ];		


		// resolve connection name for capsule
		$capsuleConnectioName = ($requestedConnection === $defaultConnection) ? 'default' : $requestedConnection;


		// Initialize Capsule
        $capsule = new Capsule;
        $capsule->addConnection($capsuleConfig, $capsuleConnectioName);
		
		// Boot only once globally
    	if (!self::$booted) {

        	$capsule->setAsGlobal();
        	$capsule->bootEloquent();
        	self::$booted = true;
    	}

		$connection = $capsule->getConnection($capsuleConnectioName);		

		// Create TinyPHP_DBCapsule object
        $instance = new self($connection, $requestedConnection);

        // Cache the instance
        self::$instances[$requestedConnection] = $instance;

        return $instance;
    }



    // -----------------------------
    // Fetch Methods
    // -----------------------------

    public function fetchAll(string $sql, array $bindings = []): array {
        
        return $this->connection->select($sql, $bindings); // array of objects
    }

    public function fetchOne(string $sql, array $bindings = []): object|null {
        
        $row = $this->connection->selectOne($sql, $bindings);
        return $row ?: null; // object or null
    }

    public function fetchCol(string $sql, array $bindings = []): array {
        
        $rows = $this->connection->select($sql, $bindings);
        
        return array_map(function($row) {
            $rowArray = (array) $row;
            return reset($rowArray); // safe, now $rowArray is a variable
        }, $rows);
    }

    public function fetchVar(string $sql, array $bindings = []) {

        $row = $this->connection->selectOne($sql, $bindings);

        if (!$row) return null;
        $arr = (array) $row;

        return reset($arr); // first column value
    }



    // -----------------------------
    // CRUD Methods
    // -----------------------------
    public function insert(string $table, array $data)
    {
        return $this->connection->table($table)->insertGetId($data);
    }

    public function update(string $table, array $data, string $where): int
    {
        return $this->connection->table($table)->whereRaw($where)->update($data);
    }

    public function delete(string $table, string $where): int
    {
        return $this->connection->table($table)->whereRaw($where)->delete();
    }


    // -----------------------------
    // Transactions
    // -----------------------------
    public function startTransaction(): bool
    {
        $this->connection->beginTransaction();
        return true;
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollBack();
    }


    // -----------------------------
    // Raw query
    // -----------------------------
    public function query(string $sql, array $bindings = [])
    {
        $queryType = strtolower(strtok(trim($sql), " "));

        if (in_array($queryType, ['select', 'show', 'describe', 'pragma'])) {
            return $this->fetchAll($sql, $bindings);
        } elseif ($queryType === 'insert') {
            $this->connection->insert($sql, $bindings);
            return $this->connection->getPdo()->lastInsertId();
        } elseif ($queryType === 'update' || $queryType === 'delete') {
            return $this->connection->affectingStatement($sql, $bindings);
        } else {
            return $this->connection->statement($sql, $bindings);
        }
    }

}
?>