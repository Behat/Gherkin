<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Loader;

use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;

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
        return array_values(array_filter(array_map(
            static function ($line) use ($resource) {
                return self::getFeature(json_decode($line, true), $resource);
            },
            file($resource)
        )));
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
            $featureJson['name'] ?? null,
            $featureJson['description'] ? trim($featureJson['description']) : null,
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
                    if ($child['scenario']['examples']) {
                        return new OutlineNode(
                            $child['scenario']['name'] ?? null,
                            self::getTags($child['scenario']),
                            self::getSteps($child['scenario']['steps'] ?? []),
                            self::getTables($child['scenario']['examples']),
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
                [],
                $item['location']['line'],
                trim($item['keyword'])
            ),
            array_values($items)
        );
    }

    /**
     * @return ExampleTableNode[]
     */
    private static function getTables(array $items): array
    {
        return array_map(
            static function ($tableJson) {
                $table = [];

                $table[$tableJson['tableHeader']['location']['line']] = array_column($tableJson['tableHeader']['cells'], 'value');

                foreach ($tableJson['tableBody'] as $bodyRow) {
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
