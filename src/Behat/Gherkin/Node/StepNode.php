<?php

/*
 * This file is part of the Behat Gherkin.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Node;

use Behat\Gherkin\Exception\NodeException;

/**
 * Represents Gherkin Step.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class StepNode implements NodeInterface
{
    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $text;
    /**
     * @var ArgumentInterface[]
     */
    private $arguments = array();
    /**
     * @var integer
     */
    private $line;

    /**
     * Initializes step.
     *
     * @param string              $type
     * @param string              $text
     * @param ArgumentInterface[] $arguments
     * @param integer             $line
     */
    public function __construct($type, $text, array $arguments, $line)
    {
        if (count($arguments) > 1) {
            throw new NodeException(sprintf(
                'Steps could have only one argument, but `%s %s` have %d.',
                $type,
                $text,
                count($arguments)
            ));
        }

        $this->type = $type;
        $this->text = $text;
        $this->arguments = $arguments;
        $this->line = $line;
    }

    /**
     * Returns node type string
     *
     * @return string
     */
    public function getNodeType()
    {
        return 'Step';
    }

    /**
     * Returns step type keyword (Given, When, Then, etc.).
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
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
     * Checks if step has arguments.
     *
     * @return Boolean
     */
    public function hasArguments()
    {
        return 0 < count($this->arguments);
    }

    /**
     * Returns step arguments.
     *
     * @return ArgumentInterface[]
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Returns step declaration line number.
     *
     * @return integer
     */
    public function getLine()
    {
        return $this->line;
    }
}
