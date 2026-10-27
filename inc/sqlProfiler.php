<?php
/**
 * SQL 探针（轻量、可开关）
 *
 * $CFG['debug_sql'] = 0  关闭（默认）
 *                      1  只记慢查询 + Server-Timing 响应头
 *                      2  全量明细（含调用点）+ 页面尾部调试注释
 *
 * 设计要点：
 *  - 关闭时只有一次布尔判断，无 microtime、无数组写入
 *  - fingerprint 归一化后聚合，同一模板出现多次可直接暴露 N+1
 *  - 每请求可 reset()，兼容 Swoole/Webman 常驻运行时
 */
class sqlProfiler
{
    const MAX_ROWS = 500;

    private static $level   = 0;
    private static $slowMs  = 5.0;
    private static $rows    = array();
    private static $agg     = array();
    private static $dropped = 0;
    private static $cliHook = false;

    public static function boot($cfg)
    {
        self::$level  = isset($cfg['debug_sql']) ? (int)$cfg['debug_sql'] : 0;
        self::$slowMs = isset($cfg['debug_sql_slow_ms']) ? (float)$cfg['debug_sql_slow_ms'] : 5.0;

        if (self::$level > 0 && PHP_SAPI === 'cli' && !self::$cliHook) {
            self::$cliHook = true;
            register_shutdown_function(array(__CLASS__, 'cliSummary'));
        }
    }

    public static function enabled()
    {
        return self::$level > 0;
    }

    public static function level()
    {
        return self::$level;
    }

    public static function reset()
    {
        self::$rows    = array();
        self::$agg     = array();
        self::$dropped = 0;
    }

    /**
     * @param string $sql   原始 SQL
     * @param float  $ms    耗时（毫秒）
     * @param int    $rows  影响/返回行数（尽量提供，取不到填 0）
     * @param bool   $err   是否执行失败
     */
    public static function record($sql, $ms, $rows = 0, $err = false)
    {
        if (self::$level < 1) return;

        $fp = self::fingerprint($sql);
        if (!isset(self::$agg[$fp])) {
            self::$agg[$fp] = array(
                'sql' => $sql, 'n' => 0, 'ms' => 0.0,
                'rows' => 0, 'err' => 0, 'caller' => '',
            );
        }
        self::$agg[$fp]['n']++;
        self::$agg[$fp]['ms']   += $ms;
        self::$agg[$fp]['rows'] += $rows;
        if ($err) self::$agg[$fp]['err']++;

        if (self::$level >= 2) {
            if (count(self::$rows) < self::MAX_ROWS) {
                self::$rows[] = array(
                    'sql' => $sql, 'ms' => $ms, 'rows' => $rows,
                    'err' => $err, 'caller' => self::caller(),
                );
            } else {
                self::$dropped++;
            }
        }

        if ($ms >= self::$slowMs) self::slowLog($sql, $ms, $rows, $err);
    }

    /**
     * 本次请求记录的全部 SQL 明细（需 level>=2），供 CLI 诊断与自动化对比使用
     */
    public static function all()
    {
        return self::$rows;
    }

    public static function summary()
    {
        $count = 0;
        $time  = 0.0;
        $dupes = array();
        foreach (self::$agg as $fp => $a) {
            $count += $a['n'];
            $time  += $a['ms'];
            if ($a['n'] >= 5) $dupes[$fp] = $a;
        }
        uasort($dupes, function ($a, $b) {
            return ($b['n'] * $b['ms']) <=> ($a['n'] * $a['ms']);
        });
        return array(
            'count'   => $count,
            'time'    => $time,
            'unique'  => count(self::$agg),
            'dupes'   => array_slice($dupes, 0, 10, true),
            'dropped' => self::$dropped,
            'level'   => self::$level,
        );
    }

    /**
     * 在输出正文之前调用：写 Server-Timing / X-DB-Queries
     */
    public static function emitHeaders()
    {
        if (self::$level < 1) return;
        if (PHP_SAPI === 'cli' || headers_sent()) return;

        $s = self::summary();
        $desc = $s['count'] . 'q/' . $s['unique'] . 'u';
        header(sprintf('Server-Timing: db;dur=%.1f;desc="%s"', $s['time'], $desc));
        header('X-DB-Queries: ' . $s['count']);
    }

