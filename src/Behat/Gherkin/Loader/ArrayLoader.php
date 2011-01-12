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
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ArrayLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports($resource)
    {
        return is_array($resource) && isset($resource['features']);
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource)
    {
        $features = array();

        foreach ($resource['features'] as $iterator => $hash) {
            $features[] = $this->loadFeatureHash($hash, $iterator);
        }

        return $features;
    }

    /**
     * Load feature from provided feature hash. 
     * 
     * @param   array   $hash   feature hash
     * @param   integer $line   feature definition line
     *
     * @return  Node\FeatureNode
     */
    protected function loadFeatureHash(array $hash, $line = 0)
    {
        $feature = new Node\FeatureNode(null, null, null, isset($hash['line']) ? $hash['line'] : $line);

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
     * Load background from provided hash. 
     * 
     * @param   array   $hash   background hash
     *
     * @return  Node\BackgroundHash
     */
    protected function loadBackgroundHash(array $hash)
    {
        $background = new Node\BackgroundNode(isset($hash['line']) ? $hash['line'] : 0);

        if (isset($hash['steps'])) {
            foreach ($hash['steps'] as $stepIterator => $stepHash) {
                $background->addStep($this->loadStepHash($stepHash, $stepIterator));
            }
        }

        return $background;
    }

    /**
     * Load scenario from provided scenario hash. 
     * 
     * @param   array   $hash   scenario hash
     * @param   integer $line   scenario definition line
     *
     * @return  Node\ScenarioNode
     */
    protected function loadScenarioHash(array $hash, $line = 0)
    {
        $scenario = new Node\ScenarioNode(null, isset($hash['line']) ? $hash['line'] : $line);

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
     * Load outline from provided outline hash. 
     * 
     * @param   array   $hash   outline hash
     * @param   integer $line   outline definition line
     *
     * @return  Node\OutlineNode
     */
    protected function loadOutlineHash(array $hash, $line = 0)
    {
        $outline = new Node\OutlineNode(null, isset($hash['line']) ? $hash['line'] : $line);

        if (isset($hash['title'])) {
            $outline->setTitle($hash['title']);
        }
        if (isset($hash['tags'])) {
            $outline->setTags($hash['tags']);
        }
        if (isset($hash['examples'])) {
            $outline->setExamples($this->loadTableHash($hash['examples']));
        }
        if (isset($hash['steps'])) {
            foreach ($hash['steps'] as $stepIterator => $stepHash) {
                $outline->addStep($this->loadStepHash($stepHash, $stepIterator));
            }
        }

        return $outline;
    }

    /**
     * Load step from provided hash. 
     * 
     * @param   array   $hash   step hash
     * @param   integer $line   step definition line
     *
     * @return  Node\StepNode
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
     * Load table from provided hash. 
     * 
     * @param   array   $hash   table hash
     *
     * @return  Node\TableNode
     */
    protected function loadTableHash(array $hash)
    {
        $table = new Node\TableNode();

        foreach ($hash as $row) {
            $table->addRow($row);
        }

        return $table;
    }

    /**
     * Load PyString from provided hash. 
     * 
     * @param   array   $hash   pystring hash
     *
     * @return  Node\PyStringNode
     */
    protected function loadPyStringHash(array $hash)
    {
        $string = new Node\PyStringNode($hash['text'], isset($hash['swallow']) ? $hash['swallow'] : 0);

        return $string;
    }
}
