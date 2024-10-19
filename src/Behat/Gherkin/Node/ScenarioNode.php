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
 * Represents Gherkin Scenario.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ScenarioNode implements ScenarioInterface
{
    /**
     * @var null|string
     */
    private $title;
    /**
     * @var array
     */
    private $tags = array();
    /**
     * @var StepNode[]
     */
    private $steps = array();
    /**
     * @var string
     */
    private $keyword;
    /**
     * @var integer
     */
    private $line;
    /**
     * @var null|string
     */
    private $description;

    /**
     * Initializes scenario.
     *
     * @param null|string $title
     * @param array       $tags
     * @param StepNode[]  $steps
     * @param string      $keyword
     * @param integer     $line
     * @param null|string $description
     */
    public function __construct($title, array $tags, array $steps, $keyword, $line, ?string $description = null)
    {
        $this->title = $title;
        $this->tags = $tags;
        $this->steps = $steps;
        $this->keyword = $keyword;
        $this->line = $line;
        $this->description = $description;
    }

    /**
     * Returns node type string
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
     * @return null|string
     */
    public function getTitle()
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
        return 0 < count($this->getTags());
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
        return 0 < count($this->steps);
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
     * @return integer
     */
    public function getLine()
    {
        return $this->line;
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
