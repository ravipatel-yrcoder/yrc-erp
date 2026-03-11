<?php
// Currently this cache Sql Queries per request
class TinyPHP_SQLCache {

    private static $instance = null;
    private array $store = []; // In-memory requet level storage
    
    private function __construct() {}

    public static function getInstance(): self {
        if( !self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
     * Generate cache key
     */
    private function generateKey(string $connectionName, string $sql, array $bindings = []): string {
        return hash('sha256', $connectionName . '|' . $sql . '|' . json_encode($bindings, JSON_UNESCAPED_UNICODE));
    }


    /**
     * Remember pattern (like Laravel)
     */
    public function remember(TinyPHP_DB $db, string $sql, array $bindings, callable $callback, bool $enabled = true): mixed {

        if (!$enabled) {
            return $callback();
        }

        $connectionName = $db->getConnectionName();
        $key = $this->generateKey($connectionName, $sql, $bindings);

        if (array_key_exists($key, $this->store)) {
            return $this->store[$key];
        }

        $result = $callback();

        $this->store[$key] = $result;

        return $result;
    }

    /**
     * Remove entire request cache
     */
    public function flush(): void {
        $this->store = [];
    }

    /**
     * Remove specific entry
     */
    public function forget(TinyPHP_DB $db, string $sql, array $bindings = []): void {
        
        $key = $this->generateKey($db->getConnectionName(), $sql, $bindings);
        unset($this->store[$key]);
    }
}