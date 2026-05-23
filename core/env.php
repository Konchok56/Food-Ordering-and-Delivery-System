<?php
/**
 * SwiftBite — Lightweight .env Loader
 * Parses a .env file and loads values into getenv() / $_ENV.
 * No external dependencies required.
 */

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return; // Silently skip — fall back to hardcoded defaults in config.php
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        // Split on first '='
        $eqPos = strpos($line, '=');
        if ($eqPos === false) {
            continue;
        }

        $key   = trim(substr($line, 0, $eqPos));
        $value = trim(substr($line, $eqPos + 1));

        // Remove surrounding quotes if present
        if (
            (strlen($value) >= 2) &&
            (($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
             ($value[0] === "'" && $value[strlen($value) - 1] === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        // Only set if not already defined by the real environment
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

/**
 * Get an environment variable with an optional default.
 */
function env(string $key, $default = null)
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    // Cast common string representations
    switch (strtolower($value)) {
        case 'true':  return true;
        case 'false': return false;
        case 'null':  return null;
    }

    return $value;
}
