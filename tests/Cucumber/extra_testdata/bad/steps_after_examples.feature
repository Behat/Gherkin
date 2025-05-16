Feature: Fail to parse step after examples

  Scenario: there are some steps after the examples table
    Given I have some <thing>

    Examples:
    | thing    |
    | whatever |

    When I write more steps