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
 * Feature Gherkin AST node.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class FeatureNode extends AbstractNode
{
    private $title;
    private $description;
    private $file;
    private $background;
    private $language   = 'en';
    private $scenarios  = array();
    private $tags       = array();
    private $frozen     = false;

    /**
     * Initializes feature.
     *
     * @param string  $title       Feature title
     * @param string  $description Feature description (3-liner)
     * @param string  $file        Feature filename
     * @param integer $line        Definition line
     */
    public function __construct($title = null, $description = null, $file = null, $line = 0)
    {
        parent::__construct($line);

        $this->title        = $title;
        $this->description  = $description;
        $this->file         = $file;
    }

    /**
     * Sets feature title.
     *
     * @param string $title Feature title
     *
     * @throws \LogicException if feature is frozen
     */
    public function setTitle($title)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change frozen feature title.');
        }

        $this->title = $title;
    }

    /**
     * Returns feature title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets feature description (narrative).
     *
     * @param string $description Feature description
     *
     * @throws \LogicException if feature is frozen
     */
    public function setDescription($description)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change frozen feature description.');
        }

        $this->description = $description;
    }

    /**
     * Returns feature description (narrative).
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets language of the feature.
     *
     * @param string $language Langauge name
     *
     * @throws \LogicException if feature is frozen
     */
    public function setLanguage($language)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change frozen feature language.');
        }

        $this->language = $language;
    }

    /**
     * Returns language of the feature.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Sets feature background.
     *
     * @param BackgroundNode $background Background instance
     *
     * @throws \LogicException if feature is frozen
     */
    public function setBackground(BackgroundNode $background)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change frozen feature background.');
        }

        $background->setFeature($this);
        $this->background = $background;
    }

    /**
     * Checks if feature has background.
     *
     * @return Boolean
     */
    public function hasBackground()
    {
        return null !== $this->background;
    }

    /**
     * Returns feature background.
     *
     * @return BackgroundNode
     */
    public function getBackground()
    {
        return $this->background;
    }

    /**
     * Adds scenario or outline to the feature.
     *
     * @param ScenarioNode $scenario Scenario instance
     *
     * @throws \LogicException if feature is frozen
     */
    public function addScenario(ScenarioNode $scenario)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change frozen feature scenarios.');
        }

        $scenario->setFeature($this);
        $this->scenarios[] = $scenario;
    }

    /**
     * Sets scenarios & outlines to the feature.
     *
     * @param array $scenarios Array of ScenariosNode's or OutlineNode's
     *
     * @throws \LogicException if feature is frozen
     */
    public function setScenarios(array $scenarios)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change frozen feature scenarios.');
        }

        $this->scenarios = array();

        foreach ($scenarios as $scenario) {
            $this->addScenario($scenario);
        }
    }

    /**
     * Checks that feature has scenarios.
     *
     * @return Boolean
     */
    public function hasScenarios()
    {
        return count($this->scenarios) > 0;
    }

    /**
     * Returns feature scenarios & outlines.
     *
     * @return array
     */
    public function getScenarios()
    {
        return $this->scenarios;
    }

    /**
     * Sets feature tags.
     *
     * @param array $tags Array of tags
     *
     * @throws \LogicException if feature is frozen
     */
    public function setTags(array $tags)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change frozen feature tags.');
        }

        $this->tags = $tags;
    }

    /**
     * Adds tag to the feature.
     *
     * @param string $tag Tag name
     *
     * @throws \LogicException if feature is frozen
     */
    public function addTag($tag)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change frozen feature tags.');
        }

        $this->tags[] = $tag;
    }

    /**
     * Checks if the feature has tags.
     *
     * @return Boolean
     */
    public function hasTags()
    {
        return count($this->getTags()) > 0;
    }

    /**
     * Checks if the feature has tag.
     *
     * @param string $tag Tag name
     *
     * @return Boolean
     */
    public function hasTag($tag)
    {
        return in_array($tag, $this->getTags());
    }

    /**
     * Returns feature tags.
     *
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
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

    /**
     * Sets feature filename.
     *
     * @param string $path Sets feature file
     *
     * @throws \LogicException if feature is frozen
     */
    public function setFile($path)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change frozen feature.');
        }

        $this->file = $path;
    }

    /**
     * Returns feature filename.
     *
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Freeze feature to changes.
     * Prevents feature modification in future
     */
    public function freeze()
    {
        $this->frozen = true;
    }

    /**
     * Checks whether feature has been frozen.
     *
     * @return Boolean
     */
    public function isFrozen()
    {
        return $this->frozen;
    }
}
