<?php

// 1. Require Shared Core Autoloader
$autoloader = dirname(__DIR__, 3) . '/packages/core-php/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
} else {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use Syntax\Core\Application;

// 2. Initialize School Application
$app = new Application(dirname(__DIR__));

// 3. Register Routes
$router = $app->router();
require_once dirname(__DIR__) . '/routes/web.php';

// 4. Run & Dispatch
$app->run();
