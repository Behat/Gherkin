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
use Behat\Gherkin\Exception\NodeException;
use Behat\Gherkin\Exception\ParserException;
use Behat\Gherkin\Exception\UnexpectedParserNodeException;
use Behat\Gherkin\Exception\UnexpectedTaggedNodeException;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Gherkin parser.
 *
 * $lexer  = new Behat\Gherkin\Lexer($keywords);
 * $parser = new Behat\Gherkin\Parser($lexer);
 * $featuresArray = $parser->parse('/path/to/feature.feature');
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @phpstan-import-type TToken from Lexer
 *
 * @phpstan-type TParsedExpressionResult FeatureNode|BackgroundNode|ScenarioNode|OutlineNode|ExampleTableNode|TableNode|PyStringNode|StepNode|string
 */
class Parser
{
    private string $input;
    private ?string $file = null;
    /**
     * @var list<string>
     */
    private array $tags = [];

    public function __construct(
        private readonly Lexer $lexer,
    ) {
    }

    /**
     * Parses input & returns features array.
     *
     * @param string $input Gherkin string document
     * @param string|null $file File name
     *
     * @return FeatureNode|null
     *
     * @throws ParserException
     */
    public function parse($input, $file = null)
    {
        $this->input = $input;
        $this->file = $file;
        $this->tags = [];

        try {
            $this->lexer->analyse($this->input, 'en');
        } catch (LexerException $e) {
            throw new ParserException(
                sprintf('Lexer exception "%s" thrown for file %s', $e->getMessage(), $file),
                0,
                $e
            );
        }

        $feature = null;
        while ($this->predictTokenType() !== 'EOS') {
            $node = $this->parseExpression();

            if ($node === "\n" || $node === '') {
                continue;
            }

            if (!$feature && $node instanceof FeatureNode) {
                $feature = $node;
                continue;
            }

            throw new UnexpectedParserNodeException('Feature', $node, $this->file);
        }

        return $feature;
    }

    /**
     * Returns next token if it's type equals to expected.
     *
     * @param string $type Token type
     *
     * @return array
     *
     * @phpstan-return TToken
     *
     * @throws ParserException
     */
    protected function expectTokenType($type)
    {
        $types = (array) $type;
        if (in_array($this->predictTokenType(), $types)) {
            return $this->lexer->getAdvancedToken();
        }

        $token = $this->lexer->predictToken();

        throw new ParserException(sprintf(
            'Expected %s token, but got %s on line: %d%s',
            implode(' or ', $types),
            $this->predictTokenType(),
            $token['line'],
            $this->file ? ' in file: ' . $this->file : ''
        ));
    }

    /**
     * Returns next token if it's type equals to expected.
     *
     * @param string $type Token type
     *
     * @return array|null
     *
     * @phpstan-return TToken|null
     */
    protected function acceptTokenType($type)
    {
        if ($type !== $this->predictTokenType()) {
            return null;
        }

        return $this->lexer->getAdvancedToken();
    }

    /**
     * Returns next token type without real input reading (prediction).
     *
     * @return string
     */
    protected function predictTokenType()
    {
        $token = $this->lexer->predictToken();

        return $token['type'];
    }

    /**
     * Parses current expression & returns Node.
     *
     * @phpstan-return TParsedExpressionResult
     *
     * @throws ParserException
     */
    protected function parseExpression()
    {
        $type = $this->predictTokenType();

        while ($type === 'Comment') {
            $this->expectTokenType('Comment');

            $type = $this->predictTokenType();
        }

        return match ($type) {
            'Feature' => $this->parseFeature(),
            'Background' => $this->parseBackground(),
            'Scenario' => $this->parseScenario(),
            'Outline' => $this->parseOutline(),
            'Examples' => $this->parseExamples(),
            'TableRow' => $this->parseTable(),
            'PyStringOp' => $this->parsePyString(),
            'Step' => $this->parseStep(),
            'Text' => $this->parseText(),
            'Newline' => $this->parseNewline(),
            'Tag' => $this->parseTags(),
            'Language' => $this->parseLanguage(),
            'EOS' => '',
            default => throw new ParserException(sprintf('Unknown token type: %s', $type)),
        };
    }

    /**
     * Parses feature token & returns it's node.
     *
     * @return FeatureNode
     *
     * @throws ParserException
     */
    protected function parseFeature()
    {
        $token = $this->expectTokenType('Feature');
        \assert(\array_key_exists('keyword', $token));
        \assert(\array_key_exists('indent', $token));

        $title = trim($token['value'] ?? '');
        $description = null;
        $tags = $this->popTags();
        $background = null;
        $scenarios = [];
        $keyword = $token['keyword'];
        $language = $this->lexer->getLanguage();
        $file = $this->file;
        $line = $token['line'];

        // Parse description, background, scenarios & outlines
        while ($this->predictTokenType() !== 'EOS') {
            $node = $this->parseExpression();

            if (is_string($node)) {
                $text = preg_replace('/^\s{0,' . ($token['indent'] + 2) . '}|\s*$/', '', $node);
                $description .= ($description !== null ? "\n" : '') . $text;
                continue;
            }

            $isBackgroundAllowed = ($background === null && $scenarios === []);

            if ($isBackgroundAllowed && $node instanceof BackgroundNode) {
                $background = $node;
                continue;
            }

            if ($node instanceof ScenarioInterface) {
                $scenarios[] = $node;
                continue;
            }

            throw new UnexpectedParserNodeException(
                match ($isBackgroundAllowed) {
                    true => 'Background, Scenario or Outline',
                    false => 'Scenario or Outline',
                },
                $node,
                $this->file,
            );
        }

        return new FeatureNode(
            rtrim($title) ?: null,
            rtrim($description ?? '') ?: null,
            $tags,
            $background,
            $scenarios,
            $keyword,
            $language,
            $file,
            $line
        );
    }

    /**
     * Parses background token & returns it's node.
     *
     * @return BackgroundNode
     *
     * @throws ParserException
     */
    protected function parseBackground()
    {
        $token = $this->expectTokenType('Background');
        \assert(\array_key_exists('keyword', $token));
        \assert(\array_key_exists('indent', $token));

        $title = trim($token['value'] ?? '');
        $keyword = $token['keyword'];
        $line = $token['line'];

        if (count($this->popTags()) !== 0) {
            // Should not be possible to happen, parseTags should have already picked this up.
            throw new UnexpectedTaggedNodeException($token, $this->file);
        }

        // Parse description and steps
        $steps = [];
        $allowedTokenTypes = ['Step', 'Newline', 'Text', 'Comment'];
        while (in_array($this->predictTokenType(), $allowedTokenTypes)) {
            $node = $this->parseExpression();

            if ($node instanceof StepNode) {
                $steps[] = $this->normalizeStepNodeKeywordType($node, $steps);
                continue;
            }

            if ($steps === [] && is_string($node)) {
                $text = preg_replace('/^\s{0,' . ($token['indent'] + 2) . '}|\s*$/', '', $node);
                $title .= "\n" . $text;
                continue;
            }

            if ($node === "\n") {
                continue;
            }

            throw new UnexpectedParserNodeException('Step', $node, $this->file);
        }

        return new BackgroundNode(rtrim($title) ?: null, $steps, $keyword, $line);
    }

    /**
     * Parses scenario token & returns it's node.
     *
     * @return OutlineNode|ScenarioNode
     *
     * @throws ParserException
     */
    protected function parseScenario()
    {
        return $this->parseScenarioOrOutlineBody($this->expectTokenType('Scenario'));
    }

    /**
     * Parses scenario outline token & returns it's node.
     *
     * @return OutlineNode|ScenarioNode
     *
     * @throws ParserException
     */
    protected function parseOutline()
    {
        return $this->parseScenarioOrOutlineBody($this->expectTokenType('Outline'));
    }

