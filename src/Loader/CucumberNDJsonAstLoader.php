<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Loader;

use Behat\Gherkin\Exception\NodeException;
use Behat\Gherkin\Node\ArgumentInterface;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Loads a feature from cucumber's messages JSON format.
 *
 * Lines in the ndjson file are expected to match the Cucumber Messages JSON schema defined at https://github.com/cucumber/messages/tree/main/jsonschema
 *
 * @phpstan-type TLocation array{line: int, column?: int}
 * @phpstan-type TBackground array{location: TLocation, keyword: string, name: string, description: string, steps: list<TStep>, id: string}
 * @phpstan-type TComment array{location: TLocation, text: string}
 * @phpstan-type TDataTable array{location: TLocation, rows: list<TTableRow>}
 * @phpstan-type TDocString array{location: TLocation, content: string, delimiter: string, mediaType?: string}
 * @phpstan-type TExamples array{location: TLocation, tags: list<TTag>, keyword: string, name: string, description: string, tableHeader?: TTableRow, tableBody: list<TTableRow>, id: string}
 * @phpstan-type TFeature array{location: TLocation, tags: list<TTag>, language: string, keyword: string, name: string, description: string, children: list<TFeatureChild>}
 * @phpstan-type TFeatureChild array{background?: TBackground, scenario?: TScenario, rule?: TRule}
 * @phpstan-type TRule array{location: TLocation, tags: list<TTag>, keyword: string, name: string, description: string, children: list<TRuleChild>, id: string}
 * @phpstan-type TRuleChild array{background?: TBackground, scenario?: TScenario}
 * @phpstan-type TScenario array{location: TLocation, tags: list<TTag>, keyword: string, name: string, description: string, steps: list<TStep>, examples: list<TExamples>, id: string}
 * @phpstan-type TStep array{location: TLocation, keyword: string, keywordType?: 'Unknown'|'Context'|'Action'|'Outcome'|'Conjunction', text: string, docString?: TDocString, dataTable?: TDataTable, id: string}
 * @phpstan-type TTableCell array{location: TLocation, value: string}
 * @phpstan-type TTableRow array{location: TLocation, cells: list<TTableCell>, id: string}
 * @phpstan-type TTag array{location: TLocation, name: string, id: string}
 * @phpstan-type TGherkinDocument array{uri?: string, feature?: TFeature, comments: list<TComment>}
 * // We only care about the gherkinDocument messages for our use case, so this does not describe the envelope fully
 * @phpstan-type TEnvelope array{gherkinDocument?: TGherkinDocument, ...}
 */
class CucumberNDJsonAstLoader implements LoaderInterface
{
    public function supports($resource)
    {
        return is_string($resource);
    }

    public function load($resource)
    {
        return array_values(
            array_filter(
                array_map(
                    static function ($line) use ($resource) {
                        // As we load data from the official Cucumber project, we assume the data matches the JSON schema.
                        return self::getFeature(json_decode($line, true, 512, \JSON_THROW_ON_ERROR), $resource);
                    },
                    file($resource)
                )
            )
        );
    }

    /**
     * @phpstan-param TEnvelope $json
     */
    private static function getFeature(array $json, string $filePath): ?FeatureNode
    {
        if (!isset($json['gherkinDocument']['feature'])) {
            return null;
        }

        $featureJson = $json['gherkinDocument']['feature'];

        return new FeatureNode(
            $featureJson['name'],
            $featureJson['description'],
            self::getTags($featureJson),
            self::getBackground($featureJson),
            self::getScenarios($featureJson),
            $featureJson['keyword'],
            $featureJson['language'],
            preg_replace('/(?<=\\.feature).*$/', '', $filePath),
            $featureJson['location']['line']
        );
    }

    /**
     * @phpstan-param array{tags: list<TTag>, ...} $json
     *
     * @return list<string>
     */
    private static function getTags(array $json): array
    {
        return array_map(
            static fn (array $tag) => preg_replace('/^@/', '', $tag['name']),
            $json['tags']
        );
    }

    /**
     * @phpstan-param TFeature $json
     *
     * @return list<ScenarioInterface>
     */
    private static function getScenarios(array $json): array
    {
        return array_values(
            array_map(
                static function ($child) {
                    $tables = self::getTables($child['scenario']['examples']);

                    if ($tables) {
                        return new OutlineNode(
                            $child['scenario']['name'],
                            self::getTags($child['scenario']),
                            self::getSteps($child['scenario']['steps']),
                            $tables,
                            $child['scenario']['keyword'],
                            $child['scenario']['location']['line']
                        );
                    }

                    return new ScenarioNode(
                        $child['scenario']['name'],
                        self::getTags($child['scenario']),
                        self::getSteps($child['scenario']['steps']),
                        $child['scenario']['keyword'],
                        $child['scenario']['location']['line']
                    );
                },
                array_filter(
                    $json['children'],
                    static function ($child) {
                        return isset($child['scenario']);
                    }
                )
            )
        );
    }

    /**
     * @phpstan-param TFeature $json
     */
    private static function getBackground(array $json): ?BackgroundNode
    {
        $backgrounds = array_filter(
            $json['children'],
            static fn ($child) => isset($child['background']),
        );

        if (count($backgrounds) !== 1) {
            return null;
        }

        $background = array_shift($backgrounds);

        return new BackgroundNode(
            $background['background']['name'],
            self::getSteps($background['background']['steps']),
            $background['background']['keyword'],
            $background['background']['location']['line']
        );
    }

    /**
     * @phpstan-param list<TStep> $items
     *
     * @return list<StepNode>
     */
    private static function getSteps(array $items): array
    {
        return array_map(
            static fn (array $item) => new StepNode(
                trim($item['keyword']),
                $item['text'],
                self::getStepArguments($item),
                $item['location']['line'],
                trim($item['keyword'])
            ),
            $items
        );
    }

    /**
     * @phpstan-param TStep $step
     *
     * @return list<ArgumentInterface>
     */
    private static function getStepArguments(array $step): array
    {
        $args = [];

        if (isset($step['docString'])) {
            $args[] = new PyStringNode(
                explode("\n", $step['docString']['content']),
                $step['docString']['location']['line'],
            );
        }

        if (isset($step['dataTable'])) {
            $table = [];
            foreach ($step['dataTable']['rows'] as $row) {
                $table[$row['location']['line']] = array_column($row['cells'], 'value');
            }
            $args[] = new TableNode($table);
        }

        return $args;
    }

    /**
     * @phpstan-param list<TExamples> $items
     *
     * @return list<ExampleTableNode>
     */
    private static function getTables(array $items): array
    {
        return array_map(
            static function ($tableJson): ExampleTableNode {
                $headerRow = $tableJson['tableHeader'] ?? null;
                $tableBody = $tableJson['tableBody'];

                if ($headerRow === null && ($tableBody !== [])) {
                    throw new NodeException(
                        sprintf(
                            'Table header is required when a table body is provided for the example on line %s.',
                            $tableJson['location']['line'],
                        )
                    );
                }

                $table = [];
                if ($headerRow !== null) {
                    $table[$headerRow['location']['line']] = array_column($headerRow['cells'], 'value');
                }

                foreach ($tableBody as $bodyRow) {
                    $table[$bodyRow['location']['line']] = array_column($bodyRow['cells'], 'value');
                }

                return new ExampleTableNode(
                    $table,
                    $tableJson['keyword'],
                    self::getTags($tableJson)
                );
            },
            $items
        );
    }
}
