<?php

namespace Behat\Gherkin\Dumper;

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
 * @author Jean-François Lépine <dev@lepine.pro>
 */
class GherkinDumper
{
    private $keywords;
    private $indent;

    /**
     * Constructs dumper.
     *
     * @param KeywordsInterface $keywords Keywords container
     * @param string            $indent   Indentation
     */
    public function __construct(KeywordsInterface $keywords, $indent = '  ')
    {
        $this->keywords = $keywords;
        $this->indent   = $indent;
    }

    /**
     * Dumps a feature.
     *
     * @see dumpFeature()
     *
     * @param FeatureNode $feature Feature instance
     *
     * @return string
     */
    public function dump(FeatureNode $feature)
    {
        return $this->dumpFeature($feature);
    }

    /**
     * Dumps a background.
     *
     * @param BackgroundNode $background Background instance
     *
     * @return string
     */
    public function dumpBackground(BackgroundNode $background)
    {
        $content = $this->dumpKeyword(
            $this->keywords->getBackgroundKeywords(), $background->getTitle()
        );

        foreach ($background->getSteps() as $step) {
            $content .=
                PHP_EOL . $this->dumpIndent(1)
                . $this->dumpStep($step);
        }

        return $content;
    }

    /**
     * Dumps comment.
     *
     * @param string $comment Comment string
     *
     * @return string
     */
    public function dumpComment($comment)
    {
        return $comment ? '# ' . $comment : '';
    }

    /**
     * Dumps feature.
     *
     * @param FeatureNode $feature Feature instance
     *
     * @return string
     */
    public function dumpFeature(FeatureNode $feature)
    {
        $language = $feature->getLanguage();
        $this->keywords->setLanguage($language);

        $content = ''
            . $this->dumpLanguage($language)
            . ($feature->getTags() ? PHP_EOL . $this->dumpTags($feature->getTags(), 0) : '')
            . PHP_EOL . $this->dumpKeyword($this->keywords->getFeatureKeywords(), $feature->getTitle(), 0)
            . PHP_EOL . $this->dumpText($feature->getDescription(), 1);

        if ($feature->getBackground()) {
            $content .= $this->dumpBackground($feature->getBackground());
        }

        $scenarios = $feature->getScenarios();
        foreach ($scenarios as $scenario) {
            $content .= PHP_EOL . $this->dumpScenario($scenario);
        }

        return $content;
    }

    /**
     * Dumps keyword.
     *
     * @param string  $keyword Keyword string
     * @param string  $text    Text
     * @param integer $indent  Indentation
     *
     * @return string
     */
    public function dumpKeyword($keyword, $text, $indent = 0)
    {
        $keywords = explode('|', $keyword);
        $keyword = reset($keywords);

        return $this->dumpIndent($indent) . $keyword . ':'
            . ((strlen($text) > 0) ? ' ' . ltrim($this->dumpText($text, $indent + 1)) : '')
        ;
    }

    /**
     * Dumps scenario.
     *
     * @param ScenarioNode $scenario Scenario instance
     *
     * @return string
     */
    public function dumpScenario(ScenarioNode $scenario)
    {
        $keyWordToUse = $scenario instanceof OutlineNode ? $this->keywords->getOutlineKeywords() : $this->keywords->getScenarioKeywords();

        $content = ''
            . (sizeof($scenario->getTags()) > 0 ? PHP_EOL . $this->dumpTags($scenario->getTags(), 1) : '')
            . PHP_EOL . $this->dumpKeyword($keyWordToUse, $scenario->getTitle(), 1)
        ;

        foreach ($scenario->getSteps() as $step) {
            $content .=
                PHP_EOL . $this->dumpIndent(2)
                . $this->dumpStep($step);
        }

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
     * Dumps table node.
     *
     * @param  TableNode $tableNode Table node
     * @param  integer   $indent    Indentation
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
     * Dumps indentation.
     *
     * @param integer $indent Indentation
     *
     * @return string
     */
    public function dumpIndent($indent)
    {
        return str_repeat($this->indent, $indent);
    }

    /**
     * Dumps a step.
     *
     * @param StepNode $step Step node instance
     *
     * @return string
     *
     * @throws Exception if invalid step type providen
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
     * Dumps text.
     *
     * @param string  $text   Text to dump
     * @param integer $indent Indentation
     *
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
     * Dumps tags.
     *
     * @param array   $tags   Array of tags
     * @param integer $indent Indentation
     *
     * @return string
     */
    public function dumpTags(array $tags, $indent = 0)
    {
        if (empty($tags)) {
            return '';
        }

        return $this->dumpIndent($indent) . '@' . ltrim(implode(' @', $tags));
    }

    /**
     * Dumps language tag.
     *
     * @param string $language Language name
     *
     * @return string
     */
    public function dumpLanguage($language)
    {
        return $this->dumpComment($this->dumpKeyword('language', $language));
    }
}