    /**
     * @phpstan-param TToken $token
     */
    private function parseScenarioOrOutlineBody(array $token): OutlineNode|ScenarioNode
    {
        \assert(\array_key_exists('keyword', $token));
        \assert(\array_key_exists('indent', $token));
        $title = trim($token['value'] ?? '');
        $tags = $this->popTags();
        $keyword = $token['keyword'];

        /** @var list<ExampleTableNode> $examples */
        $examples = [];
        $line = $token['line'];

        // Parse description, steps and examples
        $steps = [];

        while (in_array($nextTokenType = $this->predictTokenType(), ['Step', 'Examples', 'Newline', 'Text', 'Comment', 'Tag'])) {
            if ($nextTokenType === 'Comment') {
                $this->lexer->skipPredictedToken();
                continue;
            }

            if ($nextTokenType === 'Tag') {
                // The only thing inside a Scenario / Scenario Outline that can be tagged is an Examples table
                // Scan on to see what the tags are attached to - if it's not Examples then we must have reached the
                // end of this scenario and be about to start a new one.
                if ($this->validateAndGetNextTaggedNodeType() !== 'Examples') {
                    break;
                }
            }

            $node = $this->parseExpression();

            if ($steps === [] && is_string($node)) {
                // Free text is only allowed before the first step (or when parsing Examples: which is done elsewhere)
                $text = preg_replace('/^\s{0,' . ($token['indent'] + 2) . '}|\s*$/', '', $node);
                $title .= "\n" . $text;
                continue;
            }

            if ($node === "\n") {
                continue;
            }

            if ($examples === [] && $node instanceof StepNode) {
                // Steps are only allowed before the first Examples table (if any)
                $steps[] = $this->normalizeStepNodeKeywordType($node, $steps);
                continue;
            }

            if ($node instanceof ExampleTableNode) {
                // NB: It is valid to have a Scenario with Examples: but no Steps
                // It is also valid to have an Examples: with no table rows (this produces no actual examples)
                $examples[] = $node;
                continue;
            }

            throw new UnexpectedParserNodeException(
                match ($examples) {
                    [] => 'Step, Examples table, or end of Scenario',
                    default => 'Examples table or end of Scenario',
                },
                $node,
                $this->file,
            );
        }

        if ($examples !== []) {
            return new OutlineNode(rtrim($title) ?: null, $tags, $steps, $examples, $keyword, $line);
        }

        return new ScenarioNode(rtrim($title) ?: null, $tags, $steps, $keyword, $line);
    }

    /**
     * Peek ahead to find the node that the current tags belong to.
     *
     * @throws UnexpectedTaggedNodeException if there is not a taggable node
     */
    private function validateAndGetNextTaggedNodeType(): string
    {
        $deferred = [];
        try {
            while (true) {
                $deferred[] = $next = $this->lexer->getAdvancedToken();
                $nextType = $next['type'];

                if (in_array($nextType, ['Tag', 'Comment', 'Newline'], true)) {
                    // These are the only node types allowed between tag node(s) and the node they are tagging
                    continue;
                }

                if (in_array($nextType, ['Feature', 'Examples', 'Scenario', 'Outline'], true)) {
                    // These are the only taggable node types
                    return $nextType;
                }

                throw new UnexpectedTaggedNodeException($next, $this->file);
            }
        } finally {
            // Rewind the lexer back to where it was when we started scanning ahead
            foreach ($deferred as $token) {
                $this->lexer->deferToken($token);
            }
        }
    }

    /**
     * Parses step token & returns it's node.
     *
     * @return StepNode
     */
    protected function parseStep()
    {
        $token = $this->expectTokenType('Step');
        \assert(\is_string($token['value']));
        \assert(\array_key_exists('keyword_type', $token));
        \assert(\array_key_exists('text', $token));

        $keyword = $token['value'];
        $keywordType = $token['keyword_type'];
        $text = trim($token['text']);
        $line = $token['line'];

        $arguments = [];
        while (in_array($predicted = $this->predictTokenType(), ['PyStringOp', 'TableRow', 'Newline', 'Comment'])) {
            if ($predicted === 'Comment' || $predicted === 'Newline') {
                $this->acceptTokenType($predicted);
                continue;
            }

            $node = $this->parseExpression();

            if ($node instanceof PyStringNode || $node instanceof TableNode) {
                $arguments[] = $node;
            }
        }

        return new StepNode($keyword, $text, $arguments, $line, $keywordType);
    }

    /**
     * Parses examples table node.
     *
     * @return ExampleTableNode
     */
    protected function parseExamples()
    {
        $token = $this->expectTokenType('Examples');
        \assert(\array_key_exists('keyword', $token));
        $keyword = $token['keyword'];
        $tags = empty($this->tags) ? [] : $this->popTags();
        $table = $this->parseTableRows();

        try {
            return new ExampleTableNode($table, $keyword, $tags);
        } catch (NodeException $e) {
            $this->rethrowNodeException($e);
        }
    }

