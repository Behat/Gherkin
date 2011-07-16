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
 * Scenario Gherkin AST node.
 *
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ScenarioNode extends AbstractScenarioNode
{
    private $title;
    private $tags = array();

    /**
     * Initializes scenario.
     *
     * @param   string  $title  scenario title
     * @param   integer $line   definition line
     */
    public function __construct($title = null, $line = 0)
    {
        parent::__construct($line);

        $this->title = $title;
    }

    /**
     * Sets scenario title.
     *
     * @param   string  $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns scenario title.
     *
     * @return  string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets scenario tags.
     *
     * @param   array   $tags
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Adds tag to scenario.
     *
     * @param   string  $tag
     */
    public function addTag($tag)
    {
        $this->tags[] = $tag;
    }

    /**
     * Checks if scenario has tags.
     *
     * @return  boolean
     */
    public function hasTags()
    {
        return count($this->getTags()) > 0;
    }

    /**
     * Checks if scenario has tag.
     *
     * @param   string  $tag
     *
     * @return  boolean
     */
    public function hasTag($tag)
    {
        return in_array($tag, $this->getTags());
    }

    /**
     * Returns scenario tags.
     *
     * @return  array
     */
    public function getTags()
    {
        $tags = $this->tags;

        if ($feature = $this->getFeature()) {
            $tags = array_merge($tags, $feature->getTags());
        }

        return $tags;
    }

    /**
     * Returns only own tags (without inherited ones).
     *
     * @return  array
     */
    public function getOwnTags()
    {
        return $this->tags;
    }
}
