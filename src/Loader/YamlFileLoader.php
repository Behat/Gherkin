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
 *
 * @extends AbstractFileLoader<string>
 *
 * @phpstan-import-type TArrayResource from ArrayLoader
 */
class YamlFileLoader extends AbstractFileLoader
{
    /**
     * @phpstan-param LoaderInterface<TArrayResource> $loader
     */
    public function __construct(
        private readonly LoaderInterface $loader = new ArrayLoader(),
    ) {
    }

    public function supports(mixed $resource)
    {
        return is_string($resource)
            && ($path = $this->findAbsolutePath($resource)) !== false
            && is_file($path)
            && pathinfo($path, PATHINFO_EXTENSION) === 'yml';
    }

    protected function doLoad(mixed $resource): array
    {
        $path = $this->getAbsolutePath($resource);
        $hash = Yaml::parseFile($path);

        // @phpstan-ignore argument.type
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
