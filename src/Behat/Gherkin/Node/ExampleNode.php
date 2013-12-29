<?php

/*
 * This file is part of the Behat Gherkin.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Node;

/**
 * Represents Gherkin Outline Example.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ExampleNode implements StepContainerInterface
{
    /**
     * @var StepNode[]
     */
    private $outlineSteps;
    /**
     * @var string
     */
    private $title;
    /**
     * @var array
     */
    private $tokens;
    /**
     * @var integer
     */
    private $line;
    /**
     * @var null|StepNode[]
     */
    private $steps;

    /**
     * Initializes outline.
     *
     * @param StepNode[] $outlineSteps
     * @param string     $title
     * @param array      $tokens
     * @param integer    $line
     */
    public function __construct(array $outlineSteps, $title, array $tokens, $line)
    {
        $this->outlineSteps = $outlineSteps;
        $this->title = $title;
        $this->tokens = $tokens;
        $this->line = $line;
    }

    /**
     * Returns node type string
     *
     * @return string
     */
    public function getNodeType()
    {
        return 'Example';
    }

    /**
     * Returns example title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Checks if outline has steps.
     *
     * @return Boolean
     */
    public function hasSteps()
    {
        return count($this->outlineSteps);
    }

    /**
     * Returns outline steps.
     *
     * @return StepNode[]
     */
    public function getSteps()
    {
        return $this->steps = $this->steps ? : $this->createExampleSteps();
    }

    /**
     * Returns example tokens.
     *
     * @return array
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * Returns outline declaration line number.
     *
     * @return integer
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * Creates steps for this example from abstract outline steps.
     *
     * @return StepNode[]
     */
    protected function createExampleSteps()
    {
        $steps = array();
        foreach ($this->outlineSteps as $outlineStep) {
            $type = $outlineStep->getType();
            $text = $this->replaceTextTokens($outlineStep->getText());
            $args = $this->replaceArgumentsTokens($outlineStep->getArguments());
            $line = $outlineStep->getLine();

            $steps[] = new StepNode($type, $text, $args, $line);
        }

        return $steps;
    }

    /**
     * Replaces tokens in arguments with row values.
     *
     * @param array $arguments
     *
     * @return array
     */
    protected function replaceArgumentsTokens(array $arguments)
    {
        foreach ($arguments as $num => $argument) {
            if ($argument instanceof TableNode) {
                $arguments[$num] = $this->replaceTableArgumentTokens($argument);
            }
            if ($argument instanceof PyStringNode) {
                $arguments[$num] = $this->replacePyStringArgumentTokens($argument);
            }
        }

        return $arguments;
    }

    /**
     * Replaces tokens in table with row values.
     *
     * @param TableNode $argument
     *
     * @return TableNode
     */
    protected function replaceTableArgumentTokens(TableNode $argument)
    {
        $table = $argument->getTable();
        foreach ($table as $line => $row) {
            foreach (array_keys($row) as $col) {
                $table[$line][$col] = $this->replaceTextTokens($table[$line][$col]);
            }
        }

        return new TableNode($table);
    }

    /**
     * Replaces tokens in PyString with row values.
     *
     * @param PyStringNode $argument
     *
     * @return PyStringNode
     */
    protected function replacePyStringArgumentTokens(PyStringNode $argument)
    {
        $strings = $argument->getStrings();
        foreach ($strings as $line => $string) {
            $strings[$line] = $this->replaceTextTokens($strings[$line]);
        }

        return new PyStringNode($strings, $argument->getLine());
    }

    /**
     * Replaces tokens in text with row values.
     *
     * @param string $text
     *
     * @return string
     */
    protected function replaceTextTokens($text)
    {
        foreach ($this->tokens as $key => $val) {
            $text = str_replace('<' . $key . '>', $val, $text);
        }

        return $text;
    }
}
