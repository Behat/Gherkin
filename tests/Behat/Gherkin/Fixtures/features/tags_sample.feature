@sample_one
Feature: Tag samples

    @sample_two @sample_four
    Scenario: Passing
        Given missing

    @sample_three
    Scenario Outline:
        Given <state>
        @examples_tag @examples_tag2
        Examples:
            | state   |
            | missing |

    @sample_three @sample_four
    Scenario: Skipped
        Given missing


    @sample_5
    Scenario Outline: passing
        Given <state>
        Examples:
            | state   |
            | missing |

    @sample_6 @sample_7
    Scenario Outline: passing
        Given <state>
        # comment before examples table tags
        @examples_tag3 @examples_tag4
        Examples:
            | state   |
            | missing |
        @examples_tag5 @examples_tag6
        # comment after examples table tags
        Examples:
            | state     |
            | something |

    # comment before tag
    @sample_8
    Scenario: scenario with comment and tag after an outline
        Given the scenario has a comment and a tag and comes after an outline

    @sample_9
    # comment after tag
    Scenario: scenario with tag and then comment
        Given the scenario has a tag and comment

    @sample_10 @sample_11
    Scenario Outline: an outline followed by more scenarios
        Given <state>
        Examples:
            | state   |
            | more    |

    @sample_12
    # comment after tag
    Scenario: another scenario with tag and then comment
        Given this scenario has a tag and comment