    /**
     * level=2 时附加到页面尾部（HTML 注释，不影响渲染）
     */
    public static function debugHtml()
    {
        if (self::$level < 2) return '';
        $s = self::summary();
        $lines = array();
        $lines[] = sprintf('SQL %d 条 / %d 种 / %.1f ms', $s['count'], $s['unique'], $s['time']);
        if (isset(mPHP::$view) && mPHP::$view) {
            $lines[] = sprintf('渲染 %d 个模板 / %.1f ms', mPHP::$view->includeCount, mPHP::$view->includeMs);
        }

        $top = self::$rows;
        usort($top, function ($a, $b) { return $b['ms'] <=> $a['ms']; });
        $lines[] = '-- 最慢 --';
        foreach (array_slice($top, 0, 10) as $r) {
            $lines[] = sprintf('%6.2fms  %s  @%s', $r['ms'], self::short($r['sql']), $r['caller']);
        }
        if ($s['dupes']) {
            $lines[] = '-- 重复模板（疑似 N+1）--';
            foreach ($s['dupes'] as $a) {
                $lines[] = sprintf('%3dx %6.2fms  %s', $a['n'], $a['ms'], self::short($a['sql']));
            }
        }
        if ($s['dropped']) $lines[] = '（明细超出上限，丢弃 ' . $s['dropped'] . ' 条）';
        return "\n<!--\n" . implode("\n", array_map(function ($l) {
            return str_replace('--', '—', $l);
        }, $lines)) . "\n-->\n";
    }

    public static function cliSummary()
    {
        if (self::$level < 1) return;
        $s = self::summary();
        fwrite(STDERR, sprintf(
            "[sql] %d queries / %d unique / %.1fms%s\n",
            $s['count'], $s['unique'], $s['time'],
            $s['dropped'] ? ' (dropped ' . $s['dropped'] . ')' : ''
        ));
        foreach ($s['dupes'] as $a) {
            fwrite(STDERR, sprintf("[sql] %dx %.1fms %s\n", $a['n'], $a['ms'], self::short($a['sql'])));
        }
    }

    /* ----------------------------- 内部 ----------------------------- */

    private static function slowLog($sql, $ms, $rows, $err)
    {
        if (!defined('LOG_PATH')) return;
        $line = array(
            'time' => date('c'),
            'ms'   => round($ms, 2),
            'rows' => $rows,
            'err'  => (bool)$err,
            'sql'  => mb_substr($sql, 0, 2000),
            'from' => self::caller(),
            'rid'  => class_exists('mPHP', false) ? mPHP::requestId() : '',
        );
        @file_put_contents(
            LOG_PATH . 'sql-slow-' . date('Y-m-d') . '.log',
            json_encode($line, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }

    private static function caller()
    {
        if (self::$level < 2) return '';
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12);
        foreach ($trace as $f) {
            if (empty($f['file'])) continue;
            if (strpos($f['file'], 'sqlProfiler.php') !== false) continue;
            if (strpos($f['file'], '/models/pdoModel.php') !== false) continue;
            $root = defined('INDEX_PATH') ? rtrim(realpath(INDEX_PATH), '/') . '/' : '';
            $file = $root !== '' ? str_replace(array($root, MPHP_PATH), array('', 'mPHP/'), $f['file']) : $f['file'];
            return $file . ':' . (isset($f['line']) ? $f['line'] : 0);
        }
        return '';
    }

    private static function fingerprint($sql)
    {
        $s = preg_replace("/'(?:[^'\\\\]|\\\\.)*'/", "'?'", $sql);
        $s = preg_replace('/\b\d+\b/', '?', $s);
        $s = preg_replace('/\s+/', ' ', trim($s));
        return $s;
    }

    private static function short($sql)
    {
        $s = preg_replace('/\s+/', ' ', trim($sql));
        return mb_strlen($s, 'UTF-8') > 160 ? mb_substr($s, 0, 160, 'UTF-8') . '…' : $s;
    }
}
