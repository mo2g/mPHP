# mPHP — 轻量级 PHP MVC 框架

<p align="center">
  <strong>一个简约、高性能的 PHP MVC 框架，为学习和实战而生。</strong>
</p>

<p align="center">
  <a href="#特性">特性</a> •
  <a href="#快速开始">快速开始</a> •
  <a href="#目录结构">目录结构</a> •
  <a href="#性能测试">性能测试</a> •
  <a href="#swoole-支持">Swoole</a> •
  <a href="#开源协议">开源协议</a> •
  <a href="./README.md">English</a>
</p>

---

## 简介

**mPHP** 是一个 2012 年创建的轻量级 PHP MVC 框架，最初用于学习和分享 MVC 架构知识。多年来，它从一个 PHP 5.5 的学习项目，演进为一个实际驱动作者技术博客 [mo2g.com](http://mo2g.com) 的实用框架。

框架强调**简洁**、**低耦合**和**最小开销**，非常适合理解 MVC 架构原理以及构建轻量级 Web 应用。

## 特性

- 🏗️ **MVC 架构** — Controller、Service、DAO、Model 层次分明
- ⚡ **Swoole 支持** — 内置 Swoole HTTP/WebSocket 服务器与连接池，满足高并发场景
- 🔧 **超低耦合** — 不强制命名规范，轻松扩展第三方类
- 📦 **内置组件** — 模板引擎、路由、缓存（File/Redis/Memcached）、分页、图片处理、邮件、文件上传等
- 🛡️ **安全机制** — 输入过滤（XSS/注入防护）、CSRF 令牌、文件锁
- 🚀 **模板编译** — 模板编译为 PHP 并缓存，修改后自动重编译
- 📱 **移动端检测** — 内置 User-Agent 移动设备识别
- 🗄️ **数据库** — PDO 和 MySQLi 双驱动，支持自动重连和连接池
- 📊 **调试模式** — 运行时性能统计，包括数据库查询次数和执行时间

## 快速开始

### 环境要求

- PHP 7.0+（兼容 PHP 8.x）
- MySQL 5.5+（可选）
- Swoole 扩展（可选，用于高并发模式）

### 1. 创建入口文件

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

### 2. 创建控制器

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

### 3. 创建模板

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

## 目录结构

```
你的项目/
├── libs/
│   ├── controllers/           # 控制器
│   ├── services/              # 业务逻辑层
│   ├── daos/                  # 数据访问层
│   ├── models/                # 应用模型
│   ├── tpl/                   # 模板文件 (*.tpl.html)
│   ├── cache/                 # 编译后的模板和缓存
│   ├── logs/                  # 日志文件
│   └── exts/class/            # 第三方类
└── public/                    # 公开目录
    ├── index.php              # 入口文件
    └── static/
        ├── js/                # JavaScript 文件
        ├── css/               # 样式文件
        └── images/            # 图片资源

mPHP/                          # 框架核心
├── mPHP.php                   # 核心类（router, view, controller 等）
├── inc/functions.php          # 辅助函数（C, U, M, S, D, P）
├── models/                    # 内置模型组件
│   ├── pdoModel.php           # PDO 数据库驱动
│   ├── mysqliModel.php        # MySQLi 数据库驱动
│   ├── sessionModel.php       # Session 管理（原生 PHP 和 Swoole 双模式）
│   ├── imageModel.php         # 图片处理（GD 和 ImageMagick）
│   ├── uploadModel.php        # 文件上传
│   ├── emailModel.php         # SMTP 邮件发送
│   ├── pageModel.php          # 分页组件
│   ├── poolModel.php          # 连接池（Swoole）
│   ├── lockModel.php          # 文件锁
│   └── cache/                 # 缓存驱动
│       ├── redisModel.php     # Redis 缓存与队列
│       ├── memcachedModel.php # Memcached 缓存
│       └── fileModel.php      # 文件缓存
└── tpl/error.tpl.html         # 默认错误模板
```

### URL 路由

mPHP 支持三种 URL 风格：

| 风格 | 示例 | 配置 |
|------|------|------|
| 查询字符串 | `?c=article&a=view&id=1` | 默认 |
| 目录式 | `/article/view/id/1.html` | `url_type = 'DIR'` |
| 扁平式 | `/article-view-id-1.html` | `url_type = 'NODIR'` |

还支持自定义路由规则：

```php
$CFG['router']['article'] = [
    ['#^/article/(\d+)\.html$#', '?c=article&a=view&id=$1'],
];
```

### 辅助函数

| 函数 | 说明 |
|------|------|
| `M('model')` | 获取 Model 单例实例 |
| `S('service')` | 获取 Service 单例实例 |
| `D('dao')` | 获取 DAO 单例实例 |
| `C($file, $key)` | 读取/写入配置文件 |
| `U($url)` | 根据路由规则生成 URL |
| `P($var)` | 调试输出（print_r/var_dump）|

## 性能测试

使用 Apache Bench (`ab`) 在单机上测试：

### `ab -n 1000`（顺序请求）

| 模式 | 请求/秒 |
|------|---------|
| 原生 PHP + FPM | 2,037 |
| **mPHP + FPM** | **757** |
| **mPHP + Swoole** | **2,940** |

### `ab -c 100 -n 1000`（100 并发）

| 模式 | 请求/秒 |
|------|---------|
| 原生 PHP + FPM | 4,125 |
| **mPHP + FPM** | **1,282** |
| **mPHP + Swoole** | **8,397** |

> 借助 Swoole，mPHP 的吞吐量达到了原生 PHP + FPM 的 **2 倍**。

## Swoole 支持

mPHP 自带 Swoole HTTP + WebSocket 服务器：

```bash
php demos/swoole_server.php
```

功能特性：
- 基于 Swoole 协程的 HTTP 请求处理
- WebSocket 支持并绑定 Session
- 数据库连接池
- 平滑重启：`sh reload_swoole_server_mPHP.sh`

## 缓存

mPHP 提供统一的缓存接口，支持多种后端：

```php
$cache = new cache\cacheModel('redis');  // 或 'file', 'memcached'
$cache->set('key', 'value', 3600);
$value = $cache->get('key');
```

### 路由级缓存

```php
// 在控制器中 — 缓存页面输出 300 秒
self::$view->loadTpl('index', '', 300);
```

## 测试

mPHP 包含 PHPUnit 单元测试：

```bash
composer install
vendor/bin/phpunit
```

测试覆盖：输入过滤（`safe` 类）、字符串工具、目录操作、文件缓存、URL 路由、辅助函数。

## 开源协议

本项目基于 [MIT License](./LICENSE) 开源协议。

## 作者

**mo2g** — [mo2g.com](http://mo2g.com) · [GitHub](https://github.com/mo2g)

---

*创建于 2012 年，用 ❤️ 维护超过十年。*
