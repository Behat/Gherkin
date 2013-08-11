<?php

namespace Behat\Gherkin;

use Behat\Gherkin\Exception\LexerException,
    Behat\Gherkin\Keywords\KeywordsInterface;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Gherkin lexer.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Lexer
{
    private $lines;
    private $line;
    private $lineNumber;
    private $eos;
    private $keywords;
    private $keywordsCache   = array();
    private $deferredObjects = array();
    private $stash           = array();
    private $inPyString      = false;
    private $pyStringSwallow = 0;
    private $featureStarted          = false;
    private $allowMultilineArguments = false;
    private $allowSteps              = false;

    /**
     * Initializes lexer.
     *
     * @param KeywordsInterface $keywords Keywords holder
     */
    public function __construct(KeywordsInterface $keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * Sets lexer input.
     *
     * @param string $input Input string
     */
    public function setInput($input)
    {
        // try to detect unsupported encoding
        if ('UTF-8' !== mb_detect_encoding($input, 'UTF-8', true)) {
            throw new LexerException('Feature file is not in UTF8 encoding');
        }

        $input = strtr($input, array("\r\n" => "\n", "\r" => "\n"));

        $this->lines      = explode("\n", $input);
        $this->line       = array_shift($this->lines);
        $this->lineNumber = 1;
        $this->eos        = false;

        $this->deferredObjects  = array();
        $this->stash            = array();
        $this->inPyString       = false;
        $this->pyStringSwallow  = 0;

        $this->featureStarted          = false;
        $this->allowMultilineArguments = false;
        $this->allowSteps              = false;
    }

    /**
     * Sets keywords language.
     *
     * @param string $language Language name
     */
    public function setLanguage($language)
    {
        $this->keywords->setLanguage($language);
        $this->keywordsCache = array();
    }

    /**
     * Returns next token or previously stashed one.
     *
     * @return stdClass
     */
    public function getAdvancedToken()
    {
        return $this->getStashedToken() ?: $this->getNextToken();
    }

    /**
     * Defers token.
     *
     * @param stdClass $token Token to defer
     */
    public function deferToken(\stdClass $token)
    {
        $token->defered = true;
        $this->deferredObjects[] = $token;
    }

    /**
     * Predicts for number of tokens.
     *
     * @param integer $number Number of tokens to predict
     *
     * @return stdClass
     */
    public function predictToken($number = 1)
    {
        $fetch = $number - count($this->stash);

        while ($fetch-- > 0) {
            $this->stash[] = $this->getNextToken();
        }

        return $this->stash[--$number];
    }

    /**
     * Constructs token with specified parameters.
     *
     * @param string $type  Token type
     * @param string $value Token value
     *
     * @return stdClass
     */
    public function takeToken($type, $value = null)
    {
        return (Object) array(
            'type'      => $type,
            'line'      => $this->lineNumber,
            'value'     => $value ?: null,
            'defered'   => false
        );
    }

    /**
     * Consumes line from input & increments line counter.
     */
    protected function consumeLine()
    {
        ++$this->lineNumber;

        if (!count($this->lines)) {
            $this->eos = true;

            return false;
        }

        $this->line = array_shift($this->lines);
    }

    /**
     * Returns stashed token or false if hasn't.
     *
     * @return stdClass|Boolean
     */
    protected function getStashedToken()
    {
        return count($this->stash) ? array_shift($this->stash) : null;
    }

    /**
     * Returns deferred token or false if hasn't.
     *
     * @return stdClass|Boolean
     */
    protected function getDeferredToken()
    {
        return count($this->deferredObjects) ? array_shift($this->deferredObjects) : null;
    }

    /**
     * Returns next token from input.
     *
     * @return stdClass
     */
    protected function getNextToken()
    {
        return $this->getDeferredToken()
            ?: $this->scanEOS()
            ?: $this->scanLanguage()
            ?: $this->scanComment()
            ?: $this->scanPyStringOperator()
            ?: $this->scanPyStringContent()
            ?: $this->scanStep()
            ?: $this->scanScenario()
            ?: $this->scanBackground()
            ?: $this->scanOutline()
            ?: $this->scanExamples()
            ?: $this->scanFeature()
            ?: $this->scanTags()
            ?: $this->scanTableRow()
            ?: $this->scanNewline()
            ?: $this->scanText();
    }

    /**
     * Scans for token with specified regex.
     *
     * @param string $regex Regular expression
     * @param string $type  Expected token type
     *
     * @return stdClass|null
     */
    protected function scanInput($regex, $type)
    {
        $matches = array();

        if (preg_match($regex, $this->line, $matches)) {
            $token = $this->takeToken($type, $matches[1]);

            $this->consumeLine();

            return $token;
        }
    }

    /**
     * Scans for token with specified keywords.
     *
     * @param string $keywords Keywords (splitted with |)
     * @param string $type     Expected token type
     *
     * @return stdClass|null
     */
    protected function scanInputForKeywords($keywords, $type)
    {
        $matches = array();

        if (preg_match('/^(\s*)('.$keywords.'):\s*(.*)/u', $this->line, $matches)) {
            $token = $this->takeToken($type, $matches[3]);
            $token->keyword = $matches[2];
            $token->indent  = mb_strlen($matches[1], 'utf8');

            $this->consumeLine();

            // turn off language searching
            if ('Feature' === $type) {
                $this->featureStarted = true;
            }

            // turn off PyString and Table searching
            if (in_array($type, array('Feature', 'Scenario', 'Outline'))) {
                $this->allowMultilineArguments = false;
            } elseif ('Examples' === $type) {
                $this->allowMultilineArguments = true;
            }

            // turn on steps searching
            if (in_array($type, array('Scenario', 'Background', 'Outline'))) {
                $this->allowSteps = true;
            }

            return $token;
        }
    }

    /**
     * Scans EOS from input & returns it if found.
     *
     * @return stdClass|null
     */
    protected function scanEOS()
    {
        if (!$this->eos) {
            return;
        }

        return $this->takeToken('EOS');
    }

    /**
     * Returns keywords for provided type.
     *
     * @param string $type Keyword type
     *
     * @return string
     */
    protected function getKeywords($type)
    {
        if (!isset($this->keywordsCache[$type])) {
            $getter   = 'get' . $type . 'Keywords';
            $keywords = $this->keywords->$getter();

            if ('Step' === $type) {
                $paded = array();
                foreach (explode('|', $keywords) as $keyword) {
                    $paded[] = false !== mb_strpos($keyword, '<', 0, 'utf8')
                        ? mb_substr($keyword, 0, -1, 'utf8').'\s*'
                        : $keyword.'\s+';
                }

                $keywords = implode('|', $paded);
            }

            $this->keywordsCache[$type] = $keywords;
        }

        return $this->keywordsCache[$type];
    }

    /**
     * Scans Feature from input & returns it if found.
     *
     * @return stdClass|null
     */
    protected function scanFeature()
    {
        return $this->scanInputForKeywords($this->getKeywords('Feature'), 'Feature');
    }

    /**
     * Scans Background from input & returns it if found.
     *
     * @return stdClass|null
     */
    protected function scanBackground()
    {
        return $this->scanInputForKeywords($this->getKeywords('Background'), 'Background');
    }

    /**
     * Scans Scenario from input & returns it if found.
     *
     * @return stdClass|null
     */
    protected function scanScenario()
    {
        return $this->scanInputForKeywords($this->getKeywords('Scenario'), 'Scenario');
    }

    /**
     * Scans Scenario Outline from input & returns it if found.
     *
     * @return stdClass|null
     */
    protected function scanOutline()
    {
        return $this->scanInputForKeywords($this->getKeywords('Outline'), 'Outline');
    }

    /**
     * Scans Scenario Outline Examples from input & returns it if found.
     *
     * @return stdClass|null
     */
    protected function scanExamples()
    {
        return $this->scanInputForKeywords($this->getKeywords('Examples'), 'Examples');
    }

    /**
     * Scans Step from input & returns it if found.
     *
     * @return stdClass|null
     */
    protected function scanStep()
    {
        if (!$this->allowSteps) {
            return;
        }

        $matches = array();

        $keywords = $this->getKeywords('Step');
        if (preg_match('/^\s*('.$keywords.')([^\s].+)/u', $this->line, $matches)) {
            $token = $this->takeToken('Step', trim($matches[1]));
            $token->text = $matches[2];

            $this->consumeLine();
            $this->allowMultilineArguments = true;

            return $token;
        }
    }

    /**
     * Scans PyString from input & returns it if found.
     *
     * @return stdClass|null
     */
    protected function scanPyStringOperator()
    {
        if (!$this->allowMultilineArguments) {
            return;
        }

        $matches = array();

        if (false !== ($pos = mb_strpos($this->line, '"""', 0, 'utf8'))) {
            $this->inPyString =! $this->inPyString;
            $token = $this->takeToken('PyStringOperator');
            $this->pyStringSwallow = $pos;

            $this->consumeLine();

            return $token;
        }
    }

    /**
     * Scans PyString content.
     *
     * @return stdClass|null
     */
    protected function scanPyStringContent()
    {
        if ($this->inPyString) {
            $token = $this->scanText();

            // swallow trailing spaces
            $token->value = preg_replace('/^\s{0,'.$this->pyStringSwallow.'}/', '', $token->value);

            return $token;
        }
    }

    /**
     * Scans Table Row from input & returns it if found.
     *
     * @return stdClass|null
     */
    protected function scanTableRow()
    {
        if (!$this->allowMultilineArguments) {
            return;
        }

        $line = trim($this->line);

        if (isset($line[0]) && '|' === $line[0]) {
            $token = $this->takeToken('TableRow');

            $line = mb_substr($line, 1, mb_strlen($line, 'utf8') - 2, 'utf8');
            $columns = array_map(function($column) {
                return trim(str_replace('\\|', '|', $column));
            }, preg_split('/(?<!\\\)\|/', $line));
            $token->columns = $columns;

            $this->consumeLine();

            return $token;
        }
    }

    /**
     * Scans Tags from input & returns it if found.
     *
     * @return stdClass|null
     */
    protected function scanTags()
    {
        $line = trim($this->line);

        if (isset($line[0]) && '@' === $line[0]) {
            $token = $this->takeToken('Tag');
            $tags = explode('@', mb_substr($line, 1, mb_strlen($line, 'utf8') - 1, 'utf8'));
            $tags = array_map('trim', $tags);
            $token->tags = $tags;

            $this->consumeLine();

            return $token;
        }
    }

    /**
     * Scans Language specifier from input & returns it if found.
     *
     * @return stdClass|null
     */
    protected function scanLanguage()
    {
        if ($this->featureStarted) {
            return;
        }

        if (!$this->inPyString) {
            if (0 === mb_strpos(ltrim($this->line), '#', 0, 'utf8') && false !== mb_strpos($this->line, 'language', 0, 'utf8')) {
                return $this->scanInput('/^\s*\#\s*language:\s*([\w_\-]+)\s*$/', 'Language');
            }
        }
    }

    /**
     * Scans Comment from input & returns it if found.
     *
     * @return stdClass|null
     */
    protected function scanComment()
    {
        if (!$this->inPyString) {
            if (0 === mb_strpos(ltrim($this->line), '#', 0, 'utf8')) {
                $token = $this->takeToken('Comment', $this->line);

                $this->consumeLine();

                return $token;
            }
        }
    }

    /**
     * Scans Newline from input & returns it if found.
     *
     * @return stdClass|null
     */
    protected function scanNewline()
    {
        if ('' === trim($this->line)) {
            $token = $this->takeToken('Newline', mb_strlen($this->line, 'utf8'));

            $this->consumeLine();

            return $token;
        }
    }

    /**
     * Scans text from input & returns it if found.
     *
     * @return stdClass|null
     */
    protected function scanText()
    {
        $token = $this->takeToken('Text', $this->line);
        $this->consumeLine();

        return $token;
    }
}
