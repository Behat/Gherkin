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
 * Represents Gherkin Background.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class BackgroundNode implements ScenarioLikeInterface
{
    /**
     * @param StepNode[] $steps
     */
    public function __construct(
        private readonly ?string $title,
        private readonly array $steps,
        private readonly string $keyword,
        private readonly int $line,
    ) {
    }

    /**
     * Returns node type string.
     *
     * @return string
     */
    public function getNodeType()
    {
        return 'Background';
    }

    /**
     * Returns background title.
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Checks if background has steps.
     *
     * @return bool
     */
    public function hasSteps()
    {
        return (bool) count($this->steps);
    }

    /**
     * Returns background steps.
     *
     * @return StepNode[]
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * Returns background keyword.
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * Returns background declaration line number.
     *
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }
}
