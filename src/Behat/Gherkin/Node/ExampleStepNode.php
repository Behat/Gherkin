<?php

namespace Behat\Gherkin\Node;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Outline example step Gherkin AST node.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ExampleStepNode extends StepNode
{
    private $cleanText;

    /**
     * Initizalizes step.
     *
     * @param StepNode $simpleStep Initial step
     * @param array    $tokens     Example table row tokens
     */
    public function __construct(StepNode $simpleStep, array $tokens)
    {
        $text = $this->cleanText = $simpleStep->getText();
        foreach ($tokens as $key => $value) {
            $text = str_replace('<' . $key . '>', $value, $text);
        }

        parent::__construct(
            $simpleStep->getType(),
            $text,
            $simpleStep->getLine()
        );

        foreach ($simpleStep->getArguments() as $argument) {
            if ($argument instanceof TableNode || $argument instanceof PyStringNode) {
                $this->addArgument($argument->createExampleRowStepArgument($tokens));
            }
        }

        $this->setParent($simpleStep->getParent());
    }

    /**
     * Returns untokenized step text.
     *
     * @return string
     */
    public function getCleanText()
    {
        return $this->cleanText;
    }
}
