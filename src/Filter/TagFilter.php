<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Filter;

use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\RuleNode;
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

    /**
     * @var array{all?: list<array{any: list<array{tag: string, hasTag: bool}>}>, filterString: string}
     */
    private array $parsedFilter;

    public function __construct(string $filterString)
    {
        $this->filterString = trim($filterString);
        $this->parseFilterString();

        // Because `filterString` is protected (and therefore could in theory be modified by a child class at runtime),
        // we need to check if the parsed filter is up to date every time isTagsMatchCondition is called.
        //
        // But in previous releases, we normalised the actual `$this->filterString` value in the constructor. Therefore,
        // we render the (normalised) parsed value back to the filter string to avoid a behaviour change here. This
        // means we also have to update the value in the `parsedFilter` array, to avoid parsing it again.
        //
        // This can all be removed in the next major if we make `filterString` private and/or readonly and remove the
        // normalisation of deprecated syntax.
        $this->filterString = $this->parsedFilter['filterString'] = implode(
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

    private function parseFilterString(): void
    {
        $this->parsedFilter = [
            'filterString' => $this->filterString,
        ];

        if ($this->filterString === '') {
            return;
        }

        $hadTagWithWhitespace = false;
        $hadTagWithoutPrefix = false;

        $this->parsedFilter['all'] = [];
        foreach (explode('&&', $this->filterString) as $andTags) {
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

            $this->parsedFilter['all'][] = ['any' => $orParts];
        }

        if ($hadTagWithWhitespace) {
            trigger_error(
                'Tags with whitespace are deprecated and may be removed in a future version',
                E_USER_DEPRECATED
            );
        }

        if ($hadTagWithoutPrefix) {
            trigger_error(
                'Filter strings should contain `@` prefixes for tags, e.g. `@wip` rather than `wip`.',
                E_USER_DEPRECATED
            );
        }
    }

    /**
     * Filters feature according to the filter.
     *
     * @return FeatureNode
     */
    public function filterFeature(FeatureNode $feature)
    {
        $filteredChildren = [];
        foreach ($feature->getChildren() as $child) {
            if ($child instanceof BackgroundNode) {
                // @todo: it's slightly awkward that `->getChildren()` includes BackgroundNode, but `->withScenarios`
                //        doesn't accept that.
                continue;
            }

            $filteredChildren[] = $child instanceof RuleNode
                ? $this->filterRule($child, $feature)
                : $this->filterScenario($child, $feature, null);
        }

        return $feature->withScenarios(array_values(array_filter($filteredChildren)));
    }

    private function filterRule(RuleNode $rule, FeatureNode $feature): RuleNode|false
    {
        $filteredChildren = array_values(array_filter(array_map(
            // @todo: Again another place where having Background in the getChildren() is a bit annoying
            fn ($scenario) => $scenario instanceof ScenarioInterface ? $this->filterScenario($scenario, $feature, $rule) : false,
            $rule->getChildren()
        )));

        if ($filteredChildren === []) {
            // No scenarios matched the filter, so drop the whole Rule (including any redundant Background).
            return false;
        }

        if ($rule->hasBackground()) {
            // If at least one scenario matched the filter, we need to add the Background (if any) back into the Rule
            array_unshift($filteredChildren, $rule->getBackground());
        }

        return $rule->withChildren($filteredChildren);
    }

    private function filterScenario(ScenarioInterface $scenario, FeatureNode $feature, ?RuleNode $rule): ScenarioInterface|false
    {
        $tags = array_merge(...array_filter([$feature->getTags(), $rule?->getTags(), $scenario->getTags()]));

        if ($scenario instanceof OutlineNode && $scenario->hasExamples()) {
            // For OutlineNode with Examples, the filter strips out any Examples that don't match.
            // If there are no Examples left, the whole Scenario is dropped.
            // NB this only runs if there were Examples to begin with. If there were no Examples, but the OutlineNode
            // itself matches, then the (empty) OutlineNode is kept. This is possibly unexpected, but has always been
            // the behaviour.
            $filteredExamples = [];
            foreach ($scenario->getExampleTables() as $exampleTable) {
                if ($this->isTagsMatchCondition([...$tags, ...$exampleTable->getTags()])) {
                    $filteredExamples[] = $exampleTable;
                }
            }

            if ($filteredExamples === []) {
                return false;
            }

            return $scenario->withTables($filteredExamples);
        }

        if ($this->isTagsMatchCondition($tags)) {
            return $scenario;
        }

        return false;
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
        return $this->filterScenario($scenario, $feature, $this->findScenarioRule($feature, $scenario)) !== false;
    }

    private function findScenarioRule(FeatureNode $feature, ScenarioInterface $scenario): ?RuleNode
    {
        // @todo: PERFORMANCE HIT / DUPLICATION OF EFFORT - IS THERE A BETTER WAY TO DO THIS? OR AT LEAST TO CACHE IT?
        foreach ($feature->getChildren() as $child) {
            if ($child instanceof RuleNode) {
                foreach ($child->getChildren() as $ruleChild) {
                    if ($ruleChild::class === $scenario::class && $scenario->getLine() === $ruleChild->getLine()) {
                        return $child;
                    }
                }
            }
        }

        return null;
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
        if ($this->parsedFilter['filterString'] !== $this->filterString) {
            // A child class has modified the filter string since the last call.
            $this->parseFilterString();
        }

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
