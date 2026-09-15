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
use Behat\Gherkin\Node\RuleNode;
use Behat\Gherkin\Node\ScenarioInterface;
use RuntimeException;

/**
 * @internal do not depend on or extend this class directly
 */
abstract class AbstractFeatureFilter implements FeatureFilterInterface
{
    public function __construct(
        private readonly bool $skipFilteringChildrenIfFeatureMatches = false,
    ) {
    }

    /**
     * Filters feature according to the filter.
     *
     * @return FeatureNode
     */
    public function filterFeature(FeatureNode $feature)
    {
        if ($this->shouldSkipFilteringChildrenIfFeatureMatches() && $this->isFeatureMatch($feature)) {
            return $feature;
        }

        return $this->filterFeatureChildren($feature);
    }

    private function shouldSkipFilteringChildrenIfFeatureMatches(): bool
    {
        // BC: The filterChildrenIfFeatureMatches property may not be initialised if a class has extended
        // SimpleFilter or ComplexFilter before it had a constructor, because they will not be calling
        // parent::__construct();

        /* @phpstan-ignore isset.initializedProperty */
        if (isset($this->skipFilteringChildrenIfFeatureMatches)) {
            return $this->skipFilteringChildrenIfFeatureMatches;
        }

        /* @phpstan-ignore deadCode.unreachable */
        if ($this instanceof ComplexFilter) {
            // ComplexFilter has always filtered children, ignoring the feature match
            return false;
        }

        if ($this instanceof SimpleFilter) {
            // SimpleFilter historically defaulted to returning early with all children if the feature matched
            // e.g. paths, role, name filters.
            return true;
        }

        // This should never happen - this class is defined as @internal and our only implementations extend the
        // pre-existing SimpleFilter or ComplexFilter.
        throw new RuntimeException(
            sprintf('Class %s extending %s must call the parent constructor', $this::class, self::class)
        );
    }

    private function filterFeatureChildren(FeatureNode $feature): FeatureNode
    {
        $originalChildren = [];
        $filteredChildren = [];

        foreach ($feature->getExecutableChildren() as $scenarioOrRule) {
            $originalChildren[] = $scenarioOrRule;

            $filteredChild = match (true) {
                $scenarioOrRule instanceof ScenarioInterface => $this->filterScenario($feature, null, $scenarioOrRule),
                $scenarioOrRule instanceof RuleNode => $this->filterRule($feature, $scenarioOrRule),
                default => throw new \LogicException('Unexpected child type ' . $scenarioOrRule::class),
            };

            if ($filteredChild !== false) {
                $filteredChildren[] = $filteredChild;
            }
        }

        return $originalChildren === $filteredChildren ? $feature : $feature->withScenarios($filteredChildren);
    }

    abstract protected function filterScenario(FeatureNode $feature, ?RuleNode $rule, ScenarioInterface $scenario): ScenarioInterface|false;

    protected function filterRule(FeatureNode $feature, RuleNode $rule): RuleNode|false
    {
        $filteredChildren = array_values(array_filter(array_map(
            fn (ScenarioInterface $scenario) => $this->filterScenario($feature, $rule, $scenario),
            $rule->getExecutableChildren(),
        )));

        if ($filteredChildren === []) {
            // Drop the rule, no scenarios match
            return false;
        }

        if ($rule->hasBackground()) {
            array_unshift($filteredChildren, $rule->getBackground());
        }

        return $rule->withChildren($filteredChildren);
    }
}
