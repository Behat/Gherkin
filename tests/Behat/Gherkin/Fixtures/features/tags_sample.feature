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
        @examples_tag3 @examples_tag4
        Examples:
            | state   |
            | missing |
