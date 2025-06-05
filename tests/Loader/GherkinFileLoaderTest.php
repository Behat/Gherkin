<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Loader;

use Behat\Gherkin\Cache\CacheInterface;
use Behat\Gherkin\Dialect\CucumberDialectProvider;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Loader\GherkinFileLoader;
use Behat\Gherkin\Parser;
use PHPUnit\Framework\TestCase;

class GherkinFileLoaderTest extends TestCase
{
    private GherkinFileLoader $loader;
    private string $featuresPath;

    protected function setUp(): void
    {
        $parser = new Parser(new Lexer(new CucumberDialectProvider()));
        $this->loader = new GherkinFileLoader($parser);

        $this->featuresPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'features';
    }

    public function testSupports(): void
    {
        $this->assertFalse($this->loader->supports('non-existent path'));
        $this->assertFalse($this->loader->supports('non-existent path:2'));

        $this->assertFalse($this->loader->supports(__DIR__));
        $this->assertFalse($this->loader->supports(__DIR__ . ':d'));
        $this->assertFalse($this->loader->supports(__FILE__));
        $this->assertTrue($this->loader->supports(__DIR__ . '/../Fixtures/features/pystring.feature'));
    }

    public function testLoad(): void
    {
        $features = $this->loader->load($this->featuresPath . '/pystring.feature');
        $this->assertCount(1, $features);
        $this->assertEquals('A py string feature', $features[0]->getTitle());
        $this->assertEquals($this->featuresPath . DIRECTORY_SEPARATOR . 'pystring.feature', $features[0]->getFile());

        $features = $this->loader->load($this->featuresPath . '/multiline_name.feature');
        $this->assertCount(1, $features);
        $this->assertEquals('multiline', $features[0]->getTitle());
        $this->assertEquals($this->featuresPath . DIRECTORY_SEPARATOR . 'multiline_name.feature', $features[0]->getFile());
    }

    public function testParsingUncachedFeature(): void
    {
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $this->loader->setCache($cache);

        $cache->expects($this->once())
            ->method('isFresh')
            ->with($path = $this->featuresPath . DIRECTORY_SEPARATOR . 'pystring.feature', filemtime($path))
            ->willReturn(false);

        $cache->expects($this->once())
            ->method('write');

        $features = $this->loader->load($this->featuresPath . '/pystring.feature');
        $this->assertCount(1, $features);
    }

    public function testParsingCachedFeature(): void
    {
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $this->loader->setCache($cache);

        $cache->expects($this->once())
            ->method('isFresh')
            ->with($path = $this->featuresPath . DIRECTORY_SEPARATOR . 'pystring.feature', filemtime($path))
            ->willReturn(true);

        $cache->expects($this->once())
            ->method('read')
            ->with($path)
            ->willReturn('cache');

        $cache->expects($this->never())
            ->method('write');

        $features = $this->loader->load($this->featuresPath . '/pystring.feature');
        $this->assertEquals('cache', $features[0]);
    }

    public function testBasePath(): void
    {
        $this->assertFalse($this->loader->supports('features'));
        $this->assertFalse($this->loader->supports('tables.feature'));

        $this->loader->setBasePath($this->featuresPath . '/../');
        $this->assertFalse($this->loader->supports('features'));
        $this->assertFalse($this->loader->supports('tables.feature'));
        $this->assertTrue($this->loader->supports('features/tables.feature'));

        $features = $this->loader->load('features/pystring.feature');
        $this->assertCount(1, $features);
        $this->assertEquals('A py string feature', $features[0]->getTitle());
        $this->assertEquals($this->featuresPath . DIRECTORY_SEPARATOR . 'pystring.feature', $features[0]->getFile());

        $this->loader->setBasePath($this->featuresPath);
        $features = $this->loader->load('multiline_name.feature');
        $this->assertCount(1, $features);
        $this->assertEquals('multiline', $features[0]->getTitle());
        $this->assertEquals($this->featuresPath . DIRECTORY_SEPARATOR . 'multiline_name.feature', $features[0]->getFile());
    }
}
