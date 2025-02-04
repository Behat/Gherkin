<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin;

use Behat\Gherkin\Exception\LexerException;
use Behat\Gherkin\Keywords\KeywordsInterface;

/**
 * Gherkin lexer.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Lexer
{
    private $language;
    private $lines;
    private $linesCount;
    private $line;
    private $trimmedLine;
    private $lineNumber;
    private $eos;
    private $keywords;
    private $keywordsCache = [];
    private $stepKeywordTypesCache = [];
    private $deferredObjects = [];
    private $deferredObjectsCount = 0;
    private $stashedToken;
    private $inPyString = false;
    private $pyStringSwallow = 0;
    private $featureStarted = false;
    private $allowMultilineArguments = false;
    private $allowSteps = false;
    private $pyStringDelimiter;

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
     * @param string $language Language name
     *
     * @throws LexerException
     */
    public function analyse($input, $language = 'en')
    {
        // try to detect unsupported encoding
        if (mb_detect_encoding($input, 'UTF-8', true) !== 'UTF-8') {
            throw new LexerException('Feature file is not in UTF8 encoding');
        }

        $input = strtr($input, ["\r\n" => "\n", "\r" => "\n"]);

        $this->lines = explode("\n", $input);
        $this->linesCount = count($this->lines);
        $this->line = $this->lines[0];
        $this->lineNumber = 1;
        $this->trimmedLine = null;
        $this->eos = false;

        $this->deferredObjects = [];
        $this->deferredObjectsCount = 0;
        $this->stashedToken = null;
        $this->inPyString = false;
        $this->pyStringSwallow = 0;

        $this->featureStarted = false;
        $this->allowMultilineArguments = false;
        $this->allowSteps = false;

        $this->keywords->setLanguage($this->language = $language);
        $this->keywordsCache = [];
        $this->stepKeywordTypesCache = [];
    }

    /**
     * Returns current lexer language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Returns next token or previously stashed one.
     *
     * @return array
     */
    public function getAdvancedToken()
    {
        return $this->getStashedToken() ?: $this->getNextToken();
    }

    /**
     * Defers token.
     *
     * @param array $token Token to defer
     */
    public function deferToken(array $token)
    {
        $token['deferred'] = true;
        $this->deferredObjects[] = $token;
        ++$this->deferredObjectsCount;
    }

    /**
     * Predicts the upcoming token without passing over it.
     *
     * @return array
     */
    public function predictToken()
    {
        return $this->stashedToken ??= $this->getNextToken();
    }

    /**
     * Skips over the currently-predicted token, if any.
     *
     * @return void
     */
    public function skipPredictedToken()
    {
        $this->stashedToken = null;
    }

    /**
     * Constructs token with specified parameters.
     *
     * @param string $type Token type
     * @param int|string|null $value Token value
     *
     * @return array
     */
    public function takeToken($type, $value = null)
    {
        return [
            'type' => $type,
            'line' => $this->lineNumber,
            'value' => $value ?: null,
            'deferred' => false,
        ];
    }

    /**
     * Consumes line from input & increments line counter.
     */
    protected function consumeLine()
    {
        ++$this->lineNumber;

        if (($this->lineNumber - 1) === $this->linesCount) {
            $this->eos = true;

            return;
        }

        $this->line = $this->lines[$this->lineNumber - 1];
        $this->trimmedLine = null;
    }

    /**
     * Consumes first part of line from input without incrementing the line number.
     */
    protected function consumeLineUntil(int $trimmedOffset)
    {
        $this->line = mb_substr(ltrim($this->line), $trimmedOffset, null, 'utf-8');
        $this->trimmedLine = null;
    }

    /**
     * Returns trimmed version of line.
     *
     * @return string
     */
    protected function getTrimmedLine()
    {
        return $this->trimmedLine ??= trim($this->line);
    }

    /**
     * Returns stashed token or null if hasn't.
     *
     * @return array|null
     */
    protected function getStashedToken()
    {
        $stashedToken = $this->stashedToken;
        $this->stashedToken = null;

        return $stashedToken;
    }

    /**
     * Returns deferred token or null if hasn't.
     *
     * @return array|null
     */
    protected function getDeferredToken()
    {
        if (!$this->deferredObjectsCount) {
            return null;
        }

        --$this->deferredObjectsCount;

        return array_shift($this->deferredObjects);
    }

    /**
     * Returns next token from input.
     *
     * @return array
     */
    protected function getNextToken()
    {
        return $this->getDeferredToken()
            ?: $this->scanEOS()
            ?: $this->scanLanguage()
            ?: $this->scanComment()
            ?: $this->scanPyStringOp()
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
     * @param string $type Expected token type
     *
     * @return array|null
     */
    protected function scanInput($regex, $type)
    {
        if (!preg_match($regex, $this->line, $matches)) {
            return null;
        }

        $token = $this->takeToken($type, $matches[1]);
        $this->consumeLine();

        return $token;
    }

    /**
     * Scans for token with specified keywords.
     *
     * @param string $keywords Keywords (separated by "|")
     * @param string $type Expected token type
     *
     * @return array|null
     */
    protected function scanInputForKeywords($keywords, $type)
    {
        if (!preg_match('/^(\s*)(' . $keywords . '):\s*(.*)/u', $this->line, $matches)) {
            return null;
        }

        $token = $this->takeToken($type, $matches[3]);
        $token['keyword'] = $matches[2];
        $token['indent'] = mb_strlen($matches[1], 'utf8');

        $this->consumeLine();

        // turn off language searching
        if ($type === 'Feature') {
            $this->featureStarted = true;
        }

        // turn off PyString and Table searching
        if ($type === 'Feature' || $type === 'Scenario' || $type === 'Outline') {
            $this->allowMultilineArguments = false;
        } elseif ($type === 'Examples') {
            $this->allowMultilineArguments = true;
        }

        // turn on steps searching
        if ($type === 'Scenario' || $type === 'Background' || $type === 'Outline') {
            $this->allowSteps = true;
        }

        return $token;
    }

    /**
     * Scans EOS from input & returns it if found.
     *
     * @return array|null
     */
    protected function scanEOS()
    {
        if (!$this->eos) {
            return null;
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
            $getter = 'get' . $type . 'Keywords';
            $keywords = $this->keywords->$getter();

            if ($type === 'Step') {
                $padded = [];
                foreach (explode('|', $keywords) as $keyword) {
                    $padded[] = str_contains($keyword, '<')
                        ? preg_quote(mb_substr($keyword, 0, -1, 'utf8'), '/') . '\s*'
                        : preg_quote($keyword, '/') . '\s+';
                }

                $keywords = implode('|', $padded);
            }

            $this->keywordsCache[$type] = $keywords;
        }

        return $this->keywordsCache[$type];
    }

    /**
     * Scans Feature from input & returns it if found.
     *
     * @return array|null
     */
    protected function scanFeature()
    {
        return $this->scanInputForKeywords($this->getKeywords('Feature'), 'Feature');
    }

    /**
     * Scans Background from input & returns it if found.
     *
     * @return array|null
     */
    protected function scanBackground()
    {
        return $this->scanInputForKeywords($this->getKeywords('Background'), 'Background');
    }

    /**
     * Scans Scenario from input & returns it if found.
     *
     * @return array|null
     */
    protected function scanScenario()
    {
        return $this->scanInputForKeywords($this->getKeywords('Scenario'), 'Scenario');
    }

    /**
     * Scans Scenario Outline from input & returns it if found.
     *
     * @return array|null
     */
    protected function scanOutline()
    {
        return $this->scanInputForKeywords($this->getKeywords('Outline'), 'Outline');
    }

    /**
     * Scans Scenario Outline Examples from input & returns it if found.
     *
     * @return array|null
     */
    protected function scanExamples()
    {
        return $this->scanInputForKeywords($this->getKeywords('Examples'), 'Examples');
    }

    /**
     * Scans Step from input & returns it if found.
     *
     * @return array|null
     */
    protected function scanStep()
    {
        if (!$this->allowSteps) {
            return null;
        }

        $keywords = $this->getKeywords('Step');
        if (!preg_match('/^\s*(' . $keywords . ')([^\s].*)/u', $this->line, $matches)) {
            return null;
        }

        $keyword = trim($matches[1]);
        $token = $this->takeToken('Step', $keyword);
        $token['keyword_type'] = $this->getStepKeywordType($keyword);
        $token['text'] = $matches[2];

        $this->consumeLine();
        $this->allowMultilineArguments = true;

        return $token;
    }

    /**
     * Scans PyString from input & returns it if found.
     *
     * @return array|null
     */
    protected function scanPyStringOp()
    {
        if (!$this->allowMultilineArguments) {
            return null;
        }

        if (!preg_match('/^\s*(?<delimiter>"""|```)/u', $this->line, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        ['delimiter' => [0 => $delimiter, 1 => $indent]] = $matches;

        if ($this->inPyString) {
            if ($this->pyStringDelimiter !== $delimiter) {
                return null;
            }
            $this->pyStringDelimiter = null;
        } else {
            $this->pyStringDelimiter = $delimiter;
        }

        $this->inPyString = !$this->inPyString;
        $token = $this->takeToken('PyStringOp');
        $this->pyStringSwallow = $indent;

        $this->consumeLine();

        return $token;
    }

    /**
     * Scans PyString content.
     *
     * @return array|null
     */
    protected function scanPyStringContent()
    {
        if (!$this->inPyString) {
            return null;
        }

        $token = $this->scanText();
        // swallow trailing spaces
        $token['value'] = preg_replace('/^\s{0,' . $this->pyStringSwallow . '}/u', '', $token['value'] ?? '');

        return $token;
    }

    /**
     * Scans Table Row from input & returns it if found.
     *
     * @return array|null
     */
    protected function scanTableRow()
    {
        if (!$this->allowMultilineArguments) {
            return null;
        }

        $line = $this->getTrimmedLine();
        if ($line === '' || !str_starts_with($line, '|') || !str_ends_with($line, '|')) {
            return null;
        }

        $token = $this->takeToken('TableRow');
        $line = mb_substr($line, 1, mb_strlen($line, 'utf8') - 2, 'utf8');
        $columns = array_map(function ($column) {
            return trim(str_replace(['\\|', '\\\\'], ['|', '\\'], $column));
        }, preg_split('/(?<!\\\)\|/u', $line));
        $token['columns'] = $columns;

        $this->consumeLine();

        return $token;
    }

    /**
     * Scans Tags from input & returns it if found.
     *
     * @return array|null
     */
    protected function scanTags()
    {
        $line = $this->getTrimmedLine();

        if ($line === '' || !str_starts_with($line, '@')) {
            return null;
        }

        if (preg_match('/^(?<line>.*)\s+#.*$/', $line, $matches)) {
            ['line' => $line] = $matches;
            $this->consumeLineUntil(mb_strlen($line, 'utf-8'));
        } else {
            $this->consumeLine();
        }

        $token = $this->takeToken('Tag');
        $tags = explode('@', mb_substr($line, 1, mb_strlen($line, 'utf8') - 1, 'utf8'));
        $tags = array_map('trim', $tags);
        $token['tags'] = $tags;

        return $token;
    }

    /**
     * Scans Language specifier from input & returns it if found.
     *
     * @return array|null
     */
    protected function scanLanguage()
    {
        if ($this->featureStarted) {
            return null;
        }

        if ($this->inPyString) {
            return null;
        }

        if (!str_starts_with(ltrim($this->line), '#')) {
            return null;
        }

        return $this->scanInput('/^\s*#\s*language:\s*([\w_\-]+)\s*$/', 'Language');
    }

    /**
     * Scans Comment from input & returns it if found.
     *
     * @return array|null
     */
    protected function scanComment()
    {
        if ($this->inPyString) {
            return null;
        }

        $line = $this->getTrimmedLine();
        if (!str_starts_with($line, '#')) {
            return null;
        }

        $token = $this->takeToken('Comment', $line);
        $this->consumeLine();

        return $token;
    }

    /**
     * Scans Newline from input & returns it if found.
     *
     * @return array|null
     */
    protected function scanNewline()
    {
        if ($this->getTrimmedLine() !== '') {
            return null;
        }

        $token = $this->takeToken('Newline', mb_strlen($this->line, 'utf8'));
        $this->consumeLine();

        return $token;
    }

    /**
     * Scans text from input & returns it if found.
     *
     * @return array|null
     */
    protected function scanText()
    {
        $token = $this->takeToken('Text', $this->line);
        $this->consumeLine();

        return $token;
    }

    /**
     * Returns step type keyword (Given, When, Then, etc.).
     *
     * @param string $native Step keyword in provided language
     *
     * @return string
     */
    private function getStepKeywordType($native)
    {
        // Consider "*" as a AND keyword so that it is normalized to the previous step type
        if ($native === '*') {
            return 'And';
        }

        if (empty($this->stepKeywordTypesCache)) {
            $this->stepKeywordTypesCache = [
                'Given' => explode('|', $this->keywords->getGivenKeywords()),
                'When' => explode('|', $this->keywords->getWhenKeywords()),
                'Then' => explode('|', $this->keywords->getThenKeywords()),
                'And' => explode('|', $this->keywords->getAndKeywords()),
                'But' => explode('|', $this->keywords->getButKeywords()),
            ];
        }

        foreach ($this->stepKeywordTypesCache as $type => $keywords) {
            if (in_array($native, $keywords, true) || in_array($native . '<', $keywords, true)) {
                return $type;
            }
        }

        return 'Given';
    }
}
