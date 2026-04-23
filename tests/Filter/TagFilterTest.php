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
use Closure;
use ErrorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
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
     * @return iterable<array{string, list<string>, bool}>
     */
    public static function providerFeatureMatches(): iterable
    {
        // Single tag matches if tag is present
        yield ['@wip', [], false];
        yield ['@wip', ['wip'], true];

        // Negated `~` tag matches if tag is NOT present
        yield ['~@done', ['wip'], true];
        yield ['~@done', ['wip', 'done'], false];

        // Or `,` matches if ANY of the list of tags is present
        yield ['@tag5,@tag4,@tag6', ['tag1', 'tag2', 'tag3'], false];
        yield ['@tag5,@tag4,@tag6', ['tag1', 'tag2', 'tag3', 'tag5'], true];
        yield ['@tag5,@tag4,@tag6', ['tag1', 'tag2', 'tag3', 'tag5'], true];

        // And `&&` matches if ALL of the list of tags is present
        yield ['@wip&&@vip', ['wip', 'done'], false];
        yield ['@wip&&@vip', ['wip', 'done'], false];
        yield ['@wip&&@vip', ['wip', 'done', 'vip'], true];

        // `,` has precedence over `&&` - resolves as "(@wip OR @vip) AND user"
        yield ['@wip,@vip&&@user', ['wip'], false];
        yield ['@wip,@vip&&@user', ['vip'], false];
        yield ['@wip,@vip&&@user', ['wip', 'user'], true];
        yield ['@wip,@vip&&@user', ['vip', 'user'], true];

        // `&&` with negated tag matches if only the first tag is present
        yield ['@wip&&~@slow', [], false];
        yield ['@wip&&~@slow', ['wip'], true];
        yield ['@wip&&~@slow', ['wip', 'fast'], true];
        yield ['@wip&&~@slow', ['wip', 'slow'], false];

        // Whitespace around operators is ignored
        yield ['@wip && ~@slow', ['wip', 'fast'], true];
        yield ['@wip && ~@slow', ['wip', 'slow'], false];
        yield ['@wip, @vip && @user', ['wip'], false];
        yield ['@wip, @vip && @user', ['vip'], false];
        yield ['@wip, @vip && @user', ['wip', 'user'], true];
        yield ['@wip, @vip && @user', ['vip', 'user'], true];

        // Edge case - whitespace before a `,` doesn't really make sense, but was historically supported
        yield ['@wip , @vip && @user', ['vip', 'user'], true];
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

        // `&&` matches if one tag present on the feature and one on the scenario
        yield ['@feature-tag&&@user', ['feature-tag'], ['wip', 'user'], true];
        yield ['@feature-tag&&@user', ['feature-tag'], ['wip'], false];

        // Does not match if the feature matches a `~` tag
        yield ['@user&&~@feature-tag', [], [], false];
        yield ['@user&&~@feature-tag', ['feature-tag'], ['user'], false];
        yield ['@user&&~@feature-tag', ['other-feature'], ['user'], true];
        yield ['@user&&~@feature-tag', ['other-feature'], ['api'], false];

        // Matches if the feature or the scenario matches an OR expression
        yield ['@api,@browser', [], [], false];
        yield ['@api,@browser', ['api'], [], true];
        yield ['@api,@browser', ['browser'], [], true];
        yield ['@api,@browser', [], ['api'], true];
        yield ['@api,@browser', [], ['browser'], true];
        yield ['@api,@browser', ['api'], ['browser'], true];
        yield ['@api,@browser', ['browser'], ['api'], true];

        // Not affected if same tag is present on Feature and Scenario
        yield ['@api', ['api'], ['api'], true];
        yield ['@api', ['slow'], ['slow'], false];
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
     * @return iterable<string, array{string, bool}>
     */
    public static function providerScenarioOutlineFilterMatches(): iterable
    {
        yield 'match if ANY Examples tables match the tag' => [
            '@etag3',
            true,
        ];

        yield 'match if ANY Examples tables match a NOT filter' => [
            '~@etag3',
            true,
        ];

        yield 'match if the Outline matches the tag' => [
            '@wip',
            true,
        ];

        yield 'no match if the Outline does not match regardless of Examples' => [
            '@etag2&&~@wip',
            false,
        ];

        yield 'match if tags present on Outline & ANY Examples' => [
            '@wip&&~@etag3',
            true,
        ];

        yield 'match if tags present on Feature, Outline & ANY Examples' => [
            '@feature-tag&&@etag1&&@wip',
            true,
        ];

        yield 'no match if the Feature does not match regardless of Examples' => [
            '@etag2&&~@feature-tag',
            false,
        ];

        yield 'match if tags present on Feature & Outline & ALL Examples match the NOT filter' => [
            '@feature-tag&&~@etag11111&&@wip',
            true,
        ];

        yield 'match if tags present on Feature & Outline & ANY Examples match the NOT filter' => [
            '@feature-tag&&~@etag1&&@wip',
            true,
        ];

        yield 'match if tags present on Feature & ALL Examples' => [
            '@feature-tag&&@etag2',
            true,
        ];

        yield 'match if tags present on Feature & ANY Examples' => [
            '@feature-tag&&@etag3',
            true,
        ];

        yield 'no match if ALL Examples match ONE of the NOT filters' => [
            '~@etag1&&~@etag3',
            false,
        ];

        yield 'no match if NO Examples match ALL of the AND filters' => [
            '@etag1&&@etag3',
            false,
        ];

        yield 'match if ANY Examples match an OR filter' => [
            '@etag1,@etag3',
            true,
        ];

        yield 'allows whitespace around operators' => [
            '@feature-tag && @etag3',
            true,
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

            // And cover with whitespace around operators
            ['tag1 && ~tag2 && tag3', [], false],
            ['tag1 && ~tag2 && tag3', ['tag1'], false],
            ['tag1 && ~tag2 && tag3', ['tag1', 'tag3'], true],
            ['tag1 && ~tag2 && tag3', ['tag1', 'tag2'], false],
            ['tag1 && ~tag2 && tag3', ['tag1', 'tag4'], false],
            ['tag1 && ~tag2 && tag3', ['tag1', 'tag2', 'tag3'], false],
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

    /**
     * @return iterable<string, array{string, expectMatch: bool, expectDeprecation: bool}>
     */
    public static function providerWhitespaceDeprecated(): iterable
    {
        yield 'deprecation if filter has spaces in tag name' => [
            '@tag with space',
            'expectMatch' => true,
            'expectDeprecation' => true,
        ];

        yield 'deprecation if negated filter has spaces in tag name' => [
            '~@tag with space',
            'expectMatch' => false,
            'expectDeprecation' => true,
        ];

        yield 'ignore leading whitespace' => [
            ' @tag1',
            'expectMatch' => true,
            'expectDeprecation' => false,
        ];

        yield 'ignore trailing whitespace' => [
            '@tag1 ',
            'expectMatch' => true,
            'expectDeprecation' => false,
        ];

        yield 'no deprecation if filter has no spaces in tag name' => [
            '@tag1',
            'expectMatch' => true,
            'expectDeprecation' => false,
        ];

        yield 'deprecation with spaces in tag name and around && operator' => [
            '@tag1 && @tag with space',
            'expectMatch' => true,
            'expectDeprecation' => true,
        ];

        yield 'deprecation with spaces in tag name and around , operator' => [
            '@any-tag, @tag with space',
            'expectMatch' => true,
            'expectDeprecation' => true,
        ];

        yield 'no deprecation with spaces only around && operator' => [
            '@tag1 && @tag2',
            'expectMatch' => true,
            'expectDeprecation' => false,
        ];

        yield 'no deprecation with spaces only after , operator' => [
            '@any-tag, @tag2',
            'expectMatch' => true,
            'expectDeprecation' => false,
        ];

        yield 'no deprecation with spaces only around , operator' => [
            '@any-tag , @tag2',
            'expectMatch' => true,
            'expectDeprecation' => false,
        ];

        yield 'no deprecation with spaces only around complex operators' => [
            '@tag1, @tag2 && ~@tag3',
            'expectMatch' => true,
            'expectDeprecation' => false,
        ];

        yield 'allows all whitespace around operators' => [
            // Very much an edge case, but the legacy implementation would have allowed this as it always just used
            // `trim`. And arguably someone *could* have a config file with an indented multiline filter expression.
            "\t@tag1,\n\t@tag2  &&  ~@tag3\n",
            'expectMatch' => true,
            'expectDeprecation' => false,
        ];

        yield 'deprecation on whitespace after ~ operator (and the negated tag is ignored)' => [
            // Edge case - we don't expect people to have whitespace after a `~` and historically that would not
            // have been trimmed so the filter would have matched even if a feature / scenario had the negated tag.
            '~ @tag1',
            'expectMatch' => true,
            'expectDeprecation' => true,
        ];
    }

    #[DataProvider('providerWhitespaceDeprecated')]
    public function testFilterWithWhitespaceIsDeprecated(string $filterString, bool $expectMatch, bool $expectDeprecation): void
    {
        $tagFilter = $this->assertWhetherTriggersDeprecation(
            $expectDeprecation ? 'Tags with whitespace' : false,
            fn () => new TagFilter($filterString)
        );

        $feature = new FeatureNode(null, null, ['tag with space', 'tag1', 'tag2'], null, [], '', '', null, 1);

        $this->assertSame($expectMatch, $tagFilter->isFeatureMatch($feature), 'Expected correct matching behaviour');
    }

    #[TestWith(['', true])]
    #[TestWith([' ', true])]
    public function testTagFilterThatIsAllWhitespaceIsIgnored(string $filterString): void
    {
        $feature = new FeatureNode(null, null, [], null, [], '', '', null, 1);
        $tagFilter = new TagFilter($filterString);
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
            ['@tag1 && ~@tag2 && @tag3', ['@tag1', '@tag3'], true],
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

    /**
     * @template T
     *
     * @param Closure():T $callable
     * @param non-empty-string|false $expectDeprecation
     *
     * @return T
     */
    private function assertWhetherTriggersDeprecation(string|false $expectDeprecation, Closure $callable): mixed
    {
        $deprecationCaptured = false;

        set_error_handler(
            static function (int $errNo, string $errStr, string $errFile, int $errLine) use (&$deprecationCaptured): bool {
                if (($errNo === E_USER_DEPRECATED) && ($deprecationCaptured === false)) {
                    $deprecationCaptured = $errStr;

                    return false;
                }
                throw new ErrorException($errStr, $errNo, filename: $errFile, line: $errLine);
            },
        );

        try {
            $result = $callable();
        } finally {
            restore_error_handler();
        }

        if ($expectDeprecation === false) {
            $this->assertFalse($deprecationCaptured, 'Expected no deprecation to be emitted');
        } else {
            $this->assertIsString($deprecationCaptured, 'Expected deprecation to be emitted');
            $this->assertStringStartsWith($expectDeprecation, $deprecationCaptured, 'Expected correct deprecation message');
        }

        return $result;
    }
}
