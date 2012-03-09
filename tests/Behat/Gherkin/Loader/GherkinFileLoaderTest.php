<?php

namespace Tests\Behat\Gherkin\Loader;

use Symfony\Component\Finder\Finder,
    Symfony\Component\Translation\Translator,
    Symfony\Component\Translation\Loader\XliffFileLoader,
    Symfony\Component\Translation\MessageSelector;

use Behat\Gherkin\Lexer,
    Behat\Gherkin\Parser,
    Behat\Gherkin\Node,
    Behat\Gherkin\Keywords\SymfonyTranslationKeywords,
    Behat\Gherkin\Loader\GherkinFileLoader;

class GherkinFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    private $loader;
    private $featuresPath;

    protected function setUp()
    {
        $translator     = new Translator('en', new MessageSelector());
        $keywords       = new SymfonyTranslationKeywords($translator);
        $parser         = new Parser(new Lexer($keywords));
        $this->loader   = new GherkinFileLoader($parser);

        $translator->addLoader('xliff', new XliffFileLoader());
        $translator->addResource('xliff', __DIR__ . '/../../../../i18n/en.xliff', 'gherkin');
        $translator->addResource('xliff', __DIR__ . '/../../../../i18n/ru.xliff', 'gherkin');

        $this->featuresPath = realpath(__DIR__ . '/../Fixtures/features');
    }

    public function testSupports()
    {
        $this->assertFalse($this->loader->supports('non-existent path'));
        $this->assertFalse($this->loader->supports('non-existent path:2'));

        $this->assertFalse($this->loader->supports(__DIR__));
        $this->assertFalse($this->loader->supports(__DIR__ . ':d'));
        $this->assertFalse($this->loader->supports(__FILE__));
        $this->assertTrue($this->loader->supports(__DIR__ . '/../Fixtures/features/pystring.feature'));
    }

    public function testLoad()
    {
        $features = $this->loader->load($this->featuresPath . '/pystring.feature');
        $this->assertEquals(1, count($features));
        $this->assertEquals('A py string feature', $features[0]->getTitle());
        $this->assertEquals($this->featuresPath.DIRECTORY_SEPARATOR.'pystring.feature', $features[0]->getFile());

        $features = $this->loader->load($this->featuresPath . '/multiline_name.feature');
        $this->assertEquals(1, count($features));
        $this->assertEquals('multiline', $features[0]->getTitle());
        $this->assertEquals($this->featuresPath.DIRECTORY_SEPARATOR.'multiline_name.feature', $features[0]->getFile());
    }

    public function testParsingUncachedFeature()
    {
        $cache = $this->getMockBuilder('Behat\Gherkin\Cache\CacheInterface')->getMock();
        $this->loader->setCache($cache);

        $cache->expects($this->once())
            ->method('isFresh')
            ->with($path = $this->featuresPath.'/pystring.feature', filemtime($path))
            ->will($this->returnValue(false));

        $cache->expects($this->once())
            ->method('write');

        $features = $this->loader->load($this->featuresPath . '/pystring.feature');
        $this->assertEquals(1, count($features));
    }

    public function testParsingCachedFeature()
    {
        $cache = $this->getMockBuilder('Behat\Gherkin\Cache\CacheInterface')->getMock();
        $this->loader->setCache($cache);

        $cache->expects($this->once())
            ->method('isFresh')
            ->with($path = $this->featuresPath.'/pystring.feature', filemtime($path))
            ->will($this->returnValue(true));

        $cache->expects($this->once())
            ->method('read')
            ->with($path)
            ->will($this->returnValue('cache'));

        $cache->expects($this->never())
            ->method('write');

        $features = $this->loader->load($this->featuresPath . '/pystring.feature');
        $this->assertEquals('cache', $features[0]);
    }

    public function testBasePath()
    {
        $this->assertFalse($this->loader->supports('features'));
        $this->assertFalse($this->loader->supports('tables.feature'));

        $this->loader->setBasePath($this->featuresPath . '/../');
        $this->assertFalse($this->loader->supports('features'));
        $this->assertFalse($this->loader->supports('tables.feature'));
        $this->assertTrue($this->loader->supports('features/tables.feature'));

        $features = $this->loader->load('features/pystring.feature');
        $this->assertEquals(1, count($features));
        $this->assertEquals('A py string feature', $features[0]->getTitle());
        $this->assertEquals('features'.DIRECTORY_SEPARATOR.'pystring.feature', $features[0]->getFile());

        $this->loader->setBasePath($this->featuresPath);
        $features = $this->loader->load('multiline_name.feature');
        $this->assertEquals(1, count($features));
        $this->assertEquals('multiline', $features[0]->getTitle());
        $this->assertEquals('multiline_name.feature', $features[0]->getFile());
    }
}
