<?php

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset router state
        router::$controller = 'index';
        router::$action = 'index';
        router::$path_info = '';

        // Initialize cache table for router
        if (router::$table === false) {
            router::$table = new cache\cacheModel('file');
            router::$table->in('router');
        }

        // Reset GET params
        unset($_GET['c'], $_GET['a']);
    }

    /**
     * Test that empty path_info returns -1
     */
    public function testEmptyPathInfoReturns()
    {
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_URI'] = '';
        $_SERVER['QUERY_STRING'] = '';
        unset($_SERVER['argv']);

        $result = router::path_info();
        $this->assertEquals(-1, $result);
    }

    /**
     * Test query string parsing: ?c=test&a=view
     */
    public function testQueryStringParsing()
    {
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_URI'] = '';
        $_SERVER['QUERY_STRING'] = 'c=test&a=view';
        unset($_SERVER['argv']);

        // Initialize mobileModel dependency
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';
        mPHP::$CFG = $GLOBALS['CFG'] ?? [];
        mPHP::$CFG['router'] = [];

        $result = router::path_info();
        $this->assertEquals(1, $result);
        $this->assertEquals('test', $_GET['c']);
        $this->assertEquals('view', $_GET['a']);

        unset($_GET['c'], $_GET['a']);
    }

    /**
     * Test path_info URL parsing: /controller/action/key/value
     */
    public function testPathInfoParsing()
    {
        $_SERVER['PATH_INFO'] = '/article/view/id/123';
        $_SERVER['REQUEST_URI'] = '';
        $_SERVER['QUERY_STRING'] = '';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';
        unset($_SERVER['argv']);

        mPHP::$CFG = $GLOBALS['CFG'] ?? [];
        mPHP::$CFG['router'] = [];

        $result = router::path_info();
        $this->assertEquals(3, $result);
        $this->assertEquals('article', $_GET['c']);
        $this->assertEquals('view', $_GET['a']);
        $this->assertEquals('123', $_GET['id']);

        unset($_GET['c'], $_GET['a'], $_GET['id']);
    }

    /**
     * Test that path_info strips .php prefix
     */
    public function testPathInfoStripsPHP()
    {
        $_SERVER['PATH_INFO'] = '/index.php/test/action';
        $_SERVER['REQUEST_URI'] = '';
        $_SERVER['QUERY_STRING'] = '';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';
        unset($_SERVER['argv']);

        mPHP::$CFG = $GLOBALS['CFG'] ?? [];
        mPHP::$CFG['router'] = [];

        $result = router::path_info();
        $this->assertEquals(3, $result);
        $this->assertEquals('test', $_GET['c']);
        $this->assertEquals('action', $_GET['a']);

        unset($_GET['c'], $_GET['a']);
    }

    /**
     * Test REQUEST_URI fallback when PATH_INFO is empty
     */
    public function testRequestUriFallback()
    {
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_URI'] = '/blog/list';
        $_SERVER['QUERY_STRING'] = '';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';
        unset($_SERVER['argv']);

        mPHP::$CFG = $GLOBALS['CFG'] ?? [];
        mPHP::$CFG['router'] = [];

        $result = router::path_info();
        $this->assertEquals(3, $result);
        $this->assertEquals('blog', $_GET['c']);
        $this->assertEquals('list', $_GET['a']);

        unset($_GET['c'], $_GET['a']);
    }
}
