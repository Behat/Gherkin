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
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
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
     * @param   string  $type   step type
     * @param   string  $text   step text
     * @param   integer $line   definition line
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
     * @param   string  $type   Given|When|Then|And etc.
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
     * @return  string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets step text.
     *
     * @param   string  $text
     */
    public function setText($text)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change step text in frozen feature.');
        }

        $this->text = $text;
    }

    /**
     * Returns tokenized step text.
     *
     * @see     setTokens
     * @return  string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Adds argument to step.
     *
     * @param   Behat\Gherkin\Node\PyStringNode|Behat\Gherkin\Node\TableNode    $argument
     */
    public function addArgument($argument)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change step arguments in frozen feature.');
        }

        $this->arguments[] = $argument;
    }

    /**
     * Sets step arguments.
     *
     * @param   array   $arguments
     */
    public function setArguments(array $arguments)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change step arguments in frozen feature.');
        }

        $this->arguments = $arguments;
    }

    /**
     * Checks if step has arguments.
     *
     * @return  boolean
     */
    public function hasArguments()
    {
        return count($this->arguments) > 0;
    }

    /**
     * Returns step arguments.
     *
     * @return  array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Sets parent node of the step.
     *
     * @param   Behat\Gherkin\Node\AbstractScenarioNode  $node
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
     * @return  Behat\Gherkin\Node\AbstractScenarioNode
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Returns definition file.
     *
     * @return  string
     */
    public function getFile()
    {
        if (null !== $this->parent) {
            return $this->parent->getFile();
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
        if (null !== $this->parent) {
            return $this->parent->getLanguage();
        }

        return null;
    }

    /**
     * Checks whether step has been frozen.
     *
     * @return Boolean
     */
    public function isFrozen()
    {
        return null !== $this->getParent()
             ? $this->getParent()->isFrozen()
             : false;
    }
}
