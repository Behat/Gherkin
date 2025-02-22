<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Loader;

use Behat\Gherkin\Node\FeatureNode;
use Symfony\Component\Yaml\Yaml;

/**
 * Yaml files loader.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class YamlFileLoader extends AbstractFileLoader
{
    private $loader;

    public function __construct()
    {
        $this->loader = new ArrayLoader();
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
            && is_file($path)
            && pathinfo($path, PATHINFO_EXTENSION) === 'yml';
    }

    /**
     * Loads features from provided resource.
     *
     * @param string $resource Resource to load
     *
     * @return FeatureNode[]
     */
    public function load($resource)
    {
        $path = $this->getAbsolutePath($resource);
        $hash = Yaml::parse(file_get_contents($path));

        $features = $this->loader->load($hash);

        return array_map(
            static fn (FeatureNode $feature) => new FeatureNode(
                $feature->getTitle(),
                $feature->getDescription(),
                $feature->getTags(),
                $feature->getBackground(),
                $feature->getScenarios(),
                $feature->getKeyword(),
                $feature->getLanguage(),
                $path,
                $feature->getLine()
            ),
            $features
        );
    }
}
