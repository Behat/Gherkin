<?php

namespace Tests\Behat\Gherkin\Fixtures;

use Symfony\Component\Yaml\Yaml;

use Behat\Gherkin\Node;

class YamlParser
{
    public function parse($yamlPath, $featurePath)
    {
        $yaml = Yaml::load($yamlPath);

        return $this->parseFeature($yaml, $featurePath);
    }

    protected function parseFeature(array $yaml, $featurePath)
    {
        $featureHash = $yaml['feature'];

        $featureNode = new Node\FeatureNode(
            $featureHash['title'], null, $featurePath, $featureHash['line']
        );
        $featureNode->setLanguage($featureHash['language']);
        $featureNode->setDescription($featureHash['description']);

        if (isset($featureHash['tags'])) {
            $featureNode->setTags($featureHash['tags']);
        }

        if (isset($featureHash['background'])) {
            $this->addBackground($featureNode, $featureHash['background']);
        }

        if (isset($featureHash['scenarios'])) {
            $this->addScenarios($featureNode, $featureHash['scenarios']);
        }

        return $featureNode;
    }

    protected function addBackground($node, array $backgroundHash)
    {
        $backgroundNode = new Node\BackgroundNode($backgroundHash['line']);
        $this->addSteps($backgroundNode, $backgroundHash['steps']);
        $node->setBackground($backgroundNode);
    }

    protected function addScenarios($node, array $scenarios)
    {
        foreach ($scenarios as $key => $scenarioHash) {
            if ('scenario' === $scenarioHash['type']) {
                $scenarioNode = new Node\ScenarioNode($scenarioHash['title'], $scenarioHash['line']);
            } else {
                $scenarioNode = new Node\OutlineNode($scenarioHash['title'], $scenarioHash['line']);
                if (isset($scenarioHash['examples'])) {
                    $scenarioNode->setExamples($this->parseTable($scenarioHash['examples']));
                }
            }
            if (isset($scenarioHash['tags'])) {
                $scenarioNode->setTags($scenarioHash['tags']);
            }
            if (isset($scenarioHash['steps'])) {
                $this->addSteps($scenarioNode, $scenarioHash['steps']);
            }
            $node->addScenario($scenarioNode);
        }
    }

    protected function addSteps($node, array $stepsHash)
    {
        $steps = array();

        foreach ($stepsHash as $key => $stepHash) {
            $stepNode = new Node\StepNode(
                $stepHash['type'], $stepHash['text'], $stepHash['line']
            );

            if (isset($stepHash['arguments'])) {
                foreach ($stepHash['arguments'] as $key => $hash) {
                    if ('pystring' === $hash['type']) {
                        $stepNode->addArgument($this->parsePyString($hash));
                    } elseif ('table' === $hash['type']) {
                        $stepNode->addArgument($this->parseTable($hash['rows']));
                    }
                }
            }

            $node->addStep($stepNode);
        }
    }

    protected function parsePyString(array $pystrHash)
    {
        return new Node\PyStringNode($pystrHash['text'], $pystrHash['swallow']);
    }

    protected function parseTable(array $tableHash)
    {
        $table = new Node\TableNode();

        foreach ($tableHash as $key => $hash) {
            $table->addRow($hash);
        }

        return $table;
    }
}
