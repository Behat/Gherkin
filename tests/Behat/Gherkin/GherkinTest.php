<?php

namespace Tests\Behat\Gherkin;

use Behat\Gherkin\Gherkin,
    Behat\Gherkin\Node\FeatureNode,
    Behat\Gherkin\Node\ScenarioNode;

class GherkinTest extends \PHPUnit_Framework_TestCase
{
    public function testLoader()
    {
        $customFilter1 = $this->getCustomFilterMock();
        $customFilter2 = $this->getCustomFilterMock();

        $gherkin = new Gherkin();
        $gherkin->addLoader($loader = $this->getLoaderMock());
        $gherkin->addFilter($nameFilter = $this->getNameFilterMock());
        $gherkin->addFilter($tagFilter = $this->getTagFilterMock());

        $feature = new FeatureNode();
        $feature->addScenario($scenario = new ScenarioNode());

        $loader
            ->expects($this->once())
            ->method('supports')
            ->with($resource = 'some/feature/resource')
            ->will($this->returnValue(true));
        $loader
            ->expects($this->once())
            ->method('load')
            ->with($resource)
            ->will($this->returnValue(array($feature)));

        $filterFeature = clone $feature;
        $nameFilter
            ->expects($this->once())
            ->method('filterFeature')
            ->with($filterFeature);
        $tagFilter
            ->expects($this->once())
            ->method('filterFeature')
            ->with($filterFeature);
        $customFilter1
            ->expects($this->once())
            ->method('filterFeature')
            ->with($filterFeature);
        $customFilter2
            ->expects($this->once())
            ->method('filterFeature')
            ->with($filterFeature);

        $features = $gherkin->load($resource, array($customFilter1, $customFilter2));
        $this->assertEquals(1, count($features));
        $this->assertTrue($feature->isFrozen());

        $scenarios = $features[0]->getScenarios();
        $this->assertEquals(1, count($scenarios));
        $this->assertSame($scenario, $scenarios[0]);
    }

    public function testSetBasePath()
    {
        $gherkin = new Gherkin();
        $gherkin->addLoader($loader1 = $this->getLoaderMock());
        $gherkin->addLoader($loader2 = $this->getLoaderMock());

        $loader1
            ->expects($this->once())
            ->method('setBasePath')
            ->with($basePath = '/base/path')
            ->will($this->returnValue(null));

        $loader2
            ->expects($this->once())
            ->method('setBasePath')
            ->with($basePath = '/base/path')
            ->will($this->returnValue(null));

        $gherkin->setBasePath($basePath);
    }

    protected function getLoaderMock()
    {
        return $this->getMockBuilder('Behat\Gherkin\Loader\GherkinFileLoader')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getCustomFilterMock()
    {
        return $this->getMockBuilder('Behat\Gherkin\Filter\FilterInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getNameFilterMock()
    {
        return $this->getMockBuilder('Behat\Gherkin\Filter\NameFilter')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getTagFilterMock()
    {
        return $this->getMockBuilder('Behat\Gherkin\Filter\TagFilter')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
