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
    /**
     * @var string
     */
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

    public function isFeatureMatch(FeatureNode $feature)
    {
        return (bool) preg_match($this->pattern, $feature->getDescription() ?? '');
    }

    public function isScenarioMatch(ScenarioInterface $scenario)
    {
        // This filter does not apply to scenarios.
        return false;
    }
}
