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
 * Step Gherkin AST node.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class StepNode extends AbstractNode
{
    private $type;
    private $text;
    private $parent;
    private $arguments = array();

    /**
     * Initizalizes step.
     *
     * @param string  $type Step type
     * @param string  $text Step text
     * @param integer $line Definition line
     */
    public function __construct($type, $text = null, $line = 0)
    {
        parent::__construct($line);

        $this->type = $type;
        $this->text = $text;
    }

    /**
     * Returns new example step, initialized with values from specific row.
     *
     * @return ExampleStepNode
     *
     * @throws \LogicException if feature is frozen
     */
    public function createExampleRowStep(array $tokens)
    {
        if (!$this->isFrozen()) {
            throw new \LogicException('Impossible to get example step from non-frozen one.');
        }

        return new ExampleStepNode($this, $tokens);
    }

    /**
     * Sets step type.
     *
     * @param string $type Step type (Given|When|Then|And etc)
     *
     * @throws \LogicException if feature is frozen
     */
    public function setType($type)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change step type in frozen feature.');
        }

        $this->type = $type;
    }

    /**
     * Returns step type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets step text.
     *
     * @param string $text Step text
     *
     * @throws \LogicException if feature is frozen
     */
    public function setText($text)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change step text in frozen feature.');
        }

        $this->text = $text;
    }

    /**
     * Returns step text.
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Adds argument to step.
     *
     * @param StepArgumentNodeInterface $argument Step argument
     *
     * @throws \LogicException if feature is frozen
     */
    public function addArgument(StepArgumentNodeInterface $argument)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change step arguments in frozen feature.');
        }

        $this->arguments[] = $argument;
    }

    /**
     * Sets step arguments.
     *
     * @param array $arguments Array of arguments
     *
     * @throws \LogicException if feature is frozen
     */
    public function setArguments(array $arguments)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change step arguments in frozen feature.');
        }

        foreach ($arguments as $argument) {
            $this->addArgument($argument);
        }
    }

    /**
     * Checks if step has arguments.
     *
     * @return Boolean
     */
    public function hasArguments()
    {
        return count($this->arguments) > 0;
    }

    /**
     * Returns step arguments.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Sets parent node of the step.
     *
     * @param AbstractScenarioNode $node Parent scenario
     *
     * @throws \LogicException if feature is frozen
     */
    public function setParent(AbstractScenarioNode $node)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to reassign step from frozen feature.');
        }

        $this->parent = $node;
    }

    /**
     * Returns parent node of the step.
     *
     * @return AbstractScenarioNode
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Returns definition file.
     *
     * @return string
     */
    public function getFile()
    {
        return null !== $this->parent
             ? $this->parent->getFile()
             : null;
    }

    /**
     * Returns language of the feature.
     *
     * @return string
     */
    public function getLanguage()
    {
        return null !== $this->parent
             ? $this->parent->getLanguage()
             : null;
    }

    /**
     * Checks whether step has been frozen.
     *
     * @return Boolean
     */
    public function isFrozen()
    {
        return null !== $this->parent
             ? $this->parent->isFrozen()
             : false;
    }
}
