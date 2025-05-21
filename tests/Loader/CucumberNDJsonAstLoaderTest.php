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

/**
 * @phpstan-import-type TEnvelope from CucumberNDJsonAstLoader
 */
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
                    'name' => 'Feature with a valid Scenario',
                    'description' => '',
                    'keyword' => 'fea',
                    'language' => 'en',
                    'tags' => [],
                    'children' => [
                        [
                            'background' => [
                                'location' => ['line' => 222],
                                'keyword' => 'bac',
                                'name' => 'Empty Background',
                                'description' => '',
                                'steps' => [],
                                'id' => '',
                            ],
                        ],
                        [
                            'scenario' => [
                                'location' => ['line' => 333],
                                'name' => 'Empty Scenario',
                                'description' => '',
                                'keyword' => 'sce',
                                'tags' => [],
                                'steps' => [],
                                'examples' => [],
                                'id' => '',
                            ],
                        ],
                        [
                            'scenario' => [
                                'location' => ['line' => 444],
                                'name' => 'Examples Scenario',
                                'description' => '',
                                'tags' => [],
                                'steps' => [],
                                'id' => '',
                                'keyword' => 'out',
                                'examples' => [
                                    [
                                        'location' => ['line' => 555],
                                        'keyword' => 'exa',
                                        'name' => '',
                                        'description' => '',
                                        'id' => '',
                                        'tags' => [],
                                        'tableHeader' => [
                                            'location' => ['line' => 666],
                                            'cells' => [
                                                ['location' => ['line' => 666], 'value' => 'A'],
                                                ['location' => ['line' => 666], 'value' => 'B'],
                                            ],
                                            'id' => '',
                                        ],
                                        'tableBody' => [
                                            [
                                                'location' => ['line' => 777],
                                                'cells' => [
                                                    ['location' => ['line' => 777], 'value' => 'A1'],
                                                    ['location' => ['line' => 777], 'value' => 'B1'],
                                                ],
                                                'id' => '',
                                            ],
                                            [
                                                'location' => ['line' => 888],
                                                'cells' => [
                                                    ['location' => ['line' => 888], 'value' => 'A2'],
                                                    ['location' => ['line' => 888], 'value' => 'B2'],
                                                ],
                                                'id' => '',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'comments' => [],
            ],
        ]);

        $features = $this->loader->load($file);

        $this->assertEquals(
            [
                new FeatureNode(
                    'Feature with a valid Scenario',
                    null,
                    [],
                    new BackgroundNode('Empty Background', [], 'bac', 222),
                    [
                        new ScenarioNode('Empty Scenario', [], [], 'sce', 333),
                        new OutlineNode('Examples Scenario', [], [], [
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

    public function testNonEmptyOutlineTableBodyRequiresTableHeader(): void
    {
        $file = $this->serializeCucumberMessagesToFile([
            'gherkinDocument' => [
                'feature' => [
                    'location' => ['line' => 1],
                    'name' => 'Feature with an invalid example table',
                    'description' => '',
                    'keyword' => 'feature',
                    'language' => 'en',
                    'tags' => [],
                    'children' => [
                        [
                            'scenario' => [
                                'location' => ['line' => 2],
                                'name' => 'Examples Scenario',
                                'description' => '',
                                'keyword' => 'outline',
                                'steps' => [],
                                'tags' => [],
                                'id' => '',
                                'examples' => [
                                    [
                                        'location' => ['line' => 3],
                                        'keyword' => 'example',
                                        'name' => '',
                                        'description' => '',
                                        'id' => '',
                                        'tags' => [],
                                        'tableBody' => [
                                            [
                                                'location' => ['line' => 777],
                                                'cells' => [
                                                    ['location' => ['line' => 777], 'value' => 'A1'],
                                                    ['location' => ['line' => 777], 'value' => 'B1'],
                                                ],
                                                'id' => '',
                                            ],
                                            [
                                                'location' => ['line' => 888],
                                                'cells' => [
                                                    ['location' => ['line' => 888], 'value' => 'A2'],
                                                    ['location' => ['line' => 888], 'value' => 'B2'],
                                                ],
                                                'id' => '',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'comments' => [],
            ],
        ]);

        $this->expectExceptionObject(new NodeException('Table header is required when a table body is provided for the example on line 3.'));

        $this->loader->load($file);
    }

    public function testEmptyOutlineTableBodyDoesNotRequireTableHeader(): void
    {
        $file = $this->serializeCucumberMessagesToFile([
            'gherkinDocument' => [
                'feature' => [
                    'location' => ['line' => 1],
                    'name' => 'Feature with an empty example table',
                    'description' => '',
                    'keyword' => 'feature',
                    'language' => 'en',
                    'tags' => [],
                    'children' => [
                        [
                            'scenario' => [
                                'location' => ['line' => 2],
                                'name' => 'Examples Scenario',
                                'description' => '',
                                'keyword' => 'outline',
                                'tags' => [],
                                'steps' => [],
                                'examples' => [
                                    [
                                        'location' => ['line' => 3],
                                        'keyword' => 'example',
                                        'tableBody' => [],
                                        'tags' => [],
                                        'name' => '',
                                        'description' => '',
                                        'id' => '',
                                    ],
                                ],
                                'id' => '',
                            ],
                        ],
                    ],
                ],
                'comments' => [],
            ],
        ]);

        $features = $this->loader->load($file);

        $this->assertEquals(
            [
                new FeatureNode(
                    'Feature with an empty example table',
                    '',
                    [],
                    null,
                    [
                        new OutlineNode(
                            'Examples Scenario',
                            [],
                            [],
                            new ExampleTableNode([], 'example'),
                            'outline',
                            2,
                        ),
                    ],
                    'feature',
                    'en',
                    $file,
                    1,
                ),
            ],
            $features,
        );
    }

    /**
     * @phpstan-param TEnvelope ...$messages
     */
    private function serializeCucumberMessagesToFile(mixed ...$messages): string
    {
        return 'data://application/x-ndjson;base64,'
            . base64_encode(implode("\n", array_map(json_encode(...), $messages)) . "\n");
    }
}
