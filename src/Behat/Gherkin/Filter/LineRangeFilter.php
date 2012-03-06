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
 * Filters scenarios by definition line number range.
 *
 * @author     Fabian Kiss <headrevision@gmail.com>
 */
class LineRangeFilter implements FilterInterface
{
    protected $filterMinLine;
    protected $filterMaxLine;

    /**
     * Initializes filter.
     *
     * @param   string  $filterMinLine minimum line of a scenario to filter on
     * @param   string  $filterMaxLine maximum line of a scenario to filter on
     */
    public function __construct($filterMinLine, $filterMaxLine)
    {
        $this->filterMinLine = intval($filterMinLine);
        if ($filterMaxLine == '*') {
            $this->filterMaxLine = PHP_INT_MAX;
        } else {
            $this->filterMaxLine = intval($filterMaxLine);
        }
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
        return $this->filterMinLine <= $scenario->getLine()
            && $this->filterMaxLine >= $scenario->getLine();
    }
}
