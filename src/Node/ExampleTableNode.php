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
 * Represents Gherkin Outline Example Table.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ExampleTableNode extends TableNode implements TaggedNodeInterface
{
    use TaggedNodeTrait;

    /**
     * @param array<int, list<string>> $table Table in form of [$rowLineNumber => [$val1, $val2, $val3]]
     * @param list<string> $tags
     */
    public function __construct(
        array $table,
        private readonly string $keyword,
        private readonly array $tags = [],
    ) {
        parent::__construct($table);
    }

    /**
     * Returns node type string.
     *
     * @return string
     */
    public function getNodeType()
    {
        return 'ExampleTable';
    }

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
}
