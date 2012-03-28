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
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Gherkin writer.
 *
 * @author      Jean-François Lépine <dev@lepine.pro>
 */
class Writer
{

    private $keywords;
    private $indent;

    /**
     * Constructor
     * 
     * @param \Behat\Gherkin\Keywords\ArrayKeywords $keywords 
     * @param string $indent 
     */
    public function __construct(\Behat\Gherkin\Keywords\ArrayKeywords $keywords, $indent = '  ') {
        $this->keywords = $keywords;
        $this->indent = $indent;
    }

    /**
     * Write a feature
     * 
     * @see Behat\Gherkin\writeFeature()
     * @alias Behat\Gherkin\writeFeature()
     * @param Behat\Gherkin\Node\FeatureNode
     * @return string
     */
    public function write(FeatureNode $feature) {
        return $this->writeFeature($feature);
    }

    /**
     * Write background
     * 
     * @param Behat\Gherkin\Node\BackgroundNode
     * @return string
     */
    public function writeBackground(BackgroundNode $background) {
        $content = $this->writeKeyword($this->keywords->getBackgroundKeywords(), $background->getTitle());

        //
        // Steps
        foreach ($background->getSteps() as $step) {
            $content .=
                    PHP_EOL . $this->writeIndent(1)
                    . $this->writeStep($step);
        }

        return $content;
    }

    /**
     * Write comment
     * 
     * @param string $comment
     * @return string
     */
    public function writeComment($comment) {
        return $comment ? '# ' . $comment : '';
    }

    /**
     * Write feature
     * 
     * @param \Behat\Gherkin\Node\FeatureNode $feature
     * @return string
     */
    public function writeFeature(FeatureNode $feature) {
        $language = $feature->getLanguage();
        $this->keywords->setLanguage($language);

        //
        // Feature's infos
        $content = ''
                . $this->writeLanguage($language)
                . ($feature->getTags() ? PHP_EOL.$this->writeTags($feature->getTags(), 0) : '')
                . PHP_EOL . $this->writeKeyword($this->keywords->getFeatureKeywords(), $feature->getTitle(), 0)
                . PHP_EOL . $this->writeText($feature->getDescription(), 1);

        //
        // Background
        if ($feature->getBackground()) {
            $content .= $this->writeBackground($feature->getBackground());
        }

        //
        // scenarios
        $scenarios = $feature->getScenarios();
        foreach ($scenarios as $scenario) {
            $content .= PHP_EOL . $this->writeScenario($scenario);
        }
        return $content;
    }

    /**
     * Write keyword
     *
     * @param string $keyword
     * @param string $text
     * @param integer $indent
     * @return string 
     */
    public function writeKeyword($keyword, $text, $indent = 0) {
        if (preg_match('!(^.*)\|!', $keyword, $matches)) {
            $keyword = $matches[1];
        }
        return $this->writeIndent($indent) . $keyword . ':' 
                . ((strlen($text) > 0) ? ' ' .ltrim($this->writeText($text, $indent + 1)) : '')
            ;
    }

    /**
     * Write scenario
     * 
     * @param \Behat\Gherkin\Node\ScenarioNode $scenario
     * @return string
     */
    public function writeScenario(ScenarioNode $scenario) {
        $keyWordToUse = $scenario instanceof OutlineNode ? $this->keywords->getOutlineKeywords() : $this->keywords->getScenarioKeywords();

        //
        // Main content
        $content = ''
                . (sizeof($scenario->getTags()) > 0 ? PHP_EOL . $this->writeTags($scenario->getTags(), 1) : '')
                . PHP_EOL . $this->writeKeyword($keyWordToUse, $scenario->getTitle(), 1)
        ;

        //
        // Steps
        foreach ($scenario->getSteps() as $step) {
            $content .=
                    PHP_EOL . $this->writeIndent(2)
                    . $this->writeStep($step);
        }

        //
        // Examples
        if ($scenario instanceof OutlineNode) {
            $content .= ''
                    . PHP_EOL . PHP_EOL . $this->writeKeyword($this->keywords->getExamplesKeywords(), '', 1)
            ;
            $examples = $scenario->getExamples();
            $content .= $this->writeTableNode($examples, 2);
        }
        return $content;
    }

    /**
     * Write table node
     * 
     * @param \Behat\Gherkin\Node\TableNode $tableNode
     * @param integer $indent
     * @return string
     */
    public function writeTableNode(TableNode $tableNode, $indent = 0) {
        $len = sizeof($tableNode->getRows());
        $content = '';
        for ($i = 0; $i < $len; $i++) {
            $content .= PHP_EOL . $this->writeIndent($indent)
                    . $tableNode->getRowAsString($i);
        }
        return $content;
    }

    /**
     * Write indent
     * 
     * @param integer $indent
     * @return string
     */
    public function writeIndent($indent) {
        return str_repeat($this->indent, $indent);
    }

    /**
     * Write step
     * 
     * @param \Behat\Gherkin\Node\StepNode $step
     * @return string
     * @throws \Behat\Gherkin\Exception\Exception
     * 
     * @todo steps with outline arguments
     */
    public function writeStep(StepNode $step) {
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
                throw new \Behat\Gherkin\Exception\Exception("invalid type given : " . $step->getType());
        }

        // todo : steps with outline arguments
        return $this->writeText($kw . ' ' . $step->getText());
    }

    /**
     * Write text
     * 
     * @param string $text
     * @param integer $indent
     * @return string
     */
    public function writeText($text, $indent = 0) {
        return $this->writeIndent($indent)
                . implode(PHP_EOL . $this->writeIndent($indent)
                        , explode(PHP_EOL, $text)
        );
    }

    /**
     * Write tags
     * 
     * @param array $tags
     * @param integer $indent
     * @return string
     */
    public function writeTags(array $array, $indent = 0) {
        if (empty($array)) {
            return '';
        }
        return $this->writeIndent($indent) . '@' . ltrim(implode(' @', $array));
    }
    
    /**
     * Write language tag
     * 
     * @param string $language
     * @return string 
     */
    public function writeLanguage($language) {
        return $this->writeComment(
            $this->writeKeyword('language', $language)
        );
    }

}