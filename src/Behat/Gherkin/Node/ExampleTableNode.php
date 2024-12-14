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
 * Represents Gherkin Outline Example Table.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ExampleTableNode extends TableNode
{
    /**
     * @var string[]
     */
    private $tags;

    /**
     * @var string
     */
    private $keyword;

    /**
     * @var null|string
     */
    private $name;

    /**
     * @var null|string
     */
    private $description;

    /**
     * Initializes example table.
     *
     * @param array $table Table in form of [$rowLineNumber => [$val1, $val2, $val3]]
     * @param string $keyword
     * @param string[] $tags
     * @param null|string $name
     * @param null|string $description
     */
    public function __construct(array $table, $keyword, array $tags = [], ?string $name = null, ?string $description = null)
    {
        $this->keyword = $keyword;
        $this->tags = $tags;
        $this->name = $name;
        $this->description = $description;

        parent::__construct($table);
    }

    /**
     * Returns node type string
     *
     * @return string
     */
    public function getNodeType()
    {
        return 'ExampleTable';
    }

    /**
     * Returns attached tags
     * @return \string[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Returns example table keyword.
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * Returns the name.
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the description.
     *
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
