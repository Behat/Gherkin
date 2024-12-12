<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Loader;

use Behat\Gherkin\Loader\ArrayLoader;
use Behat\Gherkin\Node\OutlineNode;
use PHPUnit\Framework\TestCase;

class ArrayLoaderTest extends TestCase
{
    /** @var ArrayLoader */
    private $loader;

    protected function setUp(): void
    {
        $this->loader = new ArrayLoader();
    }

    public function testSupports()
    {
        $this->assertFalse($this->loader->supports(__DIR__));
        $this->assertFalse($this->loader->supports(__FILE__));
        $this->assertFalse($this->loader->supports('string'));
        $this->assertFalse($this->loader->supports(['wrong_root']));
        $this->assertFalse($this->loader->supports(['features']));
        $this->assertTrue($this->loader->supports(['features' => []]));
        $this->assertTrue($this->loader->supports(['feature' => []]));
    }

    public function testLoadEmpty()
    {
        $this->assertEquals([], $this->loader->load(['features' => []]));
    }

    public function testLoadFeatures()
    {
        $features = $this->loader->load([
            'features' => [
                [
                    'title' => 'First feature',
                    'line' => 3,
                ],
                [
                    'description' => 'Second feature description',
                    'language' => 'ru',
                    'tags' => ['some', 'tags'],
                ],
            ],
        ]);

        $this->assertCount(2, $features);

        $this->assertEquals(3, $features[0]->getLine());
        $this->assertEquals('First feature', $features[0]->getTitle());
        $this->assertNull($features[0]->getDescription());
        $this->assertNull($features[0]->getFile());
        $this->assertEquals('en', $features[0]->getLanguage());
        $this->assertFalse($features[0]->hasTags());

        $this->assertEquals(1, $features[1]->getLine());
        $this->assertNull($features[1]->getTitle());
        $this->assertEquals('Second feature description', $features[1]->getDescription());
        $this->assertNull($features[1]->getFile());
        $this->assertEquals('ru', $features[1]->getLanguage());
        $this->assertEquals(['some', 'tags'], $features[1]->getTags());
    }

