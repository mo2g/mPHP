<?php

use PHPUnit\Framework\TestCase;

class StrModelTest extends TestCase
{
    /**
     * Test UTF-8 substring with mb_substr available
     */
    public function testMsubstrUtf8()
    {
        $str = '你好世界Hello';
        $result = strModel::msubstr($str, 0, 2, 'utf-8', false);
        $this->assertEquals('你好', $result);
    }

    /**
     * Test UTF-8 substring with offset
     */
    public function testMsubstrUtf8Offset()
    {
        $str = '你好世界Hello';
        $result = strModel::msubstr($str, 2, 2, 'utf-8', false);
        $this->assertEquals('世界', $result);
    }

    /**
     * Test msubstr with ASCII-only string
     */
    public function testMsubstrAscii()
    {
        $str = 'Hello World';
        $result = strModel::msubstr($str, 0, 5, 'utf-8', false);
        $this->assertEquals('Hello', $result);
    }

    /**
     * Test substrForKey stops at keyword
     */
    public function testSubstrForKey()
    {
        $str = 'hello<!--mPHP-->world';
        $result = strModel::substrForKey($str);
        $this->assertEquals('hello', $result);
    }

    /**
     * Test substrForKey returns full string when keyword not found
     */
    public function testSubstrForKeyNoMatch()
    {
        $str = 'hello world';
        $result = strModel::substrForKey($str);
        $this->assertEquals('hello world', $result);
    }

    /**
     * Test substrForKey with custom keyword
     */
    public function testSubstrForKeyCustom()
    {
        $str = 'before|STOP|after';
        $result = strModel::substrForKey($str, '|STOP|');
        $this->assertEquals('before', $result);
    }

    /**
     * Test rand_string default length (6)
     */
    public function testRandStringDefaultLength()
    {
        $str = strModel::rand_string();
        $this->assertEquals(6, strlen($str));
    }

    /**
     * Test rand_string custom length
     */
    public function testRandStringCustomLength()
    {
        $str = strModel::rand_string(10);
        $this->assertEquals(10, strlen($str));
    }

    /**
     * Test rand_string type 0 (letters only)
     */
    public function testRandStringLettersOnly()
    {
        $str = strModel::rand_string(20, 0);
        $this->assertMatchesRegularExpression('/^[A-Za-z]+$/', $str);
    }

    /**
     * Test rand_string type 1 (numbers only)
     */
    public function testRandStringNumbersOnly()
    {
        $str = strModel::rand_string(20, 1);
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $str);
    }

    /**
     * Test rand_string type 2 (uppercase only)
     */
    public function testRandStringUppercase()
    {
        $str = strModel::rand_string(20, 2);
        $this->assertMatchesRegularExpression('/^[A-Z]+$/', $str);
    }

    /**
     * Test rand_string type 3 (lowercase only)
     */
    public function testRandStringLowercase()
    {
        $str = strModel::rand_string(20, 3);
        $this->assertMatchesRegularExpression('/^[a-z]+$/', $str);
    }

    /**
     * Test rand_string long string (> 10 chars)
     */
    public function testRandStringLongLength()
    {
        $str = strModel::rand_string(30);
        $this->assertEquals(30, strlen($str));
    }

    /**
     * Test rand_string produces varied output
     */
    public function testRandStringRandomness()
    {
        $results = [];
        for ($i = 0; $i < 20; $i++) {
            $results[] = strModel::rand_string(10);
        }
        $unique = array_unique($results);
        $this->assertGreaterThan(1, count($unique));
    }
}
