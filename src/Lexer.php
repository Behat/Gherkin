<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin;

use Behat\Gherkin\Dialect\DialectProviderInterface;
use Behat\Gherkin\Dialect\GherkinDialect;
use Behat\Gherkin\Dialect\KeywordsDialectProvider;
use Behat\Gherkin\Exception\LexerException;
use Behat\Gherkin\Exception\NoSuchLanguageException;
use Behat\Gherkin\Keywords\KeywordsInterface;
use LogicException;

use function assert;

/**
 * Gherkin lexer.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @final since 4.15.0
 *
 * @phpstan-type TStepKeyword 'Given'|'When'|'Then'|'And'|'But'
 * @phpstan-type TTitleKeyword 'Feature'|'Background'|'Scenario'|'Outline'|'Examples'
 * @phpstan-type TTokenType 'Text'|'Comment'|'EOS'|'Newline'|'PyStringOp'|'TableRow'|'Tag'|'Language'|'Step'|TTitleKeyword
 * @phpstan-type TToken TStringValueToken|TNullValueToken|TTitleToken|TStepToken|TTagToken|TTableRowToken
 * @phpstan-type TStringValueToken array{type: TTokenType, value: string, line: int, deferred: bool}
 * @phpstan-type TNullValueToken array{type: TTokenType, value: null, line: int, deferred: bool}
 * @phpstan-type TTitleToken array{type: TTitleKeyword, value: null|non-empty-string, line: int, deferred: bool, keyword: string, indent: int}
 * @phpstan-type TStepToken array{type: 'Step', value: string, line: int, deferred: bool, keyword_type: string, text: string}
 * @phpstan-type TTagToken array{type: 'Tag', value: null, line: int, deferred: bool, tags: list<string>}
 * @phpstan-type TTableRowToken array{type: 'TableRow', value: null, line: int, deferred: bool, columns: list<string>}
 */
class Lexer
{
    /**
     * Splits a string around | char, only if it's not preceded by an odd number of \.
     *
     * @see https://github.com/cucumber/gherkin/blob/679a87e21263699c15ea635159c6cda60f64af3b/php/src/StringGherkinLine.php#L14
     */
    private const CELL_PATTERN = '/(?<!\\\\)(?:\\\\{2})*\K\\|/u';

