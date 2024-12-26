<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Node;

/**
 * Represents Gherkin Outline.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class OutlineNode implements ScenarioInterface
{
    /**
     * @var string
     */
    private $title;
    /**
     * @var list<string>
     */
    private $tags;
    /**
     * @var list<StepNode>
     */
    private $steps;
    /**
     * @var ExampleTableNode|list<ExampleTableNode>
     */
    private $tables;
    /**
     * @var string
     */
    private $keyword;
    /**
     * @var int
     */
    private $line;
    /**
     * @var list<ExampleNode>|null
     */
    private $examples;

    /**
     * Initializes outline.
     *
     * @param string|null $title
     * @param list<string> $tags
     * @param list<StepNode> $steps
     * @param ExampleTableNode|list<ExampleTableNode> $tables
     * @param string $keyword
     * @param int $line
     */
    public function __construct(
        $title,
        array $tags,
        array $steps,
        $tables,
        $keyword,
        $line,
    ) {
        $this->title = $title;
        $this->tags = array_values($tags);
        $this->steps = array_values($steps);
        $this->keyword = $keyword;
        $this->line = $line;
        if (!is_array($tables)) {
            $this->tables = [$tables];
        } else {
            $this->tables = array_values($tables);
        }
    }

    /**
     * Returns node type string.
     *
     * @return string
     */
    public function getNodeType()
    {
        return 'Outline';
    }

    /**
     * Returns outline title.
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
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
        return count($this->getTags()) > 0;
    }

    /**
     * Returns outline tags (including inherited from feature).
     *
     * @return list<string>
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
        return count($this->steps) > 0;
    }

    /**
     * Returns outline steps.
     *
     * @return list<StepNode>
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * Checks if outline has examples.
     *
     * @return bool
     */
    public function hasExamples()
    {
        return count($this->tables) > 0;
    }

    /**
     * Builds and returns examples table for the outline.
     *
     * WARNING: it returns a merged table with tags lost.
     *
     * @deprecated use getExampleTables instead
     *
     * @return ExampleTableNode
     */
    public function getExampleTable()
    {
        $table = [];
        foreach ($this->tables[0]->getTable() as $k => $v) {
            $table[$k] = $v;
        }

        /** @var ExampleTableNode $exampleTableNode */
        $exampleTableNode = new ExampleTableNode($table, $this->tables[0]->getKeyword());
        $tableCount = count($this->tables);
        for ($i = 1; $i < $tableCount; ++$i) {
            $exampleTableNode->mergeRowsFromTable($this->tables[$i]);
        }

        return $exampleTableNode;
    }

    /**
     * Returns list of examples for the outline.
     *
     * @return list<ExampleNode>
     */
    public function getExamples()
    {
        return $this->examples = $this->examples ?: $this->createExamples();
    }

    /**
     * Returns examples tables array for the outline.
     *
     * @return list<ExampleTableNode>
     */
    public function getExampleTables()
    {
        return $this->tables;
    }

    /**
     * Returns outline keyword.
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * Returns outline declaration line number.
     *
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * Creates examples for this outline using examples table.
     *
     * @return list<ExampleNode>
     */
    protected function createExamples()
    {
        $examples = [];

        foreach ($this->getExampleTables() as $exampleTable) {
            foreach ($exampleTable->getColumnsHash() as $rowNum => $row) {
                $examples[] = new ExampleNode(
                    $exampleTable->getRowAsString($rowNum + 1),
                    array_merge($this->tags, $exampleTable->getTags()),
                    $this->getSteps(),
                    $row,
                    $exampleTable->getRowLine($rowNum + 1),
                    $this->getTitle(),
                    $rowNum + 1
                );
            }
        }

        return $examples;
    }
}
