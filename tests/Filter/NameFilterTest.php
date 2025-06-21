<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\NameFilter;
use Behat\Gherkin\Node\ExampleNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NameFilterTest extends TestCase
{
    public function testFilterFeature(): void
    {
        $feature = new FeatureNode('feature1', null, [], null, [], '', '', null, 1);
        $filter = new NameFilter('feature1');
        $this->assertSame($feature, $filter->filterFeature($feature));

        $scenarios = [
            new ScenarioNode('scenario1', [], [], '', 2),
            $matchedScenario = new ScenarioNode('scenario2', [], [], '', 4),
        ];
        $feature = new FeatureNode('feature1', null, [], null, $scenarios, '', '', null, 1);
        $filter = new NameFilter('scenario2');
        $filteredFeature = $filter->filterFeature($feature);

        $this->assertSame([$matchedScenario], $filteredFeature->getScenarios());
    }

    public function testIsFeatureMatchFilter(): void
    {
        $feature = new FeatureNode('random feature title', null, [], null, [], '', '', null, 1);

        $filter = new NameFilter('feature1');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('feature1', null, [], null, [], '', '', null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('feature1 title', null, [], null, [], '', '', null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('some feature1 title', null, [], null, [], '', '', null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('some feature title', null, [], null, [], '', '', null, 1);
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new NameFilter('/fea.ure/');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('some feaSure title', null, [], null, [], '', '', null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('some feture title', null, [], null, [], '', '', null, 1);
        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testFeatureFilterDoesNotMatchDescription(): void
    {
        // Feature descriptions are parsed separately for all GherkinCompatibilityMode settings, and have always been.
        // So for BC we ignore them when filtering a feature.
        $filter = new NameFilter('feature1');
        $feature = new FeatureNode('some feature title', 'for feature1', [], null, [], '', '', null, 1);
        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testUntitledFeatureDoesNotMatch(): void
    {
        $feature = new FeatureNode(null, null, [], null, [], '', '', null, 1);
        $filter = new NameFilter('');

        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testIsScenarioMatchFilter(): void
    {
        $filter = new NameFilter('scenario1');

        $scenario = new ScenarioNode('UNKNOWN', [], [], '', 2);
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $scenario = new ScenarioNode('scenario1', [], [], '', 2);
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $scenario = new ScenarioNode('scenario1 title', [], [], '', 2);
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $scenario = new ScenarioNode('some scenario title', [], [], '', 2);
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $filter = new NameFilter('/sce.ario/');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $filter = new NameFilter('/scen.rio/');
        $this->assertTrue($filter->isScenarioMatch($scenario));
    }

    /**
     * @phpstan-return array<string, list{array{title: string, description: string|null}, bool}>
     */
    public static function providerScenarioMatchDescription(): array
    {
        return [
            'parsed in legacy mode, not matching filter' => [
                ['title' => "multiline\nwith start in text", 'description' => ''],
                false,
            ],
            'parsed in legacy mode, matching filter' => [
                ['title' => "multiline\nstarting as expected", 'description' => ''],
                true,
            ],
            'parsed in compat mode, not matching filter' => [
                ['title' => 'multiline', 'description' => 'with start in text'],
                false,
            ],
            'parsed in compat mode, matching filter' => [
                ['title' => 'multiline', 'description' => 'starting as expected'],
                true,
            ],
            'parsed in compat mode, title matches (and no description)' => [
                ['title' => 'starting title', 'description' => null],
                true,
            ],
        ];
    }

    /**
     * @param array{title:string|null, description:string|null} $scenario
     */
    #[DataProvider('providerScenarioMatchDescription')]
    public function testScenarioFilterMatchesIncludingDescription(array $scenario, bool $expectMatch): void
    {
        // Scenarios may be parsed with multi-line text titles, or with a single line title followed by a description,
        // depending on the GherkinCompatibilityMode.
        // So for BC, the filter considers title *and* description when matching by name.
        $filter = new NameFilter('/^start/m');
        $scenario = new ScenarioNode($scenario['title'], [], [], '', 2, $scenario['description']);
        $this->assertSame($expectMatch, $filter->isScenarioMatch($scenario));
    }

    public function testUntitledScenarioDoesNotMatch(): void
    {
        $scenario = new ScenarioNode(null, [], [], '', 1);
        $filter = new NameFilter('');

        $this->assertFalse($filter->isScenarioMatch($scenario));
    }

    /**
     * @phpstan-return array<string, list<ScenarioInterface>>
     */
    public static function providerScenarioFilterValidTypes(): array
    {
        return [
            'ScenarioNode' => [new ScenarioNode('Scenario match', [], [], '', 2)],
            'OutlineNode' => [new OutlineNode('Outline match', [], [], [], '', 2)],
            // ExampleNode is an example of a ScenarioInterface that does *not* have a description property
            'ExampleNode' => [new ExampleNode('Example match', [], [], [], 2, '', 1)],
        ];
    }

    #[DataProvider('providerScenarioFilterValidTypes')]
    public function testScenarioFilterMatchesAllScenarioInterface(ScenarioInterface $scenario): void
    {
        $filter = new NameFilter('match');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $filter = new NameFilter('no match');
        $this->assertFalse($filter->isScenarioMatch($scenario));
    }
}
