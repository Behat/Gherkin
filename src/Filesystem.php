<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * @internal
 */
final class Filesystem
{
    /**
     * @throws RuntimeException
     */
    public static function readFile(string $fileName): string
    {
        $data = @file_get_contents($fileName);
        if ($data === false) {
            throw new RuntimeException("Failed to read file: $fileName");
        }

        return $data;
    }

    /**
     * @throws \JsonException
     */
    public static function readJsonFile(string $fileName, bool $assoc = false): mixed
    {
        return json_decode(self::readFile($fileName), $assoc, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<string>
     */
    public static function findFilesRecursively(string $path, string $pattern): array
    {
        /**
         * @var iterable<string, SplFileInfo> $fileIterator
         */
        $fileIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST);

        $found = [];
        foreach ($fileIterator as $file) {
            if ($file->isFile() && fnmatch($pattern, $file->getFilename())) {
                $found[] = $file->getPathname();
            }
        }

        return $found;
    }
}
