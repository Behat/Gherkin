<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Loader;

use Behat\Gherkin\Loader\DirectoryLoader;
use PHPUnit\Framework\TestCase;

class DirectoryLoaderTest extends TestCase
{
    private $gherkin;
    private $loader;
    private $featuresPath;

    protected function setUp(): void
    {
        $this->gherkin = $this->createGherkinMock();
        $this->loader = new DirectoryLoader($this->gherkin);

        $this->featuresPath = realpath(__DIR__ . '/../Fixtures/directories');
    }

    protected function createGherkinMock()
    {
        $gherkin = $this->getMockBuilder('Behat\Gherkin\Gherkin')
            ->disableOriginalConstructor()
            ->getMock();

        return $gherkin;
    }

    protected function createGherkinFileLoaderMock()
    {
        $loader = $this->getMockBuilder('Behat\Gherkin\Loader\GherkinFileLoader')
            ->disableOriginalConstructor()
            ->getMock();

        return $loader;
    }

    public function testSupports()
    {
        $this->assertFalse($this->loader->supports('non-existent path'));
        $this->assertFalse($this->loader->supports('non-existent path:2'));

        $this->assertFalse($this->loader->supports(__DIR__ . ':d'));
        $this->assertFalse($this->loader->supports(__DIR__ . '/../Fixtures/features/pystring.feature'));
        $this->assertTrue($this->loader->supports(__DIR__));
        $this->assertTrue($this->loader->supports(__DIR__ . '/../Fixtures/features'));
    }

    public function testUndefinedFileLoad()
    {
        $this->gherkin
            ->expects($this->once())
            ->method('resolveLoader')
            ->with($this->featuresPath . DIRECTORY_SEPARATOR . 'phps' . DIRECTORY_SEPARATOR . 'some_file.php')
            ->will($this->returnValue(null));

        $this->assertEquals([], $this->loader->load($this->featuresPath . '/phps'));
    }

    public function testBasePath()
    {
        $this->gherkin
            ->expects($this->once())
            ->method('resolveLoader')
            ->with($this->featuresPath . DIRECTORY_SEPARATOR . 'phps' . DIRECTORY_SEPARATOR . 'some_file.php')
            ->will($this->returnValue(null));

        $this->loader->setBasePath($this->featuresPath);

        $this->assertEquals([], $this->loader->load('phps'));
    }

    public function testDefinedFileLoad()
    {
        $loaderMock = $this->createGherkinFileLoaderMock();

        $this->gherkin
            ->expects($this->once())
            ->method('resolveLoader')
            ->with($this->featuresPath . DIRECTORY_SEPARATOR . 'phps' . DIRECTORY_SEPARATOR . 'some_file.php')
            ->will($this->returnValue($loaderMock));

        $loaderMock
            ->expects($this->once())
            ->method('load')
            ->with($this->featuresPath . DIRECTORY_SEPARATOR . 'phps' . DIRECTORY_SEPARATOR . 'some_file.php')
            ->will($this->returnValue(['feature1', 'feature2']));

        $this->assertEquals(['feature1', 'feature2'], $this->loader->load($this->featuresPath . '/phps'));
    }
}
