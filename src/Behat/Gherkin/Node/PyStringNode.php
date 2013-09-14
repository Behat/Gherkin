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
 * Represents Gherkin PyString argument.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class PyStringNode implements ArgumentInterface
{
    /**
     * @var array
     */
    private $strings = array();
    /**
     * @var NodeInterface
     */
    private $subject;
    /**
     * @var integer
     */
    private $line;

    /**
     * Initializes PyString.
     *
     * @param array   $strings String in form of [$stringLine]
     * @param integer $line    Line number where string been started
     */
    public function __construct(array $strings, $line)
    {
        $this->strings = $strings;
        $this->line = $line;
    }

    /**
     * Returns node type.
     *
     * @return string
     */
    public function getNodeType()
    {
        return 'PyString';
    }

    /**
     * Returns entire PyString lines set.
     *
     * @return array
     */
    public function getStrings()
    {
        return $this->strings;
    }

    /**
     * Returns raw string.
     *
     * @return string
     */
    public function getRaw()
    {
        return implode("\n", $this->strings);
    }

    /**
     * Returns PyString subject.
     *
     * @return NodeInterface
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Sets PyString subject node.
     *
     * @param NodeInterface $subject
     */
    public function setSubject(NodeInterface $subject)
    {
        $this->subject = $subject;
    }

    /**
     * Converts PyString into string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getRaw();
    }

    /**
     * Returns feature language.
     *
     * @return string
     *
     * @throws NodeException If subject is not set
     */
    public function getLanguage()
    {
        if (null === $this->subject) {
            throw new NodeException('Can not identify language of argument that is not bound to subject.');
        }

        return $this->subject->getLanguage();
    }

    /**
     * Returns feature file
     *
     * @return string
     *
     * @throws NodeException If subject is not set
     */
    public function getFile()
    {
        if (null === $this->subject) {
            throw new NodeException('Can not identify file of argument that is not bound to subject.');
        }

        return $this->subject->getFile();
    }

    /**
     * Returns line number at which PyString was started.
     *
     * @return integer
     */
    public function getLine()
    {
        return $this->line;
    }
}
