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

    private TagFilterMatcher $filterMatcher;

    private ?RuleNode $currentlyFilteringRule = null;

    public function __construct(string $filterString)
    {
        $this->filterMatcher = new TagFilterMatcher($filterString);
        // Because `filterString` is protected (and therefore could in theory be modified by a child class at runtime),
        // we need to check if the parsed filter is up to date every time isTagsMatchCondition is called.
        //
        // But in previous releases, we normalised the actual `$this->filterString` value in the constructor. Therefore,
        // we render the (normalised) parsed value back to the filter string to avoid a behaviour change here. This
        // means we also have to update the value in the `parsedFilter` array, to avoid parsing it again.
        //
        // This can all be removed in the next major if we make `filterString` private and/or readonly and remove the
        // normalisation of deprecated syntax.
        $this->filterString = $this->filterMatcher->getNormalisedFilterString();

        // Always filter the individual children, don't check the feature itself
        parent::__construct(skipFilteringChildrenIfFeatureMatches: false);
    }

    protected function filterScenario(FeatureNode $feature, ?RuleNode $rule, ScenarioInterface $scenario): ScenarioInterface|false
    {
        $this->currentlyFilteringRule = $rule;
        try {
            $isScenarioMatch = $this->isScenarioMatch($feature, $scenario);
        } finally {
            $this->currentlyFilteringRule = null;
        }

        if (!$isScenarioMatch) {
            return false;
        }

        $tags = [...$feature->getTags(), ...$scenario->getTags(), ...($rule?->getTags() ?? [])];

        if ($scenario instanceof OutlineNode && $scenario->hasExamples()) {
            $exampleTables = [];
            foreach ($scenario->getExampleTables() as $exampleTable) {
                if ($this->isTagsMatchCondition([...$tags, ...$exampleTable->getTags()])) {
                    $exampleTables[] = $exampleTable;
                }
            }

            return $scenario->withTables($exampleTables);
        }

        return $scenario;
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
        // Note, this will only consider Rule tags when filtering features using our implementation of `filterFeature`.
        // It will not consider Rule tags if:
        // - this method is called from outside our main `filterFeature` loop (e.g. to check a tagged hook in Behat)
        // - an end-user has extended `TagFilter` and overridden this method instead of customising `filterFeature`
        //
        // For the same reason, we can't refactor this method / filterFeature to avoid iterating the tables twice -
        // because that would break end-user assumptions about the relationship between these two methods if they
        // have extended either method.
        $tags = [...$feature->getTags(), ...$scenario->getTags(), ...($this->currentlyFilteringRule?->getTags() ?? [])];

        if ($scenario instanceof OutlineNode && $scenario->hasExamples()) {
            foreach ($scenario->getExampleTables() as $example) {
                if ($this->isTagsMatchCondition([...$tags, ...$example->getTags()])) {
                    return true;
                }
            }

            return false;
        }

        return $this->isTagsMatchCondition($tags);
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
        if ($this->filterMatcher->getNormalisedFilterString() !== $this->filterString) {
            // A child class has modified the filter string since the last call.
            $this->filterMatcher = new TagFilterMatcher($this->filterString);
            $this->filterString = $this->filterMatcher->getNormalisedFilterString();
        }

        return $this->filterMatcher->isMatch($tags);
    }
}
