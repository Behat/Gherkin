<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin;

class Filesystem
{
    public static function readFile(string $fileName): string
    {
        $data = @file_get_contents($fileName);
        if ($data === false) {
            throw new \RuntimeException("Failed to read file: $fileName");
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    public static function findRecursively(string $path, string $pattern): array
    {
        /**
         * @var iterable<string, \SplFileInfo> $fileIterator
         */
        $fileIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::CHILD_FIRST);

        $found = [];
        foreach ($fileIterator as $file) {
            if (fnmatch($pattern, $file->getFilename())) {
                $found[] = $file->getPathname();
            }
        }

        return $found;
    }
}
