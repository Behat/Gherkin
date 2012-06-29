<?php

namespace Behat\Gherkin\Loader;

use Behat\Gherkin\Node;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @return array
     */
    public function load($resource)
    {
        $features = array();

        if (isset($resource['features'])) {
            foreach ($resource['features'] as $iterator => $hash) {
                $feature    = $this->loadFeatureHash($hash, $iterator);
                $features[] = $feature;
            }
        } elseif (isset($resource['feature'])) {
            $feature    = $this->loadFeatureHash($resource['feature'], 0);
            $features[] = $feature;
        }

        return $features;
    }

    /**
     * Loads feature from provided feature hash.
     *
     * @param array   $hash Feature hash
     * @param integer $line Feature definition line
     *
     * @return FeatureNode
     */
    protected function loadFeatureHash(array $hash, $line = 0)
    {
        $feature = new Node\FeatureNode(null, null, null, isset($hash['line']) ? $hash['line'] : $line);

        $feature->setKeyword(isset($hash['keyword']) ? $hash['keyword'] : 'Feature');

        if (isset($hash['title'])) {
            $feature->setTitle($hash['title']);
        }
        if (isset($hash['description'])) {
            $feature->setDescription($hash['description']);
        }
        if (isset($hash['tags'])) {
            $feature->setTags($hash['tags']);
        }
        if (isset($hash['language'])) {
            $feature->setLanguage($hash['language']);
        }
        if (isset($hash['background'])) {
            $feature->setBackground($this->loadBackgroundHash($hash['background']));
        }
        if (isset($hash['scenarios'])) {
            foreach ($hash['scenarios'] as $scenarioIterator => $scenarioHash) {
                if (isset($scenarioHash['type']) && 'outline' === $scenarioHash['type']) {
                    $feature->addScenario($this->loadOutlineHash($scenarioHash, $scenarioIterator));
                } else {
                    $feature->addScenario($this->loadScenarioHash($scenarioHash, $scenarioIterator));
                }
            }
        }

        return $feature;
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
        $background = new Node\BackgroundNode(null, isset($hash['line']) ? $hash['line'] : 0);

        $background->setKeyword(isset($hash['keyword']) ? $hash['keyword'] : 'Background');

        if (isset($hash['title'])) {
            $background->setTitle($hash['title']);
        }
        if (isset($hash['steps'])) {
            foreach ($hash['steps'] as $stepIterator => $stepHash) {
                $background->addStep($this->loadStepHash($stepHash, $stepIterator));
            }
        }

        return $background;
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
        $scenario = new Node\ScenarioNode(null, isset($hash['line']) ? $hash['line'] : $line);

        $scenario->setKeyword(isset($hash['keyword']) ? $hash['keyword'] : 'Scenario');

        if (isset($hash['title'])) {
            $scenario->setTitle($hash['title']);
        }
        if (isset($hash['tags'])) {
            $scenario->setTags($hash['tags']);
        }
        if (isset($hash['steps'])) {
            foreach ($hash['steps'] as $stepIterator => $stepHash) {
                $scenario->addStep($this->loadStepHash($stepHash, $stepIterator));
            }
        }

        return $scenario;
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
        $outline = new Node\OutlineNode(null, isset($hash['line']) ? $hash['line'] : $line);

        $outline->setKeyword(isset($hash['keyword']) ? $hash['keyword'] : 'Scenario Outline');

        if (isset($hash['title'])) {
            $outline->setTitle($hash['title']);
        }
        if (isset($hash['tags'])) {
            $outline->setTags($hash['tags']);
        }
        if (isset($hash['examples'])) {
            if (isset($hash['examples']['keyword'])) {
                $keyword = $hash['examples']['keyword'];
                unset($hash['examples']['keyword']);
            } else {
                $keyword = 'Examples';
            }
            $table = $this->loadTableHash($hash['examples']);
            $table->setKeyword($keyword);
            $outline->setExamples($table);
        }
        if (isset($hash['steps'])) {
            foreach ($hash['steps'] as $stepIterator => $stepHash) {
                $outline->addStep($this->loadStepHash($stepHash, $stepIterator));
            }
        }

        return $outline;
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
        $step = new Node\StepNode(
            $hash['type'], isset($hash['text']) ? $hash['text'] : null, isset($hash['line']) ? $hash['line'] : $line
        );

        if (isset($hash['arguments'])) {
            foreach ($hash['arguments'] as $argumentHash) {
                if ('table' === $argumentHash['type']) {
                    $step->addArgument($this->loadTableHash($argumentHash['rows']));
                } elseif ('pystring' === $argumentHash['type']) {
                    $step->addArgument($this->loadPyStringHash($argumentHash));
                }
            }
        }

        return $step;
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
        $table = new Node\TableNode();

        foreach ($hash as $line => $row) {
            $table->addRow($row, $line);
        }

        return $table;
    }

    /**
     * Loads PyString from provided hash.
     *
     * @param array $hash PyString hash
     *
     * @return PyStringNode
     */
    protected function loadPyStringHash(array $hash)
    {
        $string = new Node\PyStringNode($hash['text']);

        return $string;
    }
}
