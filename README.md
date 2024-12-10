# Behat Gherkin Parser

This is the php Gherkin parser for Behat. It comes bundled with more than 40 native languages (see `i18n.php`) support
and clean architecture.

## Useful Links

- [Behat Site](https://behat.org)
- [Note on Patches/Pull Requests](CONTRIBUTING.md)

## Usage Example

```php
<?php

$keywords = new Behat\Gherkin\Keywords\ArrayKeywords(array(
    'en' => array(
        'feature'          => 'Feature',
        'background'       => 'Background',
        'scenario'         => 'Scenario',
        'scenario_outline' => 'Scenario Outline|Scenario Template',
        'examples'         => 'Examples|Scenarios',
        'given'            => 'Given',
        'when'             => 'When',
        'then'             => 'Then',
        'and'              => 'And',
        'but'              => 'But'
    ),
    'en-pirate' => array(
        'feature'          => 'Ahoy matey!',
        'background'       => 'Yo-ho-ho',
        'scenario'         => 'Heave to',
        'scenario_outline' => 'Shiver me timbers',
        'examples'         => 'Dead men tell no tales',
        'given'            => 'Gangway!',
        'when'             => 'Blimey!',
        'then'             => 'Let go and haul',
        'and'              => 'Aye',
        'but'              => 'Avast!'
    )
));
$lexer  = new Behat\Gherkin\Lexer($keywords);
$parser = new Behat\Gherkin\Parser($lexer);

$feature = $parser->parse(file_get_contents('some.feature'));
```

## Installing Dependencies

```shell
curl https://getcomposer.org/installer | php
php composer.phar update
```

## Contributors

* Konstantin Kudryashov [everzet](http://github.com/everzet) [lead developer]
* Other [awesome developers](https://github.com/Behat/Gherkin/graphs/contributors)
