<?php
declare(strict_types=1);

/*
 * This file is part of the Behat Gherkin.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Node;

/**
 * Represents Gherkin Rule.
 */
final class RuleNode implements KeywordNodeInterface, TaggedNodeInterface
{
    /**
     * @var string|null
     */
    private $title;
    /**
     * @var array
     */
    private $tags;
    /**
     * @var BackgroundNode|null
     */
    private $background;
    /**
     * @var array
     */
    private $scenarios;
    /**
     * @var string
     */
    private $keyword;
    /**
     * @var int
     */
    private $line;
    /**
     * @var string|null
     */
    private $description;

    public function __construct(
        ?string $title,
        ?string $description,
        array $tags,
        ?BackgroundNode $background,
        array $scenarios,
        string $keyword,
        int $line
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->tags = $tags;
        $this->background = $background;
        $this->scenarios = $scenarios;
        $this->keyword = $keyword;
        $this->line = $line;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getBackground(): ?BackgroundNode
    {
        return $this->background;
    }

    public function getScenarios(): array
    {
        return $this->scenarios;
    }

    public function getKeyword(): string
    {
        return $this->keyword;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getNodeType()
    {
        return 'Rule';
    }

    public function hasTag($tag)
    {
        return in_array($tag, $this->tags);
    }

    public function hasTags()
    {
        return 0 < count($this->tags);
    }
}
