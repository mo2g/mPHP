<?php

use PHPUnit\Framework\TestCase;

class ErrorLoggingTest extends TestCase
{
    protected function setUp(): void
    {
        mPHP::$CFG = $GLOBALS['CFG'] ?? [];
        mPHP::$CFG['log_buffer_size'] = 1024;
        mPHP::$log_buffers = [];
        mPHP::$log_buffer_size = 0;
        mPHP::$log_writing = false;
        mPHP::$request_id = false;
        unset($_SERVER['HTTP_X_REQUEST_ID']);

        foreach (glob(LOG_PATH . '*.log') ?: [] as $file) {
            @unlink($file);
        }
    }

    protected function tearDown(): void
    {
        mPHP::flushLogs();
    }

    public function testLogWritesBufferedContent(): void
    {
        $ok = mPHP::log('INFO', 'buffered log test');
        $this->assertTrue($ok);

        mPHP::flushLogs();
        $file = LOG_PATH . 'INFO-' . date('Y-m-d') . '.log';
        $this->assertFileExists($file);
        $this->assertStringContainsString('buffered log test', (string)file_get_contents($file));
    }

    public function testUnknownLogLevelFallsBackToInfo(): void
    {
        mPHP::log('unknown-level', 'fallback info level');
        mPHP::flushLogs();

        $file = LOG_PATH . 'INFO-' . date('Y-m-d') . '.log';
        $this->assertFileExists($file);
        $this->assertStringContainsString('[INFO]', (string)file_get_contents($file));
    }

    public function testErrorHandlerLogsWarning(): void
    {
        $ret = mError::errorHandler(E_WARNING, 'warning sample', __FILE__, __LINE__);
        $this->assertTrue($ret);

        mPHP::flushLogs();
        $file = LOG_PATH . 'WARNING-' . date('Y-m-d') . '.log';
        $this->assertFileExists($file);
        $content = (string)file_get_contents($file);
        $this->assertStringContainsString('"error_type":"E_WARNING"', $content);
        $this->assertStringContainsString('warning sample', $content);
    }

    public function testBusinessAndErrorLogsShareRequestId(): void
    {
        mPHP::log('INFO', 'business event');
        mError::errorHandler(E_WARNING, 'warning for same request', __FILE__, __LINE__);
        mPHP::flushLogs();

        $rid = mPHP::requestId();
        $infoFile = LOG_PATH . 'INFO-' . date('Y-m-d') . '.log';
        $warnFile = LOG_PATH . 'WARNING-' . date('Y-m-d') . '.log';

        $this->assertFileExists($infoFile);
        $this->assertFileExists($warnFile);
        $this->assertStringContainsString("[rid={$rid}]", (string)file_get_contents($infoFile));
        $this->assertStringContainsString("[rid={$rid}]", (string)file_get_contents($warnFile));
    }

    public function testRequestIdFromHeaderIsSanitized(): void
    {
        $_SERVER['HTTP_X_REQUEST_ID'] = 'abc-123<>bad';
        $rid = mPHP::requestId();
        $this->assertEquals('abc-123bad', $rid);
    }
}
