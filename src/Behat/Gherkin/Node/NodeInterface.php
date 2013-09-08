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
 * Gherkin node interface.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface NodeInterface
{
    /**
     * Returns node type string
     *
     * @return string
     */
    public function getNodeType();

    /**
     * Returns feature language.
     *
     * @return string
     */
    public function getLanguage();

    /**
     * Returns feature file.
     *
     * @return null|string
     */
    public function getFile();

    /**
     * Returns feature declaration line number.
     *
     * @return integer
     */
    public function getLine();
}
