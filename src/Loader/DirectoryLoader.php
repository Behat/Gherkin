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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Traversable;

/**
 * Directory contents loader.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @extends AbstractFileLoader<string>
 */
class DirectoryLoader extends AbstractFileLoader
{
    /**
     * @var Gherkin
     */
    protected $gherkin;

    /**
     * Initializes loader.
     */
    public function __construct(Gherkin $gherkin)
    {
        $this->gherkin = $gherkin;
    }

    public function supports(mixed $resource)
    {
        return is_string($resource)
            && ($path = $this->findAbsolutePath($resource)) !== false
            && is_dir($path);
    }

    protected function doLoad(mixed $resource): array
    {
        $path = $this->getAbsolutePath($resource);
        /** @var Traversable<SplFileInfo> $iterator */
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
