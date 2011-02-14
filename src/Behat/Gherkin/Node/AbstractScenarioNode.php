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
 * Abstract Gherkin AST node.
 *
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
abstract class AbstractScenarioNode extends AbstractNode
{
    protected $steps = array();
    protected $feature;

    /**
     * Adds step to the node.
     *
     * @param   Behat\Gherkin\Node\StepNode $step
     */
    public function addStep(StepNode $step)
    {
        $step->setParent($this);
        $this->steps[] = $step;
    }

    /**
     * Sets steps array.
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
     * Checks if node has steps.
     *
     * @return  boolean
     */
    public function hasSteps()
    {
        return count($this->steps) > 0;
    }

    /**
     * Returns steps array.
     *
     * @return  array           array of StepNode
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * Sets parent feature of the node.
     *
     * @param   Behat\Gherkin\Node\FeatureNode $feature
     */
    public function setFeature(FeatureNode $feature)
    {
        $this->feature = $feature;
    }

    /**
     * Returns parent feature of the node.
     *
     * @return  Behat\Gherkin\Node\FeatureNode
     */
    public function getFeature()
    {
        return $this->feature;
    }

    /**
     * Returns definition file.
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
     * Returns language of the feature.
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
