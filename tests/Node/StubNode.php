<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Node\ArgumentInterface;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\RuleNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;

final class StubNode
{
    /**
     * @phpstan-param list<string> $tags
     * @phpstan-param array<RuleNode|ScenarioInterface> $scenarios
     */
    public static function feature(
        ?string $title = null,
        ?string $description = null,
        array $tags = [],
        ?BackgroundNode $background = null,
        array $scenarios = [],
        string $keyword = 'Feature',
        string $language = 'en',
        ?string $file = null,
        int $line = 0,
    ): FeatureNode {
        return new FeatureNode(...get_defined_vars());
    }

    /**
     * @phpstan-param StepNode[] $steps
     */
    public static function background(
        ?string $title = null,
        array $steps = [],
        string $keyword = 'Background',
        int $line = 0,
        ?string $description = null,
    ): BackgroundNode {
        return new BackgroundNode(...get_defined_vars());
    }

    /**
     * @phpstan-param list<string> $tags
     * @phpstan-param StepNode[] $steps
     */
    public static function scenario(
        ?string $title = null,
        array $tags = [],
        array $steps = [],
        string $keyword = 'Scenario',
        int $line = 0,
        ?string $description = null,
    ): ScenarioNode {
        return new ScenarioNode(...get_defined_vars());
    }

    /**
     * @phpstan-param list<string> $tags
     * @phpstan-param StepNode[] $steps
     * @phpstan-param ExampleTableNode|array<array-key, ExampleTableNode> $tables
     */
    public static function outline(
        ?string $title = null,
        array $tags = [],
        array $steps = [],
        array|ExampleTableNode $tables = [],
        string $keyword = 'Scenario Outline',
        int $line = 0,
        ?string $description = null,
    ): OutlineNode {
        return new OutlineNode(...get_defined_vars());
    }

    /**
     * @phpstan-param list<string> $tags
     * @phpstan-param list<BackgroundNode|ScenarioInterface> $children
     */
    public static function rule(
        ?string $title = null,
        ?string $description = null,
        array $tags = [],
        array $children = [],
        string $keyword = 'Rule',
        int $line = 0,
    ): RuleNode {
        return new RuleNode(...get_defined_vars());
    }

    /**
     * @phpstan-param ArgumentInterface[] $arguments
     */
    public static function step(
        string $keyword = 'Given',
        string $text = '',
        array $arguments = [],
        int $line = 0,
        ?string $keywordType = null,
        ?string $fullText = null,
    ): StepNode {
        return new StepNode(...get_defined_vars());
    }

    /**
     * @phpstan-param array<int, list<string>> $table
     * @phpstan-param  list<string> $tags
     */
    public static function exampleTable(
        array $table = [],
        string $keyword = 'Examples',
        array $tags = [],
        ?string $name = null,
        ?string $description = null,
    ): ExampleTableNode {
        return new ExampleTableNode(...get_defined_vars());
    }

    private function __construct()
    {
    }
}
