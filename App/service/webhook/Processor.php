<?php

/**
 * Service_Webhook_Processor
 *
 * Processes pending webhook_logs records into CRM leads.
 *
 * Designed to be called from the CLI command (WebhookProcessCommand)
 * but has no CLI dependency — can be called from anywhere.
 *
 * Flow per log:
 *   fetch → lock → duplicate check → parse → fetch stage → create lead → update status
 *
 * On failure:
 *   attempt 1 → status = retrying, retry_at = +5 min
 *   attempt 2 → status = retrying, retry_at = +30 min
 *   attempt 3 → status = failed (no more retries)
 */
class Service_Webhook_Processor extends Service_Base {

    private const MAX_ATTEMPTS = 3;
    private const RETRY_DELAYS = [
        1 => '+5 minutes',
        2 => '+30 minutes',
    ];

    /**
     * Source → parser class map.
     * Add new sources here as integrations are built.
     */
    private const PARSERS = [
        'indiamart' => Service_Webhook_Parser_Indiamart::class,
    ];


    private const POSSIBLE_FOLLOWUP_LEAD_TYPES = [
        'PNS CALL',
        'CATALOG VIEW',
    ];    


    /** @var callable|null  Optional callback for per-log progress output */
    private $logger;

    /** @var array  Per-company default stage cache [ companyId => {id, name} ] */
    private array $stageCache = [];

    /** @var array  Per-company admin user cache [ companyId => {id} ] */
    private array $ownerCache = [];


