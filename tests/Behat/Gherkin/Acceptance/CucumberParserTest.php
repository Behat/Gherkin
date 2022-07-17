<?php declare(strict_types=1);

namespace Tests\Behat\Gherkin\Acceptance;

use Behat\Gherkin\Loader\CucumberGherkinLoader;
use Behat\Gherkin\Node\FeatureNode;
use PHPUnit\Framework\TestCase;

/** @group cucumber */
final class CucumberParserTest extends TestCase
{
    protected $etalons_skip = [
        'comments', # see https://github.com/cucumber/common/issues/1413
        'multiline_name_with_newlines', # cucumber does not preserve leading newlines in description blocks
    ];

    public function setUp() : void
    {
        $this->loader = new CucumberGherkinLoader();
    }

    use CompatibilityTestTrait;

    protected function parseFeature($featureFile): FeatureNode
    {
        return $this->loader->load($featureFile)[0];
    }
}
