<?php

namespace Tests\Behat\Gherkin;

use Symfony\Component\Finder\Finder;

use Behat\Gherkin\Lexer,
    Behat\Gherkin\Parser,
    Behat\Gherkin\Keywords\ArrayKeywords;

class ParserExceptionsTest extends \PHPUnit_Framework_TestCase
{
    private $gherkin;

    protected function setUp()
    {
        $keywords       = new ArrayKeywords(array(
            'en' => array(
                'Feature'           => 'Feature',
                'Background'        => 'Background',
                'Scenario'          => 'Scenario',
                'Scenario Outline'  => 'Scenario Outline',
                'Examples'          => 'Examples',
                'Step Types'        => 'Given|When|Then|And|But'
            ),
            'ru' => array(
                'Feature'           => 'Функционал',
                'Background'        => 'Предыстория',
                'Scenario'          => 'Сценарий',
                'Scenario Outline'  => 'Структура сценария',
                'Examples'          => 'Значения',
                'Step Types'        => 'Допустим|То|Если|И|Но'
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

        $this->assertEquals('Given some step-like line', $parsed[0]->getDescription());
    }

    /**
     * @expectedException Behat\Gherkin\Exception\ParserException
     */
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

        $this->gherkin->parse($feature);
    }

    public function testTextInScenario()
    {
        $feature = <<<GHERKIN
Feature: Behat bug test
	Scenario: remove X to couse bug
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

        $features  = $this->gherkin->parse($feature);
        $scenarios = $features[0]->getScenarios();

        $this->assertCount(2, $scenarios);
        $this->assertEquals(<<<TEXT
remove X to couse bug
Step is red form is not valid
asd
asd
as
da
sd
as
das
d
TEXT
        , $scenarios[0]->getTitle());
        $this->assertEquals(<<<TEXT
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
TEXT
        , $scenarios[1]->getTitle());
    }

    /**
     * @expectedException Behat\Gherkin\Exception\ParserException
     */
    public function testAmbigiousLanguage()
    {
        $feature = <<<GHERKIN
# language: en

# language: ru

Feature: Some feature

    Given something wrong
GHERKIN;

        $this->gherkin->parse($feature);
    }

    /**
     * @expectedException Behat\Gherkin\Exception\ParserException
     */
    public function testEmptyOutline()
    {
        $feature = <<<GHERKIN
Feature: Some feature

    Scenario Outline:
GHERKIN;

        $this->gherkin->parse($feature);
    }

    /**
     * @expectedException Behat\Gherkin\Exception\ParserException
     */
    public function testWrongTagPlacement()
    {
        $feature = <<<GHERKIN
Feature: Some feature

    Scenario:
        Given some step
        @some_tag
        Then some additional step
GHERKIN;

        $this->gherkin->parse($feature);
    }

    /**
     * @expectedException Behat\Gherkin\Exception\ParserException
     */
    public function testBackgroundWithTag()
    {
        $feature = <<<GHERKIN
Feature: Some feature

    @some_tag
    Background:
        Given some step
GHERKIN;

        $this->gherkin->parse($feature);
    }

    /**
     * @expectedException Behat\Gherkin\Exception\ParserException
     */
    public function testTableArgumentNotInPlace()
    {
        $feature = <<<GHERKIN
Feature:

    Scenario:

        | as | sa |
        | ds | sd |
GHERKIN;

        $this->gherkin->parse($feature);
    }

    /**
     * @expectedException Behat\Gherkin\Exception\ParserException
     */
    public function testPyStringArgumentNotInPlace()
    {
        $feature = <<<GHERKIN
Feature:

    Scenario:

        """
        """
GHERKIN;

        $this->gherkin->parse($feature);
    }

    /**
     * @expectedException Behat\Gherkin\Exception\ParserException
     */
    public function testEndlessPyString()
    {
        $feature = <<<GHERKIN
Feature:

    Scenario:
        Given something with:
            """
            some text
GHERKIN;

        $this->gherkin->parse($feature);
    }

    /**
     * @expectedException Behat\Gherkin\Exception\ParserException
     */
    public function testWrongStepType()
    {
        $feature = <<<GHERKIN
Feature:

    Scenario:
        Given some step

        Aaand some step
GHERKIN;

        $parsed = $this->gherkin->parse($feature);
    }

    /**
     * @expectedException Behat\Gherkin\Exception\ParserException
     */
    public function testMultipleBackgrounds()
    {
        $feature = <<<GHERKIN
Feature:

    Background:
        Given some step

    Background:
        Aaand some step
GHERKIN;

        $parsed = $this->gherkin->parse($feature);
    }
}
