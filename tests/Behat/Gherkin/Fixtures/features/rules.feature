Feature: Highlander

  Rule: There can be only One

    Background:
      Given there are 3 ninjas

    Example: Only One -- More than one alive
      Given there are more than one ninjas alive
      When 2 ninjas meet, they will fight
      Then one ninja dies (but not me)
      And there is one ninja less alive

    Example: Only One -- One alive
      Given there is only 1 ninja alive
      Then he (or she) will live forever ;-)

  Rule: There can be Two (in some cases)

    Example: Two -- Dead and Reborn as Phoenix
      Given that it is not yet time to slumber and feast in Valhalla
