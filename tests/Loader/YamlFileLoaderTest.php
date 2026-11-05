<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Loader;

use Behat\Gherkin\Loader\YamlFileLoader;
use Behat\Gherkin\Node\ScenarioNode;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;

#[IgnoreDeprecations]
class YamlFileLoaderTest extends TestCase
{
    private YamlFileLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new YamlFileLoader();
    }

    public function testIsDeprecated(): void
    {
        $deprecations = [];
        set_error_handler(
            static function (int $errNo, string $errStr) use (&$deprecations): bool {
                $deprecations[] = $errStr;

                return true;
            },
            E_USER_DEPRECATED,
        );

        try {
            new YamlFileLoader();
        } finally {
            restore_error_handler();
        }

        $this->assertSame(
            [
                'Behat\Gherkin\Loader\ArrayLoader is deprecated and will be removed in 5.0',
                'Behat\Gherkin\Loader\YamlFileLoader is deprecated and will be removed in 5.0',
            ],
            $deprecations,
        );
    }

    public function testSupports(): void
    {
        $this->assertFalse($this->loader->supports(__DIR__));
        $this->assertFalse($this->loader->supports(__FILE__));
        $this->assertFalse($this->loader->supports('string'));
        $this->assertFalse($this->loader->supports(__DIR__ . '/file.yml'));
        $this->assertTrue($this->loader->supports(__DIR__ . '/Fixtures/etalons/addition.yml'));
    }

    public function testLoadAddition(): void
    {
        $basePath = __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures';
        $this->loader->setBasePath($basePath);
        $features = $this->loader->load('etalons/addition.yml');

        $this->assertCount(1, $features);
        $this->assertEquals($basePath . DIRECTORY_SEPARATOR . 'etalons' . DIRECTORY_SEPARATOR . 'addition.yml', $features[0]->getFile());
        $this->assertEquals('Addition', $features[0]->getTitle());
        $this->assertEquals(2, $features[0]->getLine());
        $this->assertEquals('en', $features[0]->getLanguage());
        $expectedDescription = <<<'EOS'
        In order to avoid silly mistakes
        As a math idiot
        I want to be told the sum of two numbers
        EOS;
        $this->assertEquals($expectedDescription, $features[0]->getDescription());

        $scenarios = $features[0]->getExecutableChildren();

        $this->assertCount(2, $scenarios);
        $this->assertInstanceOf(ScenarioNode::class, $scenarios[0]);
        $this->assertEquals(7, $scenarios[0]->getLine());
        /* @phpstan-ignore method.deprecated (testing for BC) */
        $this->assertEquals('Add two numbers', $scenarios[0]->getTitle());
        $steps = $scenarios[0]->getSteps();
        $this->assertCount(4, $steps);
        $this->assertEquals(9, $steps[1]->getLine());
        /* @phpstan-ignore method.deprecated (testing for BC) */
        $this->assertEquals('And', $steps[1]->getType());
        $this->assertEquals('And', $steps[1]->getKeyword());
        $this->assertEquals('Given', $steps[1]->getKeywordType());
        $this->assertEquals('I have entered 12 into the calculator', $steps[1]->getText());

        $this->assertInstanceOf(ScenarioNode::class, $scenarios[1]);
        $this->assertEquals(13, $scenarios[1]->getLine());
        /* @phpstan-ignore method.deprecated (testing for BC) */
        $this->assertEquals('Div two numbers', $scenarios[1]->getTitle());
        $steps = $scenarios[1]->getSteps();
        $this->assertCount(4, $steps);
        $this->assertEquals(16, $steps[2]->getLine());
        /* @phpstan-ignore method.deprecated (testing for BC) */
        $this->assertEquals('When', $steps[2]->getType());
        $this->assertEquals('When', $steps[2]->getKeyword());
        $this->assertEquals('When', $steps[2]->getKeywordType());
        $this->assertEquals('I press div', $steps[2]->getText());
    }
}
