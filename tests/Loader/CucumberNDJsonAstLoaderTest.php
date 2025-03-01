<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Loader;

use Behat\Gherkin\Exception\NodeException;
use Behat\Gherkin\Loader\CucumberNDJsonAstLoader;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioNode;
use PHPUnit\Framework\TestCase;

final class CucumberNDJsonAstLoaderTest extends TestCase
{
    private CucumberNDJsonAstLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new CucumberNDJsonAstLoader();
    }

    public function testStringResourcesAreSupported(): void
    {
        $this->assertTrue($this->loader->supports('a string'));
    }

    public function testValidLoading(): void
    {
        $file = $this->serializeCucumberMessagesToFile([
            'gherkinDocument' => [
                'feature' => [
                    'location' => ['line' => 111],
                    'description' => 'Feature with a valid Scenario',
                    'keyword' => 'fea',
                    'language' => 'en',
                    'children' => [
                        [
                            'background' => [
                                'location' => ['line' => 222],
                                'keyword' => 'bac',
                                'name' => 'Empty Background',
                            ],
                        ],
                        [
                            'scenario' => [
                                'location' => ['line' => 333],
                                'keyword' => 'sce',
                                'name' => 'Empty Scenario',
                                'examples' => [],
                            ],
                        ],
                        [
                            'scenario' => [
                                'location' => ['line' => 444],
                                'keyword' => 'out',
                                'examples' => [
                                    [
                                        'location' => ['line' => 555],
                                        'keyword' => 'exa',
                                        'tableHeader' => [
                                            'location' => ['line' => 666],
                                            'cells' => [
                                                ['value' => 'A'],
                                                ['value' => 'B'],
                                            ],
                                        ],
                                        'tableBody' => [
                                            [
                                                'location' => ['line' => 777],
                                                'cells' => [
                                                    ['value' => 'A1'],
                                                    ['value' => 'B1'],
                                                ],
                                            ],
                                            [
                                                'location' => ['line' => 888],
                                                'cells' => [
                                                    ['value' => 'A2'],
                                                    ['value' => 'B2'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $features = $this->loader->load($file);

        $this->assertEquals(
            [
                new FeatureNode(
                    null,
                    'Feature with a valid Scenario',
                    [],
                    new BackgroundNode('Empty Background', [], 'bac', 222),
                    [
                        new ScenarioNode('Empty Scenario', [], [], 'sce', 333),
                        new OutlineNode(null, [], [], [
                            new ExampleTableNode([
                                666 => ['A', 'B'],
                                777 => ['A1', 'B1'],
                                888 => ['A2', 'B2'],
                            ], 'exa'),
                        ], 'out', 444),
                    ],
                    'fea',
                    'en',
                    $file,
                    111,
                ),
            ],
            $features,
        );
    }

    public function testOutlineTableHeaderIsRequired(): void
    {
        $this->expectException(NodeException::class);
        $this->expectExceptionMessage('Table header is required, but none was specified for the example on line 3.');

        $this->loader->load($this->serializeCucumberMessagesToFile([
            'gherkinDocument' => [
                'feature' => [
                    'location' => ['line' => 1],
                    'description' => 'Feature containing a scenario with an invalid example table structure',
                    'children' => [
                        [
                            'scenario' => [
                                'location' => ['line' => 2],
                                'examples' => [
                                    [
                                        'location' => ['line' => 3],
                                        'tableBody' => [
                                            [
                                                'cells' => [
                                                    ['value' => 'A1'],
                                                    ['value' => 'B1'],
                                                ],
                                            ],
                                            [
                                                'cells' => [
                                                    ['value' => 'A2'],
                                                    ['value' => 'B2'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]));
    }

    private function serializeCucumberMessagesToFile(mixed ...$messages): string
    {
        return 'data://application/x-ndjson;base64,'
            . base64_encode(implode("\n", array_map(json_encode(...), $messages)) . "\n");
    }
}
