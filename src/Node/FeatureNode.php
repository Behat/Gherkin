<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Node;

use InvalidArgumentException;
use UnexpectedValueException;
use WeakMap;

use function strlen;

/**
 * Represents Gherkin Feature.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @final since 4.15.0
 */
class FeatureNode implements KeywordNodeInterface, TaggedNodeInterface, DescribableNodeInterface
{
    use TaggedNodeTrait;

    /**
     * @param list<string> $tags
     * @param array<RuleNode|ScenarioInterface> $scenarios
     * @param string|null $file the absolute path to the feature file
     */
    public function __construct(
        private readonly ?string $title,
        private readonly ?string $description,
        private readonly array $tags,
        private readonly ?BackgroundNode $background,
        private readonly array $scenarios,
        private readonly string $keyword,
        private readonly string $language,
        private readonly ?string $file,
        private readonly int $line,
    ) {
        // Verify that the feature file is an absolute path.
        if (!empty($file) && !$this->isAbsolutePath($file)) {
            throw new InvalidArgumentException('The file should be an absolute path.');
        }
    }

    /**
     * Returns node type string.
     *
     * @return string
     */
    public function getNodeType()
    {
        return 'Feature';
    }

    /**
     * Returns feature title.
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Checks if feature has a description.
     *
     * @return bool
     */
    public function hasDescription()
    {
        return !empty($this->description);
    }

    /**
     * Returns feature description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Checks if feature has background.
     *
     * @return bool
     */
    public function hasBackground()
    {
        return $this->background !== null;
    }

    /**
     * Returns feature background.
     *
     * @return BackgroundNode|null
     */
    public function getBackground()
    {
        return $this->background;
    }

    /**
     * Checks if feature has scenarios.
     *
     * @return bool
     */
    public function hasScenarios()
    {
        return count($this->scenarios) > 0;
    }

    /**
     * Returns feature scenarios.
     *
     * To provide backwards compatibility, this method will hoist any scenarios nested insideRules (losing any
     * information about the Rule in the process). If the Rule had a Background, then any steps from that will be cloned
     * and merged into each Scenario.
     *
     * @return ScenarioInterface[]
     *
     * @deprecated use getChildren() for first-class handling of Rule nodes
     */
    public function getScenarios()
    {
        // It is safe for this cache to be static, because the result is always the same for a given RuleNode,
        // and the WeakMap will automatically clean up any references to RuleNodes that are no longer in use.
        // Making it static and private to this method also eliminates any potential issues with serialising
        // FeatureNode e.g. for caching or tests.
        static $ruleScenariosCache;

        $result = [];
        foreach ($this->scenarios as $child) {
            if ($child instanceof RuleNode) {
                $ruleScenariosCache ??= new WeakMap();
                /** @var WeakMap<RuleNode, array<int, ScenarioInterface>> $ruleScenariosCache */
                $ruleScenariosCache[$child] ??= $this->extractRuleScenarios($child);
                array_push($result, ...$ruleScenariosCache[$child]);
            } else {
                $result[] = $child;
            }
        }

        return $result;
    }

    /**
     * @return array<int, ScenarioInterface>
     */
    private function extractRuleScenarios(RuleNode $node): array
    {
        $backgroundSteps = array_values($node->getBackground()?->getSteps() ?? []);

        return array_filter(array_map(
            function ($child) use ($backgroundSteps) {
                if ($child instanceof BackgroundNode) {
                    // This has already been handled
                    return null;
                }

                if ($backgroundSteps === []) {
                    // There's no background, so nothing to merge or convert - just return the original nodes.
                    return $child;
                }

                if (($child::class === ScenarioNode::class) || ($child::class === OutlineNode::class)) {
                    // We only do the ->withSteps expansion on our own classes, as we don't control the constructor
                    // signature on any third-party ScenarioInterface classes.
                    return $child->withSteps([
                        ...$backgroundSteps,
                        ...array_values($child->getSteps()),
                    ]);
                }

                throw new UnexpectedValueException('Cannot merge rule background and scenario steps for custom ScenarioInterface ' . $child::class);
            },
            $node->getChildren())
        );
    }

    /**
     * @return list<BackgroundNode|RuleNode|ScenarioInterface>
     */
    public function getChildren(): array
    {
        $children = $this->scenarios;
        if ($this->background) {
            array_unshift($children, $this->background);
        }

        return array_values($children);
    }

    /**
     * Returns feature keyword.
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * Returns feature language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Returns feature file as an absolute path.
     *
     * @return string|null
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Returns feature declaration line number.
     *
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * Returns a copy of this feature, but with a different set of scenarios.
     *
     * @param array<array-key, ScenarioInterface> $scenarios
     */
    public function withScenarios(array $scenarios): self
    {
        return new self(
            $this->title,
            $this->description,
            $this->tags,
            $this->background,
            array_values($scenarios),
            $this->keyword,
            $this->language,
            $this->file,
            $this->line,
        );
    }

    /**
     * Returns whether the file path is an absolute path.
     *
     * @param string|null $file A file path
     *
     * @return bool
     *
     * @see https://github.com/symfony/filesystem/blob/master/Filesystem.php
     */
    protected function isAbsolutePath(?string $file)
    {
        if ($file === null) {
            throw new InvalidArgumentException('The provided file path must not be null.');
        }

        return strspn($file, '/\\', 0, 1)
            || (strlen($file) > 3 && ctype_alpha($file[0])
                && $file[1] === ':'
                && strspn($file, '/\\', 2, 1)
            )
            || parse_url($file, PHP_URL_SCHEME) !== null;
    }
}
