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
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ScenarioNode extends AbstractScenarioNode
{
    private $tags = array();

    /**
     * Sets scenario tags.
     *
     * @param array $tags Array of tag names
     *
     * @throws \LogicException if feature is frozen
     */
    public function setTags(array $tags)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change scenario tags in frozen feature.');
        }

        $this->tags = $tags;
    }

    /**
     * Adds tag to scenario.
     *
     * @param string $tag Tag name
     *
     * @throws \LogicException if feature is frozen
     */
    public function addTag($tag)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change scenario tags in frozen feature.');
        }

        $this->tags[] = $tag;
    }

    /**
     * Checks if scenario has tags.
     *
     * @return Boolean
     */
    public function hasTags()
    {
        return count($this->getTags()) > 0;
    }

    /**
     * Checks if scenario has tag.
     *
     * @param string $tag
     *
     * @return Boolean
     */
    public function hasTag($tag)
    {
        return in_array($tag, $this->getTags());
    }

    /**
     * Returns scenario tags.
     *
     * @return array
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
     * @return array
     */
    public function getOwnTags()
    {
        return $this->tags;
    }
}
