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

use function strlen;

/**
 * Represents Gherkin Feature.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class FeatureNode implements KeywordNodeInterface, TaggedNodeInterface
{
    /**
     * @var string|null
     */
    private $title;
    /**
     * @var string|null
     */
    private $description;
    /**
     * @var string[]
     */
    private $tags = [];
    /**
     * @var BackgroundNode|null
     */
    private $background;
    /**
     * @var ScenarioInterface[]
     */
    private $scenarios = [];
    /**
     * @var string
     */
    private $keyword;
    /**
     * @var string
     */
    private $language;
    /**
     * @var string|null
     */
    private $file;
    /**
     * @var int
     */
    private $line;

    /**
     * Initializes feature.
     *
     * @param string|null $title
     * @param string|null $description
     * @param string[] $tags
     * @param ScenarioInterface[] $scenarios
     * @param string $keyword
     * @param string $language
     * @param string|null $file the absolute path to the feature file
     * @param int $line
     */
    public function __construct(
        $title,
        $description,
        array $tags,
        ?BackgroundNode $background,
        array $scenarios,
        $keyword,
        $language,
        $file,
        $line,
    ) {
        // Verify that the feature file is an absolute path.
        if (!empty($file) && !$this->isAbsolutePath($file)) {
            throw new InvalidArgumentException('The file should be an absolute path.');
        }
        $this->title = $title;
        $this->description = $description;
        $this->tags = $tags;
        $this->background = $background;
        $this->scenarios = $scenarios;
        $this->keyword = $keyword;
        $this->language = $language;
        $this->file = $file;
        $this->line = $line;
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

    /**
     * Checks if feature is tagged with tag.
     *
     * @param string $tag
     *
     * @return bool
     */
    public function hasTag($tag)
    {
        return in_array($tag, $this->tags);
    }

    /**
     * Checks if feature has tags.
     *
     * @return bool
     */
    public function hasTags()
    {
        return count($this->tags) > 0;
    }

    /**
     * Returns feature tags.
     *
     * @return string[]
     */
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
     * @return ScenarioInterface[]
     */
    public function getScenarios()
    {
        return $this->scenarios;
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
     * Returns whether the file path is an absolute path.
     *
     * @param string|null $file A file path
     *
     * @return bool
     *
     * @see https://github.com/symfony/filesystem/blob/master/Filesystem.php
     */
    protected function isAbsolutePath($file)
    {
        if ($file === null) {
            throw new InvalidArgumentException('The provided file path must not be null.');
        }

        return strspn($file, '/\\', 0, 1)
            || (strlen($file) > 3 && ctype_alpha($file[0])
                && $file[1] === ':'
                && strspn($file, '/\\', 2, 1)
            )
            || parse_url($file, PHP_URL_SCHEME) !== null
        ;
    }
}
