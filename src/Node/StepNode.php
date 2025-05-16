<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Node;

use Behat\Gherkin\Exception\NodeException;

/**
 * Represents Gherkin Step.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class StepNode implements NodeInterface
{
    private readonly string $keywordType;

    /**
     * @param ArgumentInterface[] $arguments
     */
    public function __construct(
        private readonly string $keyword,
        private readonly string $text,
        private readonly array $arguments,
        private readonly int $line,
        ?string $keywordType = null,
    ) {
        if (count($arguments) > 1) {
            throw new NodeException(sprintf(
                'Steps could have only one argument, but `%s %s` have %d.',
                $keyword,
                $text,
                count($arguments)
            ));
        }

        $this->keywordType = $keywordType ?: 'Given';
    }

    /**
     * Returns node type string.
     *
     * @return string
     */
    public function getNodeType()
    {
        return 'Step';
    }

    /**
     * Returns step keyword in provided language (Given, When, Then, etc.).
     *
     * @return string
     *
     * @deprecated use getKeyword() instead
     */
    public function getType()
    {
        return $this->getKeyword();
    }

    /**
     * Returns step keyword in provided language (Given, When, Then, etc.).
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * Returns step type keyword (Given, When, Then, etc.).
     *
     * @return string
     */
    public function getKeywordType()
    {
        return $this->keywordType;
    }

    /**
     * Returns step text.
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Checks if step has arguments.
     *
     * @return bool
     */
    public function hasArguments()
    {
        return (bool) count($this->arguments);
    }

    /**
     * Returns step arguments.
     *
     * @return ArgumentInterface[]
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Returns step declaration line number.
     *
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }
}
