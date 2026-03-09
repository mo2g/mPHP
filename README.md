# mPHP — Lightweight PHP MVC Framework

<p align="center">
  <strong>A minimalist, high-performance PHP MVC framework built for learning and production use.</strong>
</p>

<p align="center">
  <a href="#features">Features</a> •
  <a href="#quick-start">Quick Start</a> •
  <a href="#architecture">Architecture</a> •
  <a href="#benchmark">Benchmark</a> •
  <a href="#swoole-support">Swoole</a> •
  <a href="#license">License</a> •
  <a href="./README_CN.md">中文文档</a>
</p>

---

## Introduction

**mPHP** is a lightweight PHP MVC framework originally created in 2012 for learning and sharing purposes. Over the years, it has evolved from a PHP 5.5 learning project into a practical framework that powers the author's personal tech blog at [mo2g.com](http://mo2g.com).

The framework emphasizes **simplicity**, **low coupling**, and **minimal overhead**, making it ideal for understanding MVC architecture internals and building lightweight web applications.

## Features

- 🏗️ **MVC Architecture** — Clean separation of Controllers, Services, DAOs, and Models
- ⚡ **Swoole Support** — Built-in Swoole HTTP/WebSocket server with connection pooling for high-concurrency scenarios
- 🔧 **Ultra-Low Coupling** — No forced naming conventions; easily extensible with third-party classes
- 📦 **Built-in Components** — Template engine, routing, caching (File / Redis / Memcached), pagination, image processing, email, file uploads, and more
- 🛡️ **Security** — Input filtering (XSS/injection protection), CSRF token generation, file-lock mechanism
- 🚀 **Template Compilation** — Templates are compiled to PHP and cached, with automatic recompilation on change
- 📱 **Mobile Detection** — Built-in user-agent based mobile device detection
- 🗄️ **Database** — PDO and MySQLi drivers with auto-reconnect and connection pooling
- 📊 **Debug Mode** — Runtime performance stats including DB query counts and execution time

## Quick Start

### Requirements

- PHP 7.0+ (PHP 8.x compatible)
- MySQL 5.5+ (optional)
- Swoole Extension (optional, for high-concurrency mode)

### 1. Create Your Entry Point

```php
<?php
// index.php
define('INIT_MPHP', 'my-app');
define('INDEX_PATH', __DIR__.'/');
define('WEB_PATH',   __DIR__.'/');
define('MPHP_PATH',  realpath(INDEX_PATH.'../../mPHP/').'/');

include MPHP_PATH . 'mPHP.php';
$mPHP = mPHP::init();
$mPHP->run();
```

### 2. Create a Controller

```php
<?php
// libs/controllers/indexController.php
class indexController extends controller {
    public function indexAction() {
        self::$view->data['title'] = 'Hello mPHP';
        self::$view->loadTpl('index');
    }
}
```

### 3. Create a Template

```html
<!-- libs/tpl/index.tpl.html -->
<!DOCTYPE html>
<html>
<head><title><!--# echo $title #--></title></head>
<body>
    <h1><!--# echo $title #--></h1>
</body>
</html>
```

## Architecture

```
your-project/
├── libs/
│   ├── controllers/           # Controller classes
│   ├── services/              # Business logic layer
│   ├── daos/                  # Data access layer
│   ├── models/                # Application models
│   ├── tpl/                   # Template files (*.tpl.html)
│   ├── cache/                 # Compiled templates & caches
│   ├── logs/                  # Log files
│   └── exts/class/            # Third-party classes
└── public/                    # Public directory
    ├── index.php              # Entry point
    └── static/
        ├── js/                # JavaScript files
        ├── css/               # Stylesheets
        └── images/            # Images

mPHP/                          # Framework core
├── mPHP.php                   # Core classes (router, view, controller, etc.)
├── inc/functions.php           # Helper functions (C, U, M, S, D, P)
├── models/                    # Built-in model components
│   ├── pdoModel.php           # PDO database driver
│   ├── mysqliModel.php        # MySQLi database driver
│   ├── sessionModel.php       # Session management (PHP-native & Swoole)
│   ├── imageModel.php         # Image processing (GD & ImageMagick)
│   ├── uploadModel.php        # File upload handling
│   ├── emailModel.php         # SMTP email sending
│   ├── pageModel.php          # Pagination
│   ├── poolModel.php          # Connection pool (Swoole)
│   ├── lockModel.php          # File-lock mechanism
│   └── cache/                 # Cache drivers
│       ├── redisModel.php     # Redis cache & queue
│       ├── memcachedModel.php # Memcached cache
│       └── fileModel.php      # File-based cache
└── tpl/error.tpl.html         # Default error template
```

### URL Routing

mPHP supports three URL styles:

| Style | Example | Config |
|-------|---------|--------|
| Query String | `?c=article&a=view&id=1` | Default |
| Directory | `/article/view/id/1.html` | `url_type = 'DIR'` |
| Flat | `/article-view-id-1.html` | `url_type = 'NODIR'` |

Custom routing rules can be defined in the config:

```php
$CFG['router']['article'] = [
    ['#^/article/(\d+)\.html$#', '?c=article&a=view&id=$1'],
];
```

### Helper Functions

| Function | Description |
|----------|-------------|
| `M('model')` | Get singleton Model instance |
| `S('service')` | Get singleton Service instance |
| `D('dao')` | Get singleton DAO instance |
| `C($file, $key)` | Read/write config file |
| `U($url)` | Generate URL with routing rules |
| `P($var)` | Debug output (print_r/var_dump) |

## Benchmark

Tested with Apache Bench (`ab`) on a single machine:

### `ab -n 1000` (Sequential)

| Mode | Req/sec |
|------|---------|
| Native PHP + FPM | 2,037 |
| **mPHP + FPM** | **757** |
| **mPHP + Swoole** | **2,940** |

### `ab -c 100 -n 1000` (100 Concurrent)

| Mode | Req/sec |
|------|---------|
| Native PHP + FPM | 4,125 |
| **mPHP + FPM** | **1,282** |
| **mPHP + Swoole** | **8,397** |

> With Swoole, mPHP achieves **2x the throughput** of native PHP + FPM.

## Swoole Support

mPHP includes a built-in Swoole HTTP + WebSocket server:

```bash
php demos/swoole_server.php
```

Features:
- HTTP request handling with Swoole coroutines
- WebSocket support with session binding
- Connection pooling for database
- Reload workers without downtime: `sh reload_swoole_server_mPHP.sh`

## Caching

mPHP provides a unified cache interface with multiple backends:

```php
$cache = new cache\cacheModel('redis');  // or 'file', 'memcached'
$cache->set('key', 'value', 3600);
$value = $cache->get('key');
```

### Route-Level Caching

```php
// In controller — cache page output for 300 seconds
self::$view->loadTpl('index', '', 300);
```
## Testing

mPHP includes a PHPUnit test suite:

```bash
composer install
vendor/bin/phpunit
```

Test coverage includes: input filtering (`safe` class), string utilities, directory operations, file caching, URL routing, and helper functions.

## License

This project is licensed under the [MIT License](LICENSE).

## Author

**mo2g** — [mo2g.com](http://mo2g.com) · [GitHub](https://github.com/mo2g)

---

*Originally created in 2012, maintained with ❤️ for over a decade.*
