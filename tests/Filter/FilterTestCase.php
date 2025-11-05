<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Dialect\CucumberDialectProvider;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Parser;
use PHPUnit\Framework\TestCase;

abstract class FilterTestCase extends TestCase
{
    protected function getParser(): Parser
    {
        return new Parser(
            new Lexer(
                new CucumberDialectProvider()
            )
        );
    }

    protected function getGherkinFeature(): string
    {
        return <<<'GHERKIN'
        Feature: Long feature with outline
          Scenario: Scenario#1
            Given initial step
            When action occurs
            Then outcomes should be visible

          Scenario: Scenario#2
            Given initial step
            And another initial step
            When action occurs
            Then outcomes should be visible

          Scenario Outline: Scenario#3
            When <action> occurs
            Then <outcome> should be visible

            @etag1
            Examples:
              | action | outcome |
              | act#1  | out#1   |
              | act#2  | out#2   |

            @etag2
            Examples:
              | action | outcome |
              | act#3  | out#3   |

        GHERKIN;
    }

    protected function getParsedFeature(): FeatureNode
    {
        return $this->getParser()->parse($this->getGherkinFeature())
            ?? throw new \LogicException('Could not parse predefined feature in getGherkinFeature()');
    }
}
