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
use Behat\Gherkin\Node\ScenarioInterface;

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
        $this->pattern = sprintf(
            '/as an? %s[$\n]/i',
            strtr(
                preg_quote($role, '/'),
                [
                    '\*' => '.*',
                    '\?' => '.',
                    '\[' => '[',
                    '\]' => ']',
                ]
            )
        );
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
        return (bool) preg_match($this->pattern, $feature->getDescription() ?? '');
    }

    /**
     * Checks if scenario or outline matches specified filter.
     *
     * @param ScenarioInterface $scenario Scenario or Outline node instance
     *
     * @return false This filter is designed to work only with features
     */
    public function isScenarioMatch(ScenarioInterface $scenario)
    {
        return false;
    }
}
