<?php

namespace Behat\Gherkin\Node;

/*
 * This file is part of the Behat Gherkin.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Gherkin arguments interface.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface ArgumentInterface extends NodeInterface
{
    /**
     * Sets argument subject node.
     *
     * @param NodeInterface $subject
     */
    public function setSubject(NodeInterface $subject);

    /**
     * Returns argument subject.
     *
     * @return NodeInterface
     */
    public function getSubject();
}
