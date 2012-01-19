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
 * Background Gherkin AST node.
 *
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class BackgroundNode extends AbstractScenarioNode
{
    private $title;

    /**
     * Initializes scenario.
     *
     * @param   string  $title  scenario title
     * @param   integer $line   definition line
     */
    public function __construct($title = null, $line = 0)
    {
        parent::__construct($line);

        $this->title = $title;
    }

    /**
     * Sets scenario title.
     *
     * @param   string  $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns scenario title.
     *
     * @return  string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
