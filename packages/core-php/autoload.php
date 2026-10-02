<?php
/**
 * Syntax Core Standalone PSR-4 Autoloader
 * Allows syntax/core-php to be used immediately without requiring external composer dump-autoload.
 */

spl_autoload_register(function ($class) {
    $prefix = 'Syntax\\Core\\';
    $baseDir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Load global helpers
if (file_exists(__DIR__ . '/src/Support/Helpers.php')) {
    require_once __DIR__ . '/src/Support/Helpers.php';
}
