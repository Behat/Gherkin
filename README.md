Behat Gherkin Parser
====================

This is the php Gherkin parser for Behat. It comes bundled with more than 40 native languages
(see `i18n.php`) support & clean architecture.

- [master](https://github.com/Behat/Gherkin/tree/master) ([![master Build
Status](https://secure.travis-ci.org/Behat/Gherkin.png?branch=master)](http://travis-ci.org/Behat/Gherkin)) - Latest 4.0 version of the parser.
- [3.1](https://github.com/Behat/Gherkin/tree/3.1) ([![3.1 Build
Status](https://secure.travis-ci.org/Behat/Gherkin.png?branch=3.1)](http://travis-ci.org/Behat/Gherkin)) - Previous 3.1 version of the parser.
- [3.0](https://github.com/Behat/Gherkin/tree/3.0) ([![3.0 Build
Status](https://secure.travis-ci.org/Behat/Gherkin.png?branch=3.0)](http://travis-ci.org/Behat/Gherkin)) - Previous 3.0 version of the parser.
- [2.3](https://github.com/Behat/Gherkin/tree/2.3) ([![2.3 Build
  Status](https://secure.travis-ci.org/Behat/Gherkin.png?branch=2.3)](http://travis-ci.org/Behat/Gherkin)) - Previous 2.3 version of the parser.

Useful Links
------------

- Official Google Group is at [http://groups.google.com/group/behat](http://groups.google.com/group/behat)
- IRC channel on [#freenode](http://freenode.net/) is `#behat`
- [Note on Patches/Pull Requests](CONTRIBUTING.md)

Usage Example
-------------

``` php
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

Installing Dependencies
-----------------------

``` bash
$> curl http://getcomposer.org/installer | php
$> php composer.phar update
```

Contributors
------------

* Konstantin Kudryashov [everzet](http://github.com/everzet) [lead developer]
* Other [awesome developers](https://github.com/Behat/Gherkin/graphs/contributors)
