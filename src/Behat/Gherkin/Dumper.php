<?php

namespace Behat\Gherkin;

use Behat\Gherkin\Exception\Exception,
    Behat\Gherkin\Keywords\KeywordsInterface,
    Behat\Gherkin\Node\FeatureNode,
    Behat\Gherkin\Node\BackgroundNode,
    Behat\Gherkin\Node\ScenarioNode,
    Behat\Gherkin\Node\TableNode,
    Behat\Gherkin\Node\StepNode,
    Behat\Gherkin\Node\OutlineNode;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2012 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Gherkin Dumper.
 *
 * @author      Jean-François Lépine <dev@lepine.pro>
 */
class Dumper
{

    private $keywords;
    private $indent;

    /**
     * Constructor
     * 
     * @param \Behat\Gherkin\Keywords\KeywordsInterface $keywords 
     * @param string $indent 
     */
    public function __construct(KeywordsInterface $keywords, $indent = '  ')
    {
        $this->keywords = $keywords;
        $this->indent = $indent;
    }

    /**
     * Dump a feature
     * 
     * @see Behat\Gherkin\dumpFeature()
     * @param Behat\Gherkin\Node\FeatureNode
     * @return string
     */
    public function dump(FeatureNode $feature)
    {
        return $this->dumpFeature($feature);
    }

    /**
     * Dump background
     * 
     * @param Behat\Gherkin\Node\BackgroundNode
     * @return string
     */
    public function dumpBackground(BackgroundNode $background)
    {
        $content = $this->dumpKeyword($this->keywords->getBackgroundKeywords(), $background->getTitle());

        //
        // Steps
        foreach ($background->getSteps() as $step) {
            $content .=
                PHP_EOL . $this->dumpIndent(1)
                . $this->dumpStep($step);
        }

        return $content;
    }

    /**
     * Dump comment
     * 
     * @param string $comment
     * @return string
     */
    public function dumpComment($comment)
    {
        return $comment ? '# ' . $comment : '';
    }

    /**
     * Dump feature
     * 
     * @param \Behat\Gherkin\Node\FeatureNode $feature
     * @return string
     */
    public function dumpFeature(FeatureNode $feature)
    {
        $language = $feature->getLanguage();
        $this->keywords->setLanguage($language);

        //
        // Feature's infos
        $content = ''
            . $this->dumpLanguage($language)
            . ($feature->getTags() ? PHP_EOL . $this->dumpTags($feature->getTags(), 0) : '')
            . PHP_EOL . $this->dumpKeyword($this->keywords->getFeatureKeywords(), $feature->getTitle(), 0)
            . PHP_EOL . $this->dumpText($feature->getDescription(), 1);

        //
        // Background
        if ($feature->getBackground()) {
            $content .= $this->dumpBackground($feature->getBackground());
        }

        //
        // scenarios
        $scenarios = $feature->getScenarios();
        foreach ($scenarios as $scenario) {
            $content .= PHP_EOL . $this->dumpScenario($scenario);
        }
        return $content;
    }

    /**
     * Dump keyword
     *
     * @param string $keyword
     * @param string $text
     * @param integer $indent
     * @return string 
     */
    public function dumpKeyword($keyword, $text, $indent = 0)
    {
        if (preg_match('!(^.*)\|!', $keyword, $matches)) {
            $keyword = $matches[1];
        }
        return $this->dumpIndent($indent) . $keyword . ':'
            . ((strlen($text) > 0) ? ' ' . ltrim($this->dumpText($text, $indent + 1)) : '')
        ;
    }

    /**
     * Dump scenario
     * 
     * @param \Behat\Gherkin\Node\ScenarioNode $scenario
     * @return string
     */
    public function dumpScenario(ScenarioNode $scenario)
    {
        $keyWordToUse = $scenario instanceof OutlineNode ? $this->keywords->getOutlineKeywords() : $this->keywords->getScenarioKeywords();

        //
        // Main content
        $content = ''
            . (sizeof($scenario->getTags()) > 0 ? PHP_EOL . $this->dumpTags($scenario->getTags(), 1) : '')
            . PHP_EOL . $this->dumpKeyword($keyWordToUse, $scenario->getTitle(), 1)
        ;

        //
        // Steps
        foreach ($scenario->getSteps() as $step) {
            $content .=
                PHP_EOL . $this->dumpIndent(2)
                . $this->dumpStep($step);
        }

        //
        // Examples
        if ($scenario instanceof OutlineNode) {
            $content .= ''
                . PHP_EOL . PHP_EOL . $this->dumpKeyword($this->keywords->getExamplesKeywords(), '', 1)
            ;
            $examples = $scenario->getExamples();
            $content .= $this->dumpTableNode($examples, 2);
        }
        return $content;
    }

    /**
     * Dump table node
     * 
     * @param \Behat\Gherkin\Node\TableNode $tableNode
     * @param integer $indent
     * @return string
     */
    public function dumpTableNode(TableNode $tableNode, $indent = 0)
    {
        $len = sizeof($tableNode->getRows());
        $content = '';
        for ($i = 0; $i < $len; $i++) {
            $content .= PHP_EOL . $this->dumpIndent($indent)
                . $tableNode->getRowAsString($i);
        }
        return $content;
    }

    /**
     * Dump indent
     * 
     * @param integer $indent
     * @return string
     */
    public function dumpIndent($indent)
    {
        return str_repeat($this->indent, $indent);
    }

    /**
     * Dump step
     * 
     * @param \Behat\Gherkin\Node\StepNode $step
     * @return string
     * @throws \Behat\Gherkin\Exception\Exception
     */
    public function dumpStep(StepNode $step)
    {
        switch ($step->getType()) {
            case 'Given':
                $kw = $this->keywords->getGivenKeywords();
                break;
            case 'When':
                $kw = $this->keywords->getWhenKeywords();
                break;
            case 'Then':
                $kw = $this->keywords->getThenKeywords();
                break;
            case 'But':
                $kw = $this->keywords->getButKeywords();
                break;
            case 'And':
                $kw = $this->keywords->getAndKeywords();
                break;
            default:
                throw new Exception("invalid type given : " . $step->getType());
        }

        return $this->dumpText($kw . ' ' . $step->getText());
    }

    /**
     * Dump text
     * 
     * @param string $text
     * @param integer $indent
     * @return string
     */
    public function dumpText($text, $indent = 0)
    {
        return $this->dumpIndent($indent) . implode(
            PHP_EOL . $this->dumpIndent($indent),
            explode(PHP_EOL, $text)
        );
    }

    /**
     * Dump tags
     * 
     * @param array $tags
     * @param integer $indent
     * @return string
     */
    public function dumpTags(array $array, $indent = 0)
    {
        if (empty($array)) {
            return '';
        }
        return $this->dumpIndent($indent) . '@' . ltrim(implode(' @', $array));
    }

    /**
     * Dump language tag
     * 
     * @param string $language
     * @return string 
     */
    public function dumpLanguage($language)
    {
        return $this->dumpComment($this->dumpKeyword('language', $language));
    }

}