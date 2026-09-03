<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Node;

class RuleNode implements KeywordNodeInterface, DescribableNodeInterface, TaggedNodeInterface
{
    use TaggedNodeTrait;

    /**
     * @param list<string> $tags
     * @param list<BackgroundNode|ScenarioInterface> $children
     */
    public function __construct(
        private readonly ?string $title,
        private readonly ?string $description,
        private readonly array $tags,
        private readonly array $children,
        private readonly string $keyword,
        private readonly int $line,
    ) {
    }

    /**
     * @internal
     *
     * @param list<BackgroundNode|ScenarioInterface> $children
     */
    final public function withChildren(array $children): self
    {
        return new self(
            title: $this->title,
            description: $this->description,
            tags: $this->tags,
            children: $children,
            keyword: $this->keyword,
            line: $this->line,
        );
    }

    public function getNodeType(): string
    {
        return 'Rule';
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getKeyword(): string
    {
        return $this->keyword;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * @return list<BackgroundNode|ScenarioInterface>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @phpstan-assert-if-true BackgroundNode $this->getBackground()
     */
    public function hasBackground(): bool
    {
        return ($this->children[0] ?? null) instanceof BackgroundNode;
    }

    public function getBackground(): ?BackgroundNode
    {
        if (($this->children[0] ?? null) instanceof BackgroundNode) {
            return $this->children[0];
        }

        return null;
    }
}
