<?php

namespace Tests\Behat\Gherkin\Cucumber;

use Behat\Gherkin\Cucumber\KeywordTypeMapper;
use Cucumber\Messages\Step\KeywordType;
use PHPUnit\Framework\TestCase;

/**
 * @group cucumber
 */
final class KeywordTypeMapperTest extends TestCase
{
    public function setUp() : void
    {
        $this->mapper = new KeywordTypeMapper();
    }

    /**
     * @dataProvider regularKeywords
     */
    public function testItMapsRegularKeywordsCorrectly($expected, KeywordType $input)
    {
        self::assertSame($expected, $this->mapper->map($input, null));
    }

    public function regularKeywords()
    {
        yield ['Given', KeywordType::CONTEXT];
        yield ['When', KeywordType::ACTION];
        yield ['Then', KeywordType::OUTCOME];
    }

    public function testItMapsToGivenIfNullIsPassed()
    {
        self::assertSame('Given', $this->mapper->map(null, null));
    }

    public function testItMapsToGivenIfUnknownIsPassed()
    {
        self::assertSame('Given', $this->mapper->map(KeywordType::UNKNOWN, null));
    }

    public function testItMapsToGivenIfConjunctionIsFirstStep()
    {
        self::assertSame('Given', $this->mapper->map(KeywordType::CONJUNCTION, null));
    }

    public function testItMapsConjunctionToPreviousType()
    {
        self::assertSame('When', $this->mapper->map(KeywordType::CONJUNCTION, 'When'));
    }
}
