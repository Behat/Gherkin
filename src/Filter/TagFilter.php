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

    private TagFilterMatcher $filterMatcher;

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
        if ($this->filterMatcher->getNormalisedFilterString() !== $this->filterString) {
            // A child class has modified the filter string since the last call.
            $this->filterMatcher = new TagFilterMatcher($this->filterString);
            $this->filterString = $this->filterMatcher->getNormalisedFilterString();
        }

        return $this->filterMatcher->isMatch($tags);
    }
}