    public function __construct(?callable $logger = null) {
        $this->logger = $logger;
    }


    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Process a batch of eligible webhook logs.
     *
     * @param  array $options {
     *     company_id?: int|null,
     *     source?:     string|null,
     *     limit?:      int,
     *     dry_run?:    bool,
     * }
     * @return array{processed: int, ignored: int, failed: int, skipped: int}
     */
    public function run(array $options = []): array
    {
        $companyId = $options['company_id'] ?? null;
        $source = $options['source']     ?? null;
        $limit = (int)($options['limit'] ?? 50);
        $dryRun = (bool)($options['dry_run'] ?? false);

        $logs = $this->fetchEligibleLogs($companyId, $source, $limit);

        $counts = ['processed' => 0, 'ignored' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($logs as $log) {
            $result = $this->processLog($log, $dryRun);
            $counts[$result]++;
        }

        return $counts;
    }


    // -------------------------------------------------------------------------
    // Fetch
    // -------------------------------------------------------------------------

    private function fetchEligibleLogs(?int $companyId, ?string $source, int $limit): array
    {
        $sql = "SELECT * FROM webhook_logs WHERE status IN ('received', 'retrying') AND (retry_at IS NULL OR retry_at <= NOW())";
        
        $bindings = [];
        if ($companyId !== null) {
            $sql .= " AND company_id = ?";
            $bindings[] = $companyId;
        }

        if ($source !== null) {
            $sql .= " AND source = ?";
            $bindings[] = $source;
        }

        $sql .= " ORDER BY created_at ASC LIMIT ?";
        $bindings[] = $limit;

        return DB()->fetchAll($sql, $bindings);
    }


    // -------------------------------------------------------------------------
    // Per-log processing
    // -------------------------------------------------------------------------

    /**
     * Process a single log record.
     *
     * @return string  'processed' | 'ignored' | 'failed' | 'skipped'
     */
    private function processLog(object $log, bool $dryRun): string
    {
        $logId = (int)$log->id;
        $companyId = (int)$log->company_id;
        $source = strtolower(trim($log->source));

        $this->log("Processing log #{$logId} — {$source}");

        // ── Wrap in transaction (supports dry-run rollback) ───────────────────
        $db = DB();
        $db->startTransaction();

        // ── Lock ─────────────────────────────────────────────────────────────
        $locked = $this->lockLog($logId, $log->status);
        if (!$locked) {
            
            $db->rollback();

            $this->log("Skipped — grabbed by another worker");
            return 'skipped';
        }

        try {

            $result = $this->doProcess($log, $logId, $companyId, $source);
            
            if ($dryRun) {

                $db->rollback();
                $this->log("  [DRY RUN] rolled back — outcome would be: {$result}");

            } else {

                $db->commit();
            }

            return $result;

        } catch (Exception $e) {
            
            $db->rollback();
            $this->handleFailure($logId, (int)$log->attempts, $e->getMessage());
            return 'failed';
        }
    }


    /**
     * Inner processing logic — runs inside the transaction.
     *
     * @return string  'processed' | 'ignored'
     * @throws Service_Exception  on any processing error
     */
    private function doProcess(object $log, int $logId, int $companyId, string $source): string
    {
        $this->log("  DEBUG: Do Process Start");

        // ── Source routing ────────────────────────────────────────────────────
        if (!isset(self::PARSERS[$source])) {
            $this->markIgnored($logId, "No parser available for source: {$source}");
            $this->log("  Ignored — no parser for source '{$source}'");
            return 'ignored';
        }

        $this->log("  DEBUG: After Source Routing");

        // ── Parse payload ─────────────────────────────────────────────────────
        $parserClass = self::PARSERS[$source];
        $parsed = $parserClass::parse((string)$log->raw_payload);

        $this->log("  DEBUG: After Data Parsed");

        $leadPayload = $parsed['lead'];
        $historyMeta = $parsed['meta'];
        $historyMeta["webhook_log_id"] = $logId;
        
        $phone = $leadPayload['phone'] ?? null;
        $email = $leadPayload['email'] ?? null;
        $leadType = $leadPayload['lead_type'] ?? "";

        $duplicate = false;
        if( in_array(strtoupper($leadType), self::POSSIBLE_FOLLOWUP_LEAD_TYPES) && ($phone || $email) ) {

        $this->log("  DEBUG: Dup Check Start");
            
            $dupCheckSql = "SELECT id, lead_code FROM crm_leads WHERE company_id = ? AND source = ? AND status = ?";
            $dupCheckSqlBinding = [$companyId, $source, "active"];

            $orConditions = [];
            if ($phone) {
                $orConditions[] = "phone = ?";
                $dupCheckSqlBinding[] = $phone;
            }

            if ($email) {
                $orConditions[] = "email = ?";                
                $dupCheckSqlBinding[] = $email;
            }

            // Apply OR group only if at least one exists
            if (!empty($orConditions)) {
                $dupCheckSql .= " AND (" . implode(' OR ', $orConditions) . ")";                
            }
            $dupCheckSql .= " LIMIT 1";

            $duplicate = DB()->fetchOne($dupCheckSql, $dupCheckSqlBinding);

            $this->log("  DEBUG: Dup Check End");
        }


        $processLogMsg = "";
        if( $duplicate )
        {
            $this->log("  DEBUG: Dup Found and Start Dup Process");

            // add as note/log
            $dupLeadId = $duplicate->id;
            $dupLeadCode = $duplicate->lead_code;

            $userId = $this->resolveAdminUserId($companyId);

            // ── Add Lead Note/Log ───────────────────────────────────────────────────
            $noteTitle = match (strtoupper($leadType)) {
                'PNS CALL' => 'Follow-up Call from IndiaMART',
                'CATALOG VIEW' => 'Catalog View from IndiaMART',
                default => 'Activity from IndiaMART',
            };

            $leadService = new Service_Crm_Lead(new Service_TenantContext($companyId, $userId));
            $leadService->logHistory($dupLeadId, [
                'log_type' => 'system',
                'title' => $noteTitle,
                'meta' => $historyMeta,
            ]);            

            $processLogMsg = "  Duplicate lead: #{$dupLeadCode}, added note/log";
        }
        else
        {
            $this->log("  DEBUG: New Lead Create Start");

            // create new lead            
            // ── Resolve default stage and admin user (cached per company) ────────
            $stage = $this->resolveDefaultStage($companyId);
            $userId = $this->resolveAdminUserId($companyId);

            $this->log("  DEBUG: After resolved stage and user");

            $leadPayload['stage_id'] = $stage ? (int) $stage->id : null;
            $leadPayload["log_title"] = "Lead created from IndiaMart";            
            $leadPayload["log_meta"] = array_filter($historyMeta, fn($v) => $v !== null && $v !== '');
            
            // ── Create CRM lead ───────────────────────────────────────────────────
            try {

                $this->log("  DEBUG: Create Lead Start Try");
                $this->log("  DEBUG: ".print_r($leadPayload, true));

                $leadService = new Service_Crm_Lead(new Service_TenantContext($companyId, $userId));
                $result = $leadService->create($leadPayload);
            } catch(Service_Exception $e) {
                $this->log("  DEBUG: Lead Create Exception");
                $this->log("  DEBUG: ".$e->getMessage());

                throw $e;
            }            

            if (!($result['success'] ?? false)) {

                $this->log("  DEBUG: Triggered Error in Lead Create");
                
                $errors = $result['errors'] ?? [];
                $msg = is_array($errors) ? implode(', ', $errors) : 'Unknown validation error';

                $this->log("  DEBUG: Error: ".$msg);

                throw new Service_Exception("Lead creation failed: {$msg}");                
            }
            
            $leadCode = $result['data']['lead_code'];

            $processLogMsg = "  Lead created: {$leadCode} ({$leadPayload['display_name']})";

            $this->log("  DEBUG: New Lead Create End");
        }

        
        // ── Mark log as processed ─────────────────────────────────────────────
        DB()->fetchOne("UPDATE webhook_logs SET status = 'processed', processed_at = NOW() WHERE id = ?", [$logId]);

        $this->log($processLogMsg);

        return 'processed';
    }


    // -------------------------------------------------------------------------
    // Per-company resolvers (cached for the duration of the run)
    // -------------------------------------------------------------------------

    /**
     * Fetch the first active CRM stage for a company.
     * Result is cached — only one DB query per company per run.
     *
     * @throws Service_Exception if no active stage exists
     */
    private function resolveDefaultStage(int $companyId): object
    {
        if (!isset($this->stageCache[$companyId])) {

            $stage = DB()->fetchOne("SELECT id, name FROM crm_stages WHERE company_id = ? AND status = ? ORDER BY sort_order ASC LIMIT 1", [$companyId, 'active']);
            if( !$stage ) {
                $stage = null;
            }

            /*if (!$stage) {
                throw new Service_Exception("No active CRM stage found for company {$companyId}");
            }*/

            $this->stageCache[$companyId] = $stage;
        }

        return $this->stageCache[$companyId];
    }


    /**
     * Fetch the admin user ID for a company.
     * Result is cached — only one DB query per company per run.
     *
     * @throws Service_Exception if no admin user exists
     */
    private function resolveAdminUserId(int $companyId): int
    {
        if (!isset($this->ownerCache[$companyId])) {

            $owner = DB()->fetchOne("SELECT id FROM users WHERE company_id = ? AND role = 'admin' LIMIT 1", [$companyId]);
            if (!$owner) {
                throw new Service_Exception("No admin user found for company {$companyId}");
            }

            $this->ownerCache[$companyId] = (int)$owner->id;
        }

        return $this->ownerCache[$companyId];
    }


    // -------------------------------------------------------------------------
    // Locking
    // -------------------------------------------------------------------------

    /**
     * Attempt to lock the log record by setting status = 'processing'.
     * Returns false if another worker already grabbed it.
     */
    private function lockLog(int $logId, string $currentStatus): bool
    {
        $affected = DB()->query("UPDATE webhook_logs SET status = 'processing' WHERE id = ? AND status = ?", [$logId, $currentStatus]);
        return (int)$affected > 0;
    }


    // -------------------------------------------------------------------------
    // Status updates
    // -------------------------------------------------------------------------

    private function markIgnored(int $logId, string $reason): void
    {
        DB()->query(
            "UPDATE webhook_logs SET status = 'ignored', failure_reason = ? WHERE id = ?",
            [$reason, $logId]
        );
    }

    private function handleFailure(int $logId, int $attempts, string $reason): void
    {
        $nextAttempt = $attempts + 1;

        $this->log("  Failed (attempt {$nextAttempt}/" . self::MAX_ATTEMPTS . "): {$reason}");

        if ($nextAttempt >= self::MAX_ATTEMPTS) {
            // Permanently failed — no more retries
            DB()->query(
                "UPDATE webhook_logs SET status = 'failed', attempts = ?, failure_reason = ? WHERE id = ?",
                [$nextAttempt, $reason, $logId]
            );
            $this->log("  Permanently failed — max attempts reached");
        } else {
            // Schedule retry with exponential backoff
            $delay    = self::RETRY_DELAYS[$nextAttempt] ?? '+2 hours';
            $retryAt  = date('Y-m-d H:i:s', strtotime($delay));

            DB()->query(
                "UPDATE webhook_logs SET status = 'retrying', attempts = ?, failure_reason = ?, retry_at = ? WHERE id = ?",
                [$nextAttempt, $reason, $retryAt, $logId]
            );
            $this->log("  Retrying at: {$retryAt}");
        }
    }


    // -------------------------------------------------------------------------
    // Internal logger
    // -------------------------------------------------------------------------

    private function log(string $message): void
    {
        if ($this->logger) {
            ($this->logger)($message);
        }
    }
}
?>
