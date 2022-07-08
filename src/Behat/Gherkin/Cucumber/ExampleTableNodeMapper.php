<?php

namespace Behat\Gherkin\Cucumber;

use Behat\Gherkin\Node\ExampleTableNode;
use Cucumber\Messages\Examples;
use Cucumber\Messages\TableCell;
use Cucumber\Messages\TableRow;

final class ExampleTableNodeMapper
{
    /**
     * @param Examples[] $exampleTables
     *
     * @return ExampleTableNode[]
     */
    public function map(array $exampleTables) : array
    {
        $exampleTableNodes = [];

        foreach ($exampleTables as $exampleTable) {
            $exampleTableNodes[] = new ExampleTableNode(
                $this->getTableArray($exampleTable),
                $exampleTable->keyword,
                []
            );
        }

        return $exampleTableNodes;
    }

    private function getTableArray(Examples $exampleTable) : array
    {
        $array = [];

        if ($exampleTable->tableHeader) {
            $array[$exampleTable->tableHeader->location->line] = array_map(
                function (TableCell $cell) {
                    return $cell->value;
                },
                $exampleTable->tableHeader->cells
            );
        }

        foreach ($exampleTable->tableBody as $row) {
            $array[$row->location->line] = array_map(
                function (TableCell $cell) {
                    return $cell->value;
                },
                $row->cells
            );
        }

        return $array;
    }
}
