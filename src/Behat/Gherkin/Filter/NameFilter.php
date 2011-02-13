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
 * Filters scenarios by feature/scenario name.
 *
 * @author     Konstantin Kudryashov <ever.zet@gmail.com>
 */
class NameFilter implements FilterInterface
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
        if ('/' === $this->filterString[0]) {
            return (bool) preg_match($this->filterString, $feature->getTitle());
        }

        return false !== mb_strpos($feature->getTitle(), $this->filterString);
    }

    /**
     * {@inheritdoc}
     */
    public function isScenarioMatch(ScenarioNode $scenario)
    {
        if ('/' === $this->filterString[0] && 1 === preg_match($this->filterString, $scenario->getTitle())) {
            return true;
        } elseif (false !== mb_strpos($scenario->getTitle(), $this->filterString)) {
            return true;
        }

        if (null !== $scenario->getFeature()) {
            return $this->isFeatureMatch($scenario->getFeature());
        }

        return false;
    }
}
