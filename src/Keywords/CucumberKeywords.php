<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Keywords;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Cucumber-translations reader.
 *
 * $keywords = new Behat\Gherkin\Keywords\CucumberKeywords($i18nYmlPath);
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class CucumberKeywords extends ArrayKeywords
{
    /**
     * Initializes holder with yaml string OR file.
     *
     * @param string $yaml Yaml string or file path
     */
    public function __construct(string $yaml)
    {
        if (!str_contains($yaml, "\n") && is_file($yaml)) {
            $content = Yaml::parseFile($yaml);
        } else {
            $content = Yaml::parse($yaml);
        }

        if (!is_array($content)) {
            throw new ParseException(sprintf('Root element must be an array, but %s found.', get_debug_type($content)));
        }

        // @phpstan-ignore argument.type
        parent::__construct($content);
    }

    /**
     * Returns Feature keywords (separated by "|").
     *
     * @return string
     */
    public function getGivenKeywords()
    {
        return $this->prepareStepString(parent::getGivenKeywords());
    }

    /**
     * Returns When keywords (separated by "|").
     *
     * @return string
     */
    public function getWhenKeywords()
    {
        return $this->prepareStepString(parent::getWhenKeywords());
    }

    /**
     * Returns Then keywords (separated by "|").
     *
     * @return string
     */
    public function getThenKeywords()
    {
        return $this->prepareStepString(parent::getThenKeywords());
    }

    /**
     * Returns And keywords (separated by "|").
     *
     * @return string
     */
    public function getAndKeywords()
    {
        return $this->prepareStepString(parent::getAndKeywords());
    }

    /**
     * Returns But keywords (separated by "|").
     *
     * @return string
     */
    public function getButKeywords()
    {
        return $this->prepareStepString(parent::getButKeywords());
    }

    /**
     * Trim *| from the beginning of the list.
     */
    private function prepareStepString(string $keywordsString): string
    {
        if (str_starts_with($keywordsString, '*|')) {
            $keywordsString = mb_substr($keywordsString, 2, mb_strlen($keywordsString, 'utf8') - 2, 'utf8');
        }

        return $keywordsString;
    }
}
