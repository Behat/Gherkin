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
class ExampleNode implements ScenarioInterface, NamedScenarioInterface
{
    /**
     * @var string
     */
    private $text;
    /**
     * @var string[]
     */
    private $tags;
    /**
     * @var StepNode[]
     */
    private $outlineSteps;
    /**
     * @var string[]
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
     * @var string
     */
    private $outlineTitle;
    /**
     * @var null|int
     */
    private $index;

    /**
     * Initializes outline.
     *
     * @param string      $text         The entire row as a string, e.g. "| 1 | 2 | 3 |"
     * @param string[]    $tags
     * @param StepNode[]  $outlineSteps
     * @param string[]    $tokens
     * @param integer     $line         Line number within the feature file.
     * @param string|null $outlineTitle Original title of the scenario outline.
     * @param null|int    $index        The 1-based index of the row/example within the scenario outline.
     */
    public function __construct($text, array $tags, $outlineSteps, array $tokens, $line, $outlineTitle = null, $index = null)
    {
        $this->text = $text;
        $this->tags = $tags;
        $this->outlineSteps = $outlineSteps;
        $this->tokens = $tokens;
        $this->line = $line;
        $this->outlineTitle = $outlineTitle;
        $this->index = $index;
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
     * Returns node keyword.
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->getNodeType();
    }

    /**
     * Returns the example row as a single string.
     *
     * @return string
     *
     * @deprecated You should normally not depend on the original row text, but if you really do, please switch
     *             to {@see self::getExampleText()} as this method will be removed in the next major version.
     */
    public function getTitle()
    {
        return $this->text;
    }

    /**
     * Checks if outline is tagged with tag.
     *
     * @param string $tag
     *
     * @return bool
     */
    public function hasTag($tag)
    {
        return in_array($tag, $this->getTags());
    }

    /**
     * Checks if outline has tags (both inherited from feature and own).
     *
     * @return bool
     */
    public function hasTags()
    {
        return 0 < count($this->getTags());
    }

    /**
     * Returns outline tags (including inherited from feature).
     *
     * @return string[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Checks if outline has steps.
     *
     * @return bool
     */
    public function hasSteps()
    {
        return 0 < count($this->outlineSteps);
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
     * @return string[]
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
     * Returns outline title.
     *
     * @return string
     */
    public function getOutlineTitle()
    {
        return $this->outlineTitle;
    }

    public function getName(): ?string
    {
        return "{$this->replaceTextTokens($this->outlineTitle)} #{$this->index}";
    }

    /**
     * Returns the example row as a single string.
     *
     * You should normally not need this, since it is an implementation detail.
     * If you need the individual example values, use {@see self::getTokens()}.
     * To get the fully-normalised/expanded title, use {@see self::getName()}.
     */
    public function getExampleText(): string
    {
        return $this->text;
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
            $keyword = $outlineStep->getKeyword();
            $keywordType = $outlineStep->getKeywordType();
            $text = $this->replaceTextTokens($outlineStep->getText());
            $args = $this->replaceArgumentsTokens($outlineStep->getArguments());
            $line = $outlineStep->getLine();

            $steps[] = new StepNode($keyword, $text, $args, $line, $keywordType);
        }

        return $steps;
    }

    /**
     * Replaces tokens in arguments with row values.
     *
     * @param ArgumentInterface[] $arguments
     *
     * @return ArgumentInterface[]
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
