<?php

use PHPUnit\Framework\TestCase;

class DirectoryModelTest extends TestCase
{
    private $testDir;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/mphp_dir_test_' . uniqid() . '/';
        mkdir($this->testDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // Recursively remove test directory
        $this->removeDir($this->testDir);
    }

    private function removeDir($dir)
    {
        if (!is_dir($dir)) return;
        $items = glob($dir . '*', GLOB_MARK);
        foreach ($items as $item) {
            is_dir($item) ? $this->removeDir($item) : unlink($item);
        }
        rmdir($dir);
    }

    /**
     * Test view() returns files in directory
     */
    public function testView()
    {
        file_put_contents($this->testDir . 'a.txt', 'aaa');
        file_put_contents($this->testDir . 'b.txt', 'bbb');
        $result = directoryModel::view($this->testDir . '*');
        $this->assertCount(2, $result);
    }

    /**
     * Test createDirs() creates numbered directories
     */
    public function testCreateDirs()
    {
        directoryModel::createDirs($this->testDir . 'sub/', 3);
        $this->assertTrue(is_dir($this->testDir . 'sub/0'));
        $this->assertTrue(is_dir($this->testDir . 'sub/1'));
        $this->assertTrue(is_dir($this->testDir . 'sub/2'));
        $this->assertFalse(is_dir($this->testDir . 'sub/3'));
    }

    /**
     * Test clearDir() removes files but keeps directories
     */
    public function testClearDirKeepsDirectories()
    {
        $subDir = $this->testDir . 'sub/';
        mkdir($subDir, 0755, true);
        file_put_contents($this->testDir . 'file1.txt', 'data');
        file_put_contents($subDir . 'file2.txt', 'data');

        directoryModel::clearDir($this->testDir);

        $this->assertTrue(is_dir($subDir), 'Subdirectory should be preserved');
        $this->assertFileDoesNotExist($this->testDir . 'file1.txt');
        $this->assertFileDoesNotExist($subDir . 'file2.txt');
    }

    /**
     * Test clearDir2() removes files but keeps directories
     */
    public function testClearDir2()
    {
        $subDir = $this->testDir . 'sub/';
        mkdir($subDir, 0755, true);
        file_put_contents($this->testDir . 'file1.txt', 'data');
        file_put_contents($subDir . 'file2.txt', 'data');

        directoryModel::clearDir2($this->testDir);

        $this->assertTrue(is_dir($subDir));
        $this->assertFileDoesNotExist($this->testDir . 'file1.txt');
        $this->assertFileDoesNotExist($subDir . 'file2.txt');
    }

    /**
     * Test clearDir() with $true=true also removes directories
     */
    public function testClearDirRemovesDirectories()
    {
        $subDir = $this->testDir . 'sub/';
        mkdir($subDir, 0755, true);
        file_put_contents($subDir . 'file.txt', 'data');

        directoryModel::clearDir($this->testDir, true);

        $this->assertDirectoryDoesNotExist($subDir);
    }

    /**
     * Test createDir() creates nested directories recursively
     */
    public function testCreateDirRecursive()
    {
        $deepPath = $this->testDir . 'a/b/c';
        directoryModel::createDir($deepPath);

        $this->assertTrue(is_dir($deepPath));
    }
}
