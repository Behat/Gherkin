<?php

namespace Behat\Gherkin\Keywords;

use Symfony\Component\Yaml\Yaml;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Cucumber-translations reader.
 *
 * $keywords = new Behat\Gherkin\Keywords\CucumberKeywords($i18nYmlPath);
 *
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class CucumberKeywords extends ArrayKeywords
{
    /**
     * Initializes holder with yaml string OR file.
     *
     * @param   string   $yaml
     */
    public function __construct($yaml)
    {
        parent::__construct(Yaml::parse($yaml));
    }

    /**
     * {@inheritdoc}
     */
    public function getGivenKeywords()
    {
        return $this->prepareStepString(parent::getGivenKeywords());
    }

    /**
     * {@inheritdoc}
     */
    public function getWhenKeywords()
    {
        return $this->prepareStepString(parent::getWhenKeywords());
    }

    /**
     * {@inheritdoc}
     */
    public function getThenKeywords()
    {
        return $this->prepareStepString(parent::getThenKeywords());
    }

    /**
     * {@inheritdoc}
     */
    public function getAndKeywords()
    {
        return $this->prepareStepString(parent::getAndKeywords());
    }

    /**
     * {@inheritdoc}
     */
    public function getButKeywords()
    {
        return $this->prepareStepString(parent::getButKeywords());
    }

    /**
     * Trim *| from the begining of the list.
     *
     * @param   string $keywordsString
     *
     * @return  string
     */
    private function prepareStepString($keywordsString)
    {
        if (0 === mb_strpos($keywordsString, '*|')) {
            $keywordsString = mb_substr($keywordsString, 2);
        }

        return $keywordsString;
    }
}
