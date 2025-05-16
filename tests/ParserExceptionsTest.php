<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin;

use Behat\Gherkin\Exception\ParserException;
use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Parser;
use PHPUnit\Framework\TestCase;

class ParserExceptionsTest extends TestCase
{
    private Parser $gherkin;

    protected function setUp(): void
    {
        $keywords = new ArrayKeywords([
            'en' => [
                'feature' => 'Feature',
                'background' => 'Background',
                'scenario' => 'Scenario',
                'scenario_outline' => 'Scenario Outline',
                'examples' => 'Examples',
                'given' => 'Given',
                'when' => 'When',
                'then' => 'Then',
                'and' => 'And',
                'but' => 'But',
            ],
            'ru' => [
                'feature' => 'Функционал',
                'background' => 'Предыстория',
                'scenario' => 'Сценарий',
                'scenario_outline' => 'Структура сценария',
                'examples' => 'Примеры',
                'given' => 'Допустим',
                'when' => 'То',
                'then' => 'Если',
                'and' => 'И',
                'but' => 'Но',
            ],
        ]);
        $this->gherkin = new Parser(new Lexer($keywords));
    }

    public function testTextInBackground(): void
    {
        $feature = <<<'GHERKIN'
        Feature: Behat bug test
            Background: remove X to cause bug
            Step is red form is not valid
            asd
            asd
            as
            da
            sd
            as
            das
            d


        Scenario: bug user edit date
        GHERKIN;

        $parsed = $this->gherkin->parse($feature);
        $this->assertInstanceOf(FeatureNode::class, $parsed);
        $background = $parsed->getBackground();
        $this->assertInstanceOf(BackgroundNode::class, $background);
        $this->assertEquals(
            "remove X to cause bug\nStep is red form is not valid\nasd\nasd\nas\nda\nsd\nas\ndas\nd",
            $background->getTitle()
        );
    }

    public function testTextInScenario(): void
    {
        $feature = <<<'GHERKIN'
        Feature: Behat bug test
            Scenario: remove X to cause bug
            Step is red form is not valid
            asd
            asd
            as
            da
            sd
            as
            das
            d


        Scenario Outline: bug user edit date
        Step is red form is not valid
        asd
        asd
        as
        da
        sd
        as
        das
        d
        Examples:
         ||

        GHERKIN;

        $feature = $this->gherkin->parse($feature);
        $this->assertInstanceOf(FeatureNode::class, $feature);

        $this->assertCount(2, $scenarios = $feature->getScenarios());
        $firstTitle = <<<'TEXT'
        remove X to cause bug
        Step is red form is not valid
        asd
        asd
        as
        da
        sd
        as
        das
        d
        TEXT;
        $this->assertEquals($firstTitle, $scenarios[0]->getTitle());
        $secondTitle = <<<'TEXT'
        bug user edit date
        Step is red form is not valid
        asd
        asd
        as
        da
        sd
        as
        das
        d
        TEXT;
        $this->assertEquals($secondTitle, $scenarios[1]->getTitle());
    }

    public function testAmbiguousLanguage(): void
    {
        $feature = <<<'GHERKIN'
        # language: en

        # language: ru

        Feature: Some feature

            Given something wrong
        GHERKIN;

        $this->expectExceptionObject(
            new ParserException('Ambiguous language specifiers on lines: 1 and 3')
        );

        $this->gherkin->parse($feature);
    }

    public function testEmptyOutline(): void
    {
        $feature = <<<'GHERKIN'
        Feature: Some feature

            Scenario Outline:
        GHERKIN;

        $this->expectExceptionObject(
            new ParserException('Outline should have examples table, but got none for outline "" on line: 3')
        );

        $this->gherkin->parse($feature);
    }

    public function testTableWithoutRightBorder(): void
    {
        $feature = <<<'GHERKIN'
        Feature:

            Scenario:
                Given something with:
                | foo | bar
                | 42  | 42
        GHERKIN;

        $this->expectExceptionObject(
            new ParserException('Expected Step, Examples table, or end of Scenario, but got text: "        | foo | bar"'),
        );

        $this->gherkin->parse($feature);
    }
}
