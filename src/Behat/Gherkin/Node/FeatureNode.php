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
     * Initialize feature.
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
     * Set feature title.
     * 
     * @param   string  $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Return feature title.
     *
     * @return  string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set feature description (3-liner).
     *
     * @param   string  $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Return feature description.
     *
     * @return  string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set language of the feature.
     *
     * @param   string  $language   en|ru|pt-BR etc.
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Return language of the feature.
     *
     * @return  string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set feature background.
     *
     * @param   BackgroundNode  $background
     */
    public function setBackground(BackgroundNode $background)
    {
        $background->setFeature($this);
        $this->background = $background;
    }

    /**
     * Check if feature has background.
     *
     * @return  boolean
     */
    public function hasBackground()
    {
        return null !== $this->background;
    }

    /**
     * Return feature background.
     *
     * @return  BackgroundNode
     */
    public function getBackground()
    {
        return $this->background;
    }

    /**
     * Add Scenario or Outline to feature.
     *
     * @param   ScenarioNode    $scenario
     */
    public function addScenario(ScenarioNode $scenario)
    {
        $scenario->setFeature($this);
        $this->scenarios[] = $scenario;
    }

    /**
     * Set Scenarios or Outlines list of the feature.
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
     * Check that feature has scenarios.
     *
     * @return  boolean
     */
    public function hasScenarios()
    {
        return count($this->scenarios) > 0;
    }

    /**
     * Return added Scenarios or Outlines.
     *
     * @return  array               array of ScenariosNode & OutlineNode
     */
    public function getScenarios()
    {
        return $this->scenarios;
    }

    /**
     * Set feature tags.
     *
     * @param   array   $tags
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Add tag to feature.
     *
     * @param   string  $tag
     */
    public function addTag($tag)
    {
        $this->tags[] = $tag;
    }

    /**
     * Check if feature has tags.
     *
     * @return  boolean
     */
    public function hasTags()
    {
        return count($this->tags) > 0;
    }

    /**
     * Check if feature has tag.
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
     * Return feature tags.
     *
     * @return  array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set feature filename. 
     * 
     * @param   string  $path 
     */
    public function setFile($path)
    {
        $this->file = $path;
    }

    /**
     * Return feature filename.
     *
     * @return  string
     */
    public function getFile()
    {
        return $this->file;
    }
}
