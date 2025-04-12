<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Keywords;

use Behat\Gherkin\Keywords\CucumberKeywords;
use Behat\Gherkin\Keywords\KeywordsInterface;
use Behat\Gherkin\Node\StepNode;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Tests\Behat\Gherkin\FileReaderTrait;

class CucumberKeywordsTest extends KeywordsTestCase
{
    use FileReaderTrait;

    protected static function getKeywords(): KeywordsInterface
    {
        return new CucumberKeywords(__DIR__ . '/../Fixtures/i18n.yml');
    }

    protected static function getKeywordsArray(): array
    {
        $data = self::readFile(__DIR__ . '/../Fixtures/i18n.yml');

        return Yaml::parse($data);
    }

    protected static function getSteps(string $keywords, string $text, int &$line, ?string $keywordType): array
    {
        $steps = [];
        foreach (explode('|', mb_substr($keywords, 2)) as $keyword) {
            if (str_contains($keyword, '<')) {
                $keyword = mb_substr($keyword, 0, -1);
            }

            $steps[] = new StepNode($keyword, $text, [], $line++, $keywordType);
        }

        return $steps;
    }

    public function testYamlSourceFileIsAttachedToException(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');

        try {
            file_put_contents($tempFile, "invalid:\n\tinvalid:yaml");

            $this->expectExceptionObject(new ParseException(
                'YAML file cannot contain tabs as indentation',
                2,
                "\tinvalid:yaml",
                $tempFile,
            ));

            new CucumberKeywords($tempFile);
        } finally {
            @unlink($tempFile);
        }
    }

    public function testYamlRootMustBeAnArray(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Root element must be an array, but string found.');

        new CucumberKeywords("a\nstring");
    }

    public function testYamlFileMustBeReadable(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');

        try {
            file_put_contents($tempFile, "aaa:\n  bbb: cccc");
            if (PHP_OS_FAMILY === 'Windows') {
                exec('icacls ' . escapeshellarg($tempFile) . ' /deny Everyone:(R)');
            } else {
                chmod($tempFile, 0);
            }

            $this->expectException(ParseException::class);
            $this->expectExceptionMessage("Unable to parse \"$tempFile\" as the file is not readable.");

            new CucumberKeywords($tempFile);
        } finally {
            @unlink($tempFile);
        }
    }
}
