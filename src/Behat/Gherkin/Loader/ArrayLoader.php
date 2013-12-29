<?php

/*
 * This file is part of the Behat Gherkin.
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
     * @return Boolean
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
        $features = array();

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
     * @param array   $hash Feature hash
     * @param integer $line
     *
     * @return FeatureNode
     */
    protected function loadFeatureHash(array $hash, $line = 0)
    {
        $title = isset($hash['title']) ? $hash['title'] : null;
        $description = isset($hash['description']) ? $hash['description'] : null;
        $tags = isset($hash['tags']) ? $hash['tags'] : array();
        $keyword = isset($hash['keyword']) ? $hash['keyword'] : 'Feature';
        $language = isset($hash['language']) ? $hash['language'] : 'en';
        $line = isset($hash['line']) ? $hash['line'] : $line;
        $background = isset($hash['background']) ? $this->loadBackgroundHash($hash['background']) : null;

        $scenarios = array();
        if (isset($hash['scenarios'])) {
            foreach ($hash['scenarios'] as $scenarioIterator => $scenarioHash) {
                if (isset($scenarioHash['type']) && 'outline' === $scenarioHash['type']) {
                    $scenarios[] = $this->loadOutlineHash($scenarioHash, $scenarioIterator);
                } else {
                    $scenarios[] = $this->loadScenarioHash($scenarioHash, $scenarioIterator);
                }
            }
        }

        return new FeatureNode($title, $description, $tags, $background, $scenarios, $keyword, $language, null, $line);
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
        $title = isset($hash['title']) ? $hash['title'] : null;
        $keyword = isset($hash['keyword']) ? $hash['keyword'] : 'Background';
        $line = isset($hash['line']) ? $hash['line'] : 0;

        $steps = array();
        if (isset($hash['steps'])) {
            foreach ($hash['steps'] as $stepIterator => $stepHash) {
                $steps[] = $this->loadStepHash($stepHash, $stepIterator);
            }
        }

        return new BackgroundNode($title, $steps, $keyword, $line);
    }

    /**
     * Loads scenario from provided scenario hash.
     *
     * @param array   $hash Scenario hash
     * @param integer $line Scenario definition line
     *
     * @return ScenarioNode
     */
    protected function loadScenarioHash(array $hash, $line = 0)
    {
        $title = isset($hash['title']) ? $hash['title'] : null;
        $keyword = isset($hash['keyword']) ? $hash['keyword'] : 'Scenario';
        $tags = isset($hash['tags']) ? $hash['tags'] : array();
        $line = isset($hash['line']) ? $hash['line'] : $line;

        $steps = array();
        if (isset($hash['steps'])) {
            foreach ($hash['steps'] as $stepIterator => $stepHash) {
                $steps[] = $this->loadStepHash($stepHash, $stepIterator);
            }
        }

        return new ScenarioNode($title, $tags, $steps, $keyword, $line);
    }

    /**
     * Loads outline from provided outline hash.
     *
     * @param array   $hash Outline hash
     * @param integer $line Outline definition line
     *
     * @return OutlineNode
     */
    protected function loadOutlineHash(array $hash, $line = 0)
    {
        $title = isset($hash['title']) ? $hash['title'] : null;
        $tags = isset($hash['tags']) ? $hash['tags'] : array();
        $keyword = isset($hash['keyword']) ? $hash['keyword'] : 'Scenario Outline';
        $line = isset($hash['line']) ? $hash['line'] : $line;

        $steps = array();
        if (isset($hash['steps'])) {
            foreach ($hash['steps'] as $stepIterator => $stepHash) {
                $steps[] = $this->loadStepHash($stepHash, $stepIterator);
            }
        }

        $examples = new ExampleTableNode(array(), 'Examples');
        if (isset($hash['examples'])) {
            if (isset($hash['examples']['keyword'])) {
                $examplesKeyword = $hash['examples']['keyword'];
                unset($hash['examples']['keyword']);
            } else {
                $examplesKeyword = 'Examples';
            }

            $examples = new ExampleTableNode($hash['examples'], $examplesKeyword);
        }

        return new OutlineNode($title, $tags, $steps, $examples, $keyword, $line);
    }

    /**
     * Loads step from provided hash.
     *
     * @param array   $hash Step hash
     * @param integer $line Step definition line
     *
     * @return StepNode
     */
    protected function loadStepHash(array $hash, $line = 0)
    {
        $type = isset($hash['type']) ? $hash['type'] : 'Given';
        $text = $hash['text'];
        $line = isset($hash['line']) ? $hash['line'] : $line;

        $arguments = array();
        if (isset($hash['arguments'])) {
            foreach ($hash['arguments'] as $argumentHash) {
                if ('table' === $argumentHash['type']) {
                    $arguments[] = $this->loadTableHash($argumentHash['rows']);
                } elseif ('pystring' === $argumentHash['type']) {
                    $arguments[] = $this->loadPyStringHash($argumentHash, $line + 1);
                }
            }
        }

        return new StepNode($type, $text, $arguments, $line);
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
     * @param array   $hash PyString hash
     * @param integer $line
     *
     * @return PyStringNode
     */
    protected function loadPyStringHash(array $hash, $line = 0)
    {
        $line = isset($hash['line']) ? $hash['line'] : $line;

        $strings = array();
        foreach (explode("\n", $hash['text']) as $string) {
            $strings[] = $string;
        }

        return new PyStringNode($strings, $line);
    }
}
