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
use Behat\Gherkin\Node\StepNode;
use SebastianBergmann\Comparator\ObjectComparator;

final class StepNodeComparator extends ObjectComparator
{
    private GherkinCompatibilityMode $compatibilityMode;

    public function setGherkinCompatibilityMode(GherkinCompatibilityMode $mode): void
    {
        $this->compatibilityMode = $mode;
    }

    public function accepts(mixed $expected, mixed $actual): bool
    {
        return $expected instanceof StepNode && $actual instanceof StepNode;
    }

    /**
     * @return array<mixed>
     */
    protected function toArray(object $object): array
    {
        $array = parent::toArray($object);

        // We cannot compare the keywordsType property on a StepNode because this concept
        // is specific to Behat/Gherkin and there is no equivalent value in the cucumber/gherkin
        // test data.
        // cucumber/gherkin has the equivalent concept in their pickle steps instead.
        unset($array['keywordType']);

        if ($this->compatibilityMode->shouldRemoveStepKeywordSpace()) {
            assert(is_string($array['keyword']));
            $array['keyword'] = trim($array['keyword']);
        }

        return $array;
    }
}
