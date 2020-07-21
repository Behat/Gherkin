<?php
declare(strict_types=1);

namespace Behat\Gherkin\Parsica;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use Verraes\Parsica\Parser;
use Verraes\Parsica\StringStream;
use function Verraes\Parsica\alphaNumChar;
use function Verraes\Parsica\atLeastOne;
use function Verraes\Parsica\blank;
use function Verraes\Parsica\char;
use function Verraes\Parsica\collect;
use function Verraes\Parsica\eol;
use function Verraes\Parsica\keepFirst;
use function Verraes\Parsica\many;
use function Verraes\Parsica\optional;
use function Verraes\Parsica\punctuationChar;
use function Verraes\Parsica\skipHSpace;
use function Verraes\Parsica\skipSpace;
use function Verraes\Parsica\string;

final class EmptyFeatureTest extends \PHPUnit\Framework\Testcase
{

    private function assertParse($expected, Parser $parser, string $input)
    {
        $actual = $parser->try(new StringStream("$input\n"))->output();

        $this->assertEquals($expected, $actual);
    }

    /** @test */
    public function it_parses_an_empty_feature()
    {
        $input = 'Feature:';

        $expected = new FeatureNode('', '',[],null,[], 'Feature','en',null,1);

        $this->assertParse($expected, feature(), $input);
    }


    /** @test */
    public function it_parses_a_feature_with_a_title()
    {
        $input = 'Feature: This is a really cool feature';
        $expected = new FeatureNode('This is a really cool feature', '',[],null,[], 'Feature','en',null,1);

        $this->assertParse($expected, feature(), $input);
    }

    /** @test */
    public function it_parses_an_example()
    {
        $input = "Example: This is a really cool example";
        $expected = new ScenarioNode('This is a really cool example', [], [],'Example', 1);

        $this->assertParse($expected, scenario(), $input);
    }

    /** @test */
    public function it_parses_a_feature_with_examples()
    {
        $input = <<<GHERKIN
            Feature: FeatureTitle
            
                Example: Example Title 1
                
                Example: Example Title 2
                Example: Example Title 3
            GHERKIN;

        $expectedSteps = [
            new ScenarioNode('Example Title 1', [], [],'Example', 1),
            new ScenarioNode('Example Title 2', [], [],'Example', 1),
            new ScenarioNode('Example Title 3', [], [],'Example', 1),
        ];
        $expected = new FeatureNode('FeatureTitle', '', [], null, $expectedSteps, 'Feature','en',null,1);

        $this->assertParse($expected, feature(), $input);
    }
}

function text() : Parser
{
    return optional(
        atLeastOne(alphaNumChar()->or(punctuationChar())->or(blank()))
    );
}

/** @todo find a better name for this */
function oneOrMoreLinesFollowedBySpace(Parser $parser) : Parser
{
    return keepFirst($parser, eol()->followedBy(skipSpace()));
}

function featureKeyword() : Parser
{
    return keepFirst(string('Feature'), char(':')->followedBy(skipHSpace()));
}

function feature() : Parser
{
    return collect(featureKeyword(), oneOrMoreLinesFollowedBySpace(text()), many(oneOrMoreLinesFollowedBySpace(scenario())))->map(
        fn(array $outputs) : FeatureNode => new FeatureNode($outputs[1], '', [], null, $outputs[2], $outputs[0], 'en', null, 1)
    );
}

function scenarioKeyword() : Parser
{
    return keepFirst(string('Example'), char(':')->followedBy(skipHSpace()));
}

function scenario() : Parser
{
    return collect(scenarioKeyword(), text())->map(
        fn(array $strs) : ScenarioNode => new ScenarioNode($strs[1], [], [], $strs[0], 1)
    );
}

/*
Feature: This thing

   Example: Example text
    Given I have a cat when it's raining
    When I kill the cat
    Then I no longer have the cat

FeatureKeyword: FeatureTitle

    ScenarioKeyword: ScenarioTitle
        Step
        Step
        Step
 */
