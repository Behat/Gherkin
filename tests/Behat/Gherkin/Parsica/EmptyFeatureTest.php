<?php
declare(strict_types=1);

namespace Behat\Gherkin\Parsica;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use Verraes\Parsica\Parser;
use Verraes\Parsica\ParserFailure;
use function Verraes\Parsica\string;

require_once('Asserts.php');

final class EmptyFeatureTest extends \PHPUnit\Framework\Testcase
{
    use Asserts;
    
    /** @test */
    public function it_parses_an_empty_feature()
    {
        $this->markTestIncomplete('Not implemented yet');
        
        $input = 'Feature:';

        $expected = new FeatureNode('', '',[],null,[], 'Feature','en',null,1);

        $this->assertParse($expected, feature(), $input);
    }


    /** @test */
    public function it_parses_a_feature_with_a_title()
    {
        $this->markTestIncomplete('Not implemented yet');
        
        $input = 'Feature: This is a really cool feature';
        $expected = new FeatureNode('This is a really cool feature', '',[],null,[], 'Feature','en',null,1);

        $this->assertParse($expected, feature(), $input);
    }

    /** @test */
    public function it_parses_an_example_with_no_title()
    {
        $this->markTestIncomplete('Not implemented yet');
        
        $input = "Example: ";
        $expected = new ScenarioNode('', [], [],'Example', 1);

        $this->assertParse($expected, scenario(), $input);
    }

    /** @test */
    public function it_parses_an_example()
    {

        $this->markTestIncomplete('Not implemented yet');
        
        $input = "Example: This is a really cool example";
        $expected = new ScenarioNode('This is a really cool example', [], [],'Example', 1);

        $this->assertParse($expected, scenario(), $input);
    }
    
    /** @test */
    public function it_parses_a_feature_with_examples()
    {
        $this->markTestIncomplete('Not implemented yet');
        
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

    /** @test */
    public function it_parses_a_simple_keyword_followed_by_space()
    {
        $this->markTestIncomplete('Not implemented yet');
        
        $input = 'Given ';
        $expected = 'Given';

        $this->assertParse($expected, keyword('Given', false), $input);
    }

    /** @test */
    public function it_is_case_sensitive_for_a_simple_keyword()
    {
        $this->markTestIncomplete('Not implemented yet');

        $this->expectException(ParserFailure::class);

        $parser = keyword('Given', false);
        $input = 'given';

        $parser->tryString($input);
    }

    /** @test */
    public function it_parses_keywords_that_need_a_colon()
    {
        $this->markTestIncomplete('Not implemented yet');
        
        $input = 'Example:';
        $expected = 'Example';

        $this->assertParse($expected, keyword('Example', true), $input);
    }

    /** @test */
    public function it_does_not_allow_space_before_suffix()
    {

        $this->markTestIncomplete('Not implemented yet');
        
        $input = 'Example :';
        $parser = keyword('Example', true);

        $this->expectException(ParserFailure::class);

        $parser->tryString($input);
    }

    /** @test */
    public function it_consumes_all_whitespace_after_parser()
    {

        $this->markTestIncomplete('Not implemented yet');
        
        $input = 'Foo    ';
        $parser = token(string('Foo'));
        $expected = 'Foo';

        $this->assertParse($expected, $parser, $input);
    }
}

/*
Feature: This thing

   This feature will be super awesome

   Example: Example text
    Given I have a cat when it's raining
    When I kill the cat
    Then I no longer have the cat

FeatureKeyword: FeatureTitle

    ScenarioKeyword: ScenarioTitle
        StepKeyword StepText
        StepKeyword StepText
        StepKeyword StepText
 */
