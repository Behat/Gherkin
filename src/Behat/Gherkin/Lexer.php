<?php

namespace Behat\Gherkin;

use Behat\Gherkin\Exception\Exception,
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
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Lexer
{
    private $lines;
    private $line;
    private $lineNumber;
    private $eos;
    private $keywords;
    private $keywordsCache    = array();
    private $deferredObjects  = array();
    private $stash            = array();
    private $inPyString       = false;

    /**
     * Initializes lexer.
     *
     * @param   Behat\Gherkin\Keywords\KeywordsInterface    $keywords   keywords holder
     */
    public function __construct(KeywordsInterface $keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * Sets lexer input.
     *
     * @param   string  $input  input string
     */
    public function setInput($input)
    {
        $input                  = strtr($input, array("\r\n" => "\n", "\r" => "\n"));

        $this->lines            = explode("\n", $input);
        $this->line             = array_shift($this->lines);
        $this->lineNumber       = 1;
        $this->eos              = false;

        $this->deferredObjects  = array();
        $this->stash            = array();
        $this->inPyString       = false;
    }

    /**
     * Sets keywords language.
     *
     * @param   string  $language
     */
    public function setLanguage($language)
    {
        $this->keywords->setLanguage($language);
        $this->keywordsCache = array();
    }

    /**
     * Returns next token or previously stashed one.
     *
     * @return  stdClass
     */
    public function getAdvancedToken()
    {
        return $this->getStashedToken() ?: $this->getNextToken();
    }

    /**
     * Defers token.
     *
     * @param   stdClass    $token  token to defer
     */
    public function deferToken(\stdClass $token)
    {
        $token->defered = true;
        $this->deferredObjects[] = $token;
    }

    /**
     * Predicts for number of tokens.
     *
     * @param   integer     $number number of tokens to predict
     *
     * @return  stdClass            predicted token
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
     * @param   string  $type   token type
     * @param   string  $value  token value
     *
     * @return  stdClass        new token object
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
     * Returns stashed token or false if hasn't.
     *
     * @return  stdClass|boolean    token if has stashed, false otherways
     */
    protected function getStashedToken()
    {
        return count($this->stash) ? array_shift($this->stash) : null;
    }

    /**
     * Returns deferred token or false if hasn't.
     *
     * @return  stdClass|boolean    token if has deferred, false otherways
     */
    protected function getDeferredToken()
    {
        return count($this->deferredObjects) ? array_shift($this->deferredObjects) : null;
    }

    /**
     * Returns next token from input.
     *
     * @return  stdClass
     */
    protected function getNextToken()
    {
        return $this->getDeferredToken()
            ?: $this->scanEOS()
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
            ?: $this->scanLanguage()
            ?: $this->scanComment()
            ?: $this->scanNewline()
            ?: $this->scanText();
    }

    /**
     * Consumes line from input.
     */
    protected function moveToNextLine()
    {
        ++$this->lineNumber;

        if (!count($this->lines)) {
            $this->eos = true;

            return false;
        }

        $this->line = array_shift($this->lines);
    }

    /**
     * Scans for token with specified regex.
     *
     * @param   string  $regex  regular expression
     * @param   string  $type   expected token type
     *
     * @return  stdClass|null
     */
    protected function scanInput($regex, $type)
    {
        $matches = array();
        if (preg_match($regex, $this->line, $matches)) {
            $token = $this->takeToken($type, $matches[1]);

            $this->moveToNextLine();

            return $token;
        }
    }

    /**
     * Scans for token with specified keywords.
     *
     * @param   string  $keywords   keywords (splitted with |)
     * @param   string  $type       expected token type
     *
     * @return  stdClass|null
     */
    protected function scanInputForKeywords($keywords, $type)
    {
        $matches = array();

        if (preg_match('/^\s*('.$keywords.')\: *([^\n\#]*)/u', $this->line, $matches)) {
            $token = $this->takeToken($type, $matches[2]);
            $token->keyword = $matches[1];

            $this->moveToNextLine();

            return $token;
        }
    }

    /**
     * Scans EOS from input & returns it if found.
     *
     * @return  stdClass|null
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
     * @param   string  $type
     *
     * @return  string
     *
     * @uses    Behat\Gherkin\Keywords\KeywordsInterface
     */
    protected function getKeywords($type)
    {
        if (!isset($this->keywordsCache[$type])) {
            $getter = 'get' . $type . 'Keywords';
            $this->keywordsCache[$type] = $this->keywords->$getter();
        }

        return $this->keywordsCache[$type];
    }

    /**
     * Scans Feature from input & returns it if found.
     *
     * @return  stdClass|null
     */
    protected function scanFeature()
    {
        return $this->scanInputForKeywords($this->getKeywords('Feature'), 'Feature');
    }

    /**
     * Scans Background from input & returns it if found.
     *
     * @return  stdClass|null
     */
    protected function scanBackground()
    {
        return $this->scanInputForKeywords($this->getKeywords('Background'), 'Background');
    }

    /**
     * Scans Scenario from input & returns it if found.
     *
     * @return  stdClass|null
     */
    protected function scanScenario()
    {
        return $this->scanInputForKeywords($this->getKeywords('Scenario'), 'Scenario');
    }

    /**
     * Scans Scenario Outline from input & returns it if found.
     *
     * @return  stdClass|null
     */
    protected function scanOutline()
    {
        return $this->scanInputForKeywords($this->getKeywords('Outline'), 'Outline');
    }

    /**
     * Scans Scenario Outline Examples from input & returns it if found.
     *
     * @return  stdClass|null
     */
    protected function scanExamples()
    {
        return $this->scanInputForKeywords($this->getKeywords('Examples'), 'Examples');
    }

    /**
     * Scans Step from input & returns it if found.
     *
     * @return  stdClass|null
     */
    protected function scanStep()
    {
        $matches = array();

        if (preg_match('/^\s*('.$this->getKeywords('Step').') *([^\n\#]+)/u', $this->line, $matches)) {
            $token = $this->takeToken('Step', $matches[1]);
            $token->text = $matches[2];

            $this->moveToNextLine();

            return $token;
        }
    }

    /**
     * Scans PyString from input & returns it if found.
     *
     * @return  stdClass|null
     */
    protected function scanPyStringOperator()
    {
        $matches = array();

        if (false !== ($pos = mb_strpos($this->line, '"""'))) {
            $this->inPyString =! $this->inPyString;
            $token = $this->takeToken('PyStringOperator');
            $token->swallow = $pos;

            $this->moveToNextLine();

            return $token;
        }
    }

    /**
     * Scans PyString content.
     *
     * @return  stdClass|null
     */
    protected function scanPyStringContent()
    {
        if ($this->inPyString) {
            $token = $this->takeToken('Text', $this->line);

            $this->moveToNextLine();

            return $token;
        }
    }

    /**
     * Scans Table Row from input & returns it if found.
     *
     * @return  stdClass|null
     */
    protected function scanTableRow()
    {
        $matches = array();

        if (preg_match('/\s*\|([^\n]+)\|/', $this->line, $matches)) {
            $token = $this->takeToken('TableRow');

            // Split & trim row columns
            $columns = explode('|', $matches[1]);
            $columns = array_map(function($column) {
                return trim($column);
            }, $columns);
            $token->columns = $columns;

            $this->moveToNextLine();

            return $token;
        }
    }

    /**
     * Scans Tags from input & returns it if found.
     *
     * @return  stdClass|null
     */
    protected function scanTags()
    {
        $matches = array();

        if (preg_match('/^\s*@([^\n]+)/', $this->line, $matches)) {
            $token = $this->takeToken('Tag');
            $tags = explode('@', $matches[1]);
            $tags = array_map(function($tag){
                return trim($tag);
            }, $tags);
            $token->tags = $tags;

            $this->moveToNextLine();

            return $token;
        }
    }

    /**
     * Scans Language specifier from input & returns it if found.
     *
     * @return  stdClass|null
     */
    protected function scanLanguage()
    {
        return $this->scanInput('/^\s*\#\s*(?:language|lang):\s*([\w_\-]+)\s*$/', 'Language');
    }

    /**
     * Scans Comment from input & returns it if found.
     *
     * @return  stdClass|null
     */
    protected function scanComment()
    {
        return $this->scanInput('/^\s*\#([^\n]*)/', 'Comment');
    }

    /**
     * Scans Newline from input & returns it if found.
     *
     * @return  stdClass|null
     */
    protected function scanNewline()
    {
        $matches = array();

        if (preg_match('/^(\s*)$/', $this->line, $matches)) {
            $token = $this->takeToken('Newline', $matches[1]);

            $this->moveToNextLine();

            return $token;
        }
    }

    /**
     * Scans text from input & returns it if found.
     *
     * @return  stdClass|null
     */
    protected function scanText()
    {
        $token = $this->takeToken('Text', $this->line);

        $this->moveToNextLine();

        return $token;
    }
}
