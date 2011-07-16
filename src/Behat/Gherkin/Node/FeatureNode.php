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
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
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

    /**
     * Initializes feature.
     *
     * @param   string  $title          feature title
     * @param   string  $description    feature description (3-liner)
     * @param   string  $file           feature filename
     * @param   integer $line           definition line
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
     * @param   string  $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns feature title.
     *
     * @return  string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets feature description (3-liner).
     *
     * @param   string  $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Returns feature description (3-liner).
     *
     * @return  string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets language of the feature.
     *
     * @param   string  $language   en|ru|pt-BR etc.
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Returns language of the feature.
     *
     * @return  string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Sets feature background.
     *
     * @param   Behat\Gherkin\Node\BackgroundNode  $background
     */
    public function setBackground(BackgroundNode $background)
    {
        $background->setFeature($this);
        $this->background = $background;
    }

    /**
     * Checks if feature has background.
     *
     * @return  boolean
     */
    public function hasBackground()
    {
        return null !== $this->background;
    }

    /**
     * Returns feature background.
     *
     * @return  BackgroundNode
     */
    public function getBackground()
    {
        return $this->background;
    }

    /**
     * Adds scenario or outline to the feature.
     *
     * @param   Behat\Gherkin\Node\ScenarioNode $scenario
     */
    public function addScenario(ScenarioNode $scenario)
    {
        $scenario->setFeature($this);
        $this->scenarios[] = $scenario;
    }

    /**
     * Sets scenarios & outlines to the feature.
     *
     * @param   array   $scenarios  array of ScenariosNode & OutlineNode
     */
    public function setScenarios(array $scenarios)
    {
        $this->scenarios = array();

        foreach ($scenarios as $scenario) {
            $this->addScenario($scenario);
        }
    }

    /**
     * Checks that feature has scenarios.
     *
     * @return  boolean
     */
    public function hasScenarios()
    {
        return count($this->scenarios) > 0;
    }

    /**
     * Returns feature scenarios & outlines.
     *
     * @return  array               array of ScenariosNode & OutlineNode
     */
    public function getScenarios()
    {
        return $this->scenarios;
    }

    /**
     * Sets feature tags.
     *
     * @param   array   $tags
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Adds tag to the feature.
     *
     * @param   string  $tag
     */
    public function addTag($tag)
    {
        $this->tags[] = $tag;
    }

    /**
     * Checks if the feature has tags.
     *
     * @return  boolean
     */
    public function hasTags()
    {
        return count($this->getTags()) > 0;
    }

    /**
     * Checks if the feature has tag.
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
     * Returns feature tags.
     *
     * @return  array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Returns only own tags (without inherited).
     *
     * @return  array
     */
    public function getOwnTags()
    {
        return $this->tags;
    }

    /**
     * Sets feature filename.
     *
     * @param   string  $path
     */
    public function setFile($path)
    {
        $this->file = $path;
    }

    /**
     * Returns feature filename.
     *
     * @return  string
     */
    public function getFile()
    {
        return $this->file;
    }
}
