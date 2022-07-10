<?php

namespace Behat\Gherkin\Cucumber;

use Behat\Gherkin\Exception\ParserException;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
use Cucumber\Messages\FeatureChild;
use Cucumber\Messages\Scenario;

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
        TagMapper              $tagMapper,
        StepNodeMapper         $stepNodeMapper,
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
    public function map(array $children): array
    {
        $scenarios = [];

        foreach ($children as $child) {
            if ($child->scenario) {

                $childScenario = $child->scenario;

                $scenario = $this->buildScenarioNode($childScenario);

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

            if ($child->rule) {

                $ruleTags = $this->tagMapper->map($child->rule->tags);

                foreach ($child->rule->children as $ruleChild) {

                    // there's no sensible way to merge this up into the feature
                    if ($ruleChild->background) {
                        throw new ParserException('Backgrounds in Rules are not currently supported');
                    }

                    if ($ruleChild->scenario) {
                        $scenarios[] = $this->buildScenarioNode($ruleChild->scenario, $ruleTags);
                    }
                }
            }

        }

        return $scenarios;
    }

    private function buildScenarioNode(?Scenario $scenario, array $extraTags = []): ScenarioNode
    {
        $title = $scenario->name;
        if ($scenario->description) {
            $title .= "\n" . $scenario->description;
        }

        return new ScenarioNode (
            MultilineStringFormatter::format(
                $title,
                $scenario->location
            ),

            array_values(array_unique(array_merge($extraTags, $this->tagMapper->map($scenario->tags)))),
            $this->stepNodeMapper->map($scenario->steps),
            $scenario->keyword,
            $scenario->location->line
        );
    }
}
