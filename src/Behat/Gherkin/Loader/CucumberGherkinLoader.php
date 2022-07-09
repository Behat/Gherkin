<?php declare(strict_types=1);

namespace Behat\Gherkin\Loader;

use Behat\Gherkin\Cucumber\BackgroundNodeMapper;
use Behat\Gherkin\Cucumber\ExampleTableNodeMapper;
use Behat\Gherkin\Cucumber\FeatureNodeMapper;
use Behat\Gherkin\Cucumber\KeywordTypeMapper;
use Behat\Gherkin\Cucumber\PyStringNodeMapper;
use Behat\Gherkin\Cucumber\ScenarioNodeMapper;
use Behat\Gherkin\Cucumber\StepNodeMapper;
use Behat\Gherkin\Cucumber\TableNodeMapper;
use Behat\Gherkin\Cucumber\TagMapper;
use Behat\Gherkin\Node\FeatureNode;
use Cucumber\Gherkin\GherkinParser;
use Cucumber\Messages\GherkinDocument;

final class CucumberGherkinLoader extends AbstractFileLoader
{
    /**
     * @var FeatureNodeMapper
     */
    private $mapper;

    /**
     * @var GherkinParser
     */
    private $parser;

    public function __construct()
    {
        $tagMapper = new TagMapper();
        $stepNodeMapper = new StepNodeMapper(
            new KeywordTypeMapper(),
            new PyStringNodeMapper(),
            new TableNodeMapper()
        );
        $this->mapper = new FeatureNodeMapper(
            $tagMapper,
            new BackgroundNodeMapper(
                $stepNodeMapper
            ),
            new ScenarioNodeMapper(
                $tagMapper,
                $stepNodeMapper,
                new ExampleTableNodeMapper(
                    $tagMapper
                )
            )
        );
        
        $this->parser = new GherkinParser(false, false, true, false);
    }

    /**
     * Checks if current loader supports provided resource.
     *
     * @param mixed $path Resource to load
     *
     * @return bool
     */
    public function supports($path)
    {
        return is_string($path)
            && is_file($absolute = $this->findAbsolutePath($path))
            && 'feature' === pathinfo($absolute, PATHINFO_EXTENSION);
    }

    /**
     * Whether this Loader is available for use
     */
    public static function isAvailable() : bool
    {
        return class_exists(GherkinParser::class);
    }

    /**
     * Loads features from provided resource.
     *
     * @param string $path Resource to load
     *
     * @return FeatureNode[]
     */
    public function load($resource)
    {
        $features = [];

        $envelopes = $this->parser->parseString($this->findAbsolutePath($resource), file_get_contents($resource));
        foreach ($envelopes as $envelope) {
            if ($envelope->gherkinDocument) {
                if ($feature = $this->mapper->map($envelope->gherkinDocument)) {
                    $features[] = $feature;
                }
            }
        }

        return $features;
    }

}
