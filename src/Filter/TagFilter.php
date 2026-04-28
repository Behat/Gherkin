<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Filter;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioInterface;

/**
 * Filters scenarios by feature/scenario tag.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class TagFilter extends ComplexFilter
{
    /**
     * @var string
     */
    protected $filterString;

    public function __construct(string $filterString)
    {
        $filterString = trim($filterString);
        $this->filterString = $this->fixLegacyFilterStringWithoutPrefixes($filterString);
        // @todo: Now that we are parsing the filter in the constructor, it would be more efficient to store the parsed
        //        filter rather than re-parsing it on every call to isTagsMatchCondition(). However, we can't safely
        //        do that till the next major, because `filterString` is protected and we can't guarantee that a child
        //        class doesn't modify it during execution.
    }

    /**
     * Fix tag expressions where the filter string does not include the `@` prefixes.
     *
     * e.g. `new TagFilter('wip&&~slow')` rather than `new TagFilter('@wip&&~@slow')`. These were historically
     * supported, although not officially, and have been reinstated to solve a BC issue. This syntax will be deprecated
     * and removed in future.
     */
    private function fixLegacyFilterStringWithoutPrefixes(string $filterString): string
    {
        if ($filterString === '') {
            return '';
        }

        $hadTagWithWhitespace = false;

        $allParts = [];
        foreach (explode('&&', $filterString) as $andTags) {
            $orParts = [];
            foreach (explode(',', $andTags) as $tag) {
                $tag = trim($tag);
                $fixedTag = match (true) {
                    // Valid - tag filter contains the `@` prefix
                    str_starts_with($tag, '@'),
                    str_starts_with($tag, '~@') => $tag,
                    // Invalid / legacy cases - insert the missing `@` prefix in the right place
                    str_starts_with($tag, '~') => '~@' . substr($tag, 1),
                    default => '@' . $tag,
                };

                // @todo trigger a deprecation if any @ were added

                $hadTagWithWhitespace = $hadTagWithWhitespace || str_contains($fixedTag, ' ');
                $orParts[] = $fixedTag;
            }

            $allParts[] = implode(',', $orParts);
        }

        if ($hadTagWithWhitespace) {
            trigger_error(
                'Tags with whitespace are deprecated and may be removed in a future version',
                E_USER_DEPRECATED
            );
        }

        return implode('&&', $allParts);
    }

    /**
     * Filters feature according to the filter.
     *
     * @return FeatureNode
     */
    public function filterFeature(FeatureNode $feature)
    {
        $scenarios = [];
        foreach ($feature->getScenarios() as $scenario) {
            if (!$this->isScenarioMatch($feature, $scenario)) {
                continue;
            }

            if ($scenario instanceof OutlineNode && $scenario->hasExamples()) {
                $exampleTables = [];

                foreach ($scenario->getExampleTables() as $exampleTable) {
                    if ($this->isTagsMatchCondition(array_merge($feature->getTags(), $scenario->getTags(), $exampleTable->getTags()))) {
                        $exampleTables[] = $exampleTable;
                    }
                }

                $scenario = $scenario->withTables($exampleTables);
            }

            $scenarios[] = $scenario;
        }

        return $feature->withScenarios($scenarios);
    }

    /**
     * Checks if Feature matches specified filter.
     *
     * @param FeatureNode $feature Feature instance
     *
     * @return bool
     */
    public function isFeatureMatch(FeatureNode $feature)
    {
        return $this->isTagsMatchCondition($feature->getTags());
    }

    /**
     * Checks if scenario or outline matches specified filter.
     *
     * @param FeatureNode $feature Feature node instance
     * @param ScenarioInterface $scenario Scenario or Outline node instance
     *
     * @return bool
     */
    public function isScenarioMatch(FeatureNode $feature, ScenarioInterface $scenario)
    {
        if ($scenario instanceof OutlineNode && $scenario->hasExamples()) {
            foreach ($scenario->getExampleTables() as $example) {
                if ($this->isTagsMatchCondition(array_merge($feature->getTags(), $scenario->getTags(), $example->getTags()))) {
                    return true;
                }
            }

            return false;
        }

        return $this->isTagsMatchCondition(array_merge($feature->getTags(), $scenario->getTags()));
    }

    /**
     * Checks that node matches condition.
     *
     * @param array<array-key, string> $tags
     *
     * @return bool
     */
    protected function isTagsMatchCondition(array $tags)
    {
        if ($this->filterString === '') {
            return true;
        }

        // If the file was parsed in legacy mode, the `@` prefix will have been removed from the individual tags on the
        // parsed node. The tags in the filter expression still have their @ so we add the prefix back here if required.
        // This can be removed once legacy parsing mode is removed.
        $tags = array_map(
            static fn (string $tag) => str_starts_with($tag, '@') ? $tag : '@' . $tag,
            $tags
        );

        foreach (explode('&&', $this->filterString) as $andTags) {
            $satisfiesComma = false;

            foreach (explode(',', $andTags) as $tag) {
                if ($tag[0] === '~') {
                    $tag = mb_substr($tag, 1, mb_strlen($tag, 'utf8') - 1, 'utf8');
                    $satisfiesComma = !in_array($tag, $tags, true) || $satisfiesComma;
                } else {
                    $satisfiesComma = in_array($tag, $tags, true) || $satisfiesComma;
                }
            }

            if (!$satisfiesComma) {
                return false;
            }
        }

        return true;
    }
}
