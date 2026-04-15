<?php

/**
 * TinyPHP_Console
 *
 * CLI dispatcher — the console equivalent of TinyPHP_Front.
 *
 * Resolves a command name from $argv, instantiates the matching command class,
 * parses --options, and calls handle(). Exits with the command's return code.
 *
 * Usage (from the `console` entry point):
 *   TinyPHP_Console::run($argv);
 */
class TinyPHP_Console
{
    /**
     * Run the console application.
     *
     * @param array $argv  PHP's $argv from the entry point
     */
    public static function run(array $argv): void
    {
        $commandName = $argv[1] ?? 'list';
        $rawArgs     = array_slice($argv, 2);

        $commands = self::loadCommands();

        // Built-in: list all registered commands
        if ($commandName === 'list') {
            self::printCommandList($commands);
            exit(0);
        }

        // Resolve command class
        if (!isset($commands[$commandName])) {
            self::printError("Command \"{$commandName}\" not found.");
            self::newline();
            self::printCommandList($commands);
            exit(1);
        }

        $class = $commands[$commandName];

        if (!class_exists($class)) {
            self::printError("Command class \"{$class}\" could not be loaded.");
            exit(1);
        }

        /** @var TinyPHP_Command $command */
        $command = new $class();
        $command->setOptions(self::parseOptions($rawArgs));

        try {
            $exitCode = $command->handle();
            exit(is_int($exitCode) ? $exitCode : 0);
        } catch (Throwable $e) {
            self::printError($e->getMessage());
            if (getenv('APP_DEBUG') === 'true') {
                echo $e->getTraceAsString() . PHP_EOL;
            }
            exit(1);
        }
    }


    // -------------------------------------------------------------------------
    // Option parsing
    // -------------------------------------------------------------------------

    /**
     * Parse $argv tokens into an options array.
     *
     * --key=value  → ['key' => 'value']
     * --flag       → ['flag' => true]
     *
     * @param  array $args
     * @return array
     */
    private static function parseOptions(array $args): array
    {
        $options = [];

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $arg = substr($arg, 2); // strip leading --
                if (str_contains($arg, '=')) {
                    [$key, $value] = explode('=', $arg, 2);
                    $options[trim($key)] = trim($value);
                } else {
                    $options[trim($arg)] = true; // boolean flag
                }
            }
        }

        return $options;
    }


    // -------------------------------------------------------------------------
    // Command registry
    // -------------------------------------------------------------------------

    /**
     * Load the command map from App/config/commands.php.
     *
     * @return array<string, string>  [ 'command:name' => ClassName ]
     */
    private static function loadCommands(): array
    {
        $file = APP_PATH . '/config/commands.php';

        if (!file_exists($file)) {
            return [];
        }

        $commands = require $file;
        return is_array($commands) ? $commands : [];
    }


    // -------------------------------------------------------------------------
    // Output helpers (static, used before a command is instantiated)
    // -------------------------------------------------------------------------

    private static function printCommandList(array $commands): void
    {
        echo PHP_EOL;
        echo "\033[32mTinyPHP Console\033[0m" . PHP_EOL;
        echo str_repeat('─', 50) . PHP_EOL;
        echo PHP_EOL;
        echo "\033[33mUsage:\033[0m" . PHP_EOL;
        echo "  php console <command> [--option=value] [--flag]" . PHP_EOL;
        echo PHP_EOL;
        echo "\033[33mAvailable commands:\033[0m" . PHP_EOL;

        if (empty($commands)) {
            echo "  (no commands registered)" . PHP_EOL;
        } else {
            // Load each class to get description, fall back to empty string
            $maxLen = max(array_map('strlen', array_keys($commands)));
            foreach ($commands as $name => $class) {
                $description = '';
                if (class_exists($class)) {
                    $instance    = new $class();
                    $description = $instance->getDescription();
                }
                $padded = str_pad($name, $maxLen + 2);
                echo "  \033[36m{$padded}\033[0m{$description}" . PHP_EOL;
            }
        }

        echo PHP_EOL;
    }

    private static function printError(string $message): void
    {
        echo "\033[31m[ERROR]\033[0m " . $message . PHP_EOL;
    }

    private static function newline(): void
    {
        echo PHP_EOL;
    }
}
?>
