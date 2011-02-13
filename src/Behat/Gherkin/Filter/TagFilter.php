<?php

namespace Behat\Gherkin\Filter;

use Behat\Gherkin\Node\FeatureNode,
    Behat\Gherkin\Node\ScenarioNode;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Filters scenarios by feature/scenario tag.
 *
 * @author     Konstantin Kudryashov <ever.zet@gmail.com>
 */
class TagFilter implements FilterInterface
{
    protected $filterString;

    /**
     * Initializes filter.
     *
     * @param   string  $filterStringString name filter string
     */
    public function __construct($filterString)
    {
        $this->filterString = trim($filterString);
    }

    /**
     * {@inheritdoc}
     */
    public function isFeatureMatch(FeatureNode $feature)
    {
        return $this->isClosuresMatch(
            function($tag) use ($feature) {
                return $feature->hasTag($tag);
            },
            function($tag) use ($feature) {
                return !$feature->hasTag($tag);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isScenarioMatch(ScenarioNode $scenario)
    {
        $feature = $scenario->getFeature();

        return $this->isClosuresMatch(
            function($tag) use ($feature, $scenario) {
                return $scenario->hasTag($tag) || $feature->hasTag($tag);
            },
            function($tag) use ($feature, $scenario) {
                return !$scenario->hasTag($tag) && !$feature->hasTag($tag);
            }
        );
    }

    /**
     * Checks if provided has/hasn't closures pass with filter.
     *
     * @param   Closure $hasTagCheck    closure to check that something has got tag
     * @param   Closure $hasntTagCheck  closure to check that something hasn't got tag
     */
    protected function isClosuresMatch(\Closure $hasTagCheck, \Closure $hasntTagCheck)
    {
        $satisfies = true;

        foreach (explode('&&', $this->filterString) as $andTags) {
            $satisfiesComma = false;

            foreach (explode(',', $andTags) as $tag) {
                $tag = str_replace('@', '', trim($tag));

                if ('~' === $tag[0]) {
                    $tag = mb_substr($tag, 1);
                    $satisfiesComma = $hasntTagCheck($tag) || $satisfiesComma;
                } else {
                    $satisfiesComma = $hasTagCheck($tag) || $satisfiesComma;
                }
            }

            $satisfies = (false !== $satisfiesComma && $satisfies && $satisfiesComma) || false;
        }

        return $satisfies;
    }
}
