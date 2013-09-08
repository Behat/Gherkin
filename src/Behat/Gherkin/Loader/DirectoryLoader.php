<?php

namespace Behat\Gherkin\Loader;

/*
 * This file is part of the Behat Gherkin.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Behat\Gherkin\Gherkin;
use Behat\Gherkin\Node\FeatureNode;
use Symfony\Component\Finder\Finder;

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
     * @return Boolean
     */
    public function supports($path)
    {
        return is_string($path)
        && is_dir($this->findAbsolutePath($path));
    }

    /**
     * Loads features from provided resource.
     *
     * @param mixed $path Resource to load
     *
     * @return FeatureNode[]
     */
    public function load($path)
    {
        $path = $this->findAbsolutePath($path);

        $finder = new Finder();
        $iterator = $finder->files()->sortByName()->in($path);
        $features = array();

        foreach ($iterator as $path) {
            $path = (string)$path;
            $loader = $this->gherkin->resolveLoader($path);

            if (null !== $loader) {
                $features = array_merge($features, $loader->load($path));
            }
        }

        return $features;
    }
}
