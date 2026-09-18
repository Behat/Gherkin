Feature: Unsubstituted argument placeholder

    Scenario Outline: See Annual Leave Details (as Management & Human Resource)
        Given the <role> exist in the system

        Examples:
            | role           | name |
            | HUMAN RESOURCE | abc  |

        Examples:
            | role    | name |
            | MANAGER | cde  |

        Examples:
            | role      | name |
            | CEO       | qqq  |
            | CTO       | xxx  |


    Scenario Outline: See Annual Leave Details (as Management & Human Resource)
        Given the <role> exist in the system

        @tag1 @tag2
        Examples:
            | role           | name |
            | HUMAN RESOURCE | abc  |

        @tag1 @tag3
        Examples:
            | role    | name |
            | MANAGER | cde  |
        @tag4
        Examples:
            | role      | name |
            | CEO       | qqq  |
            | CTO       | xxx  |