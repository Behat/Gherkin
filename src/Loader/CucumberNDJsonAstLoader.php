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
 * Loads a feature from cucumber's protobuf JSON format.
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
                        return self::getFeature(json_decode($line, true), $resource);
                    },
                    file($resource)
                )
            )
        );
    }

    /**
     * @return FeatureNode|null
     */
    private static function getFeature(array $json, $filePath)
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
     * @return list<string>
     */
    private static function getTags(array $json)
    {
        return array_map(
            static fn (array $tag) => preg_replace('/^@/', '', $tag['name']),
            array_values($json['tags'] ?? [])
        );
    }

    /**
     * @return list<ScenarioInterface>
     */
    private static function getScenarios(array $json)
    {
        return array_values(
            array_map(
                static function ($child) {
                    $tables = self::getTables($child['scenario']['examples']);

                    if ($tables) {
                        return new OutlineNode(
                            $child['scenario']['name'],
                            self::getTags($child['scenario']),
                            self::getSteps($child['scenario']['steps'] ?? []),
                            $tables,
                            $child['scenario']['keyword'],
                            $child['scenario']['location']['line']
                        );
                    }

                    return new ScenarioNode(
                        $child['scenario']['name'],
                        self::getTags($child['scenario']),
                        self::getSteps($child['scenario']['steps'] ?? []),
                        $child['scenario']['keyword'],
                        $child['scenario']['location']['line']
                    );
                },
                array_filter(
                    $json['children'] ?? [],
                    static function ($child) {
                        return isset($child['scenario']);
                    }
                )
            )
        );
    }

    private static function getBackground(array $json): ?BackgroundNode
    {
        $backgrounds = array_filter(
            $json['children'] ?? [],
            static fn ($child) => isset($child['background']),
        );

        if (count($backgrounds) !== 1) {
            return null;
        }

        $background = array_shift($backgrounds);

        return new BackgroundNode(
            $background['background']['name'],
            self::getSteps($background['background']['steps'] ?? []),
            $background['background']['keyword'],
            $background['background']['location']['line']
        );
    }

    /**
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
            array_values($items)
        );
    }

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
     * @return ExampleTableNode[]
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
            array_values($items)
        );
    }
}
