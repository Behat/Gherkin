<?php

namespace Behat\Gherkin;

use Behat\Gherkin\Exception\ParserException,
    Behat\Gherkin\Exception\LexerException,
    Behat\Gherkin\Node;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Gherkin parser.
 *
 * $lexer  = new Behat\Gherkin\Lexer($keywords);
 * $parser = new Behat\Gherkin\Parser($lexer);
 * $featuresArray = $parser->parse('/path/to/feature.feature');
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Parser
{
    private $file;
    private $lexer;

    /**
     * Initializes parser.
     *
     * @param Lexer $lexer Lexer instance
     */
    public function __construct(Lexer $lexer)
    {
        $this->lexer = $lexer;
    }

    /**
     * Parses input & returns features array.
     *
     * @param string $input Gherkin string document
     *
     * @return array
     *
     * @throws ParserException
     */
    public function parse($input, $file = null)
    {
        $this->file = $file;

        try {
            $this->lexer->setInput($input);
        } catch (LexerException $e) {
            throw new ParserException(
                sprintf('Lexer exception "%s" throwed for file %s', $e->getMessage(), $file)
            );
        }

        $this->lexer->setLanguage($language = 'en');
        $languageSpecifierLine = null;

        $feature = null;
        while ('EOS' !== ($predicted = $this->predictTokenType())) {
            if ('Newline' === $predicted || 'Comment' === $predicted) {
                $this->lexer->getAdvancedToken();
            } elseif ('Language' === $predicted) {
                $token      = $this->expectTokenType('Language');
                $language   = $token->value;

                if (null === $languageSpecifierLine) {
                    // Reparse input with new language
                    $languageSpecifierLine = $token->line;
                    $this->lexer->setInput($input);
                    $this->lexer->setLanguage($language);
                } elseif ($languageSpecifierLine !== $token->line) {
                    // Language already specified
                    throw new ParserException(sprintf(
                        'Ambigious language specifiers on lines: %d and %d%s',
                        $languageSpecifierLine,
                        $token->line,
                        $this->file ? ' in file: ' . $this->file : ''
                    ));
                }
            } elseif (null === $feature &&
                ('Feature' === $predicted || (
                     'Tag' === $predicted && 'Feature' === $this->predictTokenType(2))
                )) {
                $feature = $this->parseExpression();
                $feature->setLanguage($language);
            } else {
                $this->expectTokenType(array('Comment', 'Scenario', 'Outline', 'Step'));
            }
        }

        return $feature;
    }

    /**
     * Returns next token if it's type equals to expected.
     *
     * @param string $types Token type
     *
     * @return stdClass
     *
     * @throws ParserException if token type is differ from expected one
     */
    protected function expectTokenType($type)
    {
        $types = (array) $type;
        if (in_array($this->predictTokenType(), $types)) {
            return $this->lexer->getAdvancedToken();
        }

        throw new ParserException(sprintf('Expected %s token, but got %s on line: %d%s',
            implode(' or ', $types), $this->predictTokenType(), $this->lexer->predictToken()->line,
            $this->file ? ' in file: ' . $this->file : ''
        ));
    }

    /**
     * Returns next token if it's type equals to expected.
     *
     * @param string $type Token type
     *
     * @return stdClass
     */
    protected function acceptTokenType($type)
    {
        if ($type === $this->predictTokenType()) {
            return $this->lexer->getAdvancedToken();
        }
    }

    /**
     * Returns next token type without real input reading (prediction).
     *
     * @param integer $number Number of tokens to predict
     *
     * @return string
     */
    protected function predictTokenType($number = 1)
    {
        return $this->lexer->predictToken($number)->type;
    }

    /**
     * Parses current expression & returns Node.
     *
     * @return string|AbstractNode
     */
    protected function parseExpression()
    {
        switch ($this->predictTokenType()) {
            case 'Feature':
                return $this->parseFeature();
            case 'Background':
                return $this->parseBackground();
            case 'Scenario':
                return $this->parseScenario();
            case 'Outline':
                return $this->parseOutline();
            case 'TableRow':
                return $this->parseTable();
            case 'PyStringOperator':
                return $this->parsePyString();
            case 'Step':
                return $this->parseStep();
            case 'Comment':
                return $this->parseComment();
            case 'Text':
                return $this->parseText();
            case 'Tag':
                $token = $this->lexer->getAdvancedToken();
                $this->skipExtraChars();
                $this->lexer->deferToken($this->lexer->getAdvancedToken());
                $this->lexer->deferToken($token);

                return $this->parseExpression();
        }
    }

    /**
     * Parses feature token & returns it's node.
     *
     * @return FeatureNode
     */
    protected function parseFeature()
    {
        $token  = $this->expectTokenType('Feature');
        $node   = new Node\FeatureNode(trim($token->value) ?: null, null, $this->file, $token->line);

        $node->setKeyword($token->keyword);

        // Parse tags
        $this->parseNodeTags($node);

        // Parse description
        $this->parseNodeDescription($node, $token->indent+2);

        // Parse background
        if ('Background' === $this->predictTokenType()) {
            $node->setBackground($this->parseExpression());
        }

        // Parse scenarios & outlines
        while ('Scenario' === ($predicted = $this->predictTokenType())
            || ('Tag' === $predicted && 'Scenario' === ($predicted2 = $this->predictTokenType(2)))
            || 'Outline' === $predicted
            || ('Tag' === $predicted && 'Outline' === $predicted2)) {
            $node->addScenario($this->parseExpression());
        }

        return $node;
    }

    /**
     * Parses background token & returns it's node.
     *
     * @return BackgroundNode
     */
    protected function parseBackground()
    {
        $token  = $this->expectTokenType('Background');
        $node   = new Node\BackgroundNode(trim($token->value) ?: null, $token->line);

        $node->setKeyword($token->keyword);
        $this->skipComments();

        // Parse title
        $this->parseNodeDescription($node, $token->indent+2);

        // Parse steps
        while ('Step' === $this->predictTokenType()) {
            $node->addStep($this->parseExpression());
        }

        return $node;
    }

    /**
     * Parses scenario outline token & returns it's node.
     *
     * @return OutlineNode
     */
    protected function parseOutline()
    {
        $token  = $this->expectTokenType('Outline');
        $node   = new Node\OutlineNode(trim($token->value) ?: null, $token->line);

        $node->setKeyword($token->keyword);

        // Parse tags
        $this->parseNodeTags($node);

        // Parse title
        $this->parseNodeDescription($node, $token->indent+2);

        // Parse steps
        while ('Step' === $this->predictTokenType()) {
            $node->addStep($this->parseExpression());
        }

        // Examples block
        $examplesToken = $this->expectTokenType('Examples');
        $this->skipExtraChars();

        // Parse examples table
        $table = $this->parseTable();
        $table->setKeyword($examplesToken->keyword);
        $node->setExamples($table);

        return $node;
    }

    /**
     * Parses scenario token & returns it's node.
     *
     * @return ScenarioNode
     */
    protected function parseScenario()
    {
        $token  = $this->expectTokenType('Scenario');
        $node   = new Node\ScenarioNode(trim($token->value) ?: null, $token->line);

        $node->setKeyword($token->keyword);

        // Parse tags
        $this->parseNodeTags($node);

        // Parse title
        $this->parseNodeDescription($node, $token->indent+2);

        // Parse scenario steps
        while ('Step' === $this->predictTokenType()) {
            $node->addStep($this->parseExpression());
        }

        return $node;
    }

    /**
     * Parses step token & returns it's node.
     *
     * @return StepNode
     */
    protected function parseStep()
    {
        $token  = $this->expectTokenType('Step');
        $node   = new Node\StepNode($token->value, trim($token->text) ?: null, $token->line);

        $this->skipExtraChars();

        // Parse PyString argument
        if ('PyStringOperator' === $this->predictTokenType()) {
            $node->addArgument($this->parseExpression());
        }

        // Parse Table argument
        if ('TableRow' === $this->predictTokenType()) {
            $node->addArgument($this->parseExpression());
        }

        return $node;
    }

    /**
     * Parses table token & returns it's node.
     *
     * @return TableNode
     */
    protected function parseTable()
    {
        $token  = $this->expectTokenType('TableRow');
        $node   = new Node\TableNode();
        $node->addRow($token->columns, $token->line);
        $this->skipExtraChars();

        while ('TableRow' === $this->predictTokenType()) {
            $token = $this->expectTokenType('TableRow');
            $node->addRow($token->columns, $token->line);
            $this->skipExtraChars();
        }

        return $node;
    }

    /**
     * Parses PyString token & returns it's node.
     *
     * @return PyStringNode
     */
    protected function parsePyString()
    {
        $token  = $this->expectTokenType('PyStringOperator');
        $node   = new Node\PyStringNode();

        while ('PyStringOperator' !== ($predicted = $this->predictTokenType()) && 'Text' === $predicted) {
            $node->addLine($this->parseText(false));
        }

        $this->expectTokenType('PyStringOperator');
        $this->skipExtraChars();

        return $node;
    }

    /**
     * Parses next text token & returns it's string content.
     *
     * @param Boolean $skipExtraChars Do we need to skip newlines & spaces
     *
     * @return string
     */
    protected function parseText($skipExtraChars = true)
    {
        $token = $this->expectTokenType('Text');

        if ($skipExtraChars) {
            $this->skipExtraChars();
        }

        return $token->value;
    }

    /**
     * Parses next comment token & returns it's string content.
     *
     * @return string
     */
    protected function parseComment()
    {
        $token = $this->expectTokenType('Comment');

        return $token->value;
    }

    /**
     * Parse tags for the feature/scenario/outline node.
     *
     * @param AbstractNode $node Node with tags
     */
    private function parseNodeTags(Node\AbstractNode $node)
    {
        $this->skipComments();

        while ('Tag' === $this->predictTokenType()) {
            $node->setTags($this->lexer->getAdvancedToken()->tags);
            $this->skipComments();
        }
    }

    /**
     * Parse description/title for feature/background/scenario/outline node.
     *
     * @param AbstractNode $node        Node with description
     * @param integer      $indentation Indentation
     */
    private function parseNodeDescription(Node\AbstractNode $node, $indentation)
    {
        $setter = 'setTitle';
        $getter = 'getTitle';
        if ($node instanceof Node\FeatureNode) {
            $setter = 'setDescription';
            $getter = 'getDescription';
        }

        // Parse description/title
        while (in_array($predicted = $this->predictTokenType(), array('Text', 'Newline'))) {
            if ('Text' === $predicted) {
                $text = $this->parseText(false);
                $text = preg_replace('/^\s{0,'.$indentation.'}|\s*$/', '', $text);
            } else {
                $this->acceptTokenType('Newline');
                $text = '';
            }

            if ($node instanceof Node\FeatureNode && null === $node->$getter()) {
                $node->$setter($text);
            } else {
                $node->$setter($node->$getter() . "\n" . $text);
            }

            $this->skipComments();
        }

        // Trim title/description
        if (null !== $node->$getter()) {
            $node->$setter(rtrim($node->$getter()) ?: null);
        }
    }

    /**
     * Skips newlines & comments in input.
     *
     * @param Boolean $skipNL Skip newline?
     */
    private function skipExtraChars()
    {
        while ($this->acceptTokenType('Newline') || $this->acceptTokenType('Comment'));
    }

    /**
     * Skips newlines & comments in input.
     */
    private function skipComments()
    {
        while ($this->acceptTokenType('Comment'));
    }
}
