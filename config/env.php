<?php
// Function to load environment variables from .env file
if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes from the value if present
                if ((startsWith($value, '"') && endsWith($value, '"')) ||
                    (startsWith($value, "'") && endsWith($value, "'"))) {
                    $value = trim($value, '"\'');
                }

                if (!getenv($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
}

if (!function_exists('startsWith')) {
    function startsWith($haystack, $needle) {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

if (!function_exists('endsWith')) {
    function endsWith($haystack, $needle) {
        return substr($haystack, -strlen($needle)) === $needle;
    }
}
?>