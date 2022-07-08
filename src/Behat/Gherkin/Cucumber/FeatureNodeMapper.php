<?php

namespace Behat\Gherkin\Cucumber;

use Behat\Gherkin\Node\FeatureNode;
use Cucumber\Messages\GherkinDocument;

final class FeatureNodeMapper
{
    /**
     * @var TagMapper
     */
    private $tagMapper;

    /**
     * @var BackgroundNodeMapper
     */
    private $backgroundMapper;

    /**
     * @var ScenarioNodeMapper
     */
    private $scenarioMapper;

    public function __construct(
        TagMapper $tagMapper,
        BackgroundNodeMapper $backgroundMapper,
        ScenarioNodeMapper $scenarioMapper
    )
    {
        $this->tagMapper = $tagMapper;
        $this->backgroundMapper = $backgroundMapper;
        $this->scenarioMapper = $scenarioMapper;
    }

    function map(GherkinDocument $gherkinDocument) : ?FeatureNode
    {
        if (!$gherkinDocument->feature) {
            return null;
        }

        return new FeatureNode(
            $gherkinDocument->feature->name,
            $gherkinDocument->feature->description,
            $this->tagMapper->map($gherkinDocument->feature->tags),
            $this->backgroundMapper->map($gherkinDocument->feature->children),
            $this->scenarioMapper->map($gherkinDocument->feature->children),
            $gherkinDocument->feature->keyword,
            $gherkinDocument->feature->language,
            $gherkinDocument->uri,
            $gherkinDocument->feature->location->line
        );
    }
}
