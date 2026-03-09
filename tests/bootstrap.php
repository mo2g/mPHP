<?php
/**
 * PHPUnit Bootstrap
 * 
 * Initializes the mPHP framework environment for testing
 * without starting the HTTP server or router.
 */

// Define constants BEFORE Composer autoload triggers mPHP.php loading
define('INIT_MPHP', 'phpunit');
define('INDEX_PATH', __DIR__ . '/fixtures/');
define('WEB_PATH',   __DIR__ . '/fixtures/');
define('MPHP_PATH',  dirname(__DIR__) . '/');

// These must be defined before mPHP.php is loaded by Composer classmap
defined('LIBS_PATH') or define('LIBS_PATH',  INDEX_PATH . 'libs/');
defined('CACHE_PATH') or define('CACHE_PATH', sys_get_temp_dir() . '/mphp_test_cache/');
defined('CACHE_HTML_PATH') or define('CACHE_HTML_PATH', CACHE_PATH . 'html/');
defined('TPL_C_PATH') or define('TPL_C_PATH', CACHE_PATH . 'tpl_c/');
defined('TPL_PATH') or define('TPL_PATH',   LIBS_PATH . 'tpl/');
defined('LOG_PATH') or define('LOG_PATH',   sys_get_temp_dir() . '/mphp_test_logs/');
defined('STATIC_PATH') or define('STATIC_PATH', INDEX_PATH . 'static/');
defined('CONTROLLERS_PATH') or define('CONTROLLERS_PATH', LIBS_PATH . 'controllers/');
defined('MODELS_PATH') or define('MODELS_PATH', LIBS_PATH . 'models/');
defined('DAOS_PATH') or define('DAOS_PATH',   LIBS_PATH . 'daos/');
defined('SERVICES_PATH') or define('SERVICES_PATH', LIBS_PATH . 'services/');
defined('CONF_PATH') or define('CONF_PATH',  INDEX_PATH . 'conf/');

// Create temp directories
foreach ([CACHE_PATH, CACHE_HTML_PATH, TPL_C_PATH, LOG_PATH, LIBS_PATH, TPL_PATH, CONTROLLERS_PATH, MODELS_PATH, DAOS_PATH, SERVICES_PATH] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Load Composer autoloader (which loads mPHP.php and model classes via classmap)
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load helper functions and register in mPHP's include tracker to prevent double-loading
$functionsPath = MPHP_PATH . 'inc/functions.php';
require_once $functionsPath;
mPHP::$include_file_lists[$functionsPath] = true;

// Initialize global CFG
$GLOBALS['CFG'] = [];
$GLOBALS['CFG']['start_time'] = microtime(1);
$GLOBALS['CFG']['db'] = [
    'count' => 0,
    'select' => ['totle' => 0, 'error' => 0],
    'insert' => ['totle' => 0, 'error' => 0],
    'update' => ['totle' => 0, 'error' => 0],
    'delete' => ['totle' => 0, 'error' => 0],
];
$GLOBALS['CFG']['template'] = [
    'tag_left' => '<!--#',
    'tag_right' => '#-->',
];
$GLOBALS['CFG']['debug'] = true;
$GLOBALS['CFG']['router'] = [];

// Set CFG on mPHP class
mPHP::$CFG = &$GLOBALS['CFG'];
