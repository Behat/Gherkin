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
 * @template TResourceType
 *
 * @implements LoaderInterface<TResourceType>
 */
abstract class AbstractLoader implements LoaderInterface
{
    public function load(mixed $resource)
    {
        if (!$this->supports($resource)) {
            throw new \LogicException(sprintf(
                '%s::%s() was called with unsupported resource `%s`.',
                static::class,
                __FUNCTION__,
                json_encode($resource)
            ));
        }

        return $this->doLoad($resource);
    }

    /**
     * @param TResourceType $resource
     *
     * @return list<FeatureNode>
     */
    abstract protected function doLoad(mixed $resource): array;
}
