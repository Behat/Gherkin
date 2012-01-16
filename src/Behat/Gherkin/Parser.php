<?php

namespace Behat\Gherkin;

use Behat\Gherkin\Exception\ParserException,
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
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Parser
{
    private $file;
    private $lexer;

    /**
     * Initializes parser.
     *
     * @param   Behat\Gherkin\Lexer     $lexer  lexer instance
     */
    public function __construct(Lexer $lexer)
    {
        $this->lexer = $lexer;
    }

    /**
     * Parses input & returns features array.
     *
     * @param   string          $input  gherkin string document
     *
     * @return  array                   array of feature nodes
     *
     * @throws  Behat\Gherkin\Exception\ParserException if feature file has more than one language specifier
     */
    public function parse($input, $file = null)
    {
        $this->file = $file;

        $this->lexer->setInput($input);
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
     * @param   string  $types  expected type
     *
     * @return  stdClass
     *
     * @throws  Behat\Gherkin\Exception\ParserException if token type is differ from expected one
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
     * @param   string  $type   type
     *
     * @return  stdClass
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
     * @param   integer     $number number of tokens to predict
     *
     * @return  string              predicted token type
     */
    protected function predictTokenType($number = 1)
    {
        return $this->lexer->predictToken($number)->type;
    }

    /**
     * Parses current expression & returns Node.
     *
     * @return  string|Behat\Gherkin\Node\AbstractNode
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
     * @return  Behat\Gherkin\Node\FeatureNode
     */
    protected function parseFeature()
    {
        $token  = $this->expectTokenType('Feature');
        $node   = new Node\FeatureNode(trim($token->value) ?: null, null, $this->file, $token->line);

        $node->setKeyword($token->keyword);
        $this->skipComments();

        // Parse defered tags
        if ('Tag' === $this->predictTokenType() && $this->lexer->predictToken()->defered) {
            $node->setTags($this->lexer->getAdvancedToken()->tags);
            $this->skipComments();
        }

        // Parse feature description
        while (in_array($predicted = $this->predictTokenType(), array('Text', 'Newline'))) {
            if ('Text' === $predicted) {
                $text = $this->parseText(false);
                $text = preg_replace('/^\s{1,'.($token->indent+2).'}|\s*$/', '', $text);
            } else {
                $this->acceptTokenType('Newline');
                $text = '';
            }

            if (null === $node->getDescription()) {
                $node->setDescription($text);
            } else {
                $node->setDescription($node->getDescription() . "\n" . $text);
            }

            $this->skipComments();
        }

        // Trim description end
        if (null !== $node->getDescription()) {
            $node->setDescription(rtrim($node->getDescription()));
        }

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
     * @return  Behat\Gherkin\Node\BackgroundNode
     */
    protected function parseBackground()
    {
        $token  = $this->expectTokenType('Background');
        $node   = new Node\BackgroundNode($token->line);

        $node->setKeyword($token->keyword);
        $this->skipExtraChars();

        // Parse steps
        while ('Step' === $this->predictTokenType()) {
            $node->addStep($this->parseExpression());
        }

        return $node;
    }

    /**
     * Parses scenario outline token & returns it's node.
     *
     * @return  Behat\Gherkin\Node\OutlineNode
     */
    protected function parseOutline()
    {
        $token  = $this->expectTokenType('Outline');
        $node   = new Node\OutlineNode(trim($token->value) ?: null, $token->line);

        $node->setKeyword($token->keyword);
        $this->skipComments();

        // Parse tags
        while ('Tag' === $this->predictTokenType()) {
            $node->setTags($this->lexer->getAdvancedToken()->tags);
            $this->skipComments();
        }

        // Parse scenario title
        while (in_array($predicted = $this->predictTokenType(), array('Text', 'Newline'))) {
            if ('Text' === $predicted) {
                $text = $this->parseText(false);
                $text = preg_replace('/^\s{1,'.($token->indent+2).'}|\s*$/', '', $text);
            } else {
                $this->acceptTokenType('Newline');
                $text = '';
            }

            if (null === $node->getTitle()) {
                $node->setTitle($text);
            } else {
                $node->setTitle($node->getTitle() . "\n" . $text);
            }

            $this->skipComments();
        }

        // Trim title end
        if (null !== $node->getTitle()) {
            $node->setTitle(rtrim($node->getTitle()));
        }

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
     * @return  Behat\Gherkin\Node\ScenarioNode
     */
    protected function parseScenario()
    {
        $token  = $this->expectTokenType('Scenario');
        $node   = new Node\ScenarioNode(trim($token->value) ?: null, $token->line);

        $node->setKeyword($token->keyword);
        $this->skipComments();

        // Parse tags
        while ('Tag' === $this->predictTokenType()) {
            $node->setTags($this->lexer->getAdvancedToken()->tags);
            $this->skipComments();
        }

        // Parse scenario title
        while (in_array($predicted = $this->predictTokenType(), array('Text', 'Newline'))) {
            if ('Text' === $predicted) {
                $text = $this->parseText(false);
                $text = preg_replace('/^\s{1,'.($token->indent+2).'}|\s*$/', '', $text);
            } else {
                $this->acceptTokenType('Newline');
                $text = '';
            }

            if (null === $node->getTitle()) {
                $node->setTitle($text);
            } else {
                $node->setTitle($node->getTitle() . "\n" . $text);
            }

            $this->skipComments();
        }

        // Trim title end
        if (null !== $node->getTitle()) {
            $node->setTitle(rtrim($node->getTitle()));
        }

        // Parse scenario steps
        while ('Step' === $this->predictTokenType()) {
            $node->addStep($this->parseExpression());
        }

        return $node;
    }

    /**
     * Parses step token & returns it's node.
     *
     * @return  Behat\Gherkin\Node\StepNode
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
     * @return  Behat\Gherkin\Node\TableNode
     */
    protected function parseTable()
    {
        $token  = $this->expectTokenType('TableRow');
        $node   = new Node\TableNode();
        $node->addRow($token->columns);
        $this->skipExtraChars();

        while ('TableRow' === $this->predictTokenType()) {
            $token = $this->expectTokenType('TableRow');
            $node->addRow($token->columns);
            $this->skipExtraChars();
        }

        return $node;
    }

    /**
     * Parses PyString token & returns it's node.
     *
     * @return  Behat\Gherkin\Node\PyStringNode
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
     * @param   boolean $skipExtraChars do we need to skip newlines & spaces
     *
     * @return  string
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
     * @return  string
     */
    protected function parseComment()
    {
        $token = $this->expectTokenType('Comment');

        return $token->value;
    }

    /**
     * Skips newlines & comments in input.
     *
     * @param   Boolean $skipNL
     */
    private function skipExtraChars()
    {
        while ($this->acceptTokenType('Newline') || $this->acceptTokenType('Comment'));
    }

    /**
     * Skips newlines & comments in input.
     *
     * @param   Boolean $skipNL
     */
    private function skipComments()
    {
        while ($this->acceptTokenType('Comment'));
    }
}
