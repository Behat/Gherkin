2.3.4 / 2013-08-11
==================

  * Fix leaks in memory cache

2.3.3 / 2013-08-11
==================

  * Fix encoding bug introduced with previous release
  * Sync i18n with cucumber

2.3.2 / 2013-08-11
==================

  * Explicitly use utf8 encoding

2.3.1 / 2013-08-10
==================

  * Support `an` prefix with RoleFilter

2.3.0 / 2013-08-04
==================

  * Add RoleFilter
  * Add PathsFilter
  * Add MemoryCache

2.2.9 / 2013-03-02
==================

  * Fix dependency version requirement

2.2.8 / 2013-03-02
==================

  * Features filtering behavior change. Now emptified (by filtering) features
    that do not match filter themselves are removed from resultset.
  * Small potential bug fix in TableNode

2.2.7 / 2013-01-27
==================

  * Fixed bug in i18n syncing script
  * Resynced Gherkin i18n

2.2.6 / 2013-01-26
==================

  * Support long row hashes in tables ([see](https://github.com/Behat/Gherkin/issues/40))
  * Synced Gherkin i18n

2.2.5 / 2012-09-26
==================

  * Fixed issue with loading empty features
  * Synced Gherkin i18n

2.2.4 / 2012-08-03
==================

  * Fixed exception message for "no loader found"

2.2.3 / 2012-08-03
==================

  * Fixed minor loader bug with empty base path
  * Synced Gherkin i18n

2.2.2 / 2012-07-01
==================

  * Added ability to filter outline scenarios by line and range filters
  * Synced Gherkin i18n
  * Refactored table parser to read row line numbers too

2.2.1 / 2012-05-04
==================

  * Fixed StepNode `getLanguage()` and `getFile()`

2.2.0 / 2012-05-03
==================

  * Features freeze after parsing
  * Implemented GherkinDumper (@Halleck45)
  * Synced i18n with Cucumber
  * Updated inline documentation

2.1.1 / 2012-03-09
==================

  * Fixed caching bug, where `isFresh()` always returned false

2.1.0 / 2012-03-09
==================

  * Added parser caching layer
  * Added support for table delimiter escaping (use `\|` for that)
  * Added LineRangeFilter (thanks @headrevision)
  * Synced i18n dictionary with cucumber/gherkin

2.0.2 / 2012-02-04
==================

  * Synced i18n dictionary with cucumber/gherkin

2.0.1 / 2012-01-26
==================

  * Fixed issue about parsing features without indentation

2.0.0 / 2012-01-19
==================

  * Background titles support
  * Correct parsing of titles/descriptions (hirarchy lexing)
  * Migration to the cucumber/gherkin i18n dictionary
  * Speed optimizations
  * Refactored KeywordsDumper
  * New loaders
  * Bugfixes

1.1.4 / 2012-01-08
==================

  * Read feature description even if it looks like a step

1.1.3 / 2011-12-14
==================

  * Removed file loading routines from Parser (fixes `is_file()` issue on some systems - thanks
    @flodocteurklein)

1.1.2 / 2011-12-01
==================

  * Updated spanish trasnaltion (@anbotero)
  * Integration with Composer and Travis CI

1.1.1 / 2011-07-29
==================

  * Updated pt language step types (@danielcsgomes)
  * Updated vendors

1.1.0 / 2011-07-16
==================

  * Return all tags, including inherited in `Scenario::getTags()`
  * New `Feature::getOwnTags()` and `Scenario::getOwnTags()` method added,
    which returns only own tags

1.0.8 / 2011-06-29
==================

  * Fixed comments parsing.
    You can’t have comments at the end of a line # like this
    # But you can still have comments at the beginning of a line

1.0.7 / 2011-06-28
==================

  * Added `getRaw()` method to PyStringNode
  * Updated vendors

1.0.6 / 2011-06-17
==================

  * Updated vendors

1.0.5 / 2011-06-10
==================

  * Fixed bug, introduced with 1.0.4 - hash in PyStrings

1.0.4 / 2011-06-10
==================

  * Fixed inability to comment pystrings

1.0.3 / 2011-04-21
==================

  * Fixed introduced with 1.0.2 pystring parsing bug

1.0.2 / 2011-04-18
==================

  * Fixed bugs in text with comments parsing

1.0.1 / 2011-04-01
==================

  * Updated vendors

1.0.0 / 2011-03-08
==================

  * Updated vendors

1.0.0RC2 / 2011-02-25
=====================

  * Windows support
  * Missing phpunit config

1.0.0RC1 / 2011-02-15
=====================

  * Huge optimizations to Lexer & Parser
  * Additional loaders (Yaml, Array, Directory)
  * Filters (Tag, Name, Line)
  * Code refactoring
  * Nodes optimizations
  * Additional tests for exceptions and translations
  * Keywords dumper

0.2.0 / 2011-01-05
==================

  * New Parser & Lexer (based on AST)
  * New verbose parsing exception handling
  * New translation mechanics
  * 47 brand new translations (see i18n)
  * Full test suite for everything from AST nodes to translations
