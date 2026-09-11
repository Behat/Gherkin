<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\RuleNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

/**
 * @phpstan-type TFeatureChild BackgroundNode|RuleNode|ScenarioInterface
 */
class FeatureNodeTest extends TestCase
{
    /**
     * @phpstan-return iterable<string, list{FeatureNode, list<TFeatureChild>}>
     */
    public static function providerExecutableChildren(): iterable
    {
        $background = StubNode::background(title: 'Background');
        $scenario1 = StubNode::scenario(title: 'Scenario 1');
        $rule1 = StubNode::rule(title: 'Rule');
        $outline1 = StubNode::outline(title: 'Outline');

        yield 'no children' => [
            StubNode::feature(background: null, scenarios: []),
            [],
        ];

        yield 'background only' => [
            StubNode::feature(background: $background, scenarios: []),
            [],
        ];

        yield 'scenarios only' => [
            StubNode::feature(background: null, scenarios: [$scenario1, $outline1]),
            [$scenario1, $outline1],
        ];

        yield 'background and scenarios' => [
            StubNode::feature(background: $background, scenarios: [$scenario1]),
            [$scenario1],
        ];

        yield 'background and scenarios and rules' => [
            StubNode::feature(background: $background, scenarios: [$scenario1, $rule1]),
            [$scenario1, $rule1],
        ];

        yield 'rules with no background' => [
            StubNode::feature(background: null, scenarios: [$rule1]),
            [$rule1],
        ];
    }

    /**
     * @phpstan-param list<TFeatureChild> $expect
     */
    #[DataProvider('providerExecutableChildren')]
    public function testGetExecutableChildrenReturnsRulesScenariosAndOutlines(FeatureNode $feature, array $expect): void
    {
        $this->assertSame($expect, $feature->getExecutableChildren());
    }

    /**
     * @phpstan-return iterable<string, list{FeatureNode, list<ScenarioInterface>}>
     */
    public static function providerRulesBackwardsCompatibility(): iterable
    {
        yield 'empty list for empty feature' => [
            StubNode::feature(background: null, scenarios: []),
            [],
        ];

        yield 'empty list when only a background' => [
            StubNode::feature(
                background: StubNode::background(),
                scenarios: [],
            ),
            [],
        ];

        yield 'original scenarios and outlines when no rules' => [
            StubNode::feature(
                background: StubNode::background(),
                scenarios: [StubNode::scenario(title: 'Scenario 1'), StubNode::outline(title: 'Outline 1')],
            ),
            [StubNode::scenario(title: 'Scenario 1'), StubNode::outline(title: 'Outline 1')],
        ];

        yield 'empty when only empty rules' => [
            StubNode::feature(
                background: null,
                scenarios: [StubNode::rule()],
            ),
            [],
        ];

        yield 'un-nests scenarios from inside single rule' => [
            StubNode::feature(
                background: null,
                scenarios: [
                    StubNode::rule(children: [StubNode::scenario(title: 'Scenario 1'),
                        StubNode::outline(title: 'Outline 1'),
                    ]),
                ],
            ),
            [StubNode::scenario(title: 'Scenario 1'), StubNode::outline(title: 'Outline 1')],
        ];

        yield 'un-nests in order with mix of scenarios and rules' => [
            StubNode::feature(
                background: null,
                scenarios: [
                    StubNode::scenario(title: 'Scenario 2'),
                    StubNode::rule(children: [
                        StubNode::scenario(title: 'Scenario 1'),
                        StubNode::outline(title: 'Outline 1'),
                    ]),
                    StubNode::rule(children: [
                        StubNode::scenario(title: 'Scenario 3')]),
                ],
            ),
            [
                StubNode::scenario(title: 'Scenario 2'),
                StubNode::scenario(title: 'Scenario 1'),
                StubNode::outline(title: 'Outline 1'),
                StubNode::scenario(title: 'Scenario 3'),
            ],
        ];

        yield 'merges rule background steps into each scenario' => [
            StubNode::feature(
                background: StubNode::background(steps: [
                    StubNode::step(keyword: 'Given', text: 'background state for all scenarios'),
                ]),
                scenarios: [
                    StubNode::rule(children: [
                        StubNode::background(steps: [
                            StubNode::step(keyword: 'Given', text: 'some pre-existing state'),
                            StubNode::step(keyword: 'And', text: 'some other precondition'),
                        ]),
                        StubNode::scenario(steps: [
                            StubNode::step('When', 'I do something'),
                        ]),
                        StubNode::outline(steps: [
                            StubNode::step('When', 'They do something'),
                        ]),
                        StubNode::scenario(
                            title: 'Scenario with no steps',
                            steps: [],
                        ),
                    ]),
                ],
            ),
            [
                StubNode::scenario(steps: [
                    StubNode::step(keyword: 'Given', text: 'some pre-existing state'),
                    StubNode::step(keyword: 'And', text: 'some other precondition'),
                    StubNode::step('When', 'I do something'),
                ]),
                StubNode::outline(steps: [
                    StubNode::step(keyword: 'Given', text: 'some pre-existing state'),
                    StubNode::step(keyword: 'And', text: 'some other precondition'),
                    StubNode::step('When', 'They do something'),
                ]),
                StubNode::scenario(
                    title: 'Scenario with no steps',
                    steps: [
                        StubNode::step(keyword: 'Given', text: 'some pre-existing state'),
                        StubNode::step(keyword: 'And', text: 'some other precondition'),
                    ],
                ),
            ],
        ];

        yield 'merges rule tags into hoisted scenarios' => [
            // External code (e.g. Behat tagged hooks) may toggle logic based on the union of feature & scenario tags.
            // Logically, the union should also include Rule tags, but the legacy caller cannot see these.
            // Therefore we merge them into the hoisted scenario.
            StubNode::feature(
                // Note the feature tag is *not* merged into the scenario - legacy callers already know about these.
                tags: ['@feature-tag'],
                scenarios: [
                    StubNode::rule(
                        tags: ['@rule-tag', '@othertag'],
                        children: [
                            StubNode::scenario(keyword: 'Scenario', title: 'Untagged scenario'),
                            StubNode::outline(keyword: 'Outline', title: 'Tagged scenario', tags: ['@othertag', '@scenario-tag']),
                        ]
                    ),
                ]
            ),
            [
                StubNode::scenario(
                    title: 'Untagged scenario',
                    tags: ['@rule-tag', '@othertag'],
                    keyword: 'Scenario'
                ),
                StubNode::outline(
                    title: 'Tagged scenario',
                    tags: ['@rule-tag', '@othertag', '@scenario-tag'],
                    keyword: 'Outline'
                ),
            ],
        ];
    }

