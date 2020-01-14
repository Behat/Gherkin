Feature: Feature with background and example

  Background:
    Given a passing step
    Examples:
      | login | password |
      |       |          |
      | unknown_user |   |

  Scenario:
    Given a failing step
    When I fill in "login" with "<login>"
    And I fill in "password" with "<password>"
