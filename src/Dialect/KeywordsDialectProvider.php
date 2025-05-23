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
use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Keywords\KeywordsInterface;

/**
 * Adapter for the legacy keywords interface.
 *
 * @internal
 */
final class KeywordsDialectProvider implements DialectProviderInterface
{
    public function __construct(
        private readonly KeywordsInterface $keywords,
    ) {
    }

    public function getDialect(string $language): GherkinDialect
    {
        // The legacy keywords interface doesn't support detecting whether changing the language worked or no.
        $this->keywords->setLanguage($language);

        if ($this->keywords instanceof ArrayKeywords && $this->keywords->getLanguage() !== $language) {
            throw new NoSuchLanguageException($language);
        }

        return $this->buildDialect($language);
    }

    public function getDefaultDialect(): GherkinDialect
    {
        // Assume a default dialect of `en` as the KeywordsInterface does not allow reading its language but returns the current data
        $language = $this->keywords instanceof ArrayKeywords ? $this->keywords->getLanguage() : 'en';

        return $this->buildDialect($language);
    }

    private function buildDialect(string $language): GherkinDialect
    {
        return new GherkinDialect($language, [
            'feature' => self::parseKeywords($this->keywords->getFeatureKeywords()),
            'background' => self::parseKeywords($this->keywords->getBackgroundKeywords()),
            'scenario' => self::parseKeywords($this->keywords->getScenarioKeywords()),
            'scenarioOutline' => self::parseKeywords($this->keywords->getOutlineKeywords()),
            'examples' => self::parseKeywords($this->keywords->getExamplesKeywords()),
            'rule' => ['Rule'], // Hardcoded value as our old keywords interface doesn't support rules.
            'given' => self::parseStepKeywords($this->keywords->getGivenKeywords()),
            'when' => self::parseStepKeywords($this->keywords->getWhenKeywords()),
            'then' => self::parseStepKeywords($this->keywords->getThenKeywords()),
            'and' => self::parseStepKeywords($this->keywords->getAndKeywords()),
            'but' => self::parseStepKeywords($this->keywords->getButKeywords()),
        ]);
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    private static function parseKeywords(string $keywordString): array
    {
        $keywords = array_values(array_filter(explode('|', $keywordString)));

        if ($keywords === []) {
            throw new \LogicException('A keyword string must contain at least one keyword.');
        }

        return $keywords;
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    private static function parseStepKeywords(string $keywordString): array
    {
        $legacyKeywords = explode('|', $keywordString);
        $keywords = [];

        foreach ($legacyKeywords as $legacyKeyword) {
            if (\strlen($legacyKeyword) >= 2 && str_ends_with($legacyKeyword, '<')) {
                $keyword = substr($legacyKeyword, 0, -1);
                \assert($keyword !== ''); // phpstan is not smart enough to detect that the length check above guarantees this invariant
                $keywords[] = $keyword;
            } else {
                $keywords[] = $legacyKeyword . ' ';
            }
        }

        return $keywords;
    }
}
