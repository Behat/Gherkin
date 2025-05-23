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
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;

/**
 * From-array loader.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @phpstan-type TFeatureHash array{title?: string|null, description?: string|null, tags?: list<string>, keyword?: string, language?: string, line?: int, background?: TBackgroundHash|null, scenarios?: array<int, TScenarioHash|TOutlineHash>}
 * @phpstan-type TBackgroundHash array{title?: string|null, keyword?: string, line?: int, steps?: array<int, TStepHash>}
 * @phpstan-type TScenarioHash array{type?: 'scenario', title?: string|null, tags?: list<string>, keyword?: string, line?: int, steps?: array<int, TStepHash>}
 * @phpstan-type TOutlineHash array{type: 'outline', title?: string|null, tags?: list<string>, keyword?: string, line?: int, steps?: array<int, TStepHash>, examples?: TExampleTableHash|array<array-key, TExampleHash>}
 * @phpstan-type TExampleHash array{table: TExampleTableHash, tags?: list<string>}|TExampleTableHash
 * @phpstan-type TExampleTableHash array<int<1, max>, list<string>>
 * @phpstan-type TStepHash array{keyword_type?: string, type?: string, text: string, keyword?: string, line?: int, arguments?: array<array-key, TArgumentHash>}
 * @phpstan-type TArgumentHash array{type: 'table', rows: TTableHash}|TPySstringHash
 * @phpstan-type TTableHash array<int, list<string|int>>
 * @phpstan-type TPySstringHash array{type: 'pystring', line?: int, text: string}
 * @phpstan-type TArrayResource array{feature: TFeatureHash}|array{features: array<int, TFeatureHash>}
 */
class ArrayLoader implements LoaderInterface
{
    /**
     * Checks if current loader supports provided resource.
     *
     * @param mixed $resource Resource to load
     *
     * @return bool
     */
    public function supports($resource)
    {
        return is_array($resource) && (isset($resource['features']) || isset($resource['feature']));
    }

    /**
     * Loads features from provided resource.
     *
     * @param mixed $resource Resource to load
     *
     * @phpstan-param TArrayResource $resource
     *
     * @return list<FeatureNode>
     */
    public function load($resource)
    {
        $features = [];

        if (isset($resource['features'])) {
            foreach ($resource['features'] as $iterator => $hash) {
                $feature = $this->loadFeatureHash($hash, $iterator);
                $features[] = $feature;
            }
        } elseif (isset($resource['feature'])) {
            $feature = $this->loadFeatureHash($resource['feature']);
            $features[] = $feature;
        }

        return $features;
    }

    /**
     * Loads feature from provided feature hash.
     *
     * @phpstan-param TFeatureHash $hash
     *
     * @return FeatureNode
     */
    protected function loadFeatureHash(array $hash, int $line = 0)
    {
        $hash = array_merge(
            [
                'title' => null,
                'description' => null,
                'tags' => [],
                'keyword' => 'Feature',
                'language' => 'en',
                'line' => $line,
                'scenarios' => [],
            ],
            $hash
        );
        $background = isset($hash['background']) ? $this->loadBackgroundHash($hash['background']) : null;

        $scenarios = [];
        foreach ((array) $hash['scenarios'] as $scenarioIterator => $scenarioHash) {
            if (isset($scenarioHash['type']) && $scenarioHash['type'] === 'outline') {
                $scenarios[] = $this->loadOutlineHash($scenarioHash, $scenarioIterator);
            } else {
                $scenarios[] = $this->loadScenarioHash($scenarioHash, $scenarioIterator);
            }
        }

        return new FeatureNode($hash['title'], $hash['description'], $hash['tags'], $background, $scenarios, $hash['keyword'], $hash['language'], null, $hash['line']);
    }

    /**
     * Loads background from provided hash.
     *
     * @phpstan-param TBackgroundHash $hash
     *
     * @return BackgroundNode
     */
    protected function loadBackgroundHash(array $hash)
    {
        $hash = array_merge(
            [
                'title' => null,
                'keyword' => 'Background',
                'line' => 0,
                'steps' => [],
            ],
            $hash
        );

        $steps = $this->loadStepsHash($hash['steps']);

        return new BackgroundNode($hash['title'], $steps, $hash['keyword'], $hash['line']);
    }

