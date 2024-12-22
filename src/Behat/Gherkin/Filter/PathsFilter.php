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
 * Filters features by their paths.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class PathsFilter extends SimpleFilter
{
    protected $filterPaths = [];

    /**
     * Initializes filter.
     *
     * @param string[] $paths List of approved paths
     */
    public function __construct(array $paths)
    {
        $this->filterPaths = array_map(
            function ($realpath) {
                return rtrim($realpath, DIRECTORY_SEPARATOR) .
                    (is_dir($realpath) ? DIRECTORY_SEPARATOR : '');
            },
            array_filter(
                array_map('realpath', $paths)
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
        foreach ($this->filterPaths as $path) {
            if (strpos(realpath($feature->getFile()), $path) === 0) {
                return true;
            }
        }

        return false;
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
