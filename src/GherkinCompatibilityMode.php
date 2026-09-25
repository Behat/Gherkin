<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin;

/**
 * Configures parser behaviour relative to official cucumber/gherkin parser versions.
 *
 * This enum is **non-exhaustive**. We may add case values for new parser modes in future
 * minor releases.
 *
 * Modes will only be removed in a major release - but they may be deprecated (and emit
 * runtime deprecations) in minor releases.
 *
 * @see https://docs.behat.org/en/latest/user_guide/gherkin/parser_mode.html
 */
enum GherkinCompatibilityMode: string
{
    /**
     * Match the behaviour of as older versions of this library.
     * Some newer Gherkin syntax is not supported, and some is parsed differently
     * to official cucumber/gherkin parsers.
     * Not recommended for new projects - but this is the default in Behat 3.x.
     */
    case LEGACY = 'legacy';

    /**
     * Match the behaviour of cucumber/gherkin parsers version >= 32.0 < 42.0.
     */
    case GHERKIN_32 = 'gherkin-32';

    /**
     * Match the behaviour of cucumber/gherkin parsers version >= 42.0
     * This is the default mode in Behat 4.0.
     */
    case GHERKIN_42 = 'gherkin-42';

    /**
     * @internal
     */
    public function shouldRemoveStepKeywordSpace(): bool
    {
        return match ($this) {
            self::LEGACY => true,
            default => false,
        };
    }

    /**
     * @internal
     */
    public function shouldRemoveDescriptionPadding(): bool
    {
        return match ($this) {
            self::LEGACY => true,
            default => false,
        };
    }

    /**
     * @internal
     */
    public function allowAllNodeDescriptions(): bool
    {
        return match ($this) {
            self::LEGACY => false,
            default => true,
        };
    }

    /**
     * @internal
     */
    public function shouldUseNewTableCellParsing(): bool
    {
        return match ($this) {
            self::LEGACY => false,
            default => true,
        };
    }

    /**
     * @internal
     */
    public function shouldUnespaceDocStringDelimiters(): bool
    {
        return match ($this) {
            self::LEGACY => false,
            default => true,
        };
    }

    /**
     * @internal
     */
    public function shouldIgnoreInvalidLanguage(): bool
    {
        return match ($this) {
            self::LEGACY => true,
            default => false,
        };
    }

    /**
     * @internal
     */
    public function allowWhitespaceInLanguageTag(): bool
    {
        return match ($this) {
            self::LEGACY => false,
            default => true,
        };
    }

    /**
     * @internal
     */
    public function shouldRemoveTagPrefixChar(): bool
    {
        // Note: When this is removed we can also remove the code in TagFilter that handles tags with no leading @
        return match ($this) {
            self::LEGACY => true,
            default => false,
        };
    }

    /**
     * @internal
     */
    public function shouldThrowOnWhitespaceInTag(): bool
    {
        return match ($this) {
            // Note, although we don't throw we have triggered an E_USER_DEPRECATED in Parser::guardTags since v4.9.0
            self::LEGACY => false,
            default => true,
        };
    }

    /**
     * @internal
     */
    public function supportsRuleKeyword(): bool
    {
        return match ($this) {
            self::LEGACY => false,
            default => true,
        };
    }

    /**
     * @internal
     */
    public function supportsMultipleStepArguments(): bool
    {
        return match ($this) {
            self::LEGACY,
            self::GHERKIN_32 => false,
            default => true,
        };
    }
}
