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
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class CucumberKeywordsTest extends KeywordsTestCase
{
    protected static function getKeywords(): KeywordsInterface
    {
        return new CucumberKeywords(__DIR__ . '/../Fixtures/i18n.yml');
    }

    protected static function getKeywordsArray(): array
    {
        // @phpstan-ignore return.type
        return Yaml::parseFile(__DIR__ . '/../Fixtures/i18n.yml');
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
        $root = vfsStream::setup();
        $root->addChild(
            $file = vfsStream::newFile('invalid.yaml')
                ->setContent("invalid:\n\tinvalid:yaml")
        );

        $this->expectExceptionObject(new ParseException(
            'YAML file cannot contain tabs as indentation',
            2,
            "\tinvalid:yaml",
            $file->url(),
        ));

        new CucumberKeywords($file->url());
    }

    public function testYamlRootMustBeAnArray(): void
    {
        $this->expectExceptionObject(
            new ParseException('Root element must be an array, but string found.')
        );

        new CucumberKeywords("a\nstring");
    }

    public function testYamlFileMustBeReadable(): void
    {
        $root = vfsStream::setup();
        $root->addChild(
            $file = vfsStream::newFile('unreadable.yaml')
                ->setContent("aaa:\n  bbb: cccc")
                ->chmod(0)
        );

        $this->expectExceptionObject(
            new ParseException("File \"{$file->url()}\" cannot be read.")
        );

        new CucumberKeywords($file->url());
    }
}
