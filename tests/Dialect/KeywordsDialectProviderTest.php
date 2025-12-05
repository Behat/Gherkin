<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Dialect;

use Behat\Gherkin\Dialect\KeywordsDialectProvider;
use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Keywords\CachedArrayKeywords;
use PHPUnit\Framework\TestCase;

class KeywordsDialectProviderTest extends TestCase
{
    public function testFailsForEmptyKeywordString(): void
    {
        $keywords = new ArrayKeywords(['en' => [
            'and' => 'And|*',
            'background' => '',
            'but' => 'But|*',
            'examples' => 'Scenarios|Examples',
            'feature' => 'Business Need|Ability|Feature',
            'given' => 'Given|*',
            'name' => 'English',
            'native' => 'English',
            'rule' => 'Rule',
            'scenario' => 'Scenario|Example',
            'scenario_outline' => 'Scenario Template|Scenario Outline',
            'then' => 'Then|*',
            'when' => 'When|*',
        ]]);

        $dialectProvider = new KeywordsDialectProvider($keywords);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A keyword string must contain at least one keyword.');
        $dialectProvider->getDialect('en');
    }

    public function testDefaultDialectAfterExplicitDialect(): void
    {
        $dialectProvider = new KeywordsDialectProvider(CachedArrayKeywords::withDefaultKeywords());

        $ruDialect = $dialectProvider->getDialect('ru');
        $defaultDialect = $dialectProvider->getDefaultDialect();

        $this->assertSame('ru', $ruDialect->getLanguage());
        $this->assertSame('en', $defaultDialect->getLanguage());
    }
}
