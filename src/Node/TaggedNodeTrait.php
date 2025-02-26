<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Node;

/**
 * This trait partially implements {@see TaggedNodeInterface}.
 *
 * @internal
 */
trait TaggedNodeTrait
{
    /**
     * @return list<string>
     */
    abstract public function getTags();

    /**
     * @param string $tag
     *
     * @return bool
     */
    public function hasTag($tag)
    {
        return in_array($tag, $this->getTags(), true);
    }

    /**
     * @return bool
     */
    public function hasTags()
    {
        return $this->getTags() !== [];
    }
}
