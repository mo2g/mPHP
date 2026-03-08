# mPHP Composer Demo

This is a minimal example of creating an mPHP project using Composer, with the recommended `public/` directory structure for security.

## Project Structure

```
composer-example/
├── composer.json                  # Composer config (requires mo2g/mphp)
├── public/                        # Document root (web server points here)
│   └── index.php                  # Entry point (only PHP file in public/)
├── libs/
│   ├── controllers/
│   │   └── indexController.php    # Demo controller
│   └── tpl/
│       └── index.tpl.html         # Demo template
└── README.md
```

> **Security**: Only the `public/` directory is exposed to the web. All PHP source code (controllers, models, configs) stays outside the document root, preventing direct access.

## Quick Start

### Option 1: With Composer (Recommended)

```bash
cd demos/composer-example
composer install
php -S localhost:8080 -t public/
```

### Option 2: Without Composer

```bash
cd demos/composer-example
php -S localhost:8080 -t public/
```

Open http://localhost:8080 in your browser.

## Available Routes

| URL | Description |
|-----|-------------|
| `http://localhost:8080/` | Home page (template rendering demo) |
| `http://localhost:8080/?c=index&a=api` | JSON API response demo |

## Creating Your Own Project

Use this as a starting template:

1. Copy this directory to your desired location
2. Update `composer.json` with your project name
3. Configure your web server (Nginx/Apache) to point the document root to `public/`
4. Add controllers in `libs/controllers/`
5. Add templates in `libs/tpl/`
6. Add services in `libs/services/` and DAOs in `libs/daos/`

### Nginx Configuration Example

```nginx
server {
    listen 80;
    server_name myapp.local;
    root /path/to/your-project/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```
