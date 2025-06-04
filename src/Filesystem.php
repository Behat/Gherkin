<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin;

use Behat\Gherkin\Exception\FilesystemException;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @internal
 */
final class Filesystem
{
    /**
     * @throws FilesystemException
     */
    public static function readFile(string $fileName): string
    {
        $data = @file_get_contents($fileName);
        if ($data === false) {
            throw new FilesystemException(sprintf(
                'File "%s" cannot be read: %s',
                $fileName,
                error_get_last()['message'] ?? 'unknown reason (this should never happen)'
            ));
        }

        return $data;
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws JsonException|FilesystemException
     */
    public static function readJsonFileArray(string $fileName): array
    {
        $result = json_decode(self::readFile($fileName), true, flags: JSON_THROW_ON_ERROR);

        \assert(is_array($result), 'File must contain JSON with an array at its root');

        return $result;
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

    public static function getLastModified(string $fileName): int
    {
        $result = @filemtime($fileName);

        if ($result === false) {
            throw new FilesystemException(sprintf(
                'Last modification time of file "%s" cannot be found: %s',
                $fileName,
                error_get_last()['message'] ?? 'unknown reason (this should never happen)'
            ));
        }

        return $result;
    }

    public static function getRealPath(string $path): string
    {
        $result = realpath($path);

        if ($result === false) {
            throw new FilesystemException("Cannot retrieve the real path of $path");
        }

        return $result;
    }
}
