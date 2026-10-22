<?php
require_once __DIR__ . '/default.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$requestUri = explode('/', $requestUri);
$scriptName = explode('/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

$appDir = [];
foreach ($requestUri as $key => $value) {
    if (isset($scriptName[$key]) && $scriptName[$key] === $value) {
        $appDir[] = $scriptName[$key];
    }
}

define('APP_DIR', rtrim(implode('/', $appDir), '/'));

$scheme = ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

define('BASE_URL', $scheme . '://' . $host . APP_DIR);

unset($config_file, $requestUri, $scriptName, $appDir, $scheme, $host);


if (defined('ACCESS_USER') && defined('ACCESS_PASSWORD')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'login.php';
    Login::instance()->dispatch();
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'wiki.php';

Wiki::instance()->dispatch();
