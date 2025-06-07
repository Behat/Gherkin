<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Loader;

use Behat\Gherkin\Gherkin;
use Behat\Gherkin\Loader\DirectoryLoader;
use Behat\Gherkin\Loader\GherkinFileLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DirectoryLoaderTest extends TestCase
{
    private MockObject&Gherkin $gherkin;
    private DirectoryLoader $loader;
    private string $featuresPath;

    protected function setUp(): void
    {
        $this->gherkin = $this->createGherkinMock();
        $this->loader = new DirectoryLoader($this->gherkin);

        $this->featuresPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'directories';
    }

    protected function createGherkinMock(): MockObject&Gherkin
    {
        return $this->getMockBuilder(Gherkin::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createGherkinFileLoaderMock(): MockObject&GherkinFileLoader
    {
        return $this->getMockBuilder(GherkinFileLoader::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSupports(): void
    {
        $this->assertFalse($this->loader->supports('non-existent path'));
        $this->assertFalse($this->loader->supports('non-existent path:2'));

        $this->assertFalse($this->loader->supports(__DIR__ . ':d'));
        $this->assertFalse($this->loader->supports(__DIR__ . '/../Fixtures/features/pystring.feature'));
        $this->assertTrue($this->loader->supports(__DIR__));
        $this->assertTrue($this->loader->supports(__DIR__ . '/../Fixtures/features'));
    }

    public function testUndefinedFileLoad(): void
    {
        $this->gherkin
            ->expects($this->once())
            ->method('resolveLoader')
            ->with($this->featuresPath . DIRECTORY_SEPARATOR . 'phps' . DIRECTORY_SEPARATOR . 'some_file.php')
            ->willReturn(null);

        $this->assertEquals([], $this->loader->load($this->featuresPath . '/phps'));
    }

    public function testBasePath(): void
    {
        $this->gherkin
            ->expects($this->once())
            ->method('resolveLoader')
            ->with($this->featuresPath . DIRECTORY_SEPARATOR . 'phps' . DIRECTORY_SEPARATOR . 'some_file.php')
            ->willReturn(null);

        $this->loader->setBasePath($this->featuresPath);

        $this->assertEquals([], $this->loader->load('phps'));
    }

    public function testDefinedFileLoad(): void
    {
        $loaderMock = $this->createGherkinFileLoaderMock();

        $this->gherkin
            ->expects($this->once())
            ->method('resolveLoader')
            ->with($this->featuresPath . DIRECTORY_SEPARATOR . 'phps' . DIRECTORY_SEPARATOR . 'some_file.php')
            ->willReturn($loaderMock);

        $loaderMock
            ->expects($this->once())
            ->method('load')
            ->with($this->featuresPath . DIRECTORY_SEPARATOR . 'phps' . DIRECTORY_SEPARATOR . 'some_file.php')
            ->willReturn(['feature1', 'feature2']);

        $this->assertEquals(['feature1', 'feature2'], $this->loader->load($this->featuresPath . '/phps'));
    }
}
