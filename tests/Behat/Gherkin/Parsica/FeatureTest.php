<?php
declare(strict_types=1);

namespace Tests\Behat\Gherkin\Parsica;

use Behat\Gherkin\Node\FeatureNode;
use PHPUnit\Framework\TestCase;
use Verraes\Parsica\PHPUnit\ParserAssertions;
use function Behat\Gherkin\Parsica\feature;

class FeatureTest extends TestCase
{
    use ParserAssertions;

    /** @test */
    function it_parses_a_empty_feature()
    {
        $input = 'Feature:';

        $expected = new FeatureNode('', '', [], null, [], 'Feature', 'en', null, 1);

        $parser = feature();

        $this->assertParses($input, $parser, $expected);
    }

    /** @test */
    public function it_parses_a_feature_with_a_title()
    {
        $input = 'Feature: This is a really cool feature';
        $expected = new FeatureNode('This is a really cool feature', '',[],null,[], 'Feature','en',null,1);

        $parser = feature();
        $this->assertParses($input, $parser, $expected);
    }
}
