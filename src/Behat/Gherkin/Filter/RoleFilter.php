<?php

namespace Behat\Gherkin\Filter;

use Behat\Gherkin\Node\FeatureNode,
    Behat\Gherkin\Node\ScenarioNode,
    Behat\Gherkin\Node\OutlineNode;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2013 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Filters features by their actors role.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class RoleFilter extends SimpleFilter
{
    protected $pattern;

    /**
     * Initializes filter.
     *
     * @param string $role Approved role wildcard
     */
    public function __construct($role)
    {
        $this->pattern = '/as an? '.strtr(preg_quote($role, '/'), array(
            '\*' => '.*',
            '\?' => '.',
            '\[' => '[',
            '\]' => ']'
        )).'[$\n]/i';
    }

    /**
     * Checks if Feature matches specified filter.
     *
     * @param FeatureNode $feature Feature instance
     *
     * @return Boolean
     */
    public function isFeatureMatch(FeatureNode $feature)
    {
        return 1 === preg_match($this->pattern, $feature->getDescription());
    }

    /**
     * Checks if scenario or outline matches specified filter.
     *
     * @param ScenarioNode $scenario Scenario or Outline node instance
     *
     * @return Boolean
     */
    public function isScenarioMatch(ScenarioNode $scenario)
    {
        return $this->isFeatureMatch($scenario->getFeature());
    }
}
