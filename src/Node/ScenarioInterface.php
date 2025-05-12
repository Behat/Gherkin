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
 * Gherkin scenario interface.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface ScenarioInterface extends ScenarioLikeInterface, TaggedNodeInterface
{
    /**
     * @todo Altering an interface feels like a BC break. What's the alternative?
     *
     * @param list<StepNode> $steps
     *
     * @return self
     */
    public function withSteps(array $steps);
}
