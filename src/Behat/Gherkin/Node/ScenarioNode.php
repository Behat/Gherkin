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
     * Initialize scenario.
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
     * Set scenario title.
     * 
     * @param   string  $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Return scenario title.
     *
     * @return  string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set scenario tags.
     *
     * @param   array   $tags
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Add tag to scenario.
     *
     * @param   string  $tag
     */
    public function addTag($tag)
    {
        $this->tags[] = $tag;
    }

    /**
     * Check if scenario has tags.
     *
     * @return  boolean
     */
    public function hasTags()
    {
        return count($this->tags) > 0;
    }

    /**
     * Check if scenario has tag.
     *
     * @param   string  $tag
     * 
     * @return  boolean
     */
    public function hasTag($tag)
    {
        return in_array($tag, $this->tags);
    }

    /**
     * Return scenario tags.
     *
     * @return  array
     */
    public function getTags()
    {
        return $this->tags;
    }
}
