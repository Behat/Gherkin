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
     * @param mixed $resource Resource to load
     *
     * @return bool
     */
    public function supports($resource)
    {
        return is_string($resource)
            && ($path = $this->findAbsolutePath($resource)) !== false
            && is_dir($path);
    }

    /**
     * Loads features from provided resource.
     *
     * @param string $resource Resource to load
     *
     * @return list<FeatureNode>
     */
    public function load($resource)
    {
        $path = $this->getAbsolutePath($resource);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $paths = array_map(strval(...), iterator_to_array($iterator));
        uasort($paths, strnatcasecmp(...));

        $features = [];

        foreach ($paths as $path) {
            $path = (string) $path;
            $loader = $this->gherkin->resolveLoader($path);

            if ($loader !== null) {
                array_push($features, ...$loader->load($path));
            }
        }

        return $features;
    }
}
