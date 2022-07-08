<?php

namespace Behat\Gherkin\Cucumber;

use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
use Cucumber\Messages\FeatureChild;

final class ScenarioNodeMapper
{
    /**
     * @var TagMapper
     */
    private $tagMapper;

    /**
     * @var StepNodeMapper
     */
    private $stepNodeMapper;

    /**
     * @var ExampleTableNodeMapper
     */
    private $exampleTableNodeMapper;

    public function __construct(
        TagMapper $tagMapper,
        StepNodeMapper $stepNodeMapper,
        ExampleTableNodeMapper $exampleTableNodeMapper
    )
    {
        $this->tagMapper = $tagMapper;
        $this->stepNodeMapper = $stepNodeMapper;
        $this->exampleTableNodeMapper = $exampleTableNodeMapper;
    }

    /**
     * @param FeatureChild[] $children
     *
     * @return ScenarioInterface[]
     */
    public function map(array $children) : array
    {
        $scenarios = [];

        foreach ($children as $child) {
            if ($child->scenario) {

                $title = $child->scenario->name;
                if ($child->scenario->description) {
                    $title .= "\n" . $child->scenario->description;
                }

                $scenario = new ScenarioNode (
                    $title,
                    $this->tagMapper->map($child->scenario->tags),
                    $this->stepNodeMapper->map($child->scenario->steps),
                    $child->scenario->keyword,
                    $child->scenario->location->line
                );

                if ($child->scenario->examples) {
                    $scenario = new OutlineNode(
                        $scenario->getTitle(),
                        $scenario->getTags(),
                        $scenario->getSteps(),
                        $this->exampleTableNodeMapper->map($child->scenario->examples),
                        $scenario->getKeyword(),
                        $scenario->getLine()
                    );
                }

                $scenarios[] = $scenario;
            }

        }

        return $scenarios;
    }
}
