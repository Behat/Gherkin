<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\RoleFilter;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;

class RoleFilterTest extends FilterTestCase
{
    public function testIsFeatureMatchFilter(): void
    {
        $description = <<<'NAR'
        In order to be able to read news in my own language
        As a french user
        I need to be able to switch website language to french
        NAR;
        $feature = new FeatureNode(null, $description, [], null, [], '', '', null, 1);

        $filter = new RoleFilter('french user');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('french *');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('french');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('user');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('*user');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('French User');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, [], null, [], '', '', null, 1);
        $filter = new RoleFilter('French User');
        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testFeatureRolePrefixedWithAn(): void
    {
        $description = <<<'NAR'
        In order to be able to read news in my own language
        As an american user
        I need to be able to switch website language to french
        NAR;
        $feature = new FeatureNode(null, $description, [], null, [], '', '', null, 1);

        $filter = new RoleFilter('american user');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('american *');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('american');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('user');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('*user');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('[\w\s]+user');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('American User');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, [], null, [], '', '', null, 1);
        $filter = new RoleFilter('American User');
        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testIsScenarioMatchFilter(): void
    {
        $scenario = new ScenarioNode(null, [], [], '', 1);

        $filter = new RoleFilter('user');

        $this->assertFalse($filter->isScenarioMatch($scenario));
    }
}
