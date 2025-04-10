<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Exception;

use Behat\Gherkin\Node\NodeInterface;

class UnexpectedParserNodeException extends ParserException
{
    public function __construct(
        public readonly string $expectation,
        public readonly string|NodeInterface $node,
        public readonly ?string $sourceFile,
    ) {
        parent::__construct(
            sprintf(
                'Expected %s, but got %s%s',
                $expectation,
                is_string($node)
                    ? "text: \"{$node}\""
                    : "{$node->getNodeType()} on line: {$node->getLine()}",
                $sourceFile ? " in file: {$sourceFile}" : ''
            ),
        );
    }
}
