<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Cucumber;

use Behat\Gherkin\GherkinCompatibilityMode;
use Behat\Gherkin\Node\FeatureNode;
use SebastianBergmann\Comparator\ObjectComparator;

class FeatureNodeComparator extends ObjectComparator
{
    private GherkinCompatibilityMode $compatibilityMode;

    public function setGherkinCompatibilityMode(GherkinCompatibilityMode $mode): void
    {
        $this->compatibilityMode = $mode;
    }

    public function accepts(mixed $expected, mixed $actual): bool
    {
        return $expected instanceof FeatureNode && $actual instanceof FeatureNode;
    }

    /**
     * @return array<mixed>
     */
    protected function toArray(object $object): array
    {
        $array = parent::toArray($object);

        assert(isset($this->compatibilityMode));
        if ($this->compatibilityMode->shouldRemoveDescriptionPadding()) {
            // Our legacy parsing mode handles whitespace in feature descriptions differently
            // to cucumber/gherkin - https://github.com/Behat/Gherkin/issues/209.
            // We need to be able to ignore that difference so that we can still run cucumber tests that
            // include a description but are covering other features.
            if ($array['description'] !== null) {
                $array['description'] = preg_replace('/^\s+/m', '', $array['description']);
            }
        }

        return $array;
    }
}
