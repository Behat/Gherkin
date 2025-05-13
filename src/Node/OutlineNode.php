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
    use TaggedNodeTrait;

    /**
     * @var string
     */
    private $title;
    /**
     * @var string[]
     */
    private $tags;
    /**
     * @var StepNode[]
     */
    private $steps;
    /**
     * @var array<array-key, ExampleTableNode>
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
     * @var ExampleNode[]|null
     */
    private $examples;

    /**
     * Initializes outline.
     *
     * @param string|null $title
     * @param string[] $tags
     * @param StepNode[] $steps
     * @param ExampleTableNode|array<array-key, ExampleTableNode> $tables
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
        $this->tags = $tags;
        $this->steps = $steps;
        $this->keyword = $keyword;
        $this->line = $line;
        $this->tables = is_array($tables) ? $tables : [$tables];
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
     * @return StepNode[]
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
     * @return ExampleTableNode
     *
     * @deprecated use getExampleTables instead
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
     * @return ExampleNode[]
     */
    public function getExamples()
    {
        return $this->examples = $this->examples ?: $this->createExamples();
    }

    /**
     * Returns examples tables array for the outline.
     *
     * @return array<array-key, ExampleTableNode>
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
     * @param array<array-key, ExampleTableNode> $exampleTables
     */
    public function withTables(array $exampleTables): self
    {
        return new OutlineNode(
            $this->title,
            $this->tags,
            $this->steps,
            $exampleTables,
            $this->keyword,
            $this->line,
        );
    }

    /**
     * Creates examples for this outline using examples table.
     *
     * @return ExampleNode[]
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
