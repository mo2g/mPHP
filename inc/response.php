<?php
/**
 * 响应对象与运行时适配
 *
 * 目标：把「写响应」从 PHP 全局函数里解耦出来，
 *  - FPM/CLI：最后统一 header() + echo
 *  - Swoole：写到 $response 对象
 *  - Webman：接入时只需要换一个 Emitter
 *
 * 业务代码不再直接调用 header()/setcookie()/exit。
 */

class Response
{
    public $status  = 200;
    public $headers = array();
    public $cookies = array();
    public $body    = '';

    public function setStatus($code)
    {
        $this->status = (int)$code;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
        // 与 PHP 原生行为保持一致：只给 Location 时默认 302
        if (strcasecmp($key, 'Location') === 0 && $this->status === 200) {
            $this->status = 302;
        }
        return $this;
    }

    public function addHeader($key, $value)
    {
        if (!isset($this->headers[$key])) {
            $this->headers[$key] = array();
        } elseif (!is_array($this->headers[$key])) {
            $this->headers[$key] = array($this->headers[$key]);
        }
        $this->headers[$key][] = $value;
        return $this;
    }

    public function setCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false)
    {
        $this->cookies[] = array(
            'name' => $name, 'value' => $value, 'expire' => $expire,
            'path' => $path, 'domain' => $domain,
            'secure' => (bool)$secure, 'httponly' => (bool)$httponly,
        );
        return $this;
    }

    public function setBody($body)
    {
        $this->body = (string)$body;
        return $this;
    }

    public function getBody()
    {
        return $this->body;
    }
}

class ResponseEmitter
{
    public static function emit(Response $r)
    {
        if (!empty(mPHP::$swoole) && is_object(mPHP::$swoole['response'])) {
            $resp = mPHP::$swoole['response'];
            $resp->status($r->status);
            foreach ($r->headers as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $v) $resp->header($key, $v);
                } else {
                    $resp->header($key, $value);
                }
            }
            foreach ($r->cookies as $c) {
                $resp->cookie($c['name'], $c['value'], $c['expire'], $c['path'], $c['domain'], $c['secure'], $c['httponly']);
            }
            $resp->end($r->body);
            return;
        }

        if (!headers_sent()) {
            http_response_code($r->status);
            foreach ($r->headers as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $v) header($key . ': ' . $v, false);
                } else {
                    header($key . ': ' . $value);
                }
            }
            foreach ($r->cookies as $c) {
                setcookie($c['name'], $c['value'], $c['expire'], $c['path'], $c['domain'], $c['secure'], $c['httponly']);
            }
        }
        if ($r->body !== '') echo $r->body;
    }
}

/**
 * 请求生命周期：控制器只负责产出内容，这里统一收尾成 Response
 */
class Application
{
    public static function handle($mPHP)
    {
        ob_start();
        try {
            $mPHP->run();
        } catch (RedirectException $e) {
            mPHP::$response->setStatus($e->status)->setHeader('Location', $e->url);
        } catch (StopException $e) {
            // 响应内容已在缓冲区
        } catch (HttpException $e) {
            mPHP::renderHttpException($e);
        } catch (Throwable $e) {
            mPHP::handleThrowable($e);
        }
        $body = ob_get_clean();
        if ($body === false) $body = '';
        mPHP::$response->setBody($body);

        if (class_exists('sqlProfiler', false) && sqlProfiler::enabled()) {
            sqlProfiler::emitHeaders();
        }
        return mPHP::$response;
    }
}
