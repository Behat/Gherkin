<?php

namespace Behat\Gherkin\Node;

/*
 * This file is part of the Behat Gherkin.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Behat\Gherkin\Exception\NodeException;

/**
 * Represents Gherkin Outline.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class OutlineNode implements ScenarioInterface
{
    /**
     * @var string
     */
    private $title;
    /**
     * @var array
     */
    private $tags;
    /**
     * @var StepNode[]
     */
    private $steps;
    /**
     * @var ExampleTableNode
     */
    private $table;
    /**
     * @var string
     */
    private $keyword;
    /**
     * @var integer
     */
    private $line;
    /**
     * @var FeatureNode
     */
    private $feature;
    /**
     * @var null|ExampleNode[]
     */
    private $examples;

    /**
     * Initializes outline.
     *
     * @param null|string      $title
     * @param array            $tags
     * @param StepNode[]       $steps
     * @param ExampleTableNode $table
     * @param string           $keyword
     * @param integer          $line
     */
    public function __construct(
        $title,
        array $tags,
        array $steps,
        ExampleTableNode $table,
        $keyword,
        $line
    )
    {
        $this->title = $title;
        $this->tags = $tags;
        $this->steps = $steps;
        $this->table = $table;
        $this->keyword = $keyword;
        $this->line = $line;

        foreach ($this->steps as $step) {
            $step->setContainer($this);
        }

        if ($table) {
            $table->setSubject($this);
        }
    }

    /**
     * Returns node type string
     *
     * @return string
     */
    public function getNodeType()
    {
        return 'Outline';
    }

    /**
     * Returns outline title.
     *
     * @return null|string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Checks if outline is tagged with tag.
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
     * Checks if outline has tags (both inherited from feature and own).
     *
     * @return Boolean
     */
    public function hasTags()
    {
        return 0 < count($this->getTags());
    }

    /**
     * Returns outline tags (including inherited from feature).
     *
     * @return array
     *
     * @throws NodeException If feature is not set
     */
    public function getTags()
    {
        if (null === $this->feature) {
            throw new NodeException('Can not identify tags of outline that is not bound to feature.');
        }

        return array_merge($this->feature->getTags(), $this->tags);
    }

    /**
     * Checks if scenario has own tags (excluding ones inherited from feature).
     *
     * @return Boolean
     */
    public function hasOwnTags()
    {
        return 0 < count($this->tags);
    }

    /**
     * Returns outline own tags (excluding ones inherited from feature).
     *
     * @return array
     */
    public function getOwnTags()
    {
        return $this->tags;
    }

    /**
     * Checks if outline has steps.
     *
     * @return Boolean
     */
    public function hasSteps()
    {
        return 0 < count($this->steps);
    }

    /**
     * Returns outline steps.
     *
     * @return StepNode[]
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * Checks if outline has examples.
     *
     * @return Boolean
     */
    public function hasExamples()
    {
        return 0 < count($this->table->getColumnsHash());
    }

    /**
     * Returns examples table.
     *
     * @return ExampleTableNode
     */
    public function getExampleTable()
    {
        return $this->table;
    }

    /**
     * Returns list of examples for the outline.
     *
     * @return ExampleNode[]
     */
    public function getExamples()
    {
        return $this->examples = $this->examples ? : $this->createExamples();
    }

    /**
     * Returns outline feature.
     *
     * @return FeatureNode
     */
    public function getFeature()
    {
        return $this->feature;
    }

    /**
     * Sets outline feature.
     *
     * @param FeatureNode $feature
     */
    public function setFeature(FeatureNode $feature)
    {
        $this->feature = $feature;
    }

    /**
     * Returns outline keyword.
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * Returns scenario index (scenario ordinal number in feature).
     *
     * @return integer
     *
     * @throws NodeException If feature is not set
     */
    public function getIndex()
    {
        if (null === $this->feature) {
            throw new NodeException('Can not identify index of outline that is not bound to feature.');
        }

        return array_search($this, $this->feature->getScenarios());
    }

    /**
     * Returns feature language.
     *
     * @return string
     *
     * @throws NodeException If feature is not set
     */
    public function getLanguage()
    {
        if (null === $this->feature) {
            throw new NodeException('Can not identify language of outline that is not bound to feature.');
        }

        return $this->feature->getLanguage();
    }

    /**
     * Returns feature file.
     *
     * @return null|string
     *
     * @throws NodeException If feature is not set
     */
    public function getFile()
    {
        if (null === $this->feature) {
            throw new NodeException('Can not identify file of outline that is not bound to feature.');
        }

        return $this->feature->getFile();
    }

    /**
     * Returns outline declaration line number.
     *
     * @return integer
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * Creates examples for this outline using examples table.
     *
     * @return ExampleNode[]
     */
    protected function createExamples()
    {
        $examples = array();
        foreach ($this->table->getColumnsHash() as $rowNum => $row) {
            $examples[] = new ExampleNode($this, $row, $this->table->getRowLine($rowNum + 1));
        }

        return $examples;
    }
}
