@ou @ou_vle @mod @mod_oublog @oublog_visitcount @javascript
Feature: Test total visit count
  In order to use OUBblog features
  As a user
  I need to be able to see the number of blog views

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
      | student2 | Student | 2 | student2@asd.com |
      | student3 | Student | 3 | student3@asd.com |
      | student4 | Student | 4 | student4@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
      | student4 | C1 | student |
    And the following "groups" exist:
      | name | course | idnumber |
      | G1 | C1 | G1 |
      | G2 | C1 | G2 |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
      | student2 | G2 |
      | student3 | G2 |
    And the following "activities" exist:
      | activity | name | intro  | course | idnumber |
      | oublog | Test oublog | Test oublog intro text | C1 | oublog1 |

    Given I log in as "student1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Test oublog"
    # Total visit of Test oublog's blog is one.
    And I should see "Total visits to this blog: 1"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title | student1 P1 |
      | Message | student1 P1 |
    And I press "Add post"
    And I log out

    Then I log in as "student2"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Test oublog"
    # Total visit of Test oublog's blog is two.
    And I should see "Total visits to this blog: 2"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title | student2 P1 |
      | Message | student2 P1 |
    And I press "Add post"
    And I log out 

    Then I log in as "student3"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Test oublog"
    # Total visit of Test oublog's blog is three.
    And I should see "Total visits to this blog: 3"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title | student3 P1 |
      | Message | student3 P1 |
    And I press "Add post"
    And I log out

  Scenario: Individual's blog total visit count.
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Test oublog"
    # Total visit of Test oublog's blog is four.
    And I should see "Total visits to this blog: 4"
    And I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | Individual blogs | Visible individual blogs |
    And I press "Save and display"
    And I log out

    Then I log in as "student1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Test oublog"
    And the field "jump" matches value "View all users"
    And I should see "Total visits to this blog: 5"
    And I set the field "jump" to "Student 1"
    And I should see "Total visits to this blog: 1"
    And I set the field "jump" to "Student 2"
    And I should see "Total visits to this blog: 1"
    And I set the field "jump" to "Student 3"
    And I should see "Total visits to this blog: 1"
    And I set the field "jump" to "View all users"
    And I should see "Total visits to this blog: 8"

    Given I log out
    And I log in as "student4"
    And I am on homepage
    And I am on "Course 1" course homepage
    When I follow "Test oublog"
    Then I should see "Total visits to this blog: 9"
    Given I set the field "jump" to "Student 4"
    Then I should see "Total visits to this blog: 1"

  Scenario: Group's blog total visit count.
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Test oublog"
    # Total visit of Test oublog's blog is four.
    And I should see "Total visits to this blog: 4"
    And I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | Individual blogs | Visible individual blogs |
      | Group mode | Visible groups |
    And I press "Save and display"
    And I log out

    Then I log in as "student1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Test oublog"
    # Blog of group 1.
    And the field "Visible groups" matches value "G1"
    And I should see "Student 1" in the ".oublog-individualselector" "css_element"
    And I should see "Total visits to this blog: 1"
    # Blog of group 2.
    And I set the field "Visible groups" to "G2"
    And I set the field "jump" to "Student 2"
    And I should see "Total visits to this blog: 1"
    And I set the field "jump" to "Student 3"
    And I should see "Total visits to this blog: 1"
    And I set the field "jump" to "View all users in group"
    And I should see "Total visits to this blog: 2"
    # All participants views.
    And I set the field "Visible groups" to "All participants"
    And I set the field "jump" to "View all users"
    And I should see "Total visits to this blog: 8"
