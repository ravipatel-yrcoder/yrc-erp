<?php

/**
 * Webhook Process Command
 *
 * Processes pending webhook_logs records into CRM leads.
 *
 * Usage:
 *   php console webhook:process
 *   php console webhook:process --company-id=5
 *   php console webhook:process --source=indiamart
 *   php console webhook:process --limit=100
 *   php console webhook:process --company-id=5 --dry-run
 *
 * Cron (shared hosting — every minute):
 *   * * * * * php /full/path/to/console webhook:process
 */

class Cli_Commands_WebhookProcessCommand extends TinyPHP_Command
{
    protected string $signature = 'webhook:process';
    protected string $description = 'Process pending webhook logs into CRM leads';

    private const DEFAULT_LIMIT = 50;

    public function handle(): int
    {
        $companyId = $this->option('company-id') !== null ? (int)$this->option('company-id') : null;
        $source = $this->option('source') !== null ? strtolower(trim($this->option('source'))) : null;
        $limit = $this->option('limit') !== null ? (int)$this->option('limit') : self::DEFAULT_LIMIT;
        $dryRun = (bool)$this->option('dry-run', false);

        // ── Header ───────────────────────────────────────────────────────────
        $this->newline();
        $this->line("\033[32mWebhook Processor\033[0m");
        $this->line(str_repeat('─', 50));

        $this->table([
            ['Company', $companyId !== null ? "#{$companyId}" : 'All tenants'],
            ['Source', $source !== null ? $source : 'All sources'],
            ['Limit', $limit],
            ['Dry run', $dryRun ? 'YES — transaction will be rolled back' : 'No'],
        ]);

        $this->newline();

        if ($dryRun) {
            $this->warn("Dry run mode — all queries will execute inside a transaction that gets rolled back. No data will be saved.");
            $this->newline();
        }

        // ── Run processor ────────────────────────────────────────────────────

        $processor = new Service_Webhook_Processor(function(string $message) {
            $this->line("  " . $message);
        });

        try {

            $counts = $processor->run([
                'company_id' => $companyId,
                'source' => $source,
                'limit' => $limit,
                'dry_run' => $dryRun,
            ]);

        } catch (\Throwable $e) {
            $this->error("Processor failed: " . $e->getMessage());
            return 1;
        }

        // ── Summary ──────────────────────────────────────────────────────────
        $total = array_sum($counts);

        $this->newline();
        $this->line(str_repeat('─', 50));

        if ($total === 0) {
            $this->info("No eligible webhook logs found.");
        } else {
            $this->table([
                ['Processed', $counts['processed']],
                ['Ignored', $counts['ignored']],
                ['Failed', $counts['failed']],
                ['Skipped', $counts['skipped']],
                ['Total', $total],
            ]);
        }

        $this->newline();
        if ($dryRun) {
            $this->warn("Dry run complete — no changes were committed.");
        } else {
            $this->success("Done.");
        }

        $this->newline();

        return $counts['failed'] > 0 ? 1 : 0;
    }
}
?>
