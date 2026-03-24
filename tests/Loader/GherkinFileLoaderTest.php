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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GherkinFileLoaderTest extends TestCase
{
    private GherkinFileLoader $loader;
    private string $featuresPath;

    private static function featuresPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'features';
    }

    protected function setUp(): void
    {
        $parser = new Parser(new Lexer(new CucumberDialectProvider()));
        $this->loader = new GherkinFileLoader($parser);

        $this->featuresPath = self::featuresPath();
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

    /**
     * @return array<string, array{?string, array<string, bool>}>
     */
    public static function providerSupportsWithBasePath(): array
    {
        return [
            'with no base path set' => [
                null,
                [
                    // The default is the current working directory, and there are no files there
                    'features' => false,
                    'tables.feature' => false,
                    'features/tables.feature' => false,
                    'features/pystring.feature' => false,
                    'features/multiline_name.feature' => false,
                ],
            ],
            'with base path set to features directory' => [
                self::featuresPath(),
                [
                    'features' => false,
                    'tables.feature' => true,
                    'pystring.feature' => true,
                    'features/tables.feature' => false,
                    'features/pystring.feature' => false,
                ],
            ],
            'with base path set to parent of features directory' => [
                self::featuresPath() . '/../',
                [
                    'features' => false,
                    'tables.feature' => false,
                    'pystring.feature' => false,
                    'features/tables.feature' => true,
                    'features/pystring.feature' => true,
                ],
            ],
        ];
    }

    /**
     * @param array<string,bool> $expected
     */
    #[DataProvider('providerSupportsWithBasePath')]
    public function testSupportsWithBasePath(?string $basePath, array $expected): void
    {
        if ($basePath !== null) {
            $this->loader->setBasePath($basePath);
        }

        $actual = [];
        foreach (array_keys($expected) as $resource) {
            $actual[$resource] = $this->loader->supports($resource);
        }

        $this->assertSame($expected, $actual);
    }

    public function testLoadWithBasePath(): void
    {
        $this->loader->setBasePath(self::featuresPath() . '/../');
        $features = $this->loader->load('features/pystring.feature');
        $this->assertCount(1, $features);
        $this->assertEquals('A py string feature', $features[0]->getTitle());
        $this->assertEquals(self::featuresPath() . DIRECTORY_SEPARATOR . 'pystring.feature', $features[0]->getFile());

        $this->loader->setBasePath(self::featuresPath());
        $features = $this->loader->load('multiline_name.feature');
        $this->assertCount(1, $features);
        $this->assertEquals('multiline', $features[0]->getTitle());
        $this->assertEquals(self::featuresPath() . DIRECTORY_SEPARATOR . 'multiline_name.feature', $features[0]->getFile());
    }
}
