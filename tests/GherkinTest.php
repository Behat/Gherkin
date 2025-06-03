<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin;

use Behat\Gherkin\Exception\ParserException;
use Behat\Gherkin\Filter\FilterInterface;
use Behat\Gherkin\Filter\NameFilter;
use Behat\Gherkin\Filter\TagFilter;
use Behat\Gherkin\Gherkin;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Loader\GherkinFileLoader;
use Behat\Gherkin\Loader\LoaderInterface;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @param list<FeatureNode> $features
     * @param list<FeatureNode> $expectedFeatures
     */
    #[DataProvider('resourceLineFilterDataProvider')]
    public function testResourceLineFilter(string $resource, array $features, string $expectedResource, array $expectedFeatures): void
    {
        $gherkin = new Gherkin();
        $loader = $this->createMock(LoaderInterface::class);
        $gherkin->addLoader($loader);

        $loader
            ->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($expectedResource))
            ->willReturn(true);
        $loader
            ->expects($this->once())
            ->method('load')
            ->with($this->identicalTo($expectedResource))
            ->willReturn($features);

        $this->assertEquals($expectedFeatures, $gherkin->load($resource));
    }

    /**
     * @return iterable<string, array{resource: string, features: list<FeatureNode>, expectedResource: string, expectedFeatures: list<FeatureNode>}>
     */
    public static function resourceLineFilterDataProvider(): iterable
    {
        // For this test, let's assume that each feature takes up 3 lines
        $features = [
            $feature1 = new FeatureNode(null, null, [], null, [], '', '', null, 1),
            $feature2 = new FeatureNode(null, null, [], null, [], '', '', null, 4),
            $feature3 = new FeatureNode(null, null, [], null, [], '', '', null, 7),
            $feature4 = new FeatureNode(null, null, [], null, [], '', '', null, 10),
        ];

        yield 'single line' => [
            'resource' => 'example1.feature:4',
            'features' => $features,
            'expectedResource' => 'example1.feature',
            'expectedFeatures' => [$feature2],
        ];

        yield 'multiple lines, finite range' => [
            'resource' => 'example2.feature:4-8',
            'features' => $features,
            'expectedResource' => 'example2.feature',
            'expectedFeatures' => [$feature2, $feature3],
        ];

        yield 'multiple lines, open-ended' => [
            'resource' => 'example3.feature:4-*',
            'features' => $features,
            'expectedResource' => 'example3.feature',
            'expectedFeatures' => [$feature2, $feature3, $feature4],
        ];

        yield 'all lines (no filter)' => [
            'resource' => 'example4.feature',
            'features' => $features,
            'expectedResource' => 'example4.feature',
            'expectedFeatures' => [$feature1, $feature2, $feature3, $feature4],
        ];
    }

    public function testThatFileMustBeReadable(): void
    {
        $parser = new Parser($this->createMock(Lexer::class));
        $gherkin = new class($parser) extends GherkinFileLoader {
            public function parseInexistentFile(): void
            {
                $this->parseFeature('inexistent-file');
            }
        };

        $this->expectExceptionObject(new ParserException('Cannot parse file: Failed to read file: inexistent-file'));

        $gherkin->parseInexistentFile();
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
