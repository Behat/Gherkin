<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use PHPUnit\Framework\TestCase;

class FeatureFilterTestFixtureTest extends TestCase
{
    public function testFromCommentedExpectationGeneratesOriginalByUncommenting(): void
    {
        $expectedEquivalentFeature = <<<'GHERKIN'
        Feature: Some feature that has comments to show what should be filtered out
          As a user
          
          
          Scenario: Assume this matches
            Given something
            
        # Scenario: But this one should not match
        #   Given other things
        #   Then OK?
        
          Scenario Outline: This should match
            Examples:
              | ok           | 
              | yes          |
        #     | not matching |
        GHERKIN;

        $case = FeatureFilterTestFixture::fromCommentedExpectation($expectedEquivalentFeature, []);

        $this->assertSame($expectedEquivalentFeature, $case->expectedEquivalentFeature);
        $this->assertSame(
            <<<'GHERKIN'
            Feature: Some feature that has comments to show what should be filtered out
              As a user
              
              
              Scenario: Assume this matches
                Given something
                
              Scenario: But this one should not match
                Given other things
                Then OK?
            
              Scenario Outline: This should match
                Examples:
                  | ok           | 
                  | yes          |
                  | not matching |
            GHERKIN,
            $case->originalFeature,
        );
    }

    public function testFromCommentedOriginalOptionallyStripsLineNumbers(): void
    {
        $case = FeatureFilterTestFixture::fromCommentedExpectation(
            <<<'GHERKIN'
            1 : Feature: Some feature that has comments to show what should be filtered out
            2 :  As a user
            4 :  
            5 :  Scenario: Assume this matches
            6 :    Given something
            7 :    
            8 : # Scenario: But this one should not match
            9 : #   Given other things
            10: #   Then OK?
            GHERKIN,
            [],
            stripLineNumbers: true,
        );

        $this->assertSame(
            <<<'GHERKIN'
             Feature: Some feature that has comments to show what should be filtered out
              As a user
              
              Scenario: Assume this matches
                Given something
                
             # Scenario: But this one should not match
             #   Given other things
             #   Then OK?
            GHERKIN,
            $case->expectedEquivalentFeature,
        );

        $this->assertSame(
            <<<'GHERKIN'
             Feature: Some feature that has comments to show what should be filtered out
              As a user
              
              Scenario: Assume this matches
                Given something
                
               Scenario: But this one should not match
                 Given other things
                 Then OK?
            GHERKIN,
            $case->originalFeature,
        );
    }

    public function testExpectingNoFilteringGivesSameFeatureForSourceAndOriginal(): void
    {
        $feature = <<<'GHERKIN'
        Feature: Some feature that has comments to show what should be filtered out
          As a user
          
          
          Scenario: Assume this matches
            Given something
            
        # Scenario: This should still be commented in the output
        #   Not that that would be useful, but just to prove we don't change anything
        GHERKIN;

        $case = FeatureFilterTestFixture::expectingNoFiltering($feature, []);

        $this->assertSame($feature, $case->expectedEquivalentFeature);
        $this->assertSame($feature, $case->originalFeature);
    }
}
