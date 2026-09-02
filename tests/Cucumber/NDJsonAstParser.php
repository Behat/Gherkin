<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Cucumber;

use Behat\Gherkin\Exception\NodeException;
use Behat\Gherkin\GherkinCompatibilityMode;
use Behat\Gherkin\Node\ArgumentInterface;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\RuleNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;
use RuntimeException;
use UnexpectedValueException;

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
class NDJsonAstParser
{
    private GherkinCompatibilityMode $compatibilityMode = GherkinCompatibilityMode::LEGACY;

    public function setGherkinCompatibilityMode(GherkinCompatibilityMode $mode): void
    {
        $this->compatibilityMode = $mode;
    }

    /**
     * @return list<FeatureNode>
     */
    public function load(string $resource): array
    {
        return array_values(
            array_filter(
                array_map(
                    function ($line) use ($resource) {
                        // As we load data from the official Cucumber project, we assume the data matches the JSON schema.
                        // @phpstan-ignore argument.type
                        return $this->getFeature(json_decode($line, true, 512, \JSON_THROW_ON_ERROR), $resource);
                    },
                    file($resource)
                        ?: throw new RuntimeException("Could not load Cucumber json file: $resource."),
                )
            )
        );
    }

    /**
     * @phpstan-param TEnvelope $json
     */
    private function getFeature(array $json, string $filePath): ?FeatureNode
    {
        if (!isset($json['gherkinDocument']['feature'])) {
            return null;
        }

        $featureJson = $json['gherkinDocument']['feature'];

        [$background, $children] = $this->splitBackgroundAndChildren($this->getChildren($featureJson['children'], allowRule: true));

        return new FeatureNode(
            $featureJson['name'],
            $featureJson['description'],
            $this->getTags($featureJson),
            $background,
            $children,
            $featureJson['keyword'],
            $featureJson['language'],
            preg_replace('/(?<=\\.feature).*$/', '', $filePath),
            $featureJson['location']['line'],
        );
    }

    /**
     * @param list<BackgroundNode|ScenarioInterface|RuleNode> $children
     *
     * @return array{?BackgroundNode, list<ScenarioInterface|RuleNode>}
     */
    private function splitBackgroundAndChildren(array $children): array
    {
        if ($children === []) {
            return [null, []];
        }

        if ($children[0] instanceof BackgroundNode) {
            $background = $children[0];
            $children = array_slice($children, 1);
        } else {
            $background = null;
        }

        foreach ($children as $child) {
            if (!$child instanceof ScenarioInterface && !$child instanceof RuleNode) {
                throw new UnexpectedValueException('Invalid child node type found in feature children');
            }
        }

        return [$background, $children];
    }

    /**
     * @phpstan-param array{tags: list<TTag>, ...} $json
     *
     * @return list<string>
     */
    private function getTags(array $json): array
    {
        return array_map(
            fn (array $tag) => match ($this->compatibilityMode->shouldRemoveTagPrefixChar()) {
                // The cucumber/gherkin testdata contains the @ prefix. We need to remove that to match our legacy
                // parser. It's not ideal to modify the expected parser result here, but the custom comparator approach
                // we have used for other parity variations is tricky because of the variety taggable nodes.
                true => preg_replace('/^@/', '', $tag['name']) ?? $tag['name'],
                false => $tag['name'],
            },
            $json['tags'],
        );
    }

    /**
     * @phpstan-param list<TRuleChild|TFeatureChild> $featureOrRuleChildren
     *
     * @phpstan-return ($allowRule is true ? list<BackgroundNode|RuleNode|ScenarioInterface> : list<BackgroundNode|ScenarioInterface>)
     */
    private function getChildren(array $featureOrRuleChildren, bool $allowRule): array
    {
        $children = array_values(array_map(
            function ($child) use ($allowRule) {
                if (count($child) > 1) {
                    throw new UnexpectedValueException('Unexpected child type ' . json_encode(array_keys($child)));
                }

                if (isset($child['scenario'])) {
                    return $this->getScenario($child['scenario']);
                }

                if (isset($child['background'])) {
                    return $this->getBackground($child['background']);
                }

                if (isset($child['rule']) && $allowRule) {
                    return $this->getRule($child['rule']);
                }

                throw new UnexpectedValueException('Unexpected child type ' . json_encode(array_keys($child)));
            },
            $featureOrRuleChildren,
        ));

        foreach (array_slice($children, 1) as $child) {
            if ($child instanceof BackgroundNode) {
                throw new UnexpectedValueException('Unexpected background node after first child');
            }
        }

        return $children;
    }

    /**
     * @phpstan-param TRule $rule
     */
    private function getRule(array $rule): RuleNode
    {
        $children = $this->getChildren($rule['children'], allowRule: false);

        return new RuleNode(
            $rule['name'],
            $rule['description'],
            $this->getTags($rule),
            $children,
            $rule['keyword'],
            $rule['location']['line'],
        );
    }

    /**
     * @phpstan-param TScenario $scenario
     */
    private function getScenario(array $scenario): ScenarioNode|OutlineNode
    {
        $tables = $this->getTables($scenario['examples']);

        if ($tables) {
            return new OutlineNode(
                $scenario['name'],
                $this->getTags($scenario),
                $this->getSteps($scenario['steps']),
                $tables,
                $scenario['keyword'],
                $scenario['location']['line'],
                $scenario['description'],
            );
        }

        return new ScenarioNode(
            $scenario['name'],
            $this->getTags($scenario),
            $this->getSteps($scenario['steps']),
            $scenario['keyword'],
            $scenario['location']['line'],
            $scenario['description'],
        );
    }

    /**
     * @phpstan-param TBackground $background
     */
    private function getBackground(array $background): BackgroundNode
    {
        return new BackgroundNode(
            $background['name'],
            $this->getSteps($background['steps']),
            $background['keyword'],
            $background['location']['line'],
            $background['description'],
        );
    }

    /**
     * @phpstan-param list<TStep> $items
     *
     * @return list<StepNode>
     */
    private function getSteps(array $items): array
    {
        return array_map(
            fn (array $item) => new StepNode(
                $item['keyword'],
                $item['text'],
                $this->getStepArguments($item),
                $item['location']['line'],
                trim($item['keyword']),
                $item['keyword'] . $item['text'],
            ),
            $items,
        );
    }

    /**
     * @phpstan-param TStep $step
     *
     * @return list<ArgumentInterface>
     */
    private function getStepArguments(array $step): array
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
    private function getTables(array $items): array
    {
        return array_map(
            function ($tableJson): ExampleTableNode {
                $headerRow = $tableJson['tableHeader'] ?? null;
                $tableBody = $tableJson['tableBody'];

                if ($headerRow === null && ($tableBody !== [])) {
                    throw new NodeException(
                        sprintf(
                            'Table header is required when a table body is provided for the example on line %s.',
                            $tableJson['location']['line'],
                        ),
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
                    $this->getTags($tableJson),
                    $tableJson['name'],
                    $tableJson['description'],
                );
            },
            $items,
        );
    }
}