    /**
     * Loads scenario from provided scenario hash.
     *
     * @phpstan-param TScenarioHash $hash
     *
     * @return ScenarioNode
     */
    protected function loadScenarioHash(array $hash, int $line = 0)
    {
        $hash = array_merge(
            [
                'title' => null,
                'tags' => [],
                'keyword' => 'Scenario',
                'line' => $line,
                'steps' => [],
            ],
            $hash
        );

        $steps = $this->loadStepsHash($hash['steps']);

        return new ScenarioNode($hash['title'], $hash['tags'], $steps, $hash['keyword'], $hash['line']);
    }

    /**
     * Loads outline from provided outline hash.
     *
     * @phpstan-param TOutlineHash $hash
     *
     * @return OutlineNode
     */
    protected function loadOutlineHash(array $hash, int $line = 0)
    {
        $hash = array_merge(
            [
                'title' => null,
                'tags' => [],
                'keyword' => 'Scenario Outline',
                'line' => $line,
                'steps' => [],
                'examples' => [],
            ],
            $hash
        );

        $steps = $this->loadStepsHash($hash['steps']);

        if (isset($hash['examples']['keyword'])) {
            $examplesKeyword = $hash['examples']['keyword'];
            unset($hash['examples']['keyword']);
        } else {
            $examplesKeyword = 'Examples';
        }

        $examples = $this->loadExamplesHash($hash['examples'], $examplesKeyword);

        return new OutlineNode($hash['title'], $hash['tags'], $steps, $examples, $hash['keyword'], $hash['line']);
    }

    /**
     * Loads steps from provided hash.
     *
     * @phpstan-param array<int, TStepHash> $hash
     *
     * @return list<StepNode>
     */
    private function loadStepsHash(array $hash)
    {
        $steps = [];
        foreach ($hash as $stepIterator => $stepHash) {
            $steps[] = $this->loadStepHash($stepHash, $stepIterator);
        }

        return $steps;
    }

    /**
     * Loads step from provided hash.
     *
     * @phpstan-param TStepHash $hash
     *
     * @return StepNode
     */
    protected function loadStepHash(array $hash, int $line = 0)
    {
        $hash = array_merge(
            [
                'keyword_type' => 'Given',
                'type' => 'Given',
                'text' => null,
                'keyword' => 'Scenario',
                'line' => $line,
                'arguments' => [],
            ],
            $hash
        );

        $arguments = [];
        foreach ($hash['arguments'] as $argumentHash) {
            if ($argumentHash['type'] === 'table') {
                $arguments[] = $this->loadTableHash($argumentHash['rows']);
            } elseif ($argumentHash['type'] === 'pystring') {
                $arguments[] = $this->loadPyStringHash($argumentHash, $hash['line'] + 1);
            }
        }

        return new StepNode($hash['type'], $hash['text'], $arguments, $hash['line'], $hash['keyword_type']);
    }

    /**
     * Loads table from provided hash.
     *
     * @phpstan-param TTableHash $hash
     *
     * @return TableNode
     */
    protected function loadTableHash(array $hash)
    {
        return new TableNode($hash);
    }

    /**
     * Loads PyString from provided hash.
     *
     * @phpstan-param TPySstringHash $hash
     *
     * @return PyStringNode
     */
    protected function loadPyStringHash(array $hash, int $line = 0)
    {
        $line = $hash['line'] ?? $line;

        $strings = [];
        foreach (explode("\n", $hash['text']) as $string) {
            $strings[] = $string;
        }

        return new PyStringNode($strings, $line);
    }

    /**
     * Processes cases when examples are in the form of array of arrays
     * OR in the form of array of objects.
     *
     * @phpstan-param TExampleHash|array<array-key, TExampleHash> $examplesHash
     *
     * @return list<ExampleTableNode>
     */
    private function loadExamplesHash(array $examplesHash, string $examplesKeyword): array
    {
        if (!isset($examplesHash[0])) {
            // examples as a single table - create a list with the one element
            return [new ExampleTableNode($examplesHash, $examplesKeyword)];
        }

        $examples = [];

        foreach ($examplesHash as $exampleHash) {
            if (isset($exampleHash['table'])) {
                // we have examples as objects, hence there could be tags
                $exHashTags = $exampleHash['tags'] ?? [];
                $examples[] = new ExampleTableNode($exampleHash['table'], $examplesKeyword, $exHashTags);
            } else {
                // we have examples as arrays
                $examples[] = new ExampleTableNode($exampleHash, $examplesKeyword);
            }
        }

        return $examples;
    }
}
