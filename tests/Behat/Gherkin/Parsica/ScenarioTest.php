<?php
declare(strict_types=1);

namespace Tests\Behat\Gherkin\Parsica;

use Behat\Gherkin\Node\ScenarioNode;
use PHPUnit\Framework\TestCase;
use Verraes\Parsica\PHPUnit\ParserAssertions;
use function Behat\Gherkin\Parsica\scenario;

class ScenarioTest extends TestCase
{
    use ParserAssertions;

    /** @test */
    function it_parses_an_empty_scenario()
    {
        $input = 'Scenario:';
        $expected = new ScenarioNode('', [], [], 'Scenario', 1);

        $this->assertParses($input, scenario(), $expected);
    }

    /** @test */
    function it_parses_a_scenario_with_title()
    {
        $input = 'Scenario: Scenario title';
        $expected = new ScenarioNode('Scenario title', [], [], 'Scenario', 1);

        $this->assertParses($input, scenario(), $expected);
    }
}
