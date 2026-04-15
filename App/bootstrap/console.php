<?php

/**
 * Console Bootstrap
 *
 * Minimal bootstrap for CLI commands. Mirrors app.php but skips everything
 * that is HTTP-specific: no session, no request parsing, no route mapping,
 * no middleware registration, no view rendering.
 *
 * Provides: env, config, timezone, DB (lazy — connects on first DB() call).
 */

// Load environment variables
TinyPHP_EnvLoader::load(APP_PATH);

// Load all config files from App/config/
TinyPHP_ConfigLoader::load(APP_PATH);

// Set default timezone from config
date_default_timezone_set(config('app.timezone'));
