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
class TableNode implements StepArgumentNodeInterface
{
    private $rows = array();
    private $keyword;

    /**
     * Initializes table.
     *
     * @param string $table Initial table string
     */
    public function __construct($table = null)
    {
        if (null !== $table) {
            $table = preg_replace("/\r\n|\r/", "\n", $table);

            foreach (explode("\n", $table) as $row) {
                $this->addRow($row);
            }
        }
    }

    /**
     * Returns new node with replaced outline example row tokens.
     *
     * @returns ExampleTableNode
     */
    public function createExampleRowStepArgument(array $tokens)
    {
        return new ExampleTableNode($this, $tokens);
    }

    /**
     * Adds a row to the string.
     *
     * @param string|array $row Columns hash (column1 => value, column2 => value) or row string
     */
    public function addRow($row)
    {
        if (is_array($row)) {
            $this->rows[] = $row;
        } else {
            $row = preg_replace("/^\s*\||\|\s*$/", '', $row);

            $this->rows[] = array_map(function($item) {
                return preg_replace("/^\s*|\s*$/", '', $item);
            }, explode('|', $row));
        }
    }

    /**
     * Returns table rows.
     *
     * @return array
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Sets table rows.
     *
     * @param array $rows
     */
    public function setRows(array $rows)
    {
        $this->rows = $rows;
    }

    /**
     * Returns specific row in a table.
     *
     * @param integer $rowNum Row number
     *
     * @return array
     */
    public function getRow($rowNum)
    {
        return $this->rows[$rowNum];
    }

    /**
     * Converts row into delimited string.
     *
     * @param integer $rowNum Row number
     *
     * @return string
     */
    public function getRowAsString($rowNum)
    {
        $values = array();
        foreach ($this->getRow($rowNum) as $col => $value) {
            $values[] = $this->padRight(' '.$value.' ', $this->getMaxLengthForColumn($col) + 2);
        }

        return sprintf('|%s|', implode('|', $values));
    }

    /**
     * Returns table hash, formed by columns (ColumnHash).
     *
     * @return array
     */
    public function getHash()
    {
        $rows = $this->getRows();
        $keys = array_shift($rows);

        $hash = array();
        foreach ($rows as $row) {
            $hash[] = array_combine($keys, $row);
        }

        return $hash;
    }

    /**
     * Returns table hash, formed by rows (RowsHash).
     *
     * @return array
     */
    public function getRowsHash()
    {
        $hash = array();

        foreach ($this->getRows() as $row) {
            $hash[$row[0]] = $row[1];
        }

        return $hash;
    }

    /**
     * Converts table into string
     *
     * @return string
     */
    public function __toString()
    {
        $string = '';

        for ($i = 0; $i < count($this->getRows()); $i++) {
            if ('' !== $string) {
                $string .= "\n";
            }
            $string .= $this->getRowAsString($i);
        }

        return $string;
    }

    /**
     * Sets current node definition keyword.
     *
     * @param string $keyword Sets table keyword
     */
    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;
    }

    /**
     * Returns current node definition keyword.
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * Returns max length of specific column.
     *
     * @param integer $columnNum Column number
     *
     * @return integer
     */
    protected function getMaxLengthForColumn($columnNum)
    {
        $max = 0;

        foreach ($this->getRows() as $row) {
            if (($tmp = mb_strlen($row[$columnNum])) > $max) {
                $max = $tmp;
            }
        }

        return $max;
    }

    /**
     * Pads string right.
     *
     * @param string  $text   Text to pad
     * @param integer $length Lenght
     *
     * @return string
     */
    protected function padRight($text, $length)
    {
        while ($length > mb_strlen($text)) {
            $text = $text . ' ';
        }

        return $text;
    }
}
