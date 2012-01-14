Behat Gherkin Parser
====================

[![Build Status](https://secure.travis-ci.org/Behat/Gherkin.png)](http://travis-ci.org/Behat/Gherkin)

This is the new Gherkin parser for Behat. It comes bundled with more than 40 native languages (see i18n) support & much cleaner architecture than previous one.

Gherkin parser from now on will be separate project from Behat core itself and has no foreign dependencies, which means, that you can use it in your DSL-specific projects.

Usage
-----

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

$arrayOfFeatures = $parser->parse(file_get_contents('some.feature'));
```


Note on Patches/Pull Requests
-----------------------------

* Fork the project `develop` branch (all new development happens here, master for releases & hotfixes only).
* Make your feature addition or bug fix.
* Add unit tests for it (look at tests/Behat/Gherkin for examples).
  This is important so I don't break it in a future version unintentionally.
* Commit
* Send me a pull request.

Running tests
-------------

``` bash
phpunit
```

If you get errors about missing dependencies - just install them

``` bash
wget -nc http://getcomposer.org/composer.phar
php composer.phar update
```

Gherkin Parser itself has no required dependencies, but test suite has.

Copyright
---------

Copyright (c) 2010 Konstantin Kudryashov (ever.zet). See LICENSE for details.

Contributors
------------

* Konstantin Kudryashov [everzet](http://github.com/everzet) [lead developer]
