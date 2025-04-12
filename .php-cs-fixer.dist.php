<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->name(['cucumber_changelog', 'update_cucumber', 'update_i18n'])
    ->notPath('i18n.php');

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@PER-CS' => true,
        '@Symfony' => true,
        'header_comment' => [
            'header' => <<<'TEXT'
                This file is part of the Behat Gherkin Parser.
                (c) Konstantin Kudryashov <ever.zet@gmail.com>
                
                For the full copyright and license information, please view the LICENSE
                file that was distributed with this source code.
                TEXT
        ],
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
        'concat_space' => ['spacing' => 'one'],
        'phpdoc_align' => ['align' => 'left'],
        'heredoc_to_nowdoc' => true,
        'heredoc_indentation' => ['indentation' => 'same_as_start'],
        'single_line_throw' => false,
        'ternary_to_null_coalescing' => true,
        'global_namespace_import' => false,
    ])
    ->setFinder($finder);
