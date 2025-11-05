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
use ErrorException;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function assert;

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
        try {
            $result = self::callSafely(static fn () => file_get_contents($fileName));
        } catch (ErrorException $e) {
            throw new FilesystemException(
                sprintf('File "%s" cannot be read: %s', $fileName, $e->getMessage()),
                previous: $e,
            );
        }

        assert($result !== false, 'file_get_contents() should not return false without emitting a PHP warning');

        return $result;
    }

    public static function writeFile(string $fileName, string $content): void
    {
        self::ensureDirectoryExists(dirname($fileName));
        try {
            $result = self::callSafely(static fn () => file_put_contents($fileName, $content));
        } catch (ErrorException $e) {
            throw new FilesystemException(
                sprintf('File "%s" cannot be written: %s', $fileName, $e->getMessage()),
                previous: $e,
            );
        }

        assert($result !== false, 'file_put_contents() should not return false without emitting a PHP warning');
    }

    /**
     * @return array<mixed>
     *
     * @throws JsonException|FilesystemException
     */
    public static function readJsonFileArray(string $fileName): array
    {
        $result = json_decode(self::readFile($fileName), true, flags: JSON_THROW_ON_ERROR);

        assert(is_array($result), 'File must contain JSON with an array or object at its root');

        return $result;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException|FilesystemException
     */
    public static function readJsonFileHash(string $fileName): array
    {
        $result = self::readJsonFileArray($fileName);
        assert(
            $result === array_filter($result, is_string(...), ARRAY_FILTER_USE_KEY),
            'File must contain a JSON object at its root',
        );

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
        try {
            $result = self::callSafely(static fn () => filemtime($fileName));
        } catch (ErrorException $e) {
            throw new FilesystemException(
                sprintf('Last modification time of file "%s" cannot be found: %s', $fileName, $e->getMessage()),
                previous: $e,
            );
        }

        assert($result !== false, 'filemtime() should not return false without emitting a PHP warning');

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

    public static function ensureDirectoryExists(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        try {
            $result = self::callSafely(static fn () => mkdir($path, 0777, true));

            assert($result !== false, 'mkdir() should not return false without emitting a PHP warning');
        } catch (ErrorException $e) {
            // @codeCoverageIgnoreStart
            if (is_dir($path)) {
                // Some other concurrent process created the directory.
                return;
            }
            // @codeCoverageIgnoreEnd

            throw new FilesystemException(
                sprintf('Path at "%s" cannot be created: %s', $path, $e->getMessage()),
                previous: $e,
            );
        }
    }

    /**
     * @template TResult
     *
     * @param (callable(): TResult) $callback
     *
     * @return TResult
     *
     * @throws ErrorException
     */
    private static function callSafely(callable $callback): mixed
    {
        set_error_handler(
            static fn (int $severity, string $message, string $file, int $line) => throw new ErrorException($message, 0, $severity, $file, $line)
        );

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }
}
