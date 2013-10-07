<?php

namespace Behat\Gherkin\Node;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Table Argument Gherkin AST node.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ExampleTableNode extends TableNode
{
    private $cleanRows = array();

    /**
     * Initializes table.
     *
     * @param TableNode $cleanTable
     * @param array     $tokens
     *
     * @internal param string $table Initial table string
     */
    public function __construct(TableNode $cleanTable, array $tokens)
    {
        $this->cleanRows = $rows = $cleanTable->getRows();

        foreach ($tokens as $key => $value) {
            foreach (array_keys($rows) as $row) {
                foreach (array_keys($rows[$row]) as $col) {
                    $rows[$row][$col] = str_replace('<'.$key.'>', $value, $rows[$row][$col]);
                }
            }
        }

        $this->setKeyword($cleanTable->getKeyword());
        $this->setRows($rows);
    }

    /**
     * Returns rows without tokens being replaced.
     *
     * @return array
     */
    public function getCleanRows()
    {
        return $this->cleanRows;
    }
}
