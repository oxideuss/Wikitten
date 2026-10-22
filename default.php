<?php

define('APP_STARTED', true);

// Conditionally load configuration from a config.php file in
// the site root, if it exists.
if (is_file($configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config.php')) {
    require_once $configFile;
}

foreach ([
    'APP_NAME' => 'Wikitten',
    'LIBRARY' => __DIR__ . DIRECTORY_SEPARATOR . 'library',
    'DEFAULT_FILE' => 'index.md',
    'USE_WIKITTEN_LOGO' => true,
    'USE_DARK_THEME' => false,
    'USE_PAGE_METADATA' => true,
    'ENABLE_EDITING' => false,
    'ENABLE_PASTEBIN' => false,
    'PASTEBIN_API_KEY' => false,
    'EXTERNAL_LINK_TARGET' => '_blank',
    'INTERNAL_WIKI_LINK' => true,
    'CUSTOM_MARKDOWN_STYLESHEET' => '',
] as $name => $value) {
    if (!defined($name)) {
        define($name, $value);
    }
}

define('PLUGINS', __DIR__ . DIRECTORY_SEPARATOR . 'plugins');

if (!function_exists('e')) {
    function e(mixed $dirty): string
    {
        return htmlspecialchars(is_scalar($dirty) ? (string) $dirty : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
