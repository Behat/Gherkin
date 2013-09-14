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
 * Represents Gherkin Background.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class BackgroundNode implements ScenarioLikeInterface
{
    /**
     * @var string
     */
    private $title;
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
     * Initializes background.
     *
     * @param null|string $title
     * @param StepNode[]  $steps
     * @param string      $keyword
     * @param integer     $line
     */
    public function __construct($title, array $steps, $keyword, $line)
    {
        $this->title = $title;
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
        return 'Background';
    }

    /**
     * Returns background title.
     *
     * @return null|string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Checks if background has steps.
     *
     * @return Boolean
     */
    public function hasSteps()
    {
        return 0 < count($this->steps);
    }

    /**
     * Returns background steps.
     *
     * @return StepNode[]
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * Returns background feature.
     *
     * @return FeatureNode
     */
    public function getFeature()
    {
        return $this->feature;
    }

    /**
     * Sets background feature.
     *
     * @param FeatureNode $feature
     */
    public function setFeature(FeatureNode $feature)
    {
        $this->feature = $feature;
    }

    /**
     * Returns background keyword.
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
            throw new NodeException('Can not identify language of background that is not bound to feature.');
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
            throw new NodeException('Can not identify file of background that is not bound to feature.');
        }

        return $this->feature->getFile();
    }

    /**
     * Returns background declaration line number.
     *
     * @return integer
     */
    public function getLine()
    {
        return $this->line;
    }
}