    public function testLoadScenarios()
    {
        $features = $this->loader->load([
            'features' => [
                [
                    'title' => 'Feature',
                    'scenarios' => [
                        [
                            'title' => 'First scenario',
                            'line' => 2,
                        ],
                        [
                            'tags' => ['second', 'scenario', 'tags'],
                        ],
                        [
                            'tags' => ['third', 'scenario'],
                            'line' => 3,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertCount(1, $features);

        $scenarios = $features[0]->getScenarios();

        $this->assertCount(3, $scenarios);

        $this->assertInstanceOf('Behat\Gherkin\Node\ScenarioNode', $scenarios[0]);
        $this->assertEquals('First scenario', $scenarios[0]->getTitle());
        $this->assertFalse($scenarios[0]->hasTags());
        $this->assertEquals(2, $scenarios[0]->getLine());

        $this->assertInstanceOf('Behat\Gherkin\Node\ScenarioNode', $scenarios[1]);
        $this->assertNull($scenarios[1]->getTitle());
        $this->assertEquals(['second', 'scenario', 'tags'], $scenarios[1]->getTags());
        $this->assertEquals(1, $scenarios[1]->getLine());

        $this->assertInstanceOf('Behat\Gherkin\Node\ScenarioNode', $scenarios[2]);
        $this->assertNull($scenarios[2]->getTitle());
        $this->assertEquals(['third', 'scenario'], $scenarios[2]->getTags());
        $this->assertEquals(3, $scenarios[2]->getLine());
    }

    public function testLoadOutline()
    {
        $features = $this->loader->load([
            'features' => [
                [
                    'title' => 'Feature',
                    'scenarios' => [
                        [
                            'type' => 'outline',
                            'title' => 'First outline',
                            'line' => 2,
                        ],
                        [
                            'type' => 'outline',
                            'tags' => ['second', 'outline', 'tags'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertCount(1, $features);

        $outlines = $features[0]->getScenarios();

        $this->assertCount(2, $outlines);

        $this->assertInstanceOf('Behat\Gherkin\Node\OutlineNode', $outlines[0]);
        $this->assertEquals('First outline', $outlines[0]->getTitle());
        $this->assertFalse($outlines[0]->hasTags());
        $this->assertEquals(2, $outlines[0]->getLine());

        $this->assertInstanceOf('Behat\Gherkin\Node\OutlineNode', $outlines[1]);
        $this->assertNull($outlines[1]->getTitle());
        $this->assertEquals(['second', 'outline', 'tags'], $outlines[1]->getTags());
        $this->assertEquals(1, $outlines[1]->getLine());
    }

    public function testOutlineExamples()
    {
        $features = $this->loader->load([
            'features' => [
                [
                    'title' => 'Feature',
                    'scenarios' => [
                        [
                            'type' => 'outline',
                            'title' => 'First outline',
                            'line' => 2,
                            'examples' => [
                                11 => ['user', 'pass'],
                                12 => ['ever', 'sdsd'],
                                13 => ['anto', 'fdfd'],
                            ],
                        ],
                        [
                            'type' => 'outline',
                            'tags' => ['second', 'outline', 'tags'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertCount(1, $features);

        /** @var OutlineNode[] $scenarios */
        $scenarios = $features[0]->getScenarios();
        $scenario = $scenarios[0];

        $this->assertEquals(
            [['user' => 'ever', 'pass' => 'sdsd'], ['user' => 'anto', 'pass' => 'fdfd']],
            $scenario->getExampleTable()->getHash()
        );
    }

    public function testLoadBackground()
    {
        $features = $this->loader->load([
            'features' => [
                [
                ],
                [
                    'background' => [],
                ],
                [
                    'background' => [
                        'line' => 2,
                    ],
                ],
            ],
        ]);

        $this->assertCount(3, $features);

        $this->assertFalse($features[0]->hasBackground());
        $this->assertTrue($features[1]->hasBackground());
        $this->assertEquals(0, $features[1]->getBackground()->getLine());
        $this->assertTrue($features[2]->hasBackground());
        $this->assertEquals(2, $features[2]->getBackground()->getLine());
    }

    public function testLoadSteps()
    {
        $features = $this->loader->load([
            'features' => [
                [
                    'background' => [
                        'steps' => [
                            ['type' => 'Gangway!', 'keyword_type' => 'Given', 'text' => 'bg step 1', 'line' => 3],
                            ['type' => 'Blimey!', 'keyword_type' => 'When', 'text' => 'bg step 2'],
                        ],
                    ],
                    'scenarios' => [
                        [
                            'title' => 'Scenario',
                            'steps' => [
                                ['type' => 'Gangway!', 'keyword_type' => 'Given', 'text' => 'sc step 1'],
                                ['type' => 'Blimey!', 'keyword_type' => 'When', 'text' => 'sc step 2'],
                            ],
                        ],
                        [
                            'title' => 'Outline',
                            'type' => 'outline',
                            'steps' => [
                                ['type' => 'Gangway!', 'keyword_type' => 'Given', 'text' => 'out step 1'],
                                ['type' => 'Blimey!', 'keyword_type' => 'When', 'text' => 'out step 2'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $background = $features[0]->getBackground();
        $this->assertTrue($background->hasSteps());
        $this->assertCount(2, $background->getSteps());
        $steps = $background->getSteps();
        $this->assertEquals('Gangway!', $steps[0]->getType());
        $this->assertEquals('Gangway!', $steps[0]->getKeyword());
        $this->assertEquals('Given', $steps[0]->getKeywordType());
        $this->assertEquals('bg step 1', $steps[0]->getText());
        $this->assertEquals(3, $steps[0]->getLine());
        $this->assertEquals('Blimey!', $steps[1]->getType());
        $this->assertEquals('Blimey!', $steps[1]->getKeyword());
        $this->assertEquals('When', $steps[1]->getKeywordType());
        $this->assertEquals('bg step 2', $steps[1]->getText());
        $this->assertEquals(1, $steps[1]->getLine());

        $scenarios = $features[0]->getScenarios();

        $scenario = $scenarios[0];
        $this->assertTrue($scenario->hasSteps());
        $this->assertCount(2, $scenario->getSteps());
        $steps = $scenario->getSteps();
        $this->assertEquals('Gangway!', $steps[0]->getType());
        $this->assertEquals('Gangway!', $steps[0]->getKeyword());
        $this->assertEquals('Given', $steps[0]->getKeywordType());
        $this->assertEquals('sc step 1', $steps[0]->getText());
        $this->assertEquals(0, $steps[0]->getLine());
        $this->assertEquals('Blimey!', $steps[1]->getType());
        $this->assertEquals('Blimey!', $steps[1]->getKeyword());
        $this->assertEquals('When', $steps[1]->getKeywordType());
        $this->assertEquals('sc step 2', $steps[1]->getText());
        $this->assertEquals(1, $steps[1]->getLine());

        $outline = $scenarios[1];
        $this->assertTrue($outline->hasSteps());
        $this->assertCount(2, $outline->getSteps());
        $steps = $outline->getSteps();
        $this->assertEquals('Gangway!', $steps[0]->getType());
        $this->assertEquals('Gangway!', $steps[0]->getKeyword());
        $this->assertEquals('Given', $steps[0]->getKeywordType());
        $this->assertEquals('out step 1', $steps[0]->getText());
        $this->assertEquals(0, $steps[0]->getLine());
        $this->assertEquals('Blimey!', $steps[1]->getType());
        $this->assertEquals('Blimey!', $steps[1]->getKeyword());
        $this->assertEquals('When', $steps[1]->getKeywordType());
        $this->assertEquals('out step 2', $steps[1]->getText());
        $this->assertEquals(1, $steps[1]->getLine());
    }

    public function testLoadStepArguments()
    {
        $features = $this->loader->load([
            'features' => [
                [
                    'background' => [
                        'steps' => [
                            [
                                'type' => 'Gangway!', 'keyword_type' => 'Given', 'text' => 'step with table argument',
                                'arguments' => [
                                    [
                                        'type' => 'table',
                                        'rows' => [
                                            ['key', 'val'],
                                            [1, 2],
                                            [3, 4],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'Blimey!', 'keyword_type' => 'When', 'text' => 'step with pystring argument',
                                'arguments' => [
                                    [
                                        'type' => 'pystring',
                                        'text' => '    some text',
                                    ],
                                ],
                            ],
                            [
                                'type' => 'Let go and haul', 'keyword_type' => 'Then', 'text' => '2nd step with pystring argument',
                                'arguments' => [
                                    [
                                        'type' => 'pystring',
                                        'text' => 'some text',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $background = $features[0]->getBackground();

        $this->assertTrue($background->hasSteps());

        $steps = $background->getSteps();

        $this->assertCount(3, $steps);

        $arguments = $steps[0]->getArguments();
        $this->assertEquals('Gangway!', $steps[0]->getType());
        $this->assertEquals('Gangway!', $steps[0]->getKeyword());
        $this->assertEquals('Given', $steps[0]->getKeywordType());
        $this->assertEquals('step with table argument', $steps[0]->getText());
        $this->assertInstanceOf('Behat\Gherkin\Node\TableNode', $arguments[0]);
        $this->assertEquals([['key' => 1, 'val' => 2], ['key' => 3, 'val' => 4]], $arguments[0]->getHash());

        $arguments = $steps[1]->getArguments();
        $this->assertEquals('Blimey!', $steps[1]->getType());
        $this->assertEquals('Blimey!', $steps[1]->getKeyword());
        $this->assertEquals('When', $steps[1]->getKeywordType());
        $this->assertEquals('step with pystring argument', $steps[1]->getText());
        $this->assertInstanceOf('Behat\Gherkin\Node\PyStringNode', $arguments[0]);
        $this->assertEquals('    some text', (string) $arguments[0]);

        $arguments = $steps[2]->getArguments();
        $this->assertEquals('Let go and haul', $steps[2]->getType());
        $this->assertEquals('Let go and haul', $steps[2]->getKeyword());
        $this->assertEquals('Then', $steps[2]->getKeywordType());
        $this->assertEquals('2nd step with pystring argument', $steps[2]->getText());
        $this->assertInstanceOf('Behat\Gherkin\Node\PyStringNode', $arguments[0]);
        $this->assertEquals('some text', (string) $arguments[0]);
    }

    public function testSingleFeatureArray()
    {
        $features = $this->loader->load([
            'feature' => [
                'title' => 'Some feature',
            ],
        ]);

        $this->assertCount(1, $features);
        $this->assertEquals('Some feature', $features[0]->getTitle());
    }
}
