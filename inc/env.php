<?php
/**
 * 极简 .env 加载器（无 composer 依赖）
 *
 * 规则：
 *  - KEY=VALUE，忽略 # 注释与空行
 *  - 值支持单/双引号包裹
 *  - 已存在的真实环境变量优先于 .env（便于容器注入）
 */
class env
{
    private static $data = array();
    private static $loaded = array();

    public static function load($file)
    {
        $file = (string)$file;
        if (isset(self::$loaded[$file])) return true;
        self::$loaded[$file] = true;

        if (!is_file($file) || !is_readable($file)) return false;
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) return false;

        foreach ($lines as $line) {
            $line = ltrim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, '=') === false) continue;

            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) continue;

            $value = trim($value);
            $len = strlen($value);
            if ($len >= 2) {
                $first = $value[0];
                $last  = $value[$len - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                } else {
                    $value = preg_replace('/\s+#.*$/', '', $value);
                }
            }
            self::$data[$key] = $value;
        }
        return true;
    }

    public static function get($key, $default = null)
    {
        $v = getenv($key);
        if ($v !== false && $v !== '') return $v;
        return array_key_exists($key, self::$data) ? self::$data[$key] : $default;
    }
}

if (!function_exists('env_get')) {
    function env_get($key, $default = null)
    {
        return env::get($key, $default);
    }
}
