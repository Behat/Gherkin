# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

This project follows the [Behat release and version support policies]
(https://docs.behat.org/en/latest/releases.html).

# [4.14.0] - 2025-05-23

### Changed

* Throw ParserException if file ends with tags by @acoulton in [#313](https://github.com/Behat/Gherkin/pull/313)
* Throw ParserException if Background comes after first Scenario by @acoulton in [#343](https://github.com/Behat/Gherkin/pull/343)
* For compatibility with the official cucumber/gherkin parsers, we now accept some gherkin syntax that would previously
  have triggered a ParserException. Users may wish to consider running a tool like gherkin-lint in CI to detect
  incomplete feature files or valid-but-unusual gherkin syntax. The specific changes are:
  - Parse `Scenario` and `Scenario Outline` as synonyms depending on the presence (or not) of an `Examples:` keyword.
    by @acoulton in [#316](https://github.com/Behat/Gherkin/pull/316) and [#324](https://github.com/Behat/Gherkin/pull/324)
  - Do not throw on some unexpected Feature / Language tags by @acoulton in [#323](https://github.com/Behat/Gherkin/pull/323)
  - Do not throw on `.feature` file that does not contain a Feature by @acoulton in [#340](https://github.com/Behat/Gherkin/pull/340)
  - Ignore content after table right-hand `|` (instead of throwing) by @acoulton in [#341](https://github.com/Behat/Gherkin/pull/341)
* Remove the line length from the NewLine token value by @stof in [#338](https://github.com/Behat/Gherkin/pull/338)
* Added precise PHPStan type information by @stof in [#332](https://github.com/Behat/Gherkin/pull/332),
  [#333](https://github.com/Behat/Gherkin/pull/333), [#339](https://github.com/Behat/Gherkin/pull/339)
  and [#334](https://github.com/Behat/Gherkin/pull/334)

### Internal

* Make private props readonly; fix tests by @uuf6429 in [#319](https://github.com/Behat/Gherkin/pull/319)
* Use the `Yaml::parseFile` API to handle Yaml files by @stof in [#335](https://github.com/Behat/Gherkin/pull/335)
* test: Make CucumberND name reading consistent by @uuf6429 in [#309](https://github.com/Behat/Gherkin/pull/309)
* test: Use vfsStream to simplify / improve filesystem-related tests by @uuf6429 in [#298](https://github.com/Behat/Gherkin/pull/298)
* test: Handle optional tableHeader when loading NDJson examples by @uuf6429 in [#294](https://github.com/Behat/Gherkin/pull/294)
* test: Refactor valid ParserExceptionsTest examples into cucumber/gherkin testdata by @acoulton in [#322](https://github.com/Behat/Gherkin/pull/322)
* test: Compare step arguments when checking gherkin parity by @acoulton in [#325](https://github.com/Behat/Gherkin/pull/325)
* test: Use a custom object comparator to ignore the keywordType of StepNode by @stof in [#331](https://github.com/Behat/Gherkin/pull/331)
* ci: Add conventional title to gherkin update, error on missing asserts by @acoulton in [#314](https://github.com/Behat/Gherkin/pull/314)
* Assert that preg_split does not fail when splitting a table row by @stof in [#337](https://github.com/Behat/Gherkin/pull/337)
* Add assertions in the parser to reflect the structure of tokens by @stof in [#342](https://github.com/Behat/Gherkin/pull/342)
* style: Define and change phpdoc order coding style by @uuf6429 in [#345](https://github.com/Behat/Gherkin/pull/345)


# [4.13.0] - 2025-05-06

### Changed

* Files have been moved to flatten paths into a PSR-4 structure (instead of the previous PSR-0). This may affect users
  who are requiring files directly rather than using the composer autoloader as expected.
  See the 4.12.0 release for the new `CachedArrayKeywords::withDefaultKeywords()` to use the `i18n.php` file without
  depending on paths to other files in this repo. By @uuf6429 in [#288](https://github.com/Behat/Gherkin/pull/288)

### Added

* ExampleTableNode now implements TaggedNodeInterface. Also refactored node tag handling methods. By @uuf6429 in
  [#289](https://github.com/Behat/Gherkin/pull/289)
* Improve some exceptions thrown when parsing invalid feature files. Also increased test coverage. By @uuf6429 in
  [#295](https://github.com/Behat/Gherkin/pull/295)
* New translations for `amh` (Amharic), `be` (Belarusian) and `ml` (Malayalam) from cucumber/gherkin in [#306](https://github.com/Behat/Gherkin/pull/306)
* Improved translations / whitespace for `ga` (Irish), `it` (Italian), `ja` (Japanese), `ka` (Georgian) and `ko` (Korean)
  from cucumber/gherkin in [#306](https://github.com/Behat/Gherkin/pull/306)

### Internal

* Fix & improve automatic CI updates to newer cucumber/gherkin test data and translations. By @acoulton in
  [#300](https://github.com/Behat/Gherkin/pull/300), [#302](https://github.com/Behat/Gherkin/pull/302),
  [#304](https://github.com/Behat/Gherkin/pull/304), [#305](https://github.com/Behat/Gherkin/pull/305)
* Update code style and resolve PHPStan warnings (up to level 9) in tests and CI scripts. By @uuf6429 in
  [#296](https://github.com/Behat/Gherkin/pull/296), [#297](https://github.com/Behat/Gherkin/pull/297)
  and [#307](https://github.com/Behat/Gherkin/pull/307)
* Make tests that expect exceptions more explicit by @uuf6429 in [#310](https://github.com/Behat/Gherkin/pull/310)
* Improve CI workflows and integrate Codecov reporting by @uuf6429 in [#299](https://github.com/Behat/Gherkin/pull/299)
  and [#301](https://github.com/Behat/Gherkin/pull/301)
* Refactor tag filtering implementation by @uuf6429 in [#308](https://github.com/Behat/Gherkin/pull/308)
* Update cucumber/gherkin parity tests to v32.1.1 in [#306](https://github.com/Behat/Gherkin/pull/306)

# [4.12.0] - 2025-02-26

### Changed
* Gherkin::VERSION is deprecated and will not be updated, use the composer runtime API if you need to identify the
  running version. This also changes the value used to namespace cached feature files.
  by @acoulton in [#279](https://github.com/Behat/Gherkin/pull/279)

### Added

* Provide `CachedArrayKeywords::withDefaultKeywords()` to create an instance without an external dependency on the path
  to the `i18n.php` file in this repo. **NOTE** that paths to source files will change in the next Gherkin release -
  use the new constructor to avoid any impact.
  by @carlos-granados in [#290](https://github.com/Behat/Gherkin/pull/290)

### Internal

* Upgrade to phpunit 10 by @uuf6429 in [#275](https://github.com/Behat/Gherkin/pull/275)
* Remove redundant files by @uuf6429 in [#278](https://github.com/Behat/Gherkin/pull/278)
* Update documentation by @uuf6429 in [#274](https://github.com/Behat/Gherkin/pull/274)
* Adopt PHP CS Fixer and apply code styles by @uuf6429 in [#277](https://github.com/Behat/Gherkin/pull/277)
* Add PHPStan and improve / fix docblock annotations and type-safety within methods to achieve level 5 by
  @uuf6429 in [#276](https://github.com/Behat/Gherkin/pull/276), [#281](https://github.com/Behat/Gherkin/pull/281),
  [#282](https://github.com/Behat/Gherkin/pull/282), and [#287](https://github.com/Behat/Gherkin/pull/287)

# [4.11.0] - 2024-12-06

### Changed

* Drop support for PHP < 8.1, Symfony < 5.4 and Symfony 6.0 - 6.3. In future we will drop support for PHP and symfony
  versions as they reach EOL. by @acoulton in [#272](https://github.com/Behat/Gherkin/pull/272)
* Deprecated `ExampleNode::getTitle()` and `ScenarioNode::getTitle()` in favour of new methods with clearer meaning.
  by @uuf6429 in [#271](https://github.com/Behat/Gherkin/pull/271)

### Added

* Added `(ExampleNode|ScenarioNode)::getName()` to access human-readable names for examples and scenarios,
  and `ExampleNode::getExampleText()` for the string content of the example table row.
  by @uuf6429 in [#271](https://github.com/Behat/Gherkin/pull/271)

### Internal

* Enable dependabot for github actions workflows by @jrfnl in [#261](https://github.com/Behat/Gherkin/pull/261)

# 4.10.0 / 2024-10-19

### Changed

- **⚠ Backslashes in feature files must now be escaped**\
  Gherkin syntax treats `\` as an escape character, which must be escaped (`\\`) to use it as a
  literal value. Historically, this was not being parsed correctly. This release fixes that bug,
  but means that if your scenarios currently use unescaped `\` you will need to replace each one
  with `\\` to achieve the same parsed result.
  By @everzet in 5a0836d.

### Added
- Symfony 6 and 7 thanks to @tacman in #257
- PHP 8.4 support thanks to @heiglandreas in #258 and @jrfnl in #262

### Fixed
- Fix exception when filter string is empty thanks to @magikid in #251

### Internal
- Sync teststuite with Cucumber 24.1.0
- Fix PHPUnit 10 deprecation messages
- A lot of great CI work by @heiglandreas and @jrfnl

# 4.9.0 / 2021-10-12

- Simplify the boolean condition for the tag matching by @stof in https://github.com/Behat/Gherkin/pull/219
- Remove symfony phpunit bridge by @ciaranmcnulty in https://github.com/Behat/Gherkin/pull/220
- Ignore the bin folder in archives by @stof in https://github.com/Behat/Gherkin/pull/226
- Cast table node exceptions into ParserExceptions when throwing by @ciaranmcnulty in https://github.com/Behat/Gherkin/pull/216
- Cucumber changelog in PRs and using correct hash by @ciaranmcnulty in https://github.com/Behat/Gherkin/pull/225
- Support alternative docstrings format (```) by @ciaranmcnulty in https://github.com/Behat/Gherkin/pull/214
- Fix DocBlocks (Boolean -> bool) by @simonhammes in https://github.com/Behat/Gherkin/pull/237
- Tag parsing by @ciaranmcnulty in https://github.com/Behat/Gherkin/pull/215
- Remove test - cucumber added an example with Rule which is not supported by @ciaranmcnulty in https://github.com/Behat/Gherkin/pull/239
- Add PHP 8.1 support by @javer in https://github.com/Behat/Gherkin/pull/242
- Fix main branch alias version by @mvorisek in https://github.com/Behat/Gherkin/pull/244

  # 4.8.0 / 2021-02-04

- Drop support for PHP before version 7.2

  # 4.7.3 / 2021-02-04

- Refactored comments parsing to avoid Maximum function nesting level errors

  # 4.7.2 / 2021-02-03

- Issue where Scenario Outline title was not populated into Examples
- Updated translations from cucumber 16.0.0

  # 4.7.1 / 2021-01-26

- Issue parsing comments before scenarios when following an Examples table

  # 4.7.0 / 2021-01-24

- Provides better messages for TableNode construct errors
- Now allows single character steps
- Supports multiple Example Tables with tags

# 4.6.2 / 2020-03-17

- Fixed issues due to incorrect cache key

# 4.6.1 / 2020-02-27

- Fix AZ translations
- Correctly filter features, now that the base path is correctly set

# 4.6.0 / 2019-01-16

- Updated translations (including 'Example' as synonym for 'Scenario' in `en`)

# 4.5.1 / 2017-08-30

- Fix regression in `PathsFilter`

# 4.5.0 / 2017-08-30

- Sync i18n with Cucumber Gherkin
- Drop support for HHVM tests on Travis
- Add `TableNode::fromList()` method (thanks @TravisCarden)
- Add `ExampleNode::getOutlineTitle()` method (thanks @duxet)
- Use realpath, so the feature receives the cwd prefixed (thanks @glennunipro)
- Explicitly handle non-two-dimensional arrays in TableNode (thanks @TravisCarden)
- Fix to line/linefilter scenario runs which take relative paths to files (thanks @generalconsensus)

# 4.4.5 / 2016-10-30

- Fix partial paths matching in `PathsFilter`

# 4.4.4 / 2016-09-18

- Provide clearer exception for non-writeable cache directories

# 4.4.3 / 2016-09-18

- Ensure we reset tags between features

# 4.4.2 / 2016-09-03

- Sync 18n with gherkin 3

# 4.4.1 / 2015-12-30

- Ensure keywords are trimmed when syncing translations
- Sync 18n with cucumber

# 4.4.0 / 2015-09-19

- Added validation enforcing that all rows of a `TableNode` have the same number of columns
- Added `TableNode::getColumn` to get a column from the table
- Sync 18n with cucumber

# 4.3.0 / 2014-06-06

- Added `setFilters(array)` method to `Gherkin` class
- Added `NarrativeFilter` for non-english `RoleFilter` lovers

# 4.2.1 / 2014-06-06

- Fix parsing of features without line feed at the end

# 4.2.0 / 2014-05-27

- Added `getKeyword()` and `getKeywordType()` methods to `StepNode`, deprecated `getType()`.
  Thanks to @kibao

# 4.1.3 / 2014-05-25

- Properly handle tables with rows terminating in whitespace

# 4.1.2 / 2014-05-14

- Handle case where Gherkin cache is broken

# 4.1.1 / 2014-05-05

- Fixed the compatibility with PHP 5.6-beta by avoiding to use the broken PHP array function
- The YamlFileLoader no longer extend from ArrayLoader but from AbstractFileLoader

# 4.1.0 / 2014-04-20

- Fixed scenario tag filtering
- Do not allow multiple multiline step arguments
- Sync 18n with cucumber

# 4.0.0 / 2014-01-05

- Changed the behavior when no loader can be found for the resource. Instead of throwing an exception, the
  Gherkin class now returns an empty array.

# 3.1.3 / 2014-01-04

- Dropped the dependency on the Symfony Finder by using SPL iterators directly
- Added testing on HHVM on Travis. HHVM is officially supported (previous release was actually already compatible)

# 3.1.2 / 2014-01-01

- All paths passed to PathsFilter are converted using realpath

# 3.1.1 / 2013-12-31

- Add `ComplexFilterInterace` that has complex behavior for scenarios and requires to pass
  feature too
- `TagFilter` is an instance of a `ComplexFilterInterace` now

# 3.1.0 / 2013-12-31

- Example node is a scenario
- Nodes do not have uprefs (memory usage fix)
- Scenario filters do not depend on feature nodes

# 3.0.5 / 2014-01-01

- All paths passed to PathsFilter are converted using realpath

# 3.0.4 / 2013-12-31

- TableNode is now traversable using foreach
- All possibly thrown exceptions implement Gherkin\Exception interface
- Sync i18n with cucumber

# 3.0.3 / 2013-09-15

- Extend ExampleNode with additional methods

# 3.0.2 / 2013-09-14

- Extract `KeywordNodeInterface` and `ScenarioLikeInterface`
- Add `getIndex()` methods to scenarios, outlines, steps and examples
- Throw proper exception for fractured node tree

# 3.0.1 / 2013-09-14

- Use versioned subfolder in FileCache

# 3.0.0 / 2013-09-14

- A lot of optimizations in Parser and Lexer
- Node tree is now immutable by nature (no setters)
- Example nodes are now part of the node tree. They are lazily generated by Outline node
- Sync with latest cucumber i18n

# 2.3.4 / 2013-08-11

- Fix leaks in memory cache

# 2.3.3 / 2013-08-11

- Fix encoding bug introduced with previous release
- Sync i18n with cucumber

# 2.3.2 / 2013-08-11

- Explicitly use utf8 encoding

# 2.3.1 / 2013-08-10

- Support `an` prefix with RoleFilter

# 2.3.0 / 2013-08-04

- Add RoleFilter
- Add PathsFilter
- Add MemoryCache

# 2.2.9 / 2013-03-02

- Fix dependency version requirement

# 2.2.8 / 2013-03-02

- Features filtering behavior change. Now emptified (by filtering) features
  that do not match filter themselves are removed from resultset.
- Small potential bug fix in TableNode

# 2.2.7 / 2013-01-27

- Fixed bug in i18n syncing script
- Resynced Gherkin i18n

# 2.2.6 / 2013-01-26

- Support long row hashes in tables ([see](https://github.com/Behat/Gherkin/issues/40))
- Synced Gherkin i18n

# 2.2.5 / 2012-09-26

- Fixed issue with loading empty features
- Synced Gherkin i18n

# 2.2.4 / 2012-08-03

- Fixed exception message for "no loader found"

# 2.2.3 / 2012-08-03

- Fixed minor loader bug with empty base path
- Synced Gherkin i18n

# 2.2.2 / 2012-07-01

- Added ability to filter outline scenarios by line and range filters
- Synced Gherkin i18n
- Refactored table parser to read row line numbers too

# 2.2.1 / 2012-05-04

- Fixed StepNode `getLanguage()` and `getFile()`

# 2.2.0 / 2012-05-03

- Features freeze after parsing
- Implemented GherkinDumper (@Halleck45)
- Synced i18n with Cucumber
- Updated inline documentation

# 2.1.1 / 2012-03-09

- Fixed caching bug, where `isFresh()` always returned false

# 2.1.0 / 2012-03-09

- Added parser caching layer
- Added support for table delimiter escaping (use `\|` for that)
- Added LineRangeFilter (thanks @headrevision)
- Synced i18n dictionary with cucumber/gherkin

# 2.0.2 / 2012-02-04

- Synced i18n dictionary with cucumber/gherkin

# 2.0.1 / 2012-01-26

- Fixed issue about parsing features without indentation

# 2.0.0 / 2012-01-19

- Background titles support
- Correct parsing of titles/descriptions (hirarchy lexing)
- Migration to the cucumber/gherkin i18n dictionary
- Speed optimizations
- Refactored KeywordsDumper
- New loaders
- Bugfixes

# 1.1.4 / 2012-01-08

- Read feature description even if it looks like a step

# 1.1.3 / 2011-12-14

- Removed file loading routines from Parser (fixes `is_file()` issue on some systems - thanks
  @flodocteurklein)

# 1.1.2 / 2011-12-01

- Updated spanish trasnaltion (@anbotero)
- Integration with Composer and Travis CI

# 1.1.1 / 2011-07-29

- Updated pt language step types (@danielcsgomes)
- Updated vendors

# 1.1.0 / 2011-07-16

- Return all tags, including inherited in `Scenario::getTags()`
- New `Feature::getOwnTags()` and `Scenario::getOwnTags()` method added,
  which returns only own tags

# 1.0.8 / 2011-06-29

- Fixed comments parsing.
  You can’t have comments at the end of a line # like this
  # But you can still have comments at the beginning of a line

# 1.0.7 / 2011-06-28

- Added `getRaw()` method to PyStringNode
- Updated vendors

# 1.0.6 / 2011-06-17

- Updated vendors

# 1.0.5 / 2011-06-10

- Fixed bug, introduced with 1.0.4 - hash in PyStrings

# 1.0.4 / 2011-06-10

- Fixed inability to comment pystrings

# 1.0.3 / 2011-04-21

- Fixed introduced with 1.0.2 pystring parsing bug

# 1.0.2 / 2011-04-18

- Fixed bugs in text with comments parsing

# 1.0.1 / 2011-04-01

- Updated vendors

# 1.0.0 / 2011-03-08

- Updated vendors

# 1.0.0RC2 / 2011-02-25

- Windows support
- Missing phpunit config

# 1.0.0RC1 / 2011-02-15

- Huge optimizations to Lexer & Parser
- Additional loaders (Yaml, Array, Directory)
- Filters (Tag, Name, Line)
- Code refactoring
- Nodes optimizations
- Additional tests for exceptions and translations
- Keywords dumper

# 0.2.0 / 2011-01-05

- New Parser & Lexer (based on AST)
- New verbose parsing exception handling
- New translation mechanics
- 47 brand new translations (see i18n)
- Full test suite for everything from AST nodes to translations

[4.14.0]: https://github.com/Behat/Gherkin/compare/v4.13.0...v4.14.0
[4.13.0]: https://github.com/Behat/Gherkin/compare/v4.12.0...v4.13.0
[4.12.0]: https://github.com/Behat/Gherkin/compare/v4.11.0...v4.12.0
[4.11.0]: https://github.com/Behat/Gherkin/compare/v4.10.0...v4.11.0
