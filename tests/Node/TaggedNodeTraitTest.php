<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Node\TaggedNodeInterface;
use Behat\Gherkin\Node\TaggedNodeTrait;
use PHPUnit\Framework\TestCase;

class TaggedNodeTraitTest extends TestCase
{
    public function testHasTags(): void
    {
        $node = $this->createTaggedNode([]);
        $this->assertFalse($node->hasTags());

        $node = $this->createTaggedNode(['a']);
        $this->assertTrue($node->hasTags());
    }

    public function testHasTag(): void
    {
        $node = $this->createTaggedNode([]);
        $this->assertFalse($node->hasTag('a'));

        $node = $this->createTaggedNode(['a']);
        $this->assertTrue($node->hasTag('a'));

        $node = $this->createTaggedNode(['a']);
        $this->assertFalse($node->hasTag('b'));
    }

    private function createTaggedNode(array $tags): TaggedNodeInterface
    {
        return new class($tags) implements TaggedNodeInterface {
            use TaggedNodeTrait;

            public function __construct(private readonly array $tags)
            {
            }

            public function getTags()
            {
                return $this->tags;
            }

            public function getNodeType()
            {
                return 'Fake';
            }

            public function getLine()
            {
                return 0;
            }
        };
    }
}
