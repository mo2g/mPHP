<?php

use PHPUnit\Framework\TestCase;

class SafeTest extends TestCase
{
    protected function setUp(): void
    {
        // Ensure CFG exists and default mode is trim-only
        mPHP::$CFG = $GLOBALS['CFG'] ?? [];
        unset(mPHP::$CFG['input_escape']);
    }

    /**
     * Test that filter() escapes HTML special characters
     */
    public function testFilterEscapesHtml()
    {
        mPHP::$CFG['input_escape'] = 'html';
        $value = '<script>alert("xss")</script>';
        safe::filter($value);
        $this->assertStringNotContainsString('<script>', $value);
        $this->assertStringContainsString('&lt;script&gt;', $value);
    }

    /**
     * Test that filter() adds slashes to quotes
     */
    public function testFilterEscapesQuotes()
    {
        mPHP::$CFG['input_escape'] = 'html';
        $value = "test'value";
        safe::filter($value);
        // htmlspecialchars converts ' to &#039; (with ENT_QUOTES)
        $this->assertStringContainsString('&#039;', $value);
    }

    /**
     * Test that filter() trims whitespace
     */
    public function testFilterTrims()
    {
        $value = '  hello  ';
        safe::filter($value);
        $this->assertEquals('hello', $value);
    }

    /**
     * Test that filter() handles arrays recursively
     */
    public function testFilterArray()
    {
        mPHP::$CFG['input_escape'] = 'html';
        $arr = ['<b>bold</b>', 'normal', ['<i>nested</i>']];
        safe::filter($arr);
        $this->assertStringNotContainsString('<b>', $arr[0]);
        $this->assertEquals('normal', $arr[1]);
        $this->assertStringNotContainsString('<i>', $arr[2][0]);
    }

    /**
     * Test that restore() reverses filter()
     */
    public function testRestoreReversesFilter()
    {
        mPHP::$CFG['input_escape'] = 'html';
        $original = 'hello world';
        $value = $original;
        safe::filter($value);
        $restored = safe::restore($value);
        $this->assertEquals($original, $restored);
    }

    /**
     * Test that restore() decodes HTML entities
     */
    public function testRestoreDecodesEntities()
    {
        $value = '&lt;script&gt;';
        $restored = safe::restore($value);
        $this->assertEquals('<script>', $restored);
    }

    /**
     * Test that getKey() returns a string of length 5
     */
    public function testGetKeyLength()
    {
        $key = safe::getKey();
        $this->assertEquals(5, strlen($key));
    }

    /**
     * Test that getKey() only contains expected characters
     */
    public function testGetKeyCharacters()
    {
        $allowed = 'abcdefghijkmnpqrstuvwxyz2356789';
        for ($i = 0; $i < 20; $i++) {
            $key = safe::getKey();
            for ($j = 0; $j < strlen($key); $j++) {
                $this->assertStringContainsString(
                    $key[$j],
                    $allowed,
                    "Character '{$key[$j]}' not in allowed set"
                );
            }
        }
    }

    /**
     * Test that getKey() produces different results (randomness)
     */
    public function testGetKeyRandomness()
    {
        $keys = [];
        for ($i = 0; $i < 50; $i++) {
            $keys[] = safe::getKey();
        }
        // With 50 random 5-char strings from 30 chars, extremely unlikely all identical
        $unique = array_unique($keys);
        $this->assertGreaterThan(1, count($unique), 'getKey() should produce varied results');
    }

    /**
     * Test that getToken() returns a valid MD5 hash
     */
    public function testGetTokenFormat()
    {
        $token = safe::getToken();
        $this->assertEquals(32, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);
    }

    /**
     * Test that getToken() produces unique values
     */
    public function testGetTokenUniqueness()
    {
        $token1 = safe::getToken();
        $token2 = safe::getToken();
        $this->assertNotEquals($token1, $token2);
    }

    /**
     * Test safeGPC() processes superglobals
     */
    public function testSafeGPC()
    {
        mPHP::$CFG['input_escape'] = 'html';
        $_GET['test'] = '<script>xss</script>';
        $_POST['test'] = "test'injection";
        $_COOKIE['test'] = '  trimme  ';

        safe::safeGPC();

        $this->assertStringNotContainsString('<script>', $_GET['test']);
        // htmlspecialchars converts ' to &#039; (with ENT_QUOTES)
        $this->assertStringContainsString('&#039;', $_POST['test']);
        $this->assertEquals('trimme', $_COOKIE['test']);

        // Cleanup
        unset($_GET['test'], $_POST['test'], $_COOKIE['test']);
    }
}
