<?php

namespace Behat\Gherkin\Cucumber;

use Behat\Gherkin\Node\PyStringNode;
use Cucumber\Messages\DocString;

final class PyStringNodeMapper
{
    /**
     * @param DocString $docString
     * @return PyStringNode[]
     */
    public function map(?DocString $docString) : array
    {
        if (!$docString) {
            return [];
        }

        return [
            new PyStringNode(
                $this->split($docString->content),
                $docString->location->line
            )
        ];
    }

    private function split(string $content) {
        $content = strtr($content, array("\r\n" => "\n", "\r" => "\n"));

        return explode("\n", $content);
    }
}
