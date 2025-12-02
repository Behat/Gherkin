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

Contributors
------------

- Konstantin Kudryashov [everzet](https://github.com/everzet) [original developer]
- Andrew Coulton [acoulton](https://github.com/acoulton) [current maintainer]
- Carlos Granados [carlos-granados](https://github.com/carlos-granados) [current maintainer]
- Christophe Coevoet [stof](https://github.com/stof) [current maintainer]
- Other [awesome developers](https://github.com/Behat/Gherkin/graphs/contributors)

Support the project
-------------------

Behat is free software, maintained by volunteers as a gift for users. If you'd like to see
the project continue to thrive, and particularly if you use it for work, we'd encourage you
to contribute.

Contributions of time - whether code, documentation, or support reviewing PRs and triaging
issues - are very welcome and valued by the maintainers and the wider Behat community.

But we also believe that [financial sponsorship is an important part of a healthy Open Source
ecosystem](https://opensourcepledge.com/about/). Maintaining a project like Behat requires a
significant commitment from the core team: your support will help us to keep making that time
available over the long term. Even small contributions make a big difference.

You can support [@acoulton](https://github.com/acoulton), [@carlos-granados](https://github.com/carlos-granados) and
[@stof](https://github.com/stof) on GitHub sponsors. If you'd like to discuss supporting us in a different way, please
get in touch!
