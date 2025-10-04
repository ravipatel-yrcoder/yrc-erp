<?php
//use Dotenv\Dotenv;
/**
 * EnvLoader - Load .env files into $_ENV and $_SERVER
 */
class TinyPHP_EnvLoader {

    private static bool $loaded = false;

	public static function load(string $path) {
		
        if( self::$loaded ) return;



        $file = rtrim($path, '/') . '/.env';
        if (!file_exists($file)) return;

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;

            [$name, $value] = array_map('trim', explode('=', $line, 2));
            $value = trim($value, "\"'"); // remove quotes

            // Only put in getenv(), NOT $_ENV or $_SERVER
            putenv("$name=$value");
        }

        self::$loaded = true;


        /*$file = rtrim($path, '/') . '/.env';

        if (!file_exists($file)) {
            return;
        }*/

        //$dotenv = Dotenv\Dotenv::createImmutable($path);
        //$dotenv->load(); // Loads into getenv() only, not $_ENV/$_SERVER

        //echo "<pre>";
        //print_r($_SERVER);
        //echo "</pre>";
        //die;

        //self::$loaded = true;

        /*
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line)
		{
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue; // skip empty lines or comments
            }

            // Split into key=value
            [$name, $value] = array_map('trim', explode('=', $line, 2));

            // Remove surrounding quotes
            $value = self::sanitizeValue($value);

            // Set into environment
            if (!isset($_ENV[$name])) $_ENV[$name] = $value;
            if (!isset($_SERVER[$name])) $_SERVER[$name] = $value;
            putenv("$name=$value");
        }
            */

	}
}

/**
 * ConfigLoader - Load configuration files and provide accessor
 */
final class TinyPHP_ConfigLoader
{
    private static array $configs = [];

    public static function load(string $path): void
    {
        foreach (glob($path . '/config/*.php') as $file) {
            $key = basename($file, '.php');
            self::$configs[$key] = require $file;
        }
    }

    public static function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $value = self::$configs;

        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return $default;
            }
        }

        return $value;
    }
}