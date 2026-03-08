<?php
/**
 * mPHP Demo - Public Entry Point
 * 
 * This file is the ONLY PHP file in the public/ directory.
 * All framework code and application logic stays outside public/,
 * preventing direct access to source files.
 * 
 * Usage:
 *   1. cd demos/composer-example
 *   2. composer install
 *   3. php -S localhost:8080 -t public/
 *   4. Open http://localhost:8080 in your browser
 */
define('INIT_MPHP', 'composer-demo');
define('INDEX_PATH', dirname(__DIR__).'/');  // Points to project root (one level up)
define('WEB_PATH',   __DIR__.'/');           // Points to public/ (document root)

// Load mPHP framework via Composer
if (file_exists(INDEX_PATH . 'vendor/autoload.php')) {
    require INDEX_PATH . 'vendor/autoload.php';
}

// Or load mPHP directly (without Composer)
if (!class_exists('mPHP')) {
    define('MPHP_PATH', realpath(INDEX_PATH . '../../') . '/');
    include MPHP_PATH . 'mPHP.php';
}

$mPHP = mPHP::init();
$mPHP->run();
