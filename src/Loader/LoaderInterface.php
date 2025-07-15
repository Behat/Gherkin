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

/**
 * Loader interface.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @template TResourceType
 */
interface LoaderInterface
{
    /**
     * Checks if current loader supports provided resource.
     *
     * @template TSupportedResourceType
     *
     * @param TSupportedResourceType $resource Resource to load
     *
     * @phpstan-assert-if-true =LoaderInterface<TSupportedResourceType> $this
     *
     * @return bool
     */
    public function supports(mixed $resource);

    /**
     * Loads features from provided resource.
     *
     * @param TResourceType $resource Resource to load
     *
     * @return list<FeatureNode>
     */
    public function load(mixed $resource);
}
