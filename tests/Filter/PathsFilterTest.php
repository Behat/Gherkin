<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\PathsFilter;
use Behat\Gherkin\Node\FeatureNode;

class PathsFilterTest extends FilterTestCase
{
    public function testIsFeatureMatchFilter(): void
    {
        $feature = new FeatureNode(null, null, [], null, [], '', '', __FILE__, 1);

        $filter = new PathsFilter([__DIR__]);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter(['/abc', '/def', dirname(__DIR__)]);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter(['/abc', '/def', __DIR__]);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter(['/abc', __DIR__, '/def']);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter(['/abc', '/def', '/wrong/path']);
        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testItDoesNotMatchPartialPaths(): void
    {
        $fixtures = __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR;

        $feature = new FeatureNode(null, null, [], null, [], '', '', $fixtures . 'full_path' . DIRECTORY_SEPARATOR . 'file1', 1);

        $filter = new PathsFilter([$fixtures . 'full']);
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new PathsFilter([$fixtures . 'full' . DIRECTORY_SEPARATOR]);
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new PathsFilter([$fixtures . 'full_path' . DIRECTORY_SEPARATOR]);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter([$fixtures . 'full_path']);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter([$fixtures . 'ful._path']); // Don't accept regexp
        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testItDoesNotMatchIfFileWithSameNameButNotPathExistsInFolder(): void
    {
        $fixtures = __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR;

        $feature = new FeatureNode(null, null, [], null, [], '', '', $fixtures . 'full_path' . DIRECTORY_SEPARATOR . 'file1', 1);

        $filter = new PathsFilter([$fixtures . 'full']);
        $this->assertFalse($filter->isFeatureMatch($feature));
    }
}
