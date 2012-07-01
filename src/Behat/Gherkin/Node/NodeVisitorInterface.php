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
 * Node Visitor Interface.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface NodeVisitorInterface
{
    /**
     * Visits specific node.
     *
     * @param AbstractNode $visitee Visitee object
     *
     * @return mixed
     */
    public function visit(AbstractNode $visitee);
}
