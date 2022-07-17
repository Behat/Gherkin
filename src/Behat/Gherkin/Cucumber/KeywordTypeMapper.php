<?php

namespace Behat\Gherkin\Cucumber;

use Cucumber\Messages\Step\KeywordType;

final class KeywordTypeMapper
{
    public function map(?KeywordType $type, ?string $prevType) : string
    {
        if ($type == KeywordType::CONTEXT) {
            return 'Given';
        }

        if ($type == KeywordType::ACTION) {
            return 'When';
        }

        if ($type == KeywordType::OUTCOME) {
            return 'Then';
        }

        if ($type == KeywordType::CONJUNCTION && $prevType != null) {
            return $prevType;
        }

        return 'Given';
    }

}
