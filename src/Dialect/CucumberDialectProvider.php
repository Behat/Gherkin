<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Dialect;

use Behat\Gherkin\Exception\NoSuchLanguageException;
use Behat\Gherkin\Filesystem;

/**
 * A dialect provider that loads the dialects based on the gherkin-languages.json file copied from the Cucumber project.
 *
 * @phpstan-import-type TDialectData from GherkinDialect
 */
final class CucumberDialectProvider implements DialectProviderInterface
{
    /**
     * @var non-empty-array<non-empty-string, TDialectData>
     */
    private readonly array $dialects;

    public function __construct()
    {
        /**
         * Here we force the type checker to assume the decoded JSON has the correct
         * structure, rather than validating it. This is safe because it's not dynamic.
         *
         * @var non-empty-array<non-empty-string, TDialectData> $data
         */
        $data = Filesystem::readJsonFileHash(__DIR__ . '/../../resources/gherkin-languages.json');
        $this->dialects = $data;
    }

    /**
     * @param non-empty-string $language
     *
     * @throws NoSuchLanguageException
     */
    public function getDialect(string $language): GherkinDialect
    {
        if (!isset($this->dialects[$language])) {
            throw new NoSuchLanguageException($language);
        }

        return new GherkinDialect($language, $this->dialects[$language]);
    }

    public function getDefaultDialect(): GherkinDialect
    {
        return $this->getDialect('en');
    }
}
