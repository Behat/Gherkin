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
    private $tokens     = array();
    private $arguments  = array();

    /**
     * Initizalize step.
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
     * Set step type.
     *
     * @param   string  $type   Given|When|Then|And etc.
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Return step type.
     *
     * @return  string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set step text.
     *
     * @param   string  $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * Return untokenized step text.
     * 
     * @return  string
     */
    public function getCleanText()
    {
        return $this->text;
    }

    /**
     * Return tokenized step text.
     * 
     * @see     setTokens
     * @return  string
     */
    public function getText()
    {
        $text = $this->text;

        foreach ($this->tokens as $key => $value) {
            $text = str_replace('<' . $key . '>', $value, $text);
        }

        return $text;
    }

    /**
     * Set text tokens (replacers).
     *
     * @param   array   $tokens     hash of tokens (search => replace, search => replace, ...)
     */
    public function setTokens(array $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * Return tokens (replacers).
     *
     * @return  array
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * Add argument to step.
     *
     * @param   PyStringNode|TableNode  $argument
     */
    public function addArgument($argument)
    {
        $this->arguments[] = $argument;
    }

    /**
     * Set step arguments.
     *
     * @param   array   $arguments
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * Check if step has arguments.
     *
     * @return  boolean
     */
    public function hasArguments()
    {
        return count($this->arguments) > 0;
    }

    /**
     * Return step arguments.
     *
     * @return  array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Set parent node of the step.
     *
     * @param   BackgroundNode  $node
     */
    public function setParent(BackgroundNode $node)
    {
        $this->parent = $node;
    }

    /**
     * Return parent node of the step.
     *
     * @return  BackgroundNode
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Return definition file.
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
}
