<?php

/**
 * Env – Minimal .env file loader for CBE LMS
 *
 * Parses KEY=VALUE pairs from a .env file and populates:
 *   - $_ENV
 *   - $_SERVER
 *   - getenv()  (via putenv)
 *
 * Usage (called once in public/index.php before anything else):
 *   Env::load(BASE_PATH . '/.env');
 *
 * Rules:
 *   - Lines starting with # are comments and are ignored.
 *   - Blank lines are ignored.
 *   - Values may optionally be wrapped in single or double quotes.
 *   - Existing environment variables are NOT overwritten so that
 *     server-level values (Apache SetEnv, etc.) always take priority.
 */
class Env
{
    /**
     * Load a .env file into the process environment.
     *
     * @param string $filePath Absolute path to the .env file.
     * @throws RuntimeException if the file cannot be read.
     */
    public static function load(string $filePath): void
    {
        if (!is_readable($filePath)) {
            throw new RuntimeException(
                "Environment file not found or not readable: {$filePath}\n" .
                "Please copy .env.example to .env and fill in your values."
            );
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and malformed lines
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            $key   = trim($key);
            $value = trim($value);

            // Strip inline comments (value must not be quoted for this to apply)
            if (!self::isQuoted($value) && ($pos = strpos($value, ' #')) !== false) {
                $value = substr($value, 0, $pos);
                $value = rtrim($value);
            }

            // Remove surrounding quotes
            $value = self::stripQuotes($value);

            // Do not overwrite values already set at the OS / server level
            if (array_key_exists($key, $_ENV) || array_key_exists($key, $_SERVER) || getenv($key) !== false) {
                continue;
            }

            putenv("{$key}={$value}");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }

    /**
     * Retrieve a value from the environment, with an optional default.
     *
     * @param string      $key
     * @param string|null $default
     * @return string|null
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return ($value !== false && $value !== null) ? (string)$value : $default;
    }

    // ----------------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------------

    private static function isQuoted(string $value): bool
    {
        return (strlen($value) >= 2)
            && (
                ($value[0] === '"'  && substr($value, -1) === '"')  ||
                ($value[0] === "'"  && substr($value, -1) === "'")
            );
    }

    private static function stripQuotes(string $value): string
    {
        if (self::isQuoted($value)) {
            return substr($value, 1, -1);
        }
        return $value;
    }
}
