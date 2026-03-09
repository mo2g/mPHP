<?php

use PHPUnit\Framework\TestCase;

class CacheFileModelTest extends TestCase
{
    private $cache;
    private $cachePath;

    protected function setUp(): void
    {
        $this->cachePath = sys_get_temp_dir() . '/mphp_cache_test_' . uniqid();
        mkdir($this->cachePath, 0755, true);

        // Create the file cache model with a custom path
        $this->cache = new cache\fileModel();
        $this->cache->setPath($this->cachePath);
        $this->cache->in('test');
    }

    protected function tearDown(): void
    {
        // Cleanup
        $files = glob($this->cachePath . '/*');
        if ($files) {
            foreach ($files as $f) {
                if (is_file($f)) unlink($f);
            }
        }
        if (is_dir($this->cachePath)) rmdir($this->cachePath);
    }

    /**
     * Test set and get a value
     */
    public function testSetAndGet()
    {
        $this->cache->set('name', 'mPHP');
        $result = $this->cache->get('name');
        $this->assertEquals('mPHP', $result);
    }

    /**
     * Test set overwrites existing value
     */
    public function testSetOverwrite()
    {
        $this->cache->set('key', 'old');
        $this->cache->set('key', 'new');
        $this->assertEquals('new', $this->cache->get('key'));
    }

    /**
     * Test get returns false for non-existent key
     */
    public function testGetNonExistent()
    {
        $result = $this->cache->get('non_existent');
        $this->assertFalse($result);
    }

    /**
     * Test get_all returns all cached data
     */
    public function testGetAll()
    {
        $this->cache->set('a', '1');
        $this->cache->set('b', '2');
        $all = $this->cache->get_all();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('a', $all);
        $this->assertArrayHasKey('b', $all);
        $this->assertEquals('1', $all['a']);
        $this->assertEquals('2', $all['b']);
    }

    /**
     * Test delete removes a key
     */
    public function testDelete()
    {
        $this->cache->set('temp', 'value');
        $this->assertEquals('value', $this->cache->get('temp'));

        $this->cache->delete('temp');
        $result = $this->cache->get('temp');
        $this->assertFalse($result);
    }

    /**
     * Test flush clears all data
     */
    public function testFlush()
    {
        $this->cache->set('x', '1');
        $this->cache->set('y', '2');
        $this->cache->flush();

        // After flush, in() must be called again to re-initialize
        $this->cache->in('test');
        $all = $this->cache->get_all();
        $this->assertIsArray($all);
        $this->assertEmpty($all);
    }

    /**
     * Test storing array values
     */
    public function testArrayValue()
    {
        $data = ['foo' => 'bar', 'baz' => [1, 2, 3]];
        $this->cache->set('complex', $data);
        $result = $this->cache->get('complex');
        $this->assertEquals($data, $result);
    }

    /**
     * Test storing numeric values
     */
    public function testNumericValue()
    {
        $this->cache->set('count', 42);
        $this->assertEquals(42, $this->cache->get('count'));
    }
}
