<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin;

use Behat\Gherkin\Exception\FilesystemException;
use Behat\Gherkin\Filesystem;
use PHPUnit\Framework\TestCase;

final class FilesystemTest extends TestCase
{
    public function testInexistentFileCannotHaveModificationTime(): void
    {
        $this->expectExceptionObject(
            new FilesystemException('Cannot retrieve last modification time of file: inexistent-file.txt'),
        );

        Filesystem::getLastModified('inexistent-file.txt');
    }

    public function testRealPathExpectsStringParameter(): void
    {
        $this->expectExceptionObject(
            new FilesystemException('Path must not be null'),
        );

        Filesystem::getRealPath(null);
    }
}
