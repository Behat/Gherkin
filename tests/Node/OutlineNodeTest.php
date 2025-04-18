<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Node\ExampleNode;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\StepNode;
use PHPUnit\Framework\TestCase;

class OutlineNodeTest extends TestCase
{
    public function testCreatesExamplesForExampleTable(): void
    {
        $steps = [
            new StepNode('Gangway!', 'I am <name>', [], 1, 'Given'),
            new StepNode('Aye!', 'my email is <email>', [], 1, 'And'),
            new StepNode('Blimey!', 'I open homepage', [], 1, 'When'),
            new StepNode('Let go and haul', 'website should recognise me', [], 1, 'Then'),
        ];

        $table = new ExampleTableNode([
            2 => ['name', 'email'],
            22 => ['everzet', 'ever.zet@gmail.com'],
            23 => ['example', 'example@example.com'],
        ], 'Examples');

        $outline = new OutlineNode(null, [], $steps, $table, '', 1);

        $this->assertCount(2, $examples = $outline->getExamples());
        $this->assertEquals(22, $examples[0]->getLine());
        $this->assertEquals(23, $examples[1]->getLine());
        $this->assertEquals(['name' => 'everzet', 'email' => 'ever.zet@gmail.com'], $examples[0]->getTokens());
        $this->assertEquals(['name' => 'example', 'email' => 'example@example.com'], $examples[1]->getTokens());
    }

    public function testCreatesExamplesForExampleTableWithSeveralExamplesAndTags(): void
    {
        $steps = [
            new StepNode('Gangway!', 'I am <name>', [], 1, 'Given'),
            new StepNode('Aye!', 'my email is <email>', [], 1, 'And'),
            new StepNode('Blimey!', 'I open homepage', [], 1, 'When'),
            new StepNode('Let go and haul', 'website should recognise me', [], 1, 'Then'),
        ];

        $table = new ExampleTableNode([
            2 => ['name', 'email'],
            22 => ['everzet', 'ever.zet@gmail.com'],
            23 => ['example', 'example@example.com'],
        ], 'Examples', []);

        $table2 = new ExampleTableNode([
            3 => ['name', 'email'],
            32 => ['everzet2', 'ever.zet2@gmail.com'],
            33 => ['example2', 'example2@example.com'],
        ], 'Examples', ['etag1', 'etag2']);

        $outline = new OutlineNode(null, ['otag1', 'otag2'], $steps, [$table, $table2], '', 1);

        $this->assertSame(
            [
                [
                    'line' => 22,
                    'tokens' => ['name' => 'everzet', 'email' => 'ever.zet@gmail.com'],
                    'tags' => ['otag1', 'otag2'],
                ],
                [
                    'line' => 23,
                    'tokens' => ['name' => 'example', 'email' => 'example@example.com'],
                    'tags' => ['otag1', 'otag2'],
                ],
                [
                    'line' => 32,
                    'tokens' => ['name' => 'everzet2', 'email' => 'ever.zet2@gmail.com'],
                    'tags' => ['otag1', 'otag2', 'etag1', 'etag2'],
                ],
                [
                    'line' => 33,
                    'tokens' => ['name' => 'example2', 'email' => 'example2@example.com'],
                    'tags' => ['otag1', 'otag2', 'etag1', 'etag2'],
                ],
            ],
            array_map(
                static fn (ExampleNode $example) => [
                    'line' => $example->getLine(),
                    'tokens' => $example->getTokens(),
                    'tags' => $example->getTags(),
                ],
                $outline->getExamples(),
            ),
        );
    }

    public function testCreatesEmptyExamplesForEmptyExampleTable(): void
    {
        $steps = [
            new StepNode('Gangway!', 'I am <name>', [], 1, 'Given'),
            new StepNode('Aye!', 'my email is <email>', [], 1, 'And'),
            new StepNode('Blimey!', 'I open homepage', [], 1, 'When'),
            new StepNode('Let go and haul', 'website should recognise me', [], 1, 'Then'),
        ];

        $table = new ExampleTableNode([
            ['name', 'email'],
        ], 'Examples');

        $outline = new OutlineNode(null, [], $steps, $table, '', 1);

        $this->assertCount(0, $outline->getExamples());
    }

    public function testCreatesEmptyExamplesForNoExampleTable(): void
    {
        $steps = [
            new StepNode('Gangway!', 'I am <name>', [], 1, 'Given'),
            new StepNode('Aye!', 'my email is <email>', [], 1, 'And'),
            new StepNode('Blimey!', 'I open homepage', [], 1, 'When'),
            new StepNode('Let go and haul', 'website should recognise me', [], 1, 'Then'),
        ];

        $table = new ExampleTableNode([], 'Examples');

        $outline = new OutlineNode(null, [], $steps, [$table], '', 1);

        $this->assertCount(0, $outline->getExamples());
    }

    public function testPopulatesExampleWithOutlineTitle(): void
    {
        $steps = [
            new StepNode('', 'I am <name>', [], 1, 'Given'),
        ];

        $table = new ExampleTableNode(
            [
                10 => ['name', 'email'],
                11 => ['Ciaran', 'ciaran@example.com'],
                12 => ['John', 'john@example.com'],
            ],
            'Examples',
            ['tagA', 'tagB'],
        );

        $outline = new OutlineNode('An outline title for <name>', [], $steps, $table, '', 1);

        $this->assertSame(
            [
                [
                    'getName' => 'An outline title for Ciaran #1',
                    'getTitle' => '| Ciaran | ciaran@example.com |',
                    'getOutlineTitle' => 'An outline title for <name>',
                    'getExampleText' => '| Ciaran | ciaran@example.com |',
                    'getTags' => ['tagA', 'tagB'],
                ],
                [
                    'getName' => 'An outline title for John #2',
                    'getTitle' => '| John   | john@example.com   |',
                    'getOutlineTitle' => 'An outline title for <name>',
                    'getExampleText' => '| John   | john@example.com   |',
                    'getTags' => ['tagA', 'tagB'],
                ],
            ],
            array_map(
                static function (ExampleNode $node) {
                    return [
                        'getName' => $node->getName(),
                        'getTitle' => $node->getTitle(),
                        'getOutlineTitle' => $node->getOutlineTitle(),
                        'getExampleText' => $node->getExampleText(),
                        'getTags' => $node->getTags(),
                    ];
                },
                $outline->getExamples()
            )
        );
    }
}
