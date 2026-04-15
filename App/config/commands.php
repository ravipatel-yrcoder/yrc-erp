<?php

/**
 * Console Command Registry
 *
 * Map command names to their handler classes.
 * Classes are resolved by the Loader from App/http/cli/commands/.
 *
 * Naming convention:
 *   Cli_Commands_{Name}Command  →  App/http/cli/commands/{Name}Command.php
 *
 * Usage:
 *   php console <command-name> [--option=value] [--flag]
 */

return [
    'test' => Cli_Commands_TestCommand::class,
    'webhook:process' => Cli_Commands_WebhookProcessCommand::class,
];
