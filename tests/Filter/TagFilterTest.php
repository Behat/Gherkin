<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\TagFilter;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioNode;
use ErrorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TagFilterTest extends TestCase
{
    public function testFilterFeature(): void
    {
        $feature = new FeatureNode(null, null, ['wip'], null, [], '', '', null, 1);
        $filter = new TagFilter('@wip');
        $this->assertEquals($feature, $filter->filterFeature($feature));

        $scenarios = [
            new ScenarioNode(null, [], [], '', 2),
            $matchedScenario = new ScenarioNode(null, ['wip'], [], '', 4),
        ];
        $feature = new FeatureNode(null, null, [], null, $scenarios, '', '', null, 1);
        $filteredFeature = $filter->filterFeature($feature);

        $this->assertSame([$matchedScenario], $filteredFeature->getScenarios());

        $filter = new TagFilter('~@wip');
        $scenarios = [
            $matchedScenario = new ScenarioNode(null, [], [], '', 2),
            new ScenarioNode(null, ['wip'], [], '', 4),
        ];
        $feature = new FeatureNode(null, null, [], null, $scenarios, '', '', null, 1);
        $filteredFeature = $filter->filterFeature($feature);

        $this->assertSame([$matchedScenario], $filteredFeature->getScenarios());
    }

    /**
     * @return array<array{string, list<string>, bool}>
     */
    public static function providerFeatureMatches(): array
    {
        return [
            // Single tag matches if tag is present
            ['@wip', [], false],
            ['@wip', ['wip'], true],

            // Negated `~` tag matches if tag is NOT present
            ['~@done', ['wip'], true],
            ['~@done', ['wip', 'done'], false],

            // Or `,` matches if ANY of the list of tags is present
            ['@tag5,@tag4,@tag6', ['tag1', 'tag2', 'tag3'], false],
            ['@tag5,@tag4,@tag6', ['tag1', 'tag2', 'tag3', 'tag5'], true],
            ['@tag5,@tag4,@tag6', ['tag1', 'tag2', 'tag3', 'tag5'], true],

            // And `&&` matches if ALL of the list of tags is present
            ['@wip&&@vip', ['wip', 'done'], false],
            ['@wip&&@vip', ['wip', 'done'], false],
            ['@wip&&@vip', ['wip', 'done', 'vip'], true],

            // `,` has precedence over `&&` - resolves as "(@wip OR @vip) AND user"
            ['@wip,@vip&&@user', ['wip'], false],
            ['@wip,@vip&&@user', ['vip'], false],
            ['@wip,@vip&&@user', ['wip', 'user'], true],
            ['@wip,@vip&&@user', ['vip', 'user'], true],
        ];
    }

    /**
     * @param list<string> $featureTags
     */
    #[DataProvider('providerFeatureMatches')]
    public function testIsFeatureMatchFilter(string $filterString, array $featureTags, bool $expect): void
    {
        $feature = new FeatureNode(null, null, $featureTags, null, [], '', '', null, 1);
        $filter = new TagFilter($filterString);
        $this->assertSame($expect, $filter->isFeatureMatch($feature));
    }

    /**
     * @return iterable<array{string, list<string>, list<string>, bool}>
     */
    public static function providerScenarioMatches(): iterable
    {
        // Behaviour matches filtering Features, if the tags are present on the Scenario instead of the Feature
        foreach (self::providerFeatureMatches() as [$filterString, $featureTags, $expect]) {
            yield [$filterString, [], $featureTags, $expect];
        }

        // Additionally, filter expressions match based on the combined list of Feature and Scenario tags
        yield from [
            // `&&` matches if one tag present on the feature and one on the scenario
            ['@feature-tag&&@user', ['feature-tag'], ['wip', 'user'], true],
            ['@feature-tag&&@user', ['feature-tag'], ['wip'], false],
        ];
    }

    /**
     * @param list<string> $featureTags
     * @param list<string> $scenarioTags
     */
    #[DataProvider('providerScenarioMatches')]
    public function testIsScenarioMatchFilterWithScenarioNode(string $filterString, array $featureTags, array $scenarioTags, bool $expect): void
    {
        $feature = new FeatureNode(null, null, $featureTags, null, [], '', '', null, 1);
        $scenario = new ScenarioNode(null, $scenarioTags, [], '', 2);
        $filter = new TagFilter($filterString);
        $this->assertSame($expect, $filter->isScenarioMatch($feature, $scenario));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function providerScenarioOutlineFilterMatches(): array
    {
        return [
            'match if ANY Examples tables match the tag' => [
                '@etag3',
                true,
            ],
            'match if ANY Examples tables match a NOT filter' => [
                '~@etag3',
                true,
            ],
            'match if the Outline matches the tag' => [
                '@wip',
                true,
            ],
            'match if tags present on Outline & ANY Examples' => [
                '@wip&&~@etag3',
                true,
            ],
            'match if tags present on Feature, Outline & ANY Examples' => [
                '@feature-tag&&@etag1&&@wip',
                true,
            ],
            'match if tags present on Feature & Outline & ALL Examples match the NOT filter' => [
                '@feature-tag&&~@etag11111&&@wip',
                true,
            ],
            'match if tags present on Feature & Outline & ANY Examples match the NOT filter' => [
                '@feature-tag&&~@etag1&&@wip',
                true,
            ],
            'match if tags present on Feature & ALL Examples' => [
                '@feature-tag&&@etag2',
                true,
            ],
            'no match if ALL Examples match ONE of the NOT filters' => [
                '~@etag1&&~@etag3',
                false,
            ],
            'no match if NO Examples match ALL of the AND filters' => [
                '@etag1&&@etag3',
                false,
            ],
        ];
    }

    #[DataProvider('providerScenarioOutlineFilterMatches')]
    public function testIsScenarioMatchFilterConsidersOutlineAndExampleTableTags(string $filterString, bool $expect): void
    {
        $feature = new FeatureNode(null, null, ['feature-tag'], null, [], '', '', null, 1);
        $scenario = new OutlineNode(null, ['wip'], [], [
            new ExampleTableNode([], '', ['etag1', 'etag2']),
            new ExampleTableNode([], '', ['etag2', 'etag3']),
        ], '', 2);

        $tagFilter = new TagFilter($filterString);
        $this->assertSame($expect, $tagFilter->isScenarioMatch($feature, $scenario));
    }

    public function testFilterFeatureWithTaggedExamples(): void
    {
        $exampleTableNode1 = new ExampleTableNode([], '', ['etag1', 'etag2']);
        $exampleTableNode2 = new ExampleTableNode([], '', ['etag2', 'etag3']);
        $scenario = new OutlineNode(null, ['wip'], [], [
            $exampleTableNode1,
            $exampleTableNode2,
        ], '', 2);
        $feature = new FeatureNode(null, null, ['feature-tag'], null, [$scenario], '', '', null, 1);

        $tagFilter = new TagFilter('@etag2');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertEquals($scenario, $scenarioInterfaces[0]);

        $tagFilter = new TagFilter('@etag1');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertInstanceOf(OutlineNode::class, $scenarioInterfaces[0]);
        $this->assertEquals([$exampleTableNode1], $scenarioInterfaces[0]->getExampleTables());

        $tagFilter = new TagFilter('~@etag3');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertInstanceOf(OutlineNode::class, $scenarioInterfaces[0]);
        $this->assertEquals([$exampleTableNode1], $scenarioInterfaces[0]->getExampleTables());

        $tagFilter = new TagFilter('@wip');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertEquals($scenario, $scenarioInterfaces[0]);

        $tagFilter = new TagFilter('@wip&&@etag3');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertInstanceOf(OutlineNode::class, $scenarioInterfaces[0]);
        $this->assertEquals([$exampleTableNode2], $scenarioInterfaces[0]->getExampleTables());

        $tagFilter = new TagFilter('@feature-tag&&@etag1&&@wip');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertInstanceOf(OutlineNode::class, $scenarioInterfaces[0]);
        $this->assertEquals([$exampleTableNode1], $scenarioInterfaces[0]->getExampleTables());

        $tagFilter = new TagFilter('@feature-tag&&~@etag11111&&@wip');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertEquals($scenario, $scenarioInterfaces[0]);

        $tagFilter = new TagFilter('@feature-tag&&~@etag1&&@wip');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertInstanceOf(OutlineNode::class, $scenarioInterfaces[0]);
        $this->assertEquals([$exampleTableNode2], $scenarioInterfaces[0]->getExampleTables());

        $tagFilter = new TagFilter('@feature-tag&&@etag2');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertEquals($scenario, $scenarioInterfaces[0]);

        $exampleTableNode1 = new ExampleTableNode([], '', ['etag1', 'etag']);
        $exampleTableNode2 = new ExampleTableNode([], '', ['etag2', 'etag22', 'etag']);
        $exampleTableNode3 = new ExampleTableNode([], '', ['etag3', 'etag22', 'etag']);
        $exampleTableNode4 = new ExampleTableNode([], '', ['etag4', 'etag']);
        $scenario1 = new OutlineNode(null, ['wip'], [], [
            $exampleTableNode1,
            $exampleTableNode2,
        ], '', 2);
        $scenario2 = new OutlineNode(null, ['wip'], [], [
            $exampleTableNode3,
            $exampleTableNode4,
        ], '', 2);
        $feature = new FeatureNode(null, null, ['feature-tag'], null, [$scenario1, $scenario2], '', '', null, 1);

        $tagFilter = new TagFilter('@etag');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertEquals([$scenario1, $scenario2], $scenarioInterfaces);

        $tagFilter = new TagFilter('@etag22');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertCount(2, $scenarioInterfaces);
        $this->assertInstanceOf(OutlineNode::class, $scenarioInterfaces[0]);
        $this->assertEquals([$exampleTableNode2], $scenarioInterfaces[0]->getExampleTables());
        $this->assertInstanceOf(OutlineNode::class, $scenarioInterfaces[1]);
        $this->assertEquals([$exampleTableNode3], $scenarioInterfaces[1]->getExampleTables());
    }

    /**
     * @phpstan-return list<array{string, list<string>, bool}>
     */
    public static function providerMatchWithNoPrefixInFilter(): array
    {
        // This is officially unsupported (but potentially widespread) use of a filter expression that does not
        // contain the `@` prefix. Behat's documentation shows that the `@` prefix should be provided - however Behat's
        // own tests include an example where this is not the case, which has been passing. Gherkin has not historically
        // validated the tag expression, so we will continue to support these for now.
        // These cases rely on the bulk of the coverage being provided by the other tests, and the knowledge that the
        // implementation ultimately uses the same logic to compare tags from all types of nodes.
        // They are only intended to be temporary until we enforce that filter expressions are valid.
        return [
            ['wip', [], false],
            ['wip', ['slow'], false],
            ['wip', ['wip'], true],
            ['wip', ['slow', 'wip'], true],
            ['tag1&&~tag2&&tag3', [], false],
            ['tag1&&~tag2&&tag3', ['tag1'], false],
            ['tag1&&~tag2&&tag3', ['tag1', 'tag3'], true],
            ['tag1&&~tag2&&tag3', ['tag1', 'tag2'], false],
            ['tag1&&~tag2&&tag3', ['tag1', 'tag4'], false],
            ['tag1&&~tag2&&tag3', ['tag1', 'tag2', 'tag3'], false],
            // Also cover when the file was parsed in compatibility mode including the prefix
            ['wip', [], false],
            ['wip', ['@slow'], false],
            ['wip', ['@wip'], true],
            ['wip', ['@slow', '@wip'], true],
            ['tag1&&~tag2&&tag3', [], false],
            ['tag1&&~tag2&&tag3', ['@tag1'], false],
            ['tag1&&~tag2&&tag3', ['@tag1', '@tag3'], true],
            ['tag1&&~tag2&&tag3', ['@tag1', '@tag2'], false],
            ['tag1&&~tag2&&tag3', ['@tag1', '@tag4'], false],
            ['tag1&&~tag2&&tag3', ['@tag1', '@tag2', '@tag3'], false],
        ];
    }

    /**
     * @phpstan-param list<string> $tags
     */
    #[DataProvider('providerMatchWithNoPrefixInFilter')]
    public function testItMatchesWhenFilterDoesNotContainPrefix(string $filter, array $tags, bool $expect): void
    {
        $feature = new FeatureNode(null, null, $tags, null, [], '', '', null, 1);
        $tagFilter = new TagFilter($filter);
        $this->assertSame($expect, $tagFilter->isFeatureMatch($feature));
    }

    public function testFilterWithWhitespaceIsDeprecated(): void
    {
        $this->expectDeprecationError();

        $tagFilter = new TagFilter('@tag with space');
        $scenario = new ScenarioNode(null, ['tag with space'], [], '', 2);
        $feature = new FeatureNode(null, null, [], null, [$scenario], '', '', null, 1);

        $scenarios = $tagFilter->filterFeature($feature)->getScenarios();

        $this->assertEquals([$scenario], $scenarios);
    }

    public function testTagFilterThatIsAllWhitespaceIsIgnored(): void
    {
        $feature = new FeatureNode(null, null, [], null, [], '', '', null, 1);
        $tagFilter = new TagFilter('');
        $result = $tagFilter->isFeatureMatch($feature);

        $this->assertTrue($result);
    }

    /**
     * @phpstan-return list<array{string, list<string>, bool}>
     */
    public static function providerMatchWithoutRemovingPrefix(): array
    {
        // These cases rely on the bulk of the coverage being provided by the other tests, and the knowledge that the
        // implementation ultimately uses the same logic to compare tags from all types of nodes. They are only intended
        // to be temporary until we drop legacy parsing mode, at which point we can add the `@` to all the tags in the
        // other tests in this file.
        return [
            ['@wip', [], false],
            ['@wip', ['@slow'], false],
            ['@wip', ['@wip'], true],
            ['@wip', ['@slow', '@wip'], true],
            ['@tag1&&~@tag2&&@tag3', [], false],
            ['@tag1&&~@tag2&&@tag3', ['@tag1'], false],
            ['@tag1&&~@tag2&&@tag3', ['@tag1', '@tag3'], true],
            ['@tag1&&~@tag2&&@tag3', ['@tag1', '@tag2'], false],
            ['@tag1&&~@tag2&&@tag3', ['@tag1', '@tag4'], false],
            ['@tag1&&~@tag2&&@tag3', ['@tag1', '@tag2', '@tag3'], false],
        ];
    }

    /**
     * @phpstan-param list<string> $tags
     */
    #[DataProvider('providerMatchWithoutRemovingPrefix')]
    public function testItMatchesTagsParsedWithoutRemovingPrefix(string $filter, array $tags, bool $expect): void
    {
        $feature = new FeatureNode(null, null, $tags, null, [], '', '', null, 1);
        $tagFilter = new TagFilter($filter);
        $this->assertSame($expect, $tagFilter->isFeatureMatch($feature));
    }

    private function expectDeprecationError(): void
    {
        set_error_handler(
            static function (int $errNo, string $errStr, string $errFile, int $errLine) {
                restore_error_handler();
                throw new ErrorException($errStr, $errNo, filename: $errFile, line: $errLine);
            },
            E_ALL
        );

        $this->expectException(ErrorException::class);
    }
}
