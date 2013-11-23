<?php

namespace Tests\Behat\Gherkin\Cache;

use Behat\Gherkin\Cache\DoctrineCache;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use Doctrine\Common\Cache\FilesystemCache;

class DoctrineCacheTest extends \PHPUnit_Framework_TestCase
{
    private $cache;

    public function testIsFreshWhenThereIsNoFile()
    {
        $this->assertFalse($this->getCacheMock(false)->isFresh('unexisting', time()));
    }

    public function testIsFreshOnFreshFile()
    {
        $feature = new FeatureNode(null, null, array(), null, array(), null, null, null, null);

        $cache = $this->getCacheMock(false);
        $cache->write('some_path', $feature);

        $this->assertFalse($cache->isFresh('some_path', time()));
    }

    public function testIsFreshOnOutdated()
    {
        $feature = new FeatureNode(null, null, array(), null, array(), null, null, null, null);

        $cache = $this->getCacheMock();
        $cache->write('some_path', $feature);

        $this->assertTrue($this->cache->isFresh('some_path', time()));
    }

    public function testCacheAndRead()
    {
        $scenarios = array(new ScenarioNode('Some scenario', array(), array(), null, null));
        $feature = new FeatureNode('Some feature', 'some description', array(), null, $scenarios, null, null, null, null);

        $cache = $this->getCacheMock(true, $feature);
        $cache->write('some_feature', $feature);

        $this->assertEquals($feature, $cache->read('some_feature'));
    }

    protected function setUp()
    {
        if (!class_exists('Doctrine\Common\Cache\Cache')) {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the Doctrine Cache to be installed.');
        }
    }

    protected function getCacheMock($contains = true, $return = null)
    {
        $mock = $this->getMock('Doctrine\Common\Cache\Cache', array('contains', 'fetch', 'save'));
        $mock->expects($this->any())
             ->method('contains')
             ->with($this->anything(), $this->anything())
             ->will($this->returnValue($contains));
        $mock->expects($this->any())
             ->method('fetch')
             ->with($this->anything())
             ->will($this->returnValue($return));
        $mock->expects($this->any())
             ->method('save')
             ->with($this->anything());

        return new DoctrineCache($mock);
    }
}
