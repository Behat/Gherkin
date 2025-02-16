<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin;

use Behat\Gherkin\Filter\FilterInterface;
use Behat\Gherkin\Filter\NameFilter;
use Behat\Gherkin\Filter\TagFilter;
use Behat\Gherkin\Gherkin;
use Behat\Gherkin\Loader\GherkinFileLoader;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GherkinTest extends TestCase
{
    public function testLoader(): void
    {
        $customFilter1 = $this->getCustomFilterMock();
        $customFilter2 = $this->getCustomFilterMock();

        $gherkin = new Gherkin();
        $gherkin->addLoader($loader = $this->getLoaderMock());
        $gherkin->addFilter($nameFilter = $this->getNameFilterMock());
        $gherkin->addFilter($tagFilter = $this->getTagFilterMock());

        $scenario = new ScenarioNode(null, [], [], '', 1);
        $feature = new FeatureNode(null, null, [], null, [$scenario], '', '', null, 1);

        $loader
            ->expects($this->once())
            ->method('supports')
            ->with($resource = 'some/feature/resource')
            ->willReturn(true);
        $loader
            ->expects($this->once())
            ->method('load')
            ->with($resource)
            ->willReturn([$feature]);

        $nameFilter
            ->expects($this->once())
            ->method('filterFeature')
            ->with($this->identicalTo($feature))
            ->willReturn($feature);
        $tagFilter
            ->expects($this->once())
            ->method('filterFeature')
            ->with($this->identicalTo($feature))
            ->willReturn($feature);
        $customFilter1
            ->expects($this->once())
            ->method('filterFeature')
            ->with($this->identicalTo($feature))
            ->willReturn($feature);
        $customFilter2
            ->expects($this->once())
            ->method('filterFeature')
            ->with($this->identicalTo($feature))
            ->willReturn($feature);

        $features = $gherkin->load($resource, [$customFilter1, $customFilter2]);
        $this->assertCount(1, $features);

        $scenarios = $features[0]->getScenarios();
        $this->assertCount(1, $scenarios);
        $this->assertSame($scenario, $scenarios[0]);
    }

    public function testNotFoundLoader(): void
    {
        $gherkin = new Gherkin();

        $this->assertEquals([], $gherkin->load('some/feature/resource'));
    }

    public function testLoaderFiltersFeatures(): void
    {
        $gherkin = new Gherkin();
        $gherkin->addLoader($loader = $this->getLoaderMock());
        $gherkin->addFilter($nameFilter = $this->getNameFilterMock());

        $feature = new FeatureNode(null, null, [], null, [], '', '', null, 1);

        $loader
            ->expects($this->once())
            ->method('supports')
            ->with($resource = 'some/feature/resource')
            ->willReturn(true);
        $loader
            ->expects($this->once())
            ->method('load')
            ->with($resource)
            ->willReturn([$feature]);

        $nameFilter
            ->expects($this->once())
            ->method('filterFeature')
            ->with($this->identicalTo($feature))
            ->willReturn($feature);
        $nameFilter
            ->expects($this->once())
            ->method('isFeatureMatch')
            ->with($this->identicalTo($feature))
            ->willReturn(false);

        $features = $gherkin->load($resource);
        $this->assertCount(0, $features);
    }

    public function testSetFiltersOverridesAllFilters(): void
    {
        $gherkin = new Gherkin();
        $gherkin->addLoader($loader = $this->getLoaderMock());
        $gherkin->addFilter($nameFilter = $this->getNameFilterMock());
        $gherkin->setFilters([]);

        $feature = new FeatureNode(null, null, [], null, [], '', '', null, 1);

        $loader
            ->expects($this->once())
            ->method('supports')
            ->with($resource = 'some/feature/resource')
            ->willReturn(true);
        $loader
            ->expects($this->once())
            ->method('load')
            ->with($resource)
            ->willReturn([$feature]);

        $nameFilter
            ->expects($this->never())
            ->method('filterFeature');
        $nameFilter
            ->expects($this->never())
            ->method('isFeatureMatch');

        $features = $gherkin->load($resource);
        $this->assertCount(1, $features);
    }

    public function testSetBasePath(): void
    {
        $gherkin = new Gherkin();
        $gherkin->addLoader($loader1 = $this->getLoaderMock());
        $gherkin->addLoader($loader2 = $this->getLoaderMock());

        $loader1
            ->expects($this->once())
            ->method('setBasePath')
            ->with('/base/path')
            ->willReturn(null);

        $loader2
            ->expects($this->once())
            ->method('setBasePath')
            ->with('/base/path')
            ->willReturn(null);

        $gherkin->setBasePath('/base/path');
    }

    protected function getLoaderMock(): MockObject&GherkinFileLoader
    {
        return $this->getMockBuilder(GherkinFileLoader::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getCustomFilterMock(): MockObject&FilterInterface
    {
        return $this->getMockBuilder(FilterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getNameFilterMock(): MockObject&NameFilter
    {
        return $this->getMockBuilder(NameFilter::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getTagFilterMock(): MockObject&TagFilter
    {
        return $this->getMockBuilder(TagFilter::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
