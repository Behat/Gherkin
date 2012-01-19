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
        return is_array($resource) && (isset($resource['features']) || isset($resource['feature']));
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource)
    {
        $features = array();

        if (isset($resource['features'])) {
            foreach ($resource['features'] as $iterator => $hash) {
                $features[] = $this->loadFeatureHash($hash, $iterator);
            }
        } elseif (isset($resource['feature'])) {
            $features[] = $this->loadFeatureHash($resource['feature'], 0);
        }

        return $features;
    }

    /**
     * Loads feature from provided feature hash.
     *
     * @param   array   $hash   feature hash
     * @param   integer $line   feature definition line
     *
     * @return  Behat\Gherkin\Node\FeatureNode
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
     * @param   array   $hash   background hash
     *
     * @return  Behat\Gherkin\Node\BackgroundHash
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
     * @param   array   $hash   scenario hash
     * @param   integer $line   scenario definition line
     *
     * @return  Behat\Gherkin\Node\ScenarioNode
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
     * @param   array   $hash   outline hash
     * @param   integer $line   outline definition line
     *
     * @return  Behat\Gherkin\Node\OutlineNode
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
     * @param   array   $hash   step hash
     * @param   integer $line   step definition line
     *
     * @return  Behat\Gherkin\Node\StepNode
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
     * @param   array   $hash   table hash
     *
     * @return  Behat\Gherkin\Node\TableNode
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
     * Loads PyString from provided hash.
     *
     * @param   array   $hash   pystring hash
     *
     * @return  Behat\Gherkin\Node\PyStringNode
     */
    protected function loadPyStringHash(array $hash)
    {
        $string = new Node\PyStringNode($hash['text']);

        return $string;
    }
}
