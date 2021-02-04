<?php

namespace Tests\Behat\Gherkin;

use Behat\Gherkin\Lexer;
use Behat\Gherkin\Parser;
use Behat\Gherkin\Keywords\ArrayKeywords;
use PHPUnit\Framework\TestCase;

class ParserExceptionsTest extends TestCase
{
    /**
     * @var Parser
     */
    private $gherkin;

    protected function setUp(): void
    {
        $keywords       = new ArrayKeywords(array(
            'en' => array(
                'feature'          => 'Feature',
                'background'       => 'Background',
                'scenario'         => 'Scenario',
                'scenario_outline' => 'Scenario Outline',
                'examples'         => 'Examples',
                'given'            => 'Given',
                'when'             => 'When',
                'then'             => 'Then',
                'and'              => 'And',
                'but'              => 'But'
            ),
            'ru' => array(
                'feature'          => 'Функционал',
                'background'       => 'Предыстория',
                'scenario'         => 'Сценарий',
                'scenario_outline' => 'Структура сценария',
                'examples'         => 'Примеры',
                'given'            => 'Допустим',
                'when'             => 'То',
                'then'             => 'Если',
                'and'              => 'И',
                'but'              => 'Но'
            )
        ));
        $this->gherkin = new Parser(new Lexer($keywords));
    }

    public function testStepRightAfterFeature()
    {
        $feature = <<<GHERKIN
Feature: Some feature

    Given some step-like line
GHERKIN;

        $parsed = $this->gherkin->parse($feature);

        $this->assertEquals("\n  Given some step-like line", $parsed->getDescription());
    }

    public function testTextInBackground()
    {
        $feature = <<<GHERKIN
Feature: Behat bug test
    Background: remove X to couse bug
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

        $feature = $this->gherkin->parse($feature);
        $background = $feature->getBackground();
        $this->assertEquals(
            "remove X to couse bug\nStep is red form is not valid\nasd\nasd\nas\nda\nsd\nas\ndas\nd",
            $background->getTitle()
        );
    }

    public function testTextInScenario()
    {
        $feature = <<<GHERKIN
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

        $this->assertCount(2, $scenarios = $feature->getScenarios());
        $firstTitle = <<<TEXT
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
        $secondTitle = <<<TEXT
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

    public function testAmbigiousLanguage()
    {
        $feature = <<<GHERKIN
# language: en

# language: ru

Feature: Some feature

    Given something wrong
GHERKIN;

        $this->expectException("\Behat\Gherkin\Exception\ParserException");
        $this->gherkin->parse($feature);
    }

    public function testEmptyOutline()
    {
        $feature = <<<GHERKIN
Feature: Some feature

    Scenario Outline:
GHERKIN;

        $this->expectException("\Behat\Gherkin\Exception\ParserException");
        $this->gherkin->parse($feature);
    }

    public function testWrongTagPlacement()
    {
        $feature = <<<GHERKIN
Feature: Some feature

    Scenario:
        Given some step
        @some_tag
        Then some additional step
GHERKIN;

        $this->expectException("\Behat\Gherkin\Exception\ParserException");
        $this->gherkin->parse($feature);
    }

    public function testBackgroundWithTag()
    {
        $feature = <<<GHERKIN
Feature: Some feature

    @some_tag
    Background:
        Given some step
GHERKIN;

        $this->expectException("\Behat\Gherkin\Exception\ParserException");
        $this->gherkin->parse($feature);
    }

    public function testEndlessPyString()
    {
        $feature = <<<GHERKIN
Feature:

    Scenario:
        Given something with:
            """
            some text
GHERKIN;

        $this->expectException("\Behat\Gherkin\Exception\ParserException");
        $this->gherkin->parse($feature);
    }

    public function testWrongStepType()
    {
        $feature = <<<GHERKIN
Feature:

    Scenario:
        Given some step

        Aaand some step
GHERKIN;

        $this->expectException("\Behat\Gherkin\Exception\ParserException");
        $this->gherkin->parse($feature);
    }

    public function testMultipleBackgrounds()
    {
        $feature = <<<GHERKIN
Feature:

    Background:
        Given some step

    Background:
        Aaand some step
GHERKIN;

        $this->expectException("\Behat\Gherkin\Exception\ParserException");
        $this->gherkin->parse($feature);
    }

    public function testMultipleFeatures()
    {
        $feature = <<<GHERKIN
Feature:

Feature:
GHERKIN;

        $this->expectException("\Behat\Gherkin\Exception\ParserException");
        $this->gherkin->parse($feature);
    }

    public function testTableWithoutRightBorder()
    {
        $feature = <<<GHERKIN
Feature:

    Scenario:
        Given something with:
        | foo | bar
        | 42  | 42
GHERKIN;

        $this->expectException("\Behat\Gherkin\Exception\ParserException");
        $this->gherkin->parse($feature);
    }
}
