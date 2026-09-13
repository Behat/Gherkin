<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Filter;

/**
 * @internal
 *
 * @phpstan-type TParsedFilterArray array{all?: list<array{any: list<array{tag: string, hasTag: bool}>}>}
 */
final class TagFilterMatcher
{
    /**
     * @var TParsedFilterArray
     */
    private readonly array $parsedFilter;

    private readonly string $normalisedFilterString;

    public function __construct(string $filterString)
    {
        $this->parsedFilter = $this->parseFilterString(trim($filterString));
        $this->normalisedFilterString = $this->normaliseFilterString();
    }

    /**
     * @return TParsedFilterArray
     */
    private function parseFilterString(string $filterString): array
    {
        if ($filterString === '') {
            return [];
        }

        $hadTagWithWhitespace = false;
        $hadTagWithoutPrefix = false;

        $filter['all'] = [];
        foreach (explode('&&', $filterString) as $andTags) {
            $orParts = [];
            foreach (explode(',', $andTags) as $tag) {
                $tag = trim($tag);

                // Fix tag expressions where the filter string does not include the `@` prefixes.
                // e.g. `new TagFilter('wip&&~slow')` rather than `new TagFilter('@wip&&~@slow')`. These were
                // historically supported, although not officially, and have been reinstated to solve a BC issue.
                // This syntax is deprecated and will be removed in future.
                $fixedTag = match (true) {
                    // Valid - tag filter contains the `@` prefix
                    str_starts_with($tag, '@'),
                    str_starts_with($tag, '~@'),
                    // Valid historical edge case - tag filter contains the `@` prefix, but there is whitespace after the `~`
                    (bool) preg_match('/^~\s+@/', $tag) => $tag,
                    // Invalid / legacy cases - insert the missing `@` prefix in the right place
                    str_starts_with($tag, '~') => '~@' . substr($tag, 1),
                    default => '@' . $tag,
                };

                if (str_starts_with($fixedTag, '~')) {
                    $orParts[] = ['tag' => substr($fixedTag, 1), 'hasTag' => false];
                } else {
                    $orParts[] = ['tag' => $fixedTag, 'hasTag' => true];
                }

                $hadTagWithoutPrefix = $hadTagWithoutPrefix || ($tag !== $fixedTag);
                $hadTagWithWhitespace = $hadTagWithWhitespace || str_contains($tag, ' ');
            }

            $filter['all'][] = ['any' => $orParts];
        }

        if ($hadTagWithWhitespace) {
            trigger_error(
                'Tags with whitespace are deprecated and may be removed in a future version',
                E_USER_DEPRECATED,
            );
        }

        if ($hadTagWithoutPrefix) {
            trigger_error(
                'Filter strings should contain `@` prefixes for tags, e.g. `@wip` rather than `wip`.',
                E_USER_DEPRECATED,
            );
        }

        return $filter;
    }

    private function normaliseFilterString(): string
    {
        return implode(
            '&&',
            array_map(
                static fn (array $filterClause) => implode(',',
                    array_map(
                        static fn (array $tag) => sprintf(
                            '%s%s',
                            $tag['hasTag'] ? '' : '~',
                            $tag['tag']
                        ),
                        $filterClause['any']
                    )),
                $this->parsedFilter['all'] ?? [],
            ),
        );
    }

    public function getNormalisedFilterString(): string
    {
        return $this->normalisedFilterString;
    }

    /**
     * @param array<array-key, string> $tags
     */
    public function isMatch(array $tags): bool
    {
        if (!isset($this->parsedFilter['all'])) {
            return true;
        }

        // If the file was parsed in legacy mode, the `@` prefix will have been removed from the individual tags on the
        // parsed node. The tags in the filter expression still have their @ so we add the prefix back here if required.
        // This can be removed once legacy parsing mode is removed.
        $tags = array_map(
            static fn (string $tag) => str_starts_with($tag, '@') ? $tag : '@' . $tag,
            $tags
        );

        foreach ($this->parsedFilter['all'] as $filterPart) {
            $satisfiesComma = false;

            foreach ($filterPart['any'] as $tag) {
                if (in_array($tag['tag'], $tags, true) === $tag['hasTag']) {
                    $satisfiesComma = true;
                    break;
                }
            }

            if (!$satisfiesComma) {
                return false;
            }
        }

        return true;
    }
}