    /**
     * Parses table token & returns it's node.
     *
     * @return TableNode
     */
    protected function parseTable()
    {
        $table = $this->parseTableRows();

        try {
            return new TableNode($table);
        } catch (NodeException $e) {
            $this->rethrowNodeException($e);
        }
    }

    /**
     * Parses PyString token & returns it's node.
     *
     * @return PyStringNode
     */
    protected function parsePyString()
    {
        $token = $this->expectTokenType('PyStringOp');

        $line = $token['line'];

        $strings = [];
        while ('PyStringOp' !== ($predicted = $this->predictTokenType()) && $predicted === 'Text') {
            $token = $this->expectTokenType('Text');
            \assert(\is_string($token['value']));

            $strings[] = $token['value'];
        }

        $this->expectTokenType('PyStringOp');

        return new PyStringNode($strings, $line);
    }

    /**
     * Parses tags.
     *
     * @phpstan-return TParsedExpressionResult
     */
    protected function parseTags()
    {
        $token = $this->expectTokenType('Tag');
        \assert(\array_key_exists('tags', $token));

        // Validate that the tags are followed by a node that can be tagged
        $this->validateAndGetNextTaggedNodeType();

        $this->guardTags($token['tags']);

        $this->tags = array_merge($this->tags, $token['tags']);

        return "\n";
    }

    /**
     * Returns current set of tags and clears tag buffer.
     *
     * @return list<string>
     */
    protected function popTags()
    {
        $tags = $this->tags;
        $this->tags = [];

        return $tags;
    }

    /**
     * Checks the tags fit the required format.
     *
     * @param string[] $tags
     *
     * @return void
     */
    protected function guardTags(array $tags)
    {
        foreach ($tags as $tag) {
            if (preg_match('/\s/', $tag)) {
                trigger_error(
                    sprintf('Whitespace in tags is deprecated, found "%s"', $tag),
                    E_USER_DEPRECATED
                );
            }
        }
    }

    /**
     * Parses next text line & returns it.
     *
     * @return string
     */
    protected function parseText()
    {
        $token = $this->expectTokenType('Text');
        \assert(\is_string($token['value']));

        return $token['value'];
    }

    /**
     * Parses next newline & returns \n.
     *
     * @return string
     */
    protected function parseNewline()
    {
        $this->expectTokenType('Newline');

        return "\n";
    }

    /**
     * Skips over language tags (they are handled inside the Lexer).
     *
     * @phpstan-return TParsedExpressionResult
     *
     * @throws ParserException
     *
     * @deprecated language tags are handled inside the Lexer, they skipped over (like any other comment) in the Parser
     */
    protected function parseLanguage()
    {
        $this->expectTokenType('Language');

        return $this->parseExpression();
    }

    /**
     * Parses the rows of a table.
     *
     * @return array<int, list<string>>
     */
    private function parseTableRows()
    {
        $table = [];
        while (in_array($predicted = $this->predictTokenType(), ['TableRow', 'Newline', 'Comment'])) {
            if ($predicted === 'Comment' || $predicted === 'Newline') {
                $this->acceptTokenType($predicted);
                continue;
            }

            $token = $this->expectTokenType('TableRow');
            \assert(\array_key_exists('columns', $token));

            $table[$token['line']] = $token['columns'];
        }

        return $table;
    }

    /**
     * Changes step node type for types But, And to type of previous step if it exists else sets to Given.
     *
     * @param StepNode[] $steps
     *
     * @return StepNode
     */
    private function normalizeStepNodeKeywordType(StepNode $node, array $steps = [])
    {
        if (!in_array($node->getKeywordType(), ['And', 'But'])) {
            return $node;
        }

        if ($prev = end($steps)) {
            $keywordType = $prev->getKeywordType();
        } else {
            $keywordType = 'Given';
        }

        return new StepNode(
            $node->getKeyword(),
            $node->getText(),
            $node->getArguments(),
            $node->getLine(),
            $keywordType
        );
    }

    private function rethrowNodeException(NodeException $e): never
    {
        throw new ParserException(
            $e->getMessage() . ($this->file ? ' in file ' . $this->file : ''),
            0,
            $e
        );
    }
}
