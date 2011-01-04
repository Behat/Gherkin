<?php

namespace Behat\Gherkin\Node;

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
}
