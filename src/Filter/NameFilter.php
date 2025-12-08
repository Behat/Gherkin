<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Filter;

use Behat\Gherkin\Node\DescribableNodeInterface;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;

/**
 * Filters scenarios by feature/scenario name.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class NameFilter extends SimpleFilter
{
    /**
     * @var string
     */
    protected $filterString;

    public function __construct(string $filterString)
    {
        $this->filterString = trim($filterString);
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
        if ($feature->getTitle() === null) {
            return false;
        }

        if ($this->filterString[0] === '/') {
            return (bool) preg_match($this->filterString, $feature->getTitle());
        }

        return str_contains($feature->getTitle(), $this->filterString);
    }

    /**
     * Checks if scenario or outline matches specified filter.
     *
     * @param ScenarioInterface $scenario Scenario or Outline node instance
     *
     * @return bool
     */
    public function isScenarioMatch(ScenarioInterface $scenario)
    {
        // Historically (and in legacy GherkinCompatibilityMode), multiline scenario text was all part of the title.
        // In new GherkinCompatibilityMode the text will be split into a single-line title & multiline description.
        // For BC, this filter should continue to match on the complete multiline text value.
        $textParts = array_filter([
            $scenario->getTitle(),
            $scenario instanceof DescribableNodeInterface ? $scenario->getDescription() : null,
        ]);

        if ($textParts === []) {
            return false;
        }

        $textToMatch = implode("\n", $textParts);

        if ($this->filterString[0] === '/' && preg_match($this->filterString, $textToMatch)) {
            return true;
        }

        if (str_contains($textToMatch, $this->filterString)) {
            return true;
        }

        return false;
    }
}
