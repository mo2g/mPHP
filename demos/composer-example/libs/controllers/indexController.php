<?php
/**
 * Demo Controller
 * URL: http://localhost:8080/?c=index&a=index
 *      or http://localhost:8080/ (default)
 */
class indexController extends controller {
    
    /**
     * Home page action
     */
    public function indexAction() {
        self::$view->data['title'] = 'mPHP Composer Demo';
        self::$view->data['version'] = PHP_VERSION;
        self::$view->data['features'] = [
            'MVC Architecture',
            'Template Compilation',
            'Multi-backend Caching (File / Redis / Memcached)',
            'PDO & MySQLi Database Drivers',
            'URL Routing Engine',
            'Swoole HTTP/WebSocket Server',
            'Input Filtering & Security',
            'Image Processing (GD / ImageMagick)',
        ];
        self::$view->loadTpl('index');
    }

    /**
     * API JSON response demo
     * URL: http://localhost:8080/?c=index&a=api
     */
    public function apiAction() {
        mPHP::header('Content-Type', 'application/json');
        echo json_encode([
            'status'  => 'ok',
            'message' => 'Hello from mPHP!',
            'php'     => PHP_VERSION,
            'time'    => date('Y-m-d H:i:s'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
