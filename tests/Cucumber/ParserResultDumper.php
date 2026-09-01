<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Cucumber;

use Behat\Gherkin\Exception\ParserException;
use Behat\Gherkin\Node\FeatureNode;
use ReflectionClass;
use Symfony\Component\Yaml\Yaml;

class ParserResultDumper
{
    public function __construct(
        private readonly string $baseDir,
    ) {
    }

    public function dump(FeatureNode|ParserException|null $result): string
    {
        if ($result instanceof ParserException) {
            $values = $this->dumpParserExceptionValues($result);
        } else {
            $values = $this->recursiveDump($result);
        }

        return $this->dumpValues($values);
    }

    /**
     * Re-dumps an already recorded expectation with the YAML component currently installed.
     *
     * The quoting style of the Symfony YAML dumper differs between the supported major versions:
     * 5.4 writes a string holding a "#" as 'Some ''#quoted'' string', later versions as
     * "Some '#quoted' string". Both parse to the same value, so normalising the recorded file
     * before comparing keeps the assertion about the parser result rather than about the dumper.
     */
    public function normalize(string $recordedExpectation): string
    {
        // The placeholder is substituted after dumping and is not valid inline YAML, so the real
        // path has to go back in before the recorded file can be parsed again.
        $restored = str_replace('{BASEDIR}', $this->baseDir, $recordedExpectation);

        return $this->dumpValues(Yaml::parse($restored));
    }

    private function dumpValues(mixed $values): string
    {
        $result = Yaml::dump(
            $values,
            inline: 999,
            flags: Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_NULL_AS_TILDE | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE,
        );

        return $this->replaceAbsolutePaths($result);
    }

    /**
     * @return array<string, array<string,mixed>>
     */
    private function dumpParserExceptionValues(ParserException $exception): array
    {
        // We don't want to include all the properties of the exception, we're not worried about matching the trace etc
        return [
            $exception::class => [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ],
        ];
    }

    private function recursiveDump(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map($this->recursiveDump(...), $value);
        }

        if ($value === null || is_scalar($value)) {
            return $value;
        }

        assert(is_object($value), 'Expected object, got: ' . get_debug_type($value));

        // Use reflection to export the object properties for completeness and simplicity.
        // Note that this is not safe against recursive object references, but is safe to use in this context because
        // we know that the FeatureNode and the objects it contains do not contain any recursive references.
        $values = [];
        $reflection = new ReflectionClass($value);
        do {
            foreach ($reflection->getProperties() as $property) {
                $values[$property->getName()] = match ($property->isInitialized($value)) {
                    true => $this->recursiveDump($property->getValue($value)),
                    false => '**NOT INITIALIZED**',
                };
            }
        } while ($reflection = $reflection->getParentClass());

        return [$value::class => $values];
    }

    private function replaceAbsolutePaths(string $text): string
    {
        $result = preg_replace('/' . preg_quote($this->baseDir, '/') . '/', '{BASEDIR}', $text);
        assert(is_string($result), 'Replacing paths should succeed');

        return $result;
    }
}
