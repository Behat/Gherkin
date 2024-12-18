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
    /**
     * @var string
     */
    private $title;
    /**
     * @var array
     */
    private $tags = [];
    /**
     * @var StepNode[]
     */
    private $steps = [];
    /**
     * @var string
     */
    private $keyword;
    /**
     * @var int
     */
    private $line;

    /**
     * Initializes scenario.
     *
     * @param string|null $title
     * @param StepNode[] $steps
     * @param string $keyword
     * @param int $line
     */
    public function __construct($title, array $tags, array $steps, $keyword, $line)
    {
        $this->title = $title;
        $this->tags = $tags;
        $this->steps = $steps;
        $this->keyword = $keyword;
        $this->line = $line;
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

    /**
     * Checks if scenario is tagged with tag.
     *
     * @param string $tag
     *
     * @return bool
     */
    public function hasTag($tag)
    {
        return in_array($tag, $this->getTags());
    }

    /**
     * Checks if scenario has tags (both inherited from feature and own).
     *
     * @return bool
     */
    public function hasTags()
    {
        return count($this->getTags()) > 0;
    }

    /**
     * Returns scenario tags (including inherited from feature).
     *
     * @return array
     */
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
