<?php

namespace Behat\Gherkin\Node;

/*
 * This file is part of the Behat Gherkin.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @var StepContainerInterface
     */
    private $container;

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
        $this->type = $type;
        $this->text = $text;
        $this->arguments = $arguments;
        $this->line = $line;

        foreach ($arguments as $argument) {
            $argument->setSubject($this);
        }
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
     * Returns step parent.
     *
     * @return StepContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Sets step parent.
     *
     * @param StepContainerInterface $parent
     */
    public function setContainer(StepContainerInterface $parent)
    {
        $this->container = $parent;
    }

    /**
     * Returns step index (step ordinal number in container).
     *
     * @return integer
     */
    public function getIndex()
    {
        return array_search($this, $this->container->getSteps());
    }

    /**
     * Returns feature language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->container->getLanguage();
    }

    /**
     * Returns feature file.
     *
     * @return null|string
     */
    public function getFile()
    {
        return $this->container->getFile();
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
