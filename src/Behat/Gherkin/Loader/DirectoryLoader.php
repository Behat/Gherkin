<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Loader;

use Behat\Gherkin\Gherkin;
use Behat\Gherkin\Node\FeatureNode;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Directory contents loader.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class DirectoryLoader extends AbstractFileLoader
{
    protected $gherkin;

    /**
     * Initializes loader.
     *
     * @param Gherkin $gherkin Gherkin manager
     */
    public function __construct(Gherkin $gherkin)
    {
        $this->gherkin = $gherkin;
    }

    /**
     * Checks if current loader supports provided resource.
     *
     * @param mixed $path Resource to load
     *
     * @return bool
     */
    public function supports($path)
    {
        return is_string($path)
        && is_dir($this->findAbsolutePath($path));
    }

    /**
     * Loads features from provided resource.
     *
     * @param string $path Resource to load
     *
     * @return list<FeatureNode>
     */
    public function load($path)
    {
        $path = $this->findAbsolutePath($path);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $paths = array_map('strval', iterator_to_array($iterator));
        uasort($paths, 'strnatcasecmp');

        $features = [];

        foreach ($paths as $path) {
            $path = (string) $path;
            $loader = $this->gherkin->resolveLoader($path);

            if ($loader !== null) {
                $features = array_merge($features, $loader->load($path));
            }
        }

        return $features;
    }
}
