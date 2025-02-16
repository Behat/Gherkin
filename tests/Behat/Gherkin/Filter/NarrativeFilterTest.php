<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\NarrativeFilter;
use Behat\Gherkin\Node\FeatureNode;

class NarrativeFilterTest extends FilterTestCase
{
    public function testIsFeatureMatchFilter(): void
    {
        $description = <<<'NAR'
        In order to be able to read news in my own language
        As a french user
        I need to be able to switch website language to french
        NAR;
        $feature = new FeatureNode(null, $description, [], null, [], '', '', null, 1);

        $filter = new NarrativeFilter('/as (?:a|an) french user/');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new NarrativeFilter('/as (?:a|an) french user/i');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new NarrativeFilter('/french .*/');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new NarrativeFilter('/^french/');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new NarrativeFilter('/user$/');
        $this->assertFalse($filter->isFeatureMatch($feature));
    }
}
