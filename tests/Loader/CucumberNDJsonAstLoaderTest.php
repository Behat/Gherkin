<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Loader;

use Behat\Gherkin\Exception\NodeException;
use Behat\Gherkin\Loader\CucumberNDJsonAstLoader;
use PHPUnit\Framework\TestCase;

final class CucumberNDJsonAstLoaderTest extends TestCase
{
    private CucumberNDJsonAstLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new CucumberNDJsonAstLoader();
    }

    public function testStringResourcesAreSupported(): void
    {
        $this->assertTrue($this->loader->supports('a string'));
    }

    public function testOutlineTableHeaderIsRequired(): void
    {
        $this->expectException(NodeException::class);
        $this->expectExceptionMessage('Table header is required, but none was specified for the example on line 3.');

        $this->loader->load($this->serializeCucumberMessagesToFile([
            'gherkinDocument' => [
                'feature' => [
                    'location' => ['line' => 1],
                    'description' => 'Feature containing a scenario with an invalid example table structure',
                    'children' => [
                        [
                            'scenario' => [
                                'location' => ['line' => 2],
                                'examples' => [
                                    [
                                        'location' => ['line' => 3],
                                        'tableBody' => [
                                            [
                                                'cells' => [
                                                    ['value' => 'A1'],
                                                    ['value' => 'B1'],
                                                ],
                                            ],
                                            [
                                                'cells' => [
                                                    ['value' => 'A2'],
                                                    ['value' => 'B2'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]));
    }

    private function serializeCucumberMessagesToFile(mixed ...$messages): string
    {
        return 'data://application/x-ndjson;base64,'
            . base64_encode(implode("\n", array_map(json_encode(...), $messages)) . "\n");
    }
}
