<?php

namespace Behat\Gherkin\Node;

class RuleNode implements KeywordNodeInterface
{

    /**
     * @var string
     */
    private $title;

    /**
     * @var int
     */
    private $line;

    /**
     * @var null|BackgroundNode
     */
    private $background;

    /**
     * @var array
     */
    private $examples = array();

    /**
     * RuleNode constructor.
     * @param $title
     * @param $line
     * @param BackgroundNode|null $background
     * @param array $examples
     */
    public function __construct($title, $line, BackgroundNode $background = null, array $examples = array())
    {
        $this->title = $title;
        $this->line = $line;
        $this->background = $background;

        array_walk($examples, array($this, 'addExample'));
    }

    private function addExample(ExampleNode $example)
    {
        $this->examples[] = $example;
    }

    /**
     * Returns node type string
     *
     * @return string
     */
    public function getNodeType()
    {
        return 'Rule';
    }

    /**
     * Returns node keyword.
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->getNodeType();
    }

    /**
     * Returns node title.
     *
     * @return null|string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Returns feature declaration line number.
     *
     * @return integer
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * @return BackgroundNode|null
     */
    public function getBackground()
    {
        return $this->background;
    }

    /**
     * @return array
     */
    public function getExamples()
    {
        return $this->examples;
    }
}