<?php
/**
 * Service_PlatformBase — base class for platform-level services.
 *
 * Platform services operate on shared SaaS data (subscriptions, modules,
 * features, roles) that lives in platform_db. They are called before or
 * independent of a per-company TenantContext, so they do not receive one.
 *
 * Contrast with Service_Base which is for tenant-scoped services that
 * receive a TenantContext and resolve a per-company DB connection.
 *
 * Services that extend this class:
 *   - Service_Subscription  — subscription + seat queries
 */
abstract class Service_PlatformBase
{
    protected $db;

    public function __construct()
    {
        //$this->db = DB('platform_db');
        $this->db = DB();
    }

    public function addError($err, $idx = null): void
    {
        if (is_array($err)) {
            foreach ($err as $key => $msg) {
                if (is_numeric($key)) {
                    $this->errors[] = $msg;
                } else {
                    $this->errors[$key] = $msg;
                }
            }
        } else {
            if (empty($idx)) {
                $this->errors[] = $err;
            } else {
                $this->errors[$idx] = $err;
            }
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function resetErrors(): void
    {
        $this->errors = [];
    }

    private array $errors = [];
}
?>
