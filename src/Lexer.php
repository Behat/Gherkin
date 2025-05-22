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
use LogicException;

use function assert;

/**
 * Gherkin lexer.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @phpstan-type TToken array{type: string, line: int, value: string|null, deferred: bool, tags?: list<string>, keyword?: string, keyword_type?: string, text?: string, indent?: int, columns?: list<string>}
 */
class Lexer
{
    /**
     * Splits a string around | char, only if it's not preceded by an odd number of \.
     *
     * @see https://github.com/cucumber/gherkin/blob/679a87e21263699c15ea635159c6cda60f64af3b/php/src/StringGherkinLine.php#L14
     */
    private const CELL_PATTERN = '/(?<!\\\\)(?:\\\\{2})*\K\\|/u';
    private string $language;
    /**
     * @var list<string>
     */
    private array $lines;
    private int $linesCount;
    private string $line;
    private ?string $trimmedLine = null;
    private int $lineNumber;
    private bool $eos;
    /**
     * @var array<string, string>
     */
    private array $keywordsCache = [];
    /**
     * @var array<string, list<string>>
     */
    private array $stepKeywordTypesCache = [];
    /**
     * @phpstan-var list<TToken>
     */
    private array $deferredObjects = [];
    private int $deferredObjectsCount = 0;
    /**
     * @phpstan-var TToken|null
     */
    private ?array $stashedToken = null;
    private bool $inPyString = false;
    private int $pyStringSwallow = 0;
    private bool $allowLanguageTag = true;
    private bool $allowFeature = true;
    private bool $allowMultilineArguments = false;
    private bool $allowSteps = false;
    private ?string $pyStringDelimiter = null;

    public function __construct(
        private readonly KeywordsInterface $keywords,
    ) {
    }

    /**
     * Sets lexer input.
     *
     * @param string $input Input string
     * @param string $language Language name
     *
     * @return void
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

        $this->allowLanguageTag = true;
        $this->allowFeature = true;
        $this->allowMultilineArguments = false;
        $this->allowSteps = false;

        $this->setLanguage($language);
    }

    private function setLanguage(string $language): void
    {
        if (($this->stashedToken !== null) || ($this->deferredObjects !== [])) {
            // @codeCoverageIgnoreStart
            // It is not possible to trigger this condition using the public interface of this class.
            // It may be possible if the end-user has extended the Lexer with custom functionality.
            throw new LogicException(
                <<<'STRING'
                Cannot set gherkin language due to unexpected Lexer state.

                Please open an issue at https://github.com/Behat/Gherkin with a copy of the current
                feature file. If you are using a Lexer or Parser class that extends the ones provided
                in behat/gherkin, please also provide details of these.
                STRING,
            );
            // @codeCoverageIgnoreEnd
        }

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
     *
     * @phpstan-return TToken
     */
    public function getAdvancedToken()
    {
        return $this->getStashedToken() ?: $this->getNextToken();
    }

    /**
     * Defers token.
     *
     * @phpstan-param TToken $token Token to defer
     *
     * @return void
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
     *
     * @phpstan-return TToken
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
     * @param string|null $value Token value
     *
     * @return array
     *
     * @phpstan-return TToken
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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @phpstan-return TToken|null
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
     *
     * @phpstan-return TToken|null
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
     *
     * @phpstan-return TToken
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
     *
     * @phpstan-return TToken|null
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
     *
     * @phpstan-return TToken|null
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

        // turn off language searching and feature detection
        if ($type === 'Feature') {
            $this->allowFeature = false;
            $this->allowLanguageTag = false;
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
     *
     * @phpstan-return TToken|null
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
     *
     * @phpstan-return TToken|null
     */
    protected function scanFeature()
    {
        if (!$this->allowFeature) {
            // The Feature: tag is only allowed once in a file, later in the file it may be part of a description node
            return null;
        }

        return $this->scanInputForKeywords($this->getKeywords('Feature'), 'Feature');
    }

    /**
     * Scans Background from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TToken|null
     */
    protected function scanBackground()
    {
        return $this->scanInputForKeywords($this->getKeywords('Background'), 'Background');
    }

    /**
     * Scans Scenario from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TToken|null
     */
    protected function scanScenario()
    {
        return $this->scanInputForKeywords($this->getKeywords('Scenario'), 'Scenario');
    }

    /**
     * Scans Scenario Outline from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TToken|null
     */
    protected function scanOutline()
    {
        return $this->scanInputForKeywords($this->getKeywords('Outline'), 'Outline');
    }

    /**
     * Scans Scenario Outline Examples from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TToken|null
     */
    protected function scanExamples()
    {
        return $this->scanInputForKeywords($this->getKeywords('Examples'), 'Examples');
    }

    /**
     * Scans Step from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TToken|null
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
     *
     * @phpstan-return TToken|null
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
     *
     * @phpstan-return TToken|null
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
     *
     * @phpstan-return TToken|null
     */
    protected function scanTableRow()
    {
        if (!$this->allowMultilineArguments) {
            return null;
        }

        $line = $this->getTrimmedLine();
        if (!str_starts_with($line, '|')) {
            // Strictly speaking, a table row only has to begin with a pipe - content to the right
            // of the final pipe will be ignored after we split the cells.
            return null;
        }

        $rawColumns = preg_split(self::CELL_PATTERN, $line);
        assert($rawColumns !== false);

        // Safely remove elements before the first and last separators
        array_shift($rawColumns);
        array_pop($rawColumns);

        $token = $this->takeToken('TableRow');
        $columns = array_map(function ($column) {
            return trim(str_replace(['\\|', '\\\\'], ['|', '\\'], $column));
        }, $rawColumns);
        $token['columns'] = $columns;

        $this->consumeLine();

        return $token;
    }

    /**
     * Scans Tags from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TToken|null
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
     *
     * @phpstan-return TToken|null
     */
    protected function scanLanguage()
    {
        if (!$this->allowLanguageTag) {
            return null;
        }

        if ($this->inPyString) {
            return null;
        }

        if (!str_starts_with(ltrim($this->line), '#')) {
            return null;
        }

        $token = $this->scanInput('/^\s*#\s*language:\s*([\w_\-]+)\s*$/', 'Language');

        if ($token) {
            \assert(\is_string($token['value']));
            $this->allowLanguageTag = false;
            $this->setLanguage($token['value']);
        }

        return $token;
    }

    /**
     * Scans Comment from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TToken|null
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
     *
     * @phpstan-return TToken|null
     */
    protected function scanNewline()
    {
        if ($this->getTrimmedLine() !== '') {
            return null;
        }

        $token = $this->takeToken('Newline');
        $this->consumeLine();

        return $token;
    }

    /**
     * Scans text from input & returns it if found.
     *
     * @return array
     *
     * @phpstan-return TToken
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
