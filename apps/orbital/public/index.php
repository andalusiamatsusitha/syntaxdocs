<?php

// 1. Require Shared Core Autoloader
$coreAutoloader = dirname(__DIR__, 3) . '/packages/core-php/autoload.php';
if (file_exists($coreAutoloader)) {
    require_once $coreAutoloader;
} else {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// 2. Register Application Namespace Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\Orbital\\';
    $baseDir = dirname(__DIR__) . '/src/';
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

use Syntax\Core\Application;

// 3. Initialize Orbital Application
$app = new Application(dirname(__DIR__));
$router = $app->router();

// 4. Register API and Web Routes
require_once dirname(__DIR__) . '/routes/api.php';
require_once dirname(__DIR__) . '/routes/web.php';

// 5. Dispatch Request
$app->run();
