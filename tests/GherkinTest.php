<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin;

use Behat\Gherkin\Filter\FeatureFilterInterface;
use Behat\Gherkin\Filter\FilterInterface;
use Behat\Gherkin\Filter\NameFilter;
use Behat\Gherkin\Filter\TagFilter;
use Behat\Gherkin\Gherkin;
use Behat\Gherkin\Loader\GherkinFileLoader;
use Behat\Gherkin\Loader\LoaderInterface;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
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

    /**
     * Multiple filters narrow the same feature one after the other, so the scenarios that
     * survive are the ones every filter keeps.
     *
     * @param list<FeatureFilterInterface> $injectedFilters
     * @param list<FeatureFilterInterface> $passedFilters
     * @param list<string|null>|null $expectedScenarioTitles null when the feature is dropped
     */
    #[DataProvider('multipleFilterCases')]
    public function testMultipleFiltersAreCombinedWithAnd(
        array $injectedFilters,
        array $passedFilters,
        ?array $expectedScenarioTitles,
    ): void {
        $gherkin = new Gherkin();
        $gherkin->addLoader($this->getFeatureLoader());

        foreach ($injectedFilters as $filter) {
            $gherkin->addFilter($filter);
        }

        $features = $gherkin->load('some/feature/resource', $passedFilters);

        if ($expectedScenarioTitles === null) {
            $this->assertSame([], $features);

            return;
        }

        $this->assertCount(1, $features);
        $this->assertSame(
            $expectedScenarioTitles,
            array_map(static fn (ScenarioInterface $scenario) => $scenario->getTitle(), $features[0]->getScenarios()),
        );
    }

    /**
     * @return iterable<string, array{list<FeatureFilterInterface>, list<FeatureFilterInterface>, list<string|null>|null}>
     */
    public static function multipleFilterCases(): iterable
    {
        yield 'a single tag filter' => [
            [new TagFilter('@smoke')],
            [],
            ['Login works', 'Logout works'],
        ];

        yield 'a single name filter' => [
            [new NameFilter('Login')],
            [],
            ['Login works', 'Login fails'],
        ];

        yield 'two filters keep the intersection' => [
            [new TagFilter('@smoke'), new NameFilter('Login')],
            [],
            ['Login works'],
        ];

        yield 'the order of the filters does not matter' => [
            [new NameFilter('Login'), new TagFilter('@smoke')],
            [],
            ['Login works'],
        ];

        yield 'filters passed to load() are merged with the injected ones' => [
            [new TagFilter('@smoke')],
            [new NameFilter('Login')],
            ['Login works'],
        ];

        yield 'a filter matching nothing drops the feature' => [
            [new TagFilter('@smoke'), new TagFilter('@missing')],
            [],
            null,
        ];

        // a filter matching the feature itself keeps every scenario of that feature
        yield 'a filter matching the feature keeps it whole' => [
            [new TagFilter('@feature-tag')],
            [],
            ['Login works', 'Logout works', 'Login fails'],
        ];

        // but that only excuses the filter that matches, not the ones after it
        yield 'a later filter still drops a feature matched by an earlier one' => [
            [new TagFilter('@feature-tag'), new NameFilter('Nothing matches this')],
            [],
            null,
        ];
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

    /**
     * @return LoaderInterface<*>
     */
    private function getFeatureLoader(): LoaderInterface
    {
        $feature = new FeatureNode('Account', null, ['feature-tag'], null, [
            new ScenarioNode('Login works', ['smoke'], [], 'Scenario', 3),
            new ScenarioNode('Logout works', ['smoke', 'slow'], [], 'Scenario', 6),
            new ScenarioNode('Login fails', ['slow'], [], 'Scenario', 9),
        ], 'Feature', 'en', __FILE__, 1);

        return new class($feature) implements LoaderInterface {
            public function __construct(private FeatureNode $feature)
            {
            }

            public function supports($resource): bool
            {
                return true;
            }

            public function load($resource): array
            {
                return [$this->feature];
            }
        };
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
