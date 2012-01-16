<?php

namespace Behat\Gherkin\Loader;

use Symfony\Component\Finder\Finder;

use Behat\Gherkin\Parser;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Gherkin *.feature files loader.
 *
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class GherkinFileLoader extends AbstractFileLoader
{
    protected $parser;

    /**
     * Initializes loader.
     *
     * @param   Behat\Gherkin\Parser    $parser
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($path)
    {
        return is_string($path)
            && is_file($absolute = $this->findAbsolutePath($path))
            && 'feature' === pathinfo($absolute, PATHINFO_EXTENSION);
    }

    /**
     * {@inheritdoc}
     */
    public function load($path)
    {
        $path = $this->findAbsolutePath($path);

        $filename   = $this->findRelativePath($path);
        $content    = file_get_contents($path);

        return array($this->parser->parse($content, $filename));
    }
}
