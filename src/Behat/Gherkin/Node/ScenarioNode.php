<?php

namespace Behat\Gherkin\Node;

/*
 * This file is part of the Behat Gherkin.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Behat\Gherkin\Exception\NodeException;

/**
 * Represents Gherkin Scenario.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ScenarioNode implements ScenarioInterface
{
    /**
     * @var string
     */
    private $title;
    /**
     * @var array
     */
    private $tags = array();
    /**
     * @var StepNode[]
     */
    private $steps = array();
    /**
     * @var string
     */
    private $keyword;
    /**
     * @var integer
     */
    private $line;
    /**
     * @var FeatureNode
     */
    private $feature;

    /**
     * Initializes scenario.
     *
     * @param null|string $title
     * @param array       $tags
     * @param StepNode[]  $steps
     * @param string      $keyword
     * @param integer     $line
     */
    public function __construct($title, array $tags, array $steps, $keyword, $line)
    {
        $this->title = $title;
        $this->tags = $tags;
        $this->steps = $steps;
        $this->keyword = $keyword;
        $this->line = $line;

        foreach ($this->steps as $step) {
            $step->setContainer($this);
        }
    }

    /**
     * Returns node type string
     *
     * @return string
     */
    public function getNodeType()
    {
        return 'Scenario';
    }

    /**
     * Returns scenario title.
     *
     * @return null|string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Checks if scenario is tagged with tag.
     *
     * @param string $tag
     *
     * @return Boolean
     */
    public function hasTag($tag)
    {
        return in_array($tag, $this->getTags());
    }

    /**
     * Checks if scenario has tags (both inherited from feature and own).
     *
     * @return Boolean
     */
    public function hasTags()
    {
        return 0 < count($this->getTags());
    }

    /**
     * Returns scenario tags (including inherited from feature).
     *
     * @return array
     *
     * @throws NodeException If feature is not set
     */
    public function getTags()
    {
        if (null === $this->feature) {
            throw new NodeException('Can not identify tags of scenario that is not bound to feature.');
        }

        return array_merge($this->feature->getTags(), $this->tags);
    }

    /**
     * Checks if scenario has own tags (excluding ones inherited from feature).
     *
     * @return Boolean
     */
    public function hasOwnTags()
    {
        return 0 < count($this->tags);
    }

    /**
     * Returns scenario own tags (excluding ones inherited from feature).
     *
     * @return array
     */
    public function getOwnTags()
    {
        return $this->tags;
    }

    /**
     * Checks if scenario has steps.
     *
     * @return Boolean
     */
    public function hasSteps()
    {
        return 0 < count($this->steps);
    }

    /**
     * Returns scenario steps.
     *
     * @return StepNode[]
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * Sets scenario feature.
     *
     * @param FeatureNode $feature
     */
    public function setFeature(FeatureNode $feature)
    {
        $this->feature = $feature;
    }

    /**
     * Returns scenario feature.
     *
     * @return FeatureNode
     */
    public function getFeature()
    {
        return $this->feature;
    }

    /**
     * Returns scenario index (scenario ordinal number in feature).
     *
     * @return integer
     *
     * @throws NodeException If feature is not set
     */
    public function getIndex()
    {
        if (null === $this->feature) {
            throw new NodeException('Can not identify index of scenario that is not bound to feature.');
        }

        return array_search($this, $this->feature->getScenarios());
    }

    /**
     * Returns scenario keyword.
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * Returns feature language.
     *
     * @return string
     *
     * @throws NodeException If feature is not set
     */
    public function getLanguage()
    {
        if (null === $this->feature) {
            throw new NodeException('Can not identify language of scenario that is not bound to feature.');
        }

        return $this->feature->getLanguage();
    }

    /**
     * Returns feature file.
     *
     * @return null|string
     *
     * @throws NodeException If feature is not set
     */
    public function getFile()
    {
        if (null === $this->feature) {
            throw new NodeException('Can not identify file of scenario that is not bound to feature.');
        }

        return $this->feature->getFile();
    }

    /**
     * Returns scenario declaration line number.
     *
     * @return integer
     */
    public function getLine()
    {
        return $this->line;
    }
}