    /**
     * @phpstan-param list<ScenarioInterface> $expect
     */
    #[DataProvider('providerRulesBackwardsCompatibility')]
    public function testGetScenariosExpandsRulesToScenariosForBackwardsCompatibility(FeatureNode $feature, array $expect): void
    {
        $scenarios = $feature->getScenarios();
        foreach ($scenarios as $scenario) {
            $this->assertInstanceOf(ScenarioInterface::class, $scenario);
        }

        $this->assertEquals($expect, $feature->getScenarios());
    }

    public function testGetScenariosClonesAllPropertiesWhenHoistingRuleChildren(): void
    {
        $step1 = StubNode::step(keyword: 'Given', text: 'some pre-existing state');
        $step2 = StubNode::step('When', 'I do something');
        $step3 = StubNode::step('When', 'They do something');
        $table1 = StubNode::exampleTable(table: [['one']]);

        $feature = StubNode::feature(
            scenarios: [
                StubNode::rule(children: [
                    StubNode::background(steps: [$step1]),
                    // Using explicit constructors rather than the Stub method so that these are reviewed if we
                    // change the constructor signature, to ensure all properties are represented in this test.
                    new ScenarioNode(
                        title: 'Scenario with steps',
                        tags: ['slow'],
                        steps: [$step2],
                        keyword: 'Story',
                        line: 15,
                        description: 'This scenario has steps',
                    ),
                    new OutlineNode(
                        title: 'Outline with steps',
                        tags: ['fast'],
                        steps: [$step3],
                        tables: [$table1],
                        keyword: 'Scenario',
                        line: 15,
                        description: 'Some outline with info',
                    ),
                ]),
            ],
        );

        $this->assertEquals([
            new ScenarioNode(
                title: 'Scenario with steps',
                tags: ['slow'],
                steps: [$step1, $step2],
                keyword: 'Story',
                line: 15,
                description: 'This scenario has steps',
            ),
            new OutlineNode(
                title: 'Outline with steps',
                tags: ['fast'],
                steps: [$step1, $step3],
                tables: [$table1],
                keyword: 'Scenario',
                line: 15,
                description: 'Some outline with info',
            ),
        ], $feature->getScenarios());
    }

    public function testGetScenariosThrowsOnAttemptToExtractRuleWithCustomScenarioClass(): void
    {
        $feature = StubNode::feature(
            scenarios: [
                StubNode::rule(
                    children: [
                        StubNode::background(
                            steps: [
                                StubNode::step(keyword: 'Given', text: 'some pre-existing state'),
                            ],
                        ),
                        // Per our phpdoc, it is valid for a Rule to take any ScenarioInterface - a user could
                        // theoretically extend the Parser to build custom objects instead of ScenarioNode.
                        // Even if they extend our ScenarioNode, they're not safe - the extension class could
                        // have a different constructor signature.
                        $this->getMockBuilder(ScenarioNode::class)->disableOriginalConstructor()->getMock(),
                    ],
                ),
            ],
        );

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot extract custom ScenarioInterface from Rule');
        $feature->getScenarios();
    }

    /**
     * Ensures that the objects returned from getScenarios are identical on every call even after unpacking Rules.
     *
     * Historically, getScenarios returned a fixed list of the ScenarioInterface objects created with the class.
     * Although we did not officially document or guarantee that, it's possible that end-user code assumes this will be
     * true and relies on this to match a ScenarioNode from e.g. a test runner event against a ScenarioNode retrieved
     * from the Feature.
     *
     * For absolute safety, we therefore ensure that any Scenarios / Outlines we create at runtime by unpacking Rules
     * also share an object identity for the life of the FeatureNode.
     */
    public function testGetScenarioHoistedRuleScenariosAreObjectIdentical(): void
    {
        $feature = StubNode::feature(
            scenarios: [
                StubNode::rule(
                    children: [
                        StubNode::background(
                            steps: [
                                StubNode::step(keyword: 'Given', text: 'some pre-existing state'),
                            ],
                        ),
                        StubNode::scenario(
                            steps: [
                                StubNode::step(keyword: 'When', text: 'some action is performed'),
                            ],
                        ),
                        StubNode::outline(steps: []),
                    ],
                ),
            ],
        );

        $scenarios1 = $feature->getScenarios();
        $this->assertSame($scenarios1, $feature->getScenarios());
    }
}
