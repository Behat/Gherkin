<?php

namespace Behat\Gherkin;

use Behat\Gherkin\Exception\Exception,
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
 * $parser = new Parser($lexer);
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
     * @param   string          $input  gherkin filename or string document
     *
     * @return  array                   array of feature nodes
     */
    public function parse($input, $file = null)
    {
        $features = array();

        if (is_file($input)) {
            $this->file = $input;
            $input      = file_get_contents($this->file);
        } else {
            $this->file = $file;
        }

        $this->lexer->setInput($input);
        $this->lexer->setLanguage($language = 'en');
        $languageSpecifierLine = null;

        while ('EOS' !== $this->predictTokenType()) {
            if ('Newline' === $this->predictTokenType()
             || 'Comment' === $this->predictTokenType()) {
                $this->lexer->getAdvancedToken();
            } elseif ('Language' === $this->predictTokenType()) {
                $token      = $this->expectTokenType('Language');
                $language   = $token->value;

                if (null === $languageSpecifierLine) {
                    // Reparse input with new language
                    $languageSpecifierLine = $token->line;
                    $this->lexer->setInput($input);
                    $this->lexer->setLanguage($language);
                } elseif ($languageSpecifierLine !== $token->line) {
                    // Language already specified
                    throw new Exception(sprintf('Ambigious language specifiers on lines: %d and %d%s',
                        $languageSpecifierLine, $token->line,
                        $this->file ? ' in file: ' . $this->file : ''
                    ));
                }
            } elseif ('Feature' === $this->predictTokenType()
                   || ('Tag' === $this->predictTokenType() && 'Feature' === $this->predictTokenType(2))) {
                $feature = $this->parseExpression();
                $feature->setLanguage($language);
                $features[] = $feature;
            } else {
                $this->expectTokenType('Feature');
            }
        }

        return $features;
    }

    /**
     * Returns next token if it's type equals to expected.
     *
     * @param   string  $type   type
     *
     * @return  stdClass
     *
     * @throws  Behat\Gherkin\Exception\Exception   if token type is differ from expected one
     */
    protected function expectTokenType($type)
    {
        if ($type === $this->predictTokenType()) {
            return $this->lexer->getAdvancedToken();
        } else {
            throw new Exception(sprintf('Expected %s, but got %s on line: %d%s',
                $type, $this->predictTokenType(), $this->lexer->predictToken()->line,
                $this->file ? ' in file: ' . $this->file : ''
            ));
        }
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
        $this->skipExtraChars();

        // Parse defered tags
        if ('Tag' === $this->predictTokenType() && $this->lexer->predictToken()->defered) {
            $node->setTags($this->lexer->getAdvancedToken()->tags);
            $this->skipExtraChars();
        }

        // Parse feature description
        while ('Text' === $this->predictTokenType()) {
            $text = trim($this->parseExpression());
            if (null === $node->getDescription()) {
                $node->setDescription($text);
            } else {
                $node->setDescription($node->getDescription() . "\n" . $text);
            }
        }

        // Parse background
        if ('Background' === $this->predictTokenType()) {
            $node->setBackground($this->parseExpression());
        }

        // Parse scenarios & outlines
        while ('Scenario' === $this->predictTokenType()
            || ('Tag' === $this->predictTokenType() && 'Scenario' === $this->predictTokenType(2))
            || 'Outline' === $this->predictTokenType()
            || ('Tag' === $this->predictTokenType() && 'Outline' === $this->predictTokenType(2))) {
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
        $this->skipExtraChars();

        // Parse tags
        if ('Tag' === $this->predictTokenType()) {
            $node->setTags($this->lexer->getAdvancedToken()->tags);
            $this->skipExtraChars();
        }

        // Parse scenario title
        while ('Text' === $this->predictTokenType()) {
            $text = trim($this->parseExpression());
            if (null === $node->getTitle()) {
                $node->setTitle($text);
            } else {
                $node->setTitle($node->getTitle() . "\n" . $text);
            }
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
        $this->skipExtraChars();

        // Parse tags
        if ('Tag' === $this->predictTokenType()) {
            $node->setTags($this->lexer->getAdvancedToken()->tags);
            $this->skipExtraChars();
        }

        // Parse scenario title
        while ('Text' === $this->predictTokenType()) {
            $text = trim($this->parseExpression());
            if (null === $node->getTitle()) {
                $node->setTitle($text);
            } else {
                $node->setTitle($node->getTitle() . "\n" . $text);
            }
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

        // Parse step text
        while ('Text' === $this->predictTokenType()) {
            $text = trim($this->parseExpression());
            if (null === $node->getText()) {
                $node->setText($text);
            } else {
                $node->setText($node->getText() . "\n" . $text);
            }
        }

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
        $node   = new Node\PyStringNode(null, $token->swallow);

        while ('PyStringOperator' !== ($predicted = $this->predictTokenType())
            && ('Text' === $predicted || 'Newline' === $predicted)) {

            if ('Newline' === $predicted) {
                $token = $this->lexer->getAdvancedToken();

                if ('Newline' === $this->predictTokenType()) {
                    $this->lexer->getAdvancedToken();
                    $node->addLine($token->value);
                }
            } else {
                $node->addLine($this->parseText(false));
            }
        }
        $this->expectTokenType('PyStringOperator');
        $this->skipExtraChars();

        return $node;
    }

    /**
     * Parses next text token & returns it's string content.
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
     */
    private function skipExtraChars()
    {
        while ($this->acceptTokenType('Newline') || $this->acceptTokenType('Comment'));
    }
}
