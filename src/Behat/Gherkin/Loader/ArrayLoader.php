<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Loader;

use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;

/**
 * From-array loader.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ArrayLoader implements LoaderInterface
{
    /**
     * Checks if current loader supports provided resource.
     *
     * @param mixed $resource Resource to load
     *
     * @return bool
     */
    public function supports($resource)
    {
        return is_array($resource) && (isset($resource['features']) || isset($resource['feature']));
    }

    /**
     * Loads features from provided resource.
     *
     * @param mixed $resource Resource to load
     *
     * @return FeatureNode[]
     */
    public function load($resource)
    {
        $features = [];

        if (isset($resource['features'])) {
            foreach ($resource['features'] as $iterator => $hash) {
                $feature = $this->loadFeatureHash($hash, $iterator);
                $features[] = $feature;
            }
        } elseif (isset($resource['feature'])) {
            $feature = $this->loadFeatureHash($resource['feature']);
            $features[] = $feature;
        }

        return $features;
    }

    /**
     * Loads feature from provided feature hash.
     *
     * @param array $hash Feature hash
     * @param int $line
     *
     * @return FeatureNode
     */
    protected function loadFeatureHash(array $hash, $line = 0)
    {
        $hash = array_merge(
            [
                'title' => null,
                'description' => null,
                'tags' => [],
                'keyword' => 'Feature',
                'language' => 'en',
                'line' => $line,
                'scenarios' => [],
            ],
            $hash
        );
        $background = isset($hash['background']) ? $this->loadBackgroundHash($hash['background']) : null;

        $scenarios = [];
        foreach ((array) $hash['scenarios'] as $scenarioIterator => $scenarioHash) {
            if (isset($scenarioHash['type']) && $scenarioHash['type'] === 'outline') {
                $scenarios[] = $this->loadOutlineHash($scenarioHash, $scenarioIterator);
            } else {
                $scenarios[] = $this->loadScenarioHash($scenarioHash, $scenarioIterator);
            }
        }

        return new FeatureNode($hash['title'], $hash['description'], $hash['tags'], $background, $scenarios, $hash['keyword'], $hash['language'], null, $hash['line']);
    }

    /**
     * Loads background from provided hash.
     *
     * @param array $hash Background hash
     *
     * @return BackgroundNode
     */
    protected function loadBackgroundHash(array $hash)
    {
        $hash = array_merge(
            [
                'title' => null,
                'keyword' => 'Background',
                'line' => 0,
                'steps' => [],
            ],
            $hash
        );

        $steps = $this->loadStepsHash($hash['steps']);

        return new BackgroundNode($hash['title'], $steps, $hash['keyword'], $hash['line']);
    }

    /**
     * Loads scenario from provided scenario hash.
     *
     * @param array $hash Scenario hash
     * @param int $line Scenario definition line
     *
     * @return ScenarioNode
     */
    protected function loadScenarioHash(array $hash, $line = 0)
    {
        $hash = array_merge(
            [
                'title' => null,
                'tags' => [],
                'keyword' => 'Scenario',
                'line' => $line,
                'steps' => [],
            ],
            $hash
        );

        $steps = $this->loadStepsHash($hash['steps']);

        return new ScenarioNode($hash['title'], $hash['tags'], $steps, $hash['keyword'], $hash['line']);
    }

    /**
     * Loads outline from provided outline hash.
     *
     * @param array $hash Outline hash
     * @param int $line Outline definition line
     *
     * @return OutlineNode
     */
    protected function loadOutlineHash(array $hash, $line = 0)
    {
        $hash = array_merge(
            [
                'title' => null,
                'tags' => [],
                'keyword' => 'Scenario Outline',
                'line' => $line,
                'steps' => [],
                'examples' => [],
            ],
            $hash
        );

        $steps = $this->loadStepsHash($hash['steps']);

        if (isset($hash['examples']['keyword'])) {
            $examplesKeyword = $hash['examples']['keyword'];
            unset($hash['examples']['keyword']);
        } else {
            $examplesKeyword = 'Examples';
        }

        $exHash = $hash['examples'];
        $examples = [];

        if ($this->examplesAreInArray($exHash)) {
            $examples = $this->processExamplesArray($exHash, $examplesKeyword, $examples);
        } else {
            // examples as a single table - we create an array with the only one element
            $examples[] = new ExampleTableNode($exHash, $examplesKeyword);
        }

        return new OutlineNode($hash['title'], $hash['tags'], $steps, $examples, $hash['keyword'], $hash['line']);
    }

    /**
     * Loads steps from provided hash.
     *
     * @return StepNode[]
     */
    private function loadStepsHash(array $hash)
    {
        $steps = [];
        foreach ($hash as $stepIterator => $stepHash) {
            $steps[] = $this->loadStepHash($stepHash, $stepIterator);
        }

        return $steps;
    }

    /**
     * Loads step from provided hash.
     *
     * @param array $hash Step hash
     * @param int $line Step definition line
     *
     * @return StepNode
     */
    protected function loadStepHash(array $hash, $line = 0)
    {
        $hash = array_merge(
            [
                'keyword_type' => 'Given',
                'type' => 'Given',
                'text' => null,
                'keyword' => 'Scenario',
                'line' => $line,
                'arguments' => [],
            ],
            $hash
        );

        $arguments = [];
        foreach ($hash['arguments'] as $argumentHash) {
            if ($argumentHash['type'] === 'table') {
                $arguments[] = $this->loadTableHash($argumentHash['rows']);
            } elseif ($argumentHash['type'] === 'pystring') {
                $arguments[] = $this->loadPyStringHash($argumentHash, $hash['line'] + 1);
            }
        }

        return new StepNode($hash['type'], $hash['text'], $arguments, $hash['line'], $hash['keyword_type']);
    }

    /**
     * Loads table from provided hash.
     *
     * @param array $hash Table hash
     *
     * @return TableNode
     */
    protected function loadTableHash(array $hash)
    {
        return new TableNode($hash);
    }

    /**
     * Loads PyString from provided hash.
     *
     * @param array $hash PyString hash
     * @param int $line
     *
     * @return PyStringNode
     */
    protected function loadPyStringHash(array $hash, $line = 0)
    {
        $line = $hash['line'] ?? $line;

        $strings = [];
        foreach (explode("\n", $hash['text']) as $string) {
            $strings[] = $string;
        }

        return new PyStringNode($strings, $line);
    }

    /**
     * Checks if examples node is an array.
     *
     * @param $exHash object hash
     *
     * @return bool
     */
    private function examplesAreInArray($exHash)
    {
        return isset($exHash[0]);
    }

    /**
     * Processes cases when examples are in the form of array of arrays
     * OR in the form of array of objects.
     *
     * @param $exHash array hash
     * @param $examplesKeyword string
     * @param $examples array
     *
     * @return array
     */
    private function processExamplesArray($exHash, $examplesKeyword, $examples)
    {
        for ($i = 0; $i < count($exHash); ++$i) {
            if (isset($exHash[$i]['table'])) {
                // we have examples as objects, hence there could be tags
                $exHashTags = $exHash[$i]['tags'] ?? [];
                $examples[] = new ExampleTableNode($exHash[$i]['table'], $examplesKeyword, $exHashTags);
            } else {
                // we have examples as arrays
                $examples[] = new ExampleTableNode($exHash[$i], $examplesKeyword);
            }
        }

        return $examples;
    }
}
