<?php
declare(strict_types=1);

use SunflowerFuchs\FakeSso\Router;
use SunflowerFuchs\FakeSso\SsoException;

require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Config.php';

try {
    Router::start();
}catch (SsoException $e){
    // These are allowed to be output
    echo $e->getMessage();
    http_response_code($e->getCode());
} catch (Exception $e) {
    // for all other exceptions we hide the actual error message for security reasons
    echo "Unexpected error occurred";
    http_response_code(500);
}