<?php

namespace Behat\Gherkin\Node;

interface NamedScenarioInterface
{
    /**
     * Returns the human-readable name of the scenario.
     */
    public function getName(): ?string;
}
