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
    /**
     * @var list<string>
     */
    protected $filterPaths = [];

    /**
     * Initializes filter.
     *
     * @param array<array-key, string> $paths List of approved paths
     */
    public function __construct(array $paths)
    {
        foreach ($paths as $path) {
            if (($realpath = realpath($path)) === false) {
                continue;
            }
            $this->filterPaths[] = rtrim($realpath, DIRECTORY_SEPARATOR)
                . (is_dir($realpath) ? DIRECTORY_SEPARATOR : '');
        }
    }

    public function isFeatureMatch(FeatureNode $feature)
    {
        foreach ($this->filterPaths as $path) {
            if (str_starts_with(realpath($feature->getFile()), $path)) {
                return true;
            }
        }

        return false;
    }

    public function isScenarioMatch(ScenarioInterface $scenario)
    {
        // This filter does not apply to scenarios.
        return false;
    }
}
