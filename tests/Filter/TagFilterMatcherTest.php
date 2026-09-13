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
use Behat\Gherkin\Filter\TagFilterMatcher;
use Behat\Gherkin\Node\FeatureNode;
use Closure;
use ErrorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TagFilterMatcherTest extends TestCase
{
    /**
     * @phpstan-return iterable<array{string, list<string>, bool}>
     */
    public static function providerIsMatch(): iterable
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

        // `&&` with negated tag matches if positive tag is present AND negated tag is absent
        yield ['@wip&&~@slow', [], false];
        yield ['@wip&&~@slow', ['wip'], true];
        yield ['@wip&&~@slow', ['wip', 'fast'], true];
        yield ['@wip&&~@slow', ['wip', 'slow'], false];

        // Whitespace around operators is ignored
        // Should match behaviour of the unspaced examples above
        yield ['@tag5, @tag4, @tag6', ['tag1', 'tag2', 'tag3'], false];
        yield ['@tag5, @tag4, @tag6', ['tag1', 'tag2', 'tag3', 'tag5'], true];

        yield ['@wip && @vip', ['wip', 'done'], false];
        yield ['@wip && @vip', ['wip', 'done', 'vip'], true];
        yield ['@wip &&@vip', ['wip', 'done'], false];
        yield ['@wip &&@vip', ['wip', 'done', 'vip'], true];
        yield ['@wip&& @vip', ['wip', 'done'], false];
        yield ['@wip&& @vip', ['wip', 'done', 'vip'], true];

        yield ['@wip, @vip&&@user', ['wip'], false];
        yield ['@wip, @vip&&@user', ['vip', 'user'], true];
        yield ['@wip, @vip&& @user', ['wip'], false];
        yield ['@wip, @vip&& @user', ['vip', 'user'], true];
        yield ['@wip, @vip &&@user', ['wip'], false];
        yield ['@wip, @vip &&@user', ['vip', 'user'], true];
        yield ['@wip, @vip&& @user', ['wip'], false];
        yield ['@wip, @vip&& @user', ['vip', 'user'], true];
        yield ['@wip,@vip &&@user', ['wip'], false];
        yield ['@wip,@vip &&@user', ['vip', 'user'], true];
        yield ['@wip,@vip&& @user', ['wip'], false];
        yield ['@wip,@vip&& @user', ['vip', 'user'], true];
        yield ['@wip,@vip && @user', ['wip'], false];
        yield ['@wip,@vip && @user', ['vip', 'user'], true];

        yield ['@wip &&~@slow', ['wip'], true];
        yield ['@wip &&~@slow', ['wip', 'slow'], false];
        yield ['@wip && ~@slow', ['wip'], true];
        yield ['@wip && ~@slow', ['wip', 'slow'], false];
        yield ['@wip&& ~@slow', ['wip'], true];
        yield ['@wip&& ~@slow', ['wip', 'slow'], false];

        // Edge case - whitespace before a `,` doesn't really make sense, but was historically supported
        yield ['@wip , @vip && @user', ['vip', 'user'], true];

        // Very much an edge case, but the legacy implementation would have allowed this as it always just used
        // `trim`. And arguably someone *could* have a config file with an indented multiline filter expression.
        yield ["\t@tag1,\n\t@tag2  &&  ~@tag3\n", ['tag1', 'tag3'], false];
        yield ["\t@tag1,\n\t@tag2  &&  ~@tag3\n", ['tag1', 'tag2'], true];

        // Leading and trailing whitespace is ignored
        yield [' @tag1', ['tag1'], true];
        yield [' @tag1 ', ['tag1'], true];
        yield ['@tag1 ', ['tag1'], true];

        // Filter that is entirely whitespace matches anything
        yield ['', ['tag1'], true];
        yield [' ', ['tag1'], true];
        yield ['', [], true];
        yield [' ', [], true];
    }

    /**
     * @param list<string> $tags
     */
    #[DataProvider('providerIsMatch')]
    public function testIsMatch(string $filterString, array $tags, bool $expect): void
    {
        $matcher = new TagFilterMatcher($filterString);
        $this->assertSame($expect, $matcher->isMatch($tags));
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
        $tagFilter = $this->assertTriggersDeprecation(
            'Filter strings should contain `@` prefixes',
            fn () => new TagFilter($filter),
        );

        $feature = new FeatureNode(null, null, $tags, null, [], '', '', null, 1);
        $this->assertSame($expect, $tagFilter->isFeatureMatch($feature));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function providerWhitespaceDeprecated(): iterable
    {
        yield 'deprecation if filter has spaces in tag name' => [
            '@tag with space',
            true,
        ];

        yield 'deprecation if negated filter has spaces in tag name' => [
            '~@tag with space',
            false,
        ];

        yield 'deprecation with spaces in tag name and around && operator' => [
            '@tag1 && @tag with space',
            true,
        ];

        yield 'deprecation with spaces in tag name and around , operator' => [
            '@any-tag, @tag with space',
            true,
        ];

        yield 'deprecation on whitespace after ~ operator (and the negated tag is ignored)' => [
            // Edge case - we don't expect people to have whitespace after a `~` and historically that would not
            // have been trimmed so the filter would have matched even if a feature / scenario had the negated tag.
            '~ @tag1',
            true,
        ];
    }

    #[DataProvider('providerWhitespaceDeprecated')]
    public function testFilterWithWhitespaceIsDeprecated(string $filterString, bool $expectMatch): void
    {
        $matcher = $this->assertTriggersDeprecation(
            'Tags with whitespace',
            fn () => new TagFilterMatcher($filterString),
        );

        $this->assertSame(
            $expectMatch,
            $matcher->isMatch(['tag with space', 'tag1', 'tag2']),
            'Expected correct matching behaviour',
        );
    }

    /**
     * @phpstan-return list<array{string, list<string>, bool}>
     */
    public static function providerMatchWithoutRemovingPrefix(): array
    {
        // These cases are only intended to be temporary until we drop legacy parsing mode, at which point we can add
        // the `@` to all the tags in the other tests in this file.
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
    public function testItMatchesTagsParsedWithoutRemovingPrefix(string $filterString, array $tags, bool $expect): void
    {
        $matcher = new TagFilterMatcher($filterString);
        $this->assertSame($expect, $matcher->isMatch($tags));
    }

    /**
     * @template T
     *
     * @param Closure():T $callable
     * @param non-empty-string $expectDeprecation
     *
     * @return T
     */
    private function assertTriggersDeprecation(string $expectDeprecation, Closure $callable): mixed
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

        $this->assertIsString($deprecationCaptured, 'Expected deprecation to be emitted');
        $this->assertStringStartsWith($expectDeprecation, $deprecationCaptured, 'Expected correct deprecation message');

        return $result;
    }
}
