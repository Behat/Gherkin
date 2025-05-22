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
 * Represents Gherkin Scenario.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ScenarioNode implements ScenarioInterface, NamedScenarioInterface
{
    use TaggedNodeTrait;

    /**
     * @param StepNode[] $steps
     * @param list<string> $tags
     */
    public function __construct(
        private readonly ?string $title,
        private readonly array $tags,
        private readonly array $steps,
        private readonly string $keyword,
        private readonly int $line,
    ) {
    }

    /**
     * Returns node type string.
     *
     * @return string
     */
    public function getNodeType()
    {
        return 'Scenario';
    }

    /**
     * Returns scenario title.
     *
     * @return string|null
     *
     * @deprecated you should use {@see self::getName()} instead as this method will be removed in the next
     *             major version
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function getName(): ?string
    {
        return $this->title;
    }

    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Checks if scenario has steps.
     *
     * @return bool
     */
    public function hasSteps()
    {
        return count($this->steps) > 0;
    }

    /**
     * Returns scenario steps.
     *
     * @return StepNode[]
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * Returns scenario keyword.
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * Returns scenario declaration line number.
     *
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }
}
