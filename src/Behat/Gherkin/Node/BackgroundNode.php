<?php

namespace Behat\Gherkin\Node;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Background Gherkin AST node.
 * 
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class BackgroundNode extends AbstractNode
{
    private $steps = array();
    private $feature;

    /**
     * Add Step to the background.
     *
     * @param   StepNode    $step
     */
    public function addStep(StepNode $step)
    {
        $step->setParent($this);
        $this->steps[] = $step;
    }

    /**
     * Set steps array of the background.
     *
     * @param   array   $steps  array of StepNode
     */
    public function setSteps(array $steps)
    {
        $this->steps = array();

        foreach ($steps as $step) {
            $this->addStep($step);
        }
    }

    /**
     * Check if background has steps.
     *
     * @return  boolean
     */
    public function hasSteps()
    {
        return count($this->steps) > 0;
    }

    /**
     * Return steps array.
     *
     * @return  array           array of StepNode
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * Set parent feature of the node.
     *
     * @param   FeatureNode $feature
     */
    public function setFeature(FeatureNode $feature)
    {
        $this->feature = $feature;
    }

    /**
     * Return parent feature of the node.
     *
     * @return  FeatureNode
     */
    public function getFeature()
    {
        return $this->feature;
    }

    /**
     * Return definition file.
     *
     * @return  string
     */
    public function getFile()
    {
        if (null !== $this->feature) {
            return $this->feature->getFile();
        }

        return null;
    }

    /**
     * Return language of the feature.
     *
     * @return  string
     */
    public function getLanguage()
    {
        if (null !== $this->feature) {
            return $this->feature->getLanguage();
        }

        return null;
    }
}
