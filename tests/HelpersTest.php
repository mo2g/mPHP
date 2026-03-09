<?php

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    private $confDir;

    protected function setUp(): void
    {
        $this->confDir = sys_get_temp_dir() . '/mphp_conf_test_' . uniqid() . '/';
        mkdir($this->confDir, 0755, true);

        // Define CONF_PATH for C() function if not already
        if (!defined('CONF_PATH_TEST')) {
            define('CONF_PATH_TEST', $this->confDir);
        }
    }

    protected function tearDown(): void
    {
        $files = glob($this->confDir . '*');
        if ($files) foreach ($files as $f) unlink($f);
        if (is_dir($this->confDir)) rmdir($this->confDir);
    }

    /**
     * Test C() reads config file
     */
    public function testCReadConfig()
    {
        $configFile = $this->confDir . 'test.config.php';
        file_put_contents($configFile, "<?php\nreturn array('name' => 'mPHP', 'version' => '1.0');");

        $result = C($configFile, 'name');
        $this->assertEquals('mPHP', $result);
    }

    /**
     * Test C() reads all config
     */
    public function testCReadAllConfig()
    {
        $configFile = $this->confDir . 'all.config.php';
        file_put_contents($configFile, "<?php\nreturn array('a' => 1, 'b' => 2);");

        $result = C($configFile);
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['a']);
        $this->assertEquals(2, $result['b']);
    }

    /**
     * Test C() writes config value
     */
    public function testCWriteConfig()
    {
        $configFile = $this->confDir . 'write.config.php';
        file_put_contents($configFile, "<?php\nreturn array('key' => 'old');");

        C($configFile, 'key', 'new');
        $result = C($configFile, 'key');
        $this->assertEquals('new', $result);
    }

    /**
     * Test C() handles dot-notation for nested arrays
     */
    public function testCDotNotation()
    {
        $configFile = $this->confDir . 'nested.config.php';
        file_put_contents($configFile, "<?php\nreturn array('db' => array('host' => 'localhost', 'port' => 3306));");

        $result = C($configFile, 'db.host');
        $this->assertEquals('localhost', $result);

        $result = C($configFile, 'db.port');
        $this->assertEquals(3306, $result);
    }

    /**
     * Test C() returns false for non-existent key
     */
    public function testCNonExistentKey()
    {
        $configFile = $this->confDir . 'miss.config.php';
        file_put_contents($configFile, "<?php\nreturn array('a' => 1);");

        $result = C($configFile, 'nonexistent');
        $this->assertFalse($result);
    }

    /**
     * Test C() returns false for non-existent file
     */
    public function testCNonExistentFile()
    {
        $result = C($this->confDir . 'no_such_file.config.php', 'key');
        $this->assertFalse($result);
    }

    /**
     * Test M() returns singleton model instances
     */
    public function testMSingleton()
    {
        // strModel is a simple model with no constructor deps
        $m1 = M('str');
        $m2 = M('str');
        $this->assertSame($m1, $m2, 'M() should return the same instance');
        $this->assertInstanceOf('strModel', $m1);
    }

    /**
     * Test P() outputs variable (capture output)
     */
    public function testPOutputsVariable()
    {
        // P() with $true=false should not call exit
        ob_start();
        P(['a' => 1, 'b' => 2], false);
        $output = ob_get_clean();

        $this->assertStringContainsString('<pre>', $output);
        $this->assertStringContainsString('[a] => 1', $output);
        $this->assertStringContainsString('[b] => 2', $output);
    }

    /**
     * Test mlog() writes to log file
     */
    public function testMlog()
    {
        $logFile = sys_get_temp_dir() . '/mphp_test_mlog_' . uniqid() . '.log';
        mlog('test message', $logFile);

        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('test message', $content);
        $this->assertStringContainsString('date：', $content);

        unlink($logFile);
    }

    /**
     * Test mini_html() compresses HTML
     */
    public function testMiniHtml()
    {
        $html = "<div>\n\t<p>  Hello  </p>\n\t<p>  World  </p>\n</div>";
        $result = mini_html($html);

        // Should have no newlines/tabs between tags
        $this->assertStringNotContainsString("\n", $result);
        $this->assertStringNotContainsString("\t", $result);
        // Content should be preserved
        $this->assertStringContainsString('Hello', $result);
        $this->assertStringContainsString('World', $result);
    }

    /**
     * Test mini_html() preserves <pre> blocks
     */
    public function testMiniHtmlPreservesPre()
    {
        $html = "<div>text</div>\n<pre>\n\tcode\n\tindented\n</pre>\n<div>more</div>";
        $result = mini_html($html);

        // Pre content should keep its formatting
        $this->assertStringContainsString("code\n\tindented", $result);
    }

    /**
     * Test mini_html() removes HTML comments
     */
    public function testMiniHtmlRemovesComments()
    {
        $html = '<div><!-- This is a comment -->content</div>';
        $result = mini_html($html);
        $this->assertStringNotContainsString('This is a comment', $result);
        $this->assertStringContainsString('content', $result);
    }
}