    private readonly DialectProviderInterface $dialectProvider;
    private GherkinDialect $currentDialect;
    private GherkinCompatibilityMode $compatibilityMode = GherkinCompatibilityMode::LEGACY;
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
     * A cache of keyword types associated with each keyword.
     *
     * @phpstan-var array<string, non-empty-list<TStepKeyword>>|null
     */
    private ?array $stepKeywordTypesCache = null;
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
        DialectProviderInterface|KeywordsInterface $dialectProvider,
    ) {
        if ($dialectProvider instanceof KeywordsInterface) {
            // TODO trigger deprecation
            $dialectProvider = new KeywordsDialectProvider($dialectProvider);
        }

        $this->dialectProvider = $dialectProvider;
    }

    /**
     * @internal
     */
    public function setCompatibilityMode(GherkinCompatibilityMode $compatibilityMode): void
    {
        $this->compatibilityMode = $compatibilityMode;
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
    public function analyse(string $input, string $language = 'en')
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

        if (\func_num_args() > 1) {
            // @codeCoverageIgnoreStart
            \assert($language !== '');
            // TODO trigger deprecation (the Parser does not use this code path)
            $this->setLanguage($language);
        // @codeCoverageIgnoreEnd
        } else {
            $this->currentDialect = $this->dialectProvider->getDefaultDialect();
            $this->stepKeywordTypesCache = null;
        }
    }

    /**
     * @param non-empty-string $language
     */
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

        try {
            $this->currentDialect = $this->dialectProvider->getDialect($language);
        } catch (NoSuchLanguageException $e) {
            if (!$this->compatibilityMode->shouldIgnoreInvalidLanguage()) {
                throw $e;
            }
        }
        $this->stepKeywordTypesCache = null;
    }

    /**
     * Returns current lexer language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->currentDialect->getLanguage();
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
        return $this->getStashedToken() ?? $this->getNextToken();
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
     * Constructs a token with specified parameters.
     *
     * @template T of TTokenType
     *
     * @param string|null $value Token value
     *
     * @phpstan-param T $type Token type
     *
     * @return array
     *
     * @phpstan-return ($value is non-empty-string ? array{type: T, value: non-empty-string, line: int, deferred: bool} : array{type: T, value: null, line: int, deferred: bool})
     */
    public function takeToken(string $type, ?string $value = null)
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
     * Returns stashed token or null if there isn't one.
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
     * Returns deferred token or null if there isn't one.
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
            ?? $this->scanEOS()
            ?? $this->scanLanguage()
            ?? $this->scanComment()
            ?? $this->scanPyStringOp()
            ?? $this->scanPyStringContent()
            ?? $this->scanStep()
            ?? $this->scanScenario()
            ?? $this->scanBackground()
            ?? $this->scanOutline()
            ?? $this->scanExamples()
            ?? $this->scanFeature()
            ?? $this->scanTags()
            ?? $this->scanTableRow()
            ?? $this->scanNewline()
            ?? $this->scanText();
    }

    /**
     * Scans for token with specified regex.
     *
     * @param string $regex Regular expression
     *
     * @phpstan-param TTokenType $type Expected token type
     *
     * @return array|null
     *
     * @phpstan-return TStringValueToken|null
     */
    protected function scanInput(string $regex, string $type)
    {
        if (!preg_match($regex, $this->line, $matches)) {
            return null;
        }

        assert($matches[1] !== '');

        $token = $this->takeToken($type, $matches[1]);
        $this->consumeLine();

        return $token;
    }

    /**
     * Scans for token with specified keywords.
     *
     * @param string $keywords Keywords (separated by "|")
     *
     * @phpstan-param TTitleKeyword $type Expected token type
     *
     * @return array|null
     *
     * @phpstan-return TTitleToken|null
     *
     * @deprecated
     */
    protected function scanInputForKeywords(string $keywords, string $type)
    {
        // @codeCoverageIgnoreStart
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
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param list<string> $keywords
     *
     * @phpstan-param TTitleKeyword $type
     *
     * @phpstan-return TTitleToken|null
     */
    private function scanTitleLine(array $keywords, string $type): ?array
    {
        $trimmedLine = $this->getTrimmedLine();

        foreach ($keywords as $keyword) {
            if (str_starts_with($trimmedLine, $keyword . ':')) {
                $title = trim(mb_substr($trimmedLine, mb_strlen($keyword) + 1));

                $token = $this->takeToken($type, $title);
                $token['keyword'] = $keyword;
                $token['indent'] = mb_strlen($this->line, 'utf8') - mb_strlen(ltrim($this->line), 'utf8');

                $this->consumeLine();

                return $token;
            }
        }

        return null;
    }

    /**
     * Scans EOS from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TNullValueToken|null
     */
    protected function scanEOS()
    {
        if (!$this->eos) {
            return null;
        }

        return $this->takeToken('EOS');
    }

    /**
     * Returns a regex matching the keywords for the provided type.
     *
     * @phpstan-param 'Step'|TTitleKeyword|TStepKeyword $type Keyword type
     *
     * @return string
     *
     * @deprecated
     */
    protected function getKeywords(string $type)
    {
        // @codeCoverageIgnoreStart
        $keywords = match ($type) {
            'Feature' => $this->currentDialect->getFeatureKeywords(),
            'Background' => $this->currentDialect->getBackgroundKeywords(),
            'Scenario' => $this->currentDialect->getScenarioKeywords(),
            'Outline' => $this->currentDialect->getScenarioOutlineKeywords(),
            'Examples' => $this->currentDialect->getExamplesKeywords(),
            'Step' => $this->currentDialect->getStepKeywords(),
            'Given' => $this->currentDialect->getGivenKeywords(),
            'When' => $this->currentDialect->getWhenKeywords(),
            'Then' => $this->currentDialect->getThenKeywords(),
            'And' => $this->currentDialect->getAndKeywords(),
            'But' => $this->currentDialect->getButKeywords(),
            default => throw new \InvalidArgumentException(sprintf('Unknown keyword type "%s"', $type)),
        };

        $keywordsRegex = implode('|', array_map(fn ($keyword) => preg_quote($keyword, '/'), $keywords));

        if ($type === 'Step') {
            $keywordsRegex = '(?:' . $keywordsRegex . ')\s*';
        }

        return $keywordsRegex;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Scans Feature from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TTitleToken|null
     */
    protected function scanFeature()
    {
        if (!$this->allowFeature) {
            // The Feature: tag is only allowed once in a file, later in the file it may be part of a description node
            return null;
        }

        $token = $this->scanTitleLine($this->currentDialect->getFeatureKeywords(), 'Feature');

        if ($token === null) {
            return null;
        }

        $this->allowFeature = false;
        $this->allowLanguageTag = false;
        $this->allowMultilineArguments = false;

        return $token;
    }

    /**
     * Scans Background from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TTitleToken|null
     */
    protected function scanBackground()
    {
        $token = $this->scanTitleLine($this->currentDialect->getBackgroundKeywords(), 'Background');

        if ($token === null) {
            return null;
        }

        $this->allowSteps = true;

        return $token;
    }

    /**
     * Scans Scenario from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TTitleToken|null
     */
    protected function scanScenario()
    {
        $token = $this->scanTitleLine($this->currentDialect->getScenarioKeywords(), 'Scenario');

        if ($token === null) {
            return null;
        }

        $this->allowMultilineArguments = false;
        $this->allowSteps = true;

        return $token;
    }

    /**
     * Scans Scenario Outline from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TTitleToken|null
     */
    protected function scanOutline()
    {
        $token = $this->scanTitleLine($this->currentDialect->getScenarioOutlineKeywords(), 'Outline');

        if ($token === null) {
            return null;
        }

        $this->allowMultilineArguments = false;
        $this->allowSteps = true;

        return $token;
    }

    /**
     * Scans Scenario Outline Examples from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TTitleToken|null
     */
    protected function scanExamples()
    {
        $token = $this->scanTitleLine($this->currentDialect->getExamplesKeywords(), 'Examples');

        if ($token === null) {
            return null;
        }

        $this->allowMultilineArguments = true;

        return $token;
    }

    /**
     * Scans Step from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TStepToken|null
     */
    protected function scanStep()
    {
        if (!$this->allowSteps) {
            return null;
        }

        $trimmedLine = $this->getTrimmedLine();
        $matchedKeyword = null;

        foreach ($this->currentDialect->getStepKeywords() as $keyword) {
            if (str_starts_with($trimmedLine, $keyword)) {
                $matchedKeyword = $keyword;
                break;
            }
        }

        if ($matchedKeyword === null) {
            return null;
        }

        $text = ltrim(mb_substr($trimmedLine, mb_strlen($matchedKeyword)));

        $nodeKeyword = $this->compatibilityMode->shouldRemoveStepKeywordSpace() ? trim($matchedKeyword) : $matchedKeyword;
        assert($nodeKeyword !== '');

        $token = $this->takeToken('Step', $nodeKeyword);
        $token['keyword_type'] = $this->getStepKeywordType($matchedKeyword);
        $token['text'] = $text;

        $this->consumeLine();
        $this->allowMultilineArguments = true;

        return $token;
    }

    /**
     * Scans PyString from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TNullValueToken|null
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
     * @phpstan-return TStringValueToken|null
     */
    protected function scanPyStringContent()
    {
        if (!$this->inPyString) {
            return null;
        }

        $token = $this->scanText();
        // swallow trailing spaces
        $token['value'] = (string) preg_replace('/^\s{0,' . $this->pyStringSwallow . '}/u', '', $token['value'] ?? '');

        return $token;
    }

    /**
     * Scans Table Row from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TTableRowToken|null
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
        if ($this->compatibilityMode->shouldSupportNewlineEscapeSequenceInTableCell()) {
            $columns = array_map($this->parseTableCell(...), $rawColumns);
        } else {
            $columns = array_map(static fn ($column) => trim(str_replace(['\\|', '\\\\'], ['|', '\\'], $column)), $rawColumns);
        }
        $token['columns'] = $columns;

        $this->consumeLine();

        return $token;
    }

    private function parseTableCell(string $cell): string
    {
        $value = preg_replace_callback('/\\\\./', function (array $matches) {
            return match ($matches[0]) {
                '\\n' => "\n",
                '\\\\' => '\\',
                '\\|' => '|',
                default => $matches[0],
            };
        }, $cell);

        assert($value !== null);

        return trim($value, ' ');
    }

    /**
     * Scans Tags from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TTagToken|null
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
        $tags = array_map(trim(...), $tags);
        $token['tags'] = $tags;

        return $token;
    }

    /**
     * Scans Language specifier from input & returns it if found.
     *
     * @return array|null
     *
     * @phpstan-return TStringValueToken|null
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

        $pattern = $this->compatibilityMode->allowWhitespaceInLanguageTag()
            ? '/^\s*#\s*language\s*:\s*([\w_\-]+)\s*$/u'
            : '/^\s*#\s*language:\s*([\w_\-]+)\s*$/';

        $token = $this->scanInput($pattern, 'Language');

        if ($token) {
            \assert(\is_string($token['value']));
            \assert($token['value'] !== ''); // the regex can only match a non-empty value.
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
     * @phpstan-return TStringValueToken|null
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
     * @phpstan-return TNullValueToken|null
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
     * @phpstan-return TStringValueToken|TNullValueToken
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
     * @phpstan-return TStepKeyword
     */
    private function getStepKeywordType(string $native): string
    {
        if ($this->stepKeywordTypesCache === null) {
            $this->stepKeywordTypesCache = [];
            $this->addStepKeywordTypes($this->currentDialect->getGivenKeywords(), 'Given');
            $this->addStepKeywordTypes($this->currentDialect->getWhenKeywords(), 'When');
            $this->addStepKeywordTypes($this->currentDialect->getThenKeywords(), 'Then');
            $this->addStepKeywordTypes($this->currentDialect->getAndKeywords(), 'And');
            $this->addStepKeywordTypes($this->currentDialect->getButKeywords(), 'But');
        }

        if (!isset($this->stepKeywordTypesCache[$native])) { // should not happen when the native keyword belongs to the dialect
            return 'Given'; // cucumber/gherkin has an UNKNOWN type, but we don't have it.
        }

        if (\count($this->stepKeywordTypesCache[$native]) === 1) {
            return $this->stepKeywordTypesCache[$native][0];
        }

        // Consider ambiguous keywords as AND keywords so that they are normalized to the previous step type.
        // This happens in English for the `* ` keyword for instance.
        // cucumber/gherkin returns that as an UNKNOWN type, but we don't have it.
        return 'And';
    }

    /**
     * @param list<string> $keywords
     *
     * @phpstan-param TStepKeyword $type
     */
    private function addStepKeywordTypes(array $keywords, string $type): void
    {
        foreach ($keywords as $keyword) {
            $this->stepKeywordTypesCache[$keyword][] = $type;
        }
    }
}
