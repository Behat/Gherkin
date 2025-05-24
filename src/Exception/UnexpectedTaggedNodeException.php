<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Exception;

use Behat\Gherkin\Lexer;

/**
 * @phpstan-import-type TToken from Lexer
 */
class UnexpectedTaggedNodeException extends ParserException
{
    /**
     * @phpstan-param TToken $taggedToken
     */
    public function __construct(
        public readonly array $taggedToken,
        public readonly ?string $sourceFile,
    ) {
        $msg = match ($this->taggedToken['type']) {
            'EOS' => 'Unexpected end of file after tags',
            default => sprintf(
                '%s can not be tagged, but it is',
                $taggedToken['type'],
            ),
        };

        parent::__construct(
            sprintf(
                '%s on line: %d%s',
                $msg,
                $taggedToken['line'],
                $this->sourceFile ? " in file: {$this->sourceFile}" : '',
            ),
        );
    }
}
