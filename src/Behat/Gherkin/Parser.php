<?php

namespace Behat\Gherkin;

use Behat\Gherkin\Exception\Exception,
    Behat\Gherkin\Node;

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
    public function parse($input)
    {
        $features = array();
        $language = 'en';

        if (is_file($input)) {
            $this->file = $input;
            $input      = file_get_contents($this->file);
        } else {
            $this->file = null;
        }

        $this->lexer->setInput($input);
        $this->lexer->setLanguage($language);

        while ('EOS' !== $this->lexer->predictToken()->type) {
            if ('Newline' === $this->lexer->predictToken()->type) {
                $this->lexer->getAdvancedToken();
            } elseif ('Comment' === $this->lexer->predictToken()->type) {
                $matches = array();
                if (preg_match('/^ *language: *([\w_\-]+)/', $this->parseExpression(), $matches)) {
                    $this->lexer->setLanguage($language = $matches[1]);
                }
            } elseif ('Feature' === $this->lexer->predictToken()->type) {
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
        if ($type === $this->lexer->predictToken()->type) {
            return $this->lexer->getAdvancedToken();
        } else {
            throw new Exception(sprintf('Expected %s, but got %s on line: %d%s',
                $type, $this->lexer->predictToken()->type, $this->lexer->getCurrentLine(),
                $this->file ? ' in file: ' . $this->file : ''
            ));
        }
    }

    /**
     * Parse current expression & return Node.
     * 
     * @return  string|Node\*
     */
    protected function parseExpression()
    {
        switch ($this->lexer->predictToken()->type) {
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
        $this->skipNewlines();

        // Parse tags
        if ('Tag' === $this->lexer->predictToken()->type) {
            $node->setTags($this->lexer->getAdvancedToken()->tags);
        }

        // Parse feature description
        while ('Text' === $this->lexer->predictToken()->type) {
            $text = trim($this->parseExpression());
            if (null === $node->getDescription()) {
                $node->setDescription($text);
            } else {
                $node->setDescription($node->getDescription() . "\n" . $text);
            }
        }

        // Parse background
        if ('Background' === $this->lexer->predictToken()->type) {
            $node->setBackground($this->parseExpression());
        }

        // Parse scenarios & outlines
        while ('Scenario' === $this->lexer->predictToken()->type
            || 'Outline' === $this->lexer->predictToken()->type) {
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
        $this->skipNewlines();

        // Parse steps
        while ('Step' === $this->lexer->predictToken()->type) {
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
        $this->skipNewlines();

        // Parse tags
        if ('Tag' === $this->lexer->predictToken()->type) {
            $node->setTags($this->lexer->getAdvancedToken()->tags);
        }

        // Parse scenario title
        while ('Text' === $this->lexer->predictToken()->type) {
            $text = trim($this->parseExpression());
            if (null === $node->getTitle()) {
                $node->setTitle($text);
            } else {
                $node->setTitle($node->getTitle() . "\n" . $text);
            }
        }

        // Parse steps
        while ('Step' === $this->lexer->predictToken()->type) {
            $node->addStep($this->parseExpression());
        }

        // Examples block
        $this->expectTokenType('Examples');
        $this->skipNewlines();

        // Parse examples table
        $node->setExamples($this->parseTable());

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
        $this->skipNewlines();

        // Parse tags
        if ('Tag' === $this->lexer->predictToken()->type) {
            $node->setTags($this->lexer->getAdvancedToken()->tags);
        }

        // Parse scenario title
        while ('Text' === $this->lexer->predictToken()->type) {
            $text = trim($this->parseExpression());
            if (null === $node->getTitle()) {
                $node->setTitle($text);
            } else {
                $node->setTitle($node->getTitle() . "\n" . $text);
            }
        }

        // Parse scenario steps
        while ('Step' === $this->lexer->predictToken()->type) {
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
        while ('Text' === $this->lexer->predictToken()->type) {
            $text = trim($this->parseExpression());
            if (null === $node->getText()) {
                $node->setText($text);
            } else {
                $node->setText($node->getText() . "\n" . $text);
            }
        }

        // Parse PyString argument
        if ('PyStringOperator' === $this->lexer->predictToken()->type) {
            $node->addArgument($this->parseExpression());
        }

        // Parse Table argument
        if ('TableRow' === $this->lexer->predictToken()->type) {
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

        while ('TableRow' === $this->lexer->predictToken()->type) {
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
        $this->skipNewlines();

        while ('PyStringOperator' !== $this->lexer->predictToken()->type
            && 'Text' === $this->lexer->predictToken()->type) {
            $node->addLine($this->parseExpression());
        }
        $this->expectTokenType('PyStringOperator');
        $this->skipNewlines();

        return $node;
    }

    /**
     * Parse next comment token.
     * 
     * @return  string
     */
    protected function parseComment()
    {
        $token = $this->expectTokenType('Comment');
        $this->skipNewlines();

        return $token->value;
    }

    /**
     * Parse next text token.
     * 
     * @return  string
     */
    protected function parseText()
    {
        $token = $this->expectTokenType('Text');
        $this->skipNewlines();

        return $token->value;
    }

    /**
     * Skip newlines in input.
     */
    private function skipNewlines()
    {
        while ('Newline' === $this->lexer->predictToken()->type
            || 'Comment' === $this->lexer->predictToken()->type) {
            $this->lexer->getAdvancedToken();
        }
    }
}
