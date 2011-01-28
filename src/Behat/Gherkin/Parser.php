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
 * Gherkin Parser.
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
     * Initialize Parser.
     * 
     * @param   Lexer   $lexer  lexer instance
     */
    public function __construct(Lexer $lexer)
    {
        $this->lexer = $lexer;
    }

    /**
     * Parse input & return features array.
     * 
     * @param   string          $input  Gherkin filename or string document
     *
     * @return  array           array of feature nodes
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
                $language = $this->expectTokenType('Language')->value;

                if (null === $languageSpecifierLine) {
                    // Reparse input with new language
                    $languageSpecifierLine = $this->lexer->getCurrentLine();
                    $this->lexer->setInput($input);
                    $this->lexer->setLanguage($language);
                } elseif ($languageSpecifierLine !== $this->lexer->getCurrentLine()) {
                    // Language already specified
                    throw new Exception(sprintf('Ambigious language specifiers on lines: %d and %d%s',
                        $languageSpecifierLine, $this->lexer->getCurrentLine(),
                        $this->file ? ' in file: ' . $this->file : ''
                    ));
                }
            } elseif ('Feature' === $this->predictTokenType()
                   || ('Tag' === $this->predictTokenType() && 'Feature' === $this->predictTokenType(3))) {
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
     * Expect given type or throw Exception.
     * 
     * @param   string  $type   type
     */
    protected function expectTokenType($type)
    {
        if ($type === $this->predictTokenType()) {
            return $this->lexer->getAdvancedToken();
        } else {
            throw new Exception(sprintf('Expected %s, but got %s on line: %d%s',
                $type, $this->predictTokenType(), $this->lexer->getCurrentLine(),
                $this->file ? ' in file: ' . $this->file : ''
            ));
        }
    }

    /**
     * Predict type for number of tokens.
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
     * Parse current expression & return Node.
     * 
     * @return  string|Node\*
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
                $this->skipNewlines();
                $this->lexer->deferToken($this->lexer->getAdvancedToken());
                $this->lexer->deferToken($token);

                return $this->parseExpression();
        }
    }

    /**
     * Parse Feature & return it's node.
     *
     * @return  Node\FeatureNode
     */
    protected function parseFeature()
    {
        $token  = $this->expectTokenType('Feature');
        $node   = new Node\FeatureNode(
            trim($token->value) ?: null, null, $this->file, $this->lexer->getCurrentLine()
        );

        $node->setKeyword($token->keyword);
        $this->skipNewlines();

        // Parse defered tags
        if ('Tag' === $this->predictTokenType() && $this->lexer->predictToken()->defered) {
            $node->setTags($this->lexer->getAdvancedToken()->tags);
            $this->skipNewlines();
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
            || ('Tag' === $this->predictTokenType() && 'Scenario' === $this->predictTokenType(3))
            || 'Outline' === $this->predictTokenType()
            || ('Tag' === $this->predictTokenType() && 'Outline' === $this->predictTokenType(3))) {
            $node->addScenario($this->parseExpression());
        }

        return $node;
    }

    /**
     * Parse Background & return it's node.
     *
     * @return  Node\BackgroundNode
     */
    protected function parseBackground()
    {
        $token  = $this->expectTokenType('Background');
        $node   = new Node\BackgroundNode($this->lexer->getCurrentLine());

        $node->setKeyword($token->keyword);
        $this->skipNewlines();

        // Parse steps
        while ('Step' === $this->predictTokenType()) {
            $node->addStep($this->parseExpression());
        }

        return $node;
    }

    /**
     * Parse Scenario Outline & return it's node.
     *
     * @return  Node\OutlineNode
     */
    protected function parseOutline()
    {
        $token  = $this->expectTokenType('Outline');
        $node   = new Node\OutlineNode(trim($token->value) ?: null, $this->lexer->getCurrentLine());

        $node->setKeyword($token->keyword);
        $this->skipNewlines();

        // Parse tags
        if ('Tag' === $this->predictTokenType()) {
            $node->setTags($this->lexer->getAdvancedToken()->tags);
            $this->skipNewlines();
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
        $this->skipNewlines();

        // Parse examples table
        $table = $this->parseTable();
        $table->setKeyword($examplesToken->keyword);
        $node->setExamples($table);

        return $node;
    }

    /**
     * Parse Scenario & return it's node.
     *
     * @return  Node\ScenarioNode
     */
    protected function parseScenario()
    {
        $token  = $this->expectTokenType('Scenario');
        $node   = new Node\ScenarioNode(trim($token->value) ?: null, $this->lexer->getCurrentLine());

        $node->setKeyword($token->keyword);
        $this->skipNewlines();

        // Parse tags
        if ('Tag' === $this->predictTokenType()) {
            $node->setTags($this->lexer->getAdvancedToken()->tags);
            $this->skipNewlines();
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
     * Parse Step & return it's node.
     *
     * @return  Node\StepNode
     */
    protected function parseStep()
    {
        $token  = $this->expectTokenType('Step');
        $node   = new Node\StepNode(
            $token->value, trim($token->text) ?: null, $this->lexer->getCurrentLine()
        );

        $this->skipNewlines();

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
     * Parse Table & return it's node.
     *
     * @return  Node\TableNode
     */
    protected function parseTable()
    {
        $token  = $this->expectTokenType('TableRow');
        $node   = new Node\TableNode();
        $node->addRow($token->columns);
        $this->skipNewlines();

        while ('TableRow' === $this->predictTokenType()) {
            $token = $this->expectTokenType('TableRow');
            $node->addRow($token->columns);
            $this->skipNewlines();
        }

        return $node;
    }

    /**
     * Parse PyString & return it's node.
     *
     * @return  Node\PyStringNode
     */
    protected function parsePyString()
    {
        $token  = $this->expectTokenType('PyStringOperator');
        $node   = new Node\PyStringNode(null, $token->swallow);

        while ('PyStringOperator' !== $this->predictTokenType()
            && ('Text' === $this->predictTokenType() || 'Newline' === $this->predictTokenType())) {
            if ('Newline' === $this->predictTokenType()) {
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
        $this->skipNewlines();

        return $node;
    }

    /**
     * Parse next text token.
     * 
     * @return  string
     */
    protected function parseText($skipNewlines = true)
    {
        $token = $this->expectTokenType('Text');

        if ($skipNewlines) {
            $this->skipNewlines();
        }

        return $token->value;
    }

    /**
     * Parse next comment token.
     * 
     * @return  string
     */
    protected function parseComment()
    {
        $token = $this->expectTokenType('Comment');

        return $token->value;
    }

    /**
     * Skip newlines in input.
     */
    private function skipNewlines()
    {
        while ('Newline' === $this->predictTokenType()
            || 'Comment' === $this->predictTokenType()) {
            $this->lexer->getAdvancedToken();
        }
    }
}
