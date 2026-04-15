<?php

/**
 * TinyPHP_Command
 *
 * Base class for all CLI commands. Extend this class to create a new command.
 *
 * Usage:
 *   class Cli_Commands_MyCommand extends TinyPHP_Command {
 *       protected string $signature   = 'my:command';
 *       protected string $description = 'What this command does';
 *
 *       public function handle(): int {
 *           $this->info("Running...");
 *           return 0;
 *       }
 *   }
 */
abstract class TinyPHP_Command
{
    protected string $signature   = '';
    protected string $description = '';

    /** Parsed --key=value and --flag options from $argv */
    private array $options = [];


    // -------------------------------------------------------------------------
    // Framework internals — called by TinyPHP_Console, not by command authors
    // -------------------------------------------------------------------------

    /**
     * Inject parsed options (called by the dispatcher before handle()).
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Entry point called by the dispatcher.
     * Implement your command logic here.
     *
     * @return int  Exit code — 0 = success, 1 = failure
     */
    abstract public function handle(): int;


    // -------------------------------------------------------------------------
    // Option access
    // -------------------------------------------------------------------------

    /**
     * Get a named option value.
     *
     * --limit=50   → option('limit')        returns "50"
     * --dry-run    → option('dry-run')       returns true
     * --company-id → option('company-id', null) returns null if not passed
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    protected function option(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $this->options)) {
            return $default;
        }

        return $this->options[$key];
    }

    /**
     * Check whether an option was provided at all.
     */
    protected function hasOption(string $key): bool
    {
        return array_key_exists($key, $this->options);
    }


    // -------------------------------------------------------------------------
    // Output helpers
    // -------------------------------------------------------------------------

    protected function line(string $message): void
    {
        echo $message . PHP_EOL;
    }

    protected function info(string $message): void
    {
        echo "\033[36m[INFO]\033[0m  " . $message . PHP_EOL;
    }

    protected function success(string $message): void
    {
        echo "\033[32m[OK]\033[0m    " . $message . PHP_EOL;
    }

    protected function warn(string $message): void
    {
        echo "\033[33m[WARN]\033[0m  " . $message . PHP_EOL;
    }

    protected function error(string $message): void
    {
        echo "\033[31m[ERROR]\033[0m " . $message . PHP_EOL;
    }

    /**
     * Print a blank line.
     */
    protected function newline(): void
    {
        echo PHP_EOL;
    }

    /**
     * Print a simple key→value table to the terminal.
     *
     * @param array $rows  [ ['Label', 'Value'], ... ]
     */
    protected function table(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        // Find max width of first column
        $maxKey = max(array_map(fn($r) => strlen((string)($r[0] ?? '')), $rows));

        foreach ($rows as $row) {
            $key   = str_pad((string)($row[0] ?? ''), $maxKey);
            $value = (string)($row[1] ?? '');
            echo "  \033[90m{$key}\033[0m  {$value}" . PHP_EOL;
        }
    }
}
?>
