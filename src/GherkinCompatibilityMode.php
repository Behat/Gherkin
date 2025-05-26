<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin;

enum GherkinCompatibilityMode: string
{
    case LEGACY = 'legacy';

    /**
     * Note: The gherkin-32 parsing mode is not yet complete, and further behaviour changes are expected.
     *
     * @see https://github.com/Behat/Gherkin/issues?q=is%3Aissue%20state%3Aopen%20label%3Acucumber-parity
     */
    case GHERKIN_32 = 'gherkin-32';

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
    public function shouldRemoveFeatureDescriptionPadding(): bool
    {
        return match ($this) {
            self::LEGACY => true,
            default => false,
        };
    }

    /**
     * @internal
     */
    public function shouldSupportNewlineEscapeSequenceInTableCell(): bool
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
}
