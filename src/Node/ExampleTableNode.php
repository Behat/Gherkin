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
 *
 * @final since 4.15.0
 */
class ExampleTableNode extends TableNode implements TaggedNodeInterface, DescribableNodeInterface
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
        private readonly ?string $name = null,
        private readonly ?string $description = null,
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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

    /**
     * @param array<int, list<string>> $table Table in form of [$rowLineNumber => [$val1, $val2, $val3]]
     */
    public function withTable(array $table): self
    {
        return new self(
            $table,
            $this->keyword,
            $this->tags,
            $this->name,
            $this->description,
        );
    }
}
