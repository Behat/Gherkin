<?php

namespace Behat\Gherkin\Cucumber;

use Cucumber\Messages\Tag;

final class TagMapper
{
    /**
     * @param Tag[] $tags
     * @return string[]
     */
    public function map(array $tags) : array {
        return array_map(
            function(Tag $t) {
                return ltrim($t->name, '@');
            },
            $tags
        );
    }
}
