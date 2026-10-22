<?php

if (!defined('APP_STARTED') && PHP_SAPI !== 'cli') {
    die('Forbidden!');
}

$requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$projectRoot = realpath(__DIR__);
$requestedFile = realpath(__DIR__ . $requestPath);

if ($projectRoot !== false
    && $requestedFile !== false
    && str_starts_with($requestedFile, $projectRoot . DIRECTORY_SEPARATOR)
    && is_file($requestedFile)
) {
    return false; // serve the requested resource as-is.
} else {
    require_once __DIR__ . '/index.php';
}
