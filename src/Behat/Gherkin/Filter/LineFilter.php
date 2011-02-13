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
 * Filters scenarios by definition line number.
 *
 * @author     Konstantin Kudryashov <ever.zet@gmail.com>
 */
class LineFilter implements FilterInterface
{
    protected $filterLine;

    /**
     * Initializes filter.
     *
     * @param   string  $filterLine line of the scenario to filter on
     */
    public function __construct($filterLine)
    {
        $this->filterLine = intval($filterLine);
    }

    /**
     * {@inheritdoc}
     */
    public function isFeatureMatch(FeatureNode $feature)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isScenarioMatch(ScenarioNode $scenario)
    {
        return $this->filterLine === $scenario->getLine();
    }
}
