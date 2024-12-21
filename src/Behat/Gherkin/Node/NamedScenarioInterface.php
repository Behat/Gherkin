<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Node;

interface NamedScenarioInterface
{
    /**
     * Returns the human-readable name of the scenario.
     */
    public function getName(): ?string;
}
