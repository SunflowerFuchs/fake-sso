<?php
declare(strict_types=1);

use SunflowerFuchs\FakeSso\Router;

require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Config.php';

try {
    Router::start();
} catch (Exception $e) {
    // hide the actual error message for security reasons
    echo "Unexpected error occurred";
}