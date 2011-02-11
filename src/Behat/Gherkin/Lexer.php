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
    private $input;
    private $keywords;
    private $line             = 1;
    private $deferredObjects  = array();
    private $stash            = array();
    private $inPyString       = false;
    private $lastIndentString = '';
    private $keywordsCache    = array();

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
        $this->input            = strtr($input, array("\r\n" => "\n", "\r" => "\n"));
        $this->line             = 1;
        $this->deferredObjects  = array();
        $this->stash            = array();
        $this->inPyString       = false;
        $this->lastIndentString = '';
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
     * Returns current line number.
     *
     * @return  integer
     */
    public function getCurrentLine()
    {
        return $this->line;
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
            'line'      => $this->line,
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
            ?: $this->scanNewline()
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
            ?: $this->scanText();
    }

    /**
     * Consumes input.
     *
     * @param   integer $length length of input to consume
     */
    protected function consumeInput($length)
    {
        $this->input = mb_substr($this->input, $length);
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
        if (preg_match($regex, $this->input, $matches)) {
            $this->consumeInput(mb_strlen($matches[0]));

            return $this->takeToken($type, $matches[1]);
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
        $matches    = array();

        if (preg_match('/^('.$keywords.')\: *([^\n]*)/u', $this->input, $matches)) {
            $this->consumeInput(mb_strlen($matches[0]));
            $token = $this->takeToken($type, $matches[2]);
            $token->keyword = $matches[1];

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
        if (mb_strlen($this->input)) {
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
        $matches    = array();
        $keywords   = $this->getKeywords('Step');

        if (preg_match('/^('.$keywords.') *([^\n]+)/u', $this->input, $matches)) {
            $this->consumeInput(mb_strlen($matches[0]));
            $token = $this->takeToken('Step', $matches[1]);
            $token->text = $matches[2];

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

        if ('"' === $this->input[0] && preg_match('/^"""[^\n]*/', $this->input, $matches)) {
            $this->consumeInput(mb_strlen($matches[0]));
            $this->inPyString =! $this->inPyString;

            $token = $this->takeToken('PyStringOperator');
            $token->swallow = mb_strlen($this->lastIndentString);

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
            $matches = array();
            if (preg_match('/^([^\n]+)/', $this->input, $matches)) {
                $this->consumeInput(mb_strlen($matches[0]));

                return $this->takeToken('Text', $this->lastIndentString . $matches[1]);
            }
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

        if ('|' === $this->input[0] && preg_match('/^\|([^\n]+)\|/', $this->input, $matches)) {
            $this->consumeInput(mb_strlen($matches[0]));
            $token = $this->takeToken('TableRow');

            // Split & trim row columns
            $columns = explode('|', $matches[1]);
            $columns = array_map(function($column) {
                return trim($column);
            }, $columns);
            $token->columns = $columns;

            return $token;
        }
    }

    /**
     * Scans Newline from input & returns it if found.
     *
     * @return  stdClass|null
     */
    protected function scanNewline()
    {
        $matches = array();

        if ("\n" === $this->input[0] && preg_match('/^\n( *)/', $this->input, $matches)) {
            $this->line++;
            $this->lastIndentString = $matches[1];

            $this->consumeInput(mb_strlen($matches[0]));
            $token = $this->takeToken('Newline', $this->lastIndentString);

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

        if ('@' === $this->input[0] && preg_match('/^@([^\n]+)/', $this->input, $matches)) {
            $this->consumeInput(mb_strlen($matches[0]));
            $token = $this->takeToken('Tag');

            $tags = explode('@', $matches[1]);
            $tags = array_map(function($tag){
                return trim($tag);
            }, $tags);
            $token->tags = $tags;

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
        if ('#' === $this->input[0]) {
            return $this->scanInput('/^\# *language: *([\w_\-]+)[^\n]*/', 'Language');
        }
    }

    /**
     * Scans Comment from input & returns it if found.
     *
     * @return  stdClass|null
     */
    protected function scanComment()
    {
        if ('#' === $this->input[0]) {
            return $this->scanInput('/^\#([^\n]*)/', 'Comment');
        }
    }

    /**
     * Scans text from input & returns it if found.
     *
     * @return  stdClass|null
     */
    protected function scanText()
    {
        return $this->scanInput('/^([^\n\#]+)/', 'Text');
    }
}
