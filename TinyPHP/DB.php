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

    /**
     * Get a database connection instance
     *
     * @param string|null $connectionName
     * @return \Illuminate\Database\Connection
     * @throws \Exception
     */
	public static function getInstance($connectionName=null) {
        
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
    	if (empty(self::$booted)) {

        	$capsule->setAsGlobal();
        	$capsule->bootEloquent();
        	self::$booted = true;
    	}

		$connection = $capsule->getConnection($capsuleConnectioName);		

		// Store instance
        self::$instances[$connectionName] = $connection;
		
        return $connection;		
    }
}
?>