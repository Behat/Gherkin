<?php

namespace Behat\Gherkin\Cucumber;

use Behat\Gherkin\Node\StepNode;
use Cucumber\Messages\Step;

final class StepNodeMapper
{
    /**
     * @var KeywordTypeMapper
     */
    private $keywordTypeMapper;

    /**
     * @var PyStringNodeMapper
     */
    private $pyStringNodeMapper;

    /**
     * @var TableNodeMapper
     */
    private $tableNodeMapper;

    public function __construct(
        KeywordTypeMapper $keywordTypeMapper,
        PyStringNodeMapper $pyStringNodeMapper,
        TableNodeMapper $tableNodeMapper
    )
    {
        $this->keywordTypeMapper = $keywordTypeMapper;
        $this->pyStringNodeMapper = $pyStringNodeMapper;
        $this->tableNodeMapper = $tableNodeMapper;
    }

    /**
     * @param Step[] $steps
     * @return StepNode[]
     */
    public function map(array $steps)
    {
        $stepNodes = [];
        $prevType = null;

        foreach ($steps as $step) {
            $stepNodes[] = new StepNode(
                // behat does not include space at end of keyword
                rtrim($step->keyword),
                $step->text,
                array_merge(
                    $this->pyStringNodeMapper->map($step->docString),
                    $this->tableNodeMapper->map($step->dataTable),
                ),
                $step->location->line,
                $prevType = $this->keywordTypeMapper->map($step->keywordType, $prevType)
            );
        }

        return $stepNodes;
    }

}
