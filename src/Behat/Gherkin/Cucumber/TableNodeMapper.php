<?php

namespace Behat\Gherkin\Cucumber;

use Behat\Gherkin\Node\TableNode;
use Cucumber\Messages\DataTable;
use Cucumber\Messages\TableCell;
use Cucumber\Messages\TableRow;

final class TableNodeMapper
{
    /**
     * @return TableNode[]
     */
    public function map(?DataTable $table) : array
    {
        if (!$table) {
            return [];
        }

        $rows = [];

        foreach($table->rows as $row) {
            $rows[$row->location->line] = array_map(
                function(TableCell $cell) {
                    return $cell->value;
                },
                $row->cells
            );
        }

        return [
            new TableNode($rows)
        ];
    }
}
