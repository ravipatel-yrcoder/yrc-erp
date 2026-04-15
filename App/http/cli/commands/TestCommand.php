<?php

/**
 * Test Command
 *
 * Verifies the CLI stack is working end-to-end:
 *   - Bootstrap loaded correctly
 *   - Config is readable
 *   - DB connection is live
 *   - Options parsing works
 *
 * Usage:
 *   php console test
 *   php console test --company-id=5
 */
class Cli_Commands_TestCommand extends TinyPHP_Command
{
    protected string $signature   = 'test';
    protected string $description = 'Verify CLI bootstrap, config, and DB connection';

    public function handle(): int
    {
        $this->newline();
        $this->line("\033[32mTinyPHP CLI — Test Command\033[0m");
        $this->line(str_repeat('─', 50));
        $this->newline();

        // 1. Bootstrap / config
        $this->info("Checking config...");
        $appName  = config('app.name', '(not set)');
        $timezone = config('app.timezone', '(not set)');
        $env = getenv('APP_ENV') ?: '(not set)';

        $this->table([
            ['App name',  $appName],
            ['Timezone',  $timezone],
            ['Env',       $env],
        ]);
        $this->newline();

        // 2. Options parsing
        $this->info("Checking options parsing...");
        $companyId = $this->option('company-id');
        $dryRun    = $this->option('dry-run', false);

        $this->table([
            ['--company-id', $companyId !== null ? $companyId : '(not provided)'],
            ['--dry-run',    $dryRun ? 'true' : 'false'],
        ]);
        $this->newline();

        // 3. DB connection
        $this->info("Checking DB connection...");
        try {
            $result = DB()->fetchOne("SELECT 1 AS connected");

            if ($result && $result->connected == 1) {
                $this->success("DB connection OK.");
            } else {
                $this->error("DB responded but returned unexpected result.");
                return 1;
            }
        } catch (Throwable $e) {
            $this->error("DB connection failed: " . $e->getMessage());
            return 1;
        }
        $this->newline();

        // 4. Optional: verify company exists if --company-id was passed
        if ($companyId !== null) {
            $this->info("Checking company ID {$companyId}...");
            try {
                $row = DB()->fetchOne("SELECT id, name FROM companies WHERE id = ? LIMIT 1", [$companyId]);
                if ($row) {
                    $this->success("Company found: {$row->name}");
                } else {
                    $this->warn("No company found with ID {$companyId}.");
                }
            } catch (Throwable $e) {
                $this->error("Company lookup failed: " . $e->getMessage());
                return 1;
            }
            $this->newline();
        }

        $this->success("All checks passed. CLI is ready.");
        $this->newline();

        return 0;
    }
}
?>
