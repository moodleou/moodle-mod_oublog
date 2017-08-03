@ou @ou_vle @mod @mod_oublog @oublog_rate
Feature: Test rate individual posts
  In order to provide student grades
  As a teacher
  I need to be able to set ratings on individual posts

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student1 | 1 | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And the following "activities" exist:
      | activity | name | intro  | course | idnumber |
      | oublog | Testing rate individuals posts | Testing separate blogs as multipe users | C1 | oublog1 |

    # Admin changes settings
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Testing rate individuals posts"
    And I follow "Edit settings"
    # Maximum points of more than 10 gets very slow
    And I set the following fields to these values:
      | Grading | Use ratings |
      | Aggregate type | Average of ratings |
      | scale[modgrade_type] | Point |
      | scale[modgrade_point] | 10 |
    And I press "Save and display"
    And I log out

  Scenario: Teacher tests the rate post widget
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Testing rate individuals posts"
    And I press "New blog post"
    And I should see "New blog post"
    And I set the following fields to these values:
      | Title | SC06 teacher post01 |
      | Message | SC06 teacher post01 content visible to admin and teacher |
    And I press "Add post"
    Then I should see "SC06 teacher post01"
    And I should see "SC06 teacher post01 content visible to admin and teacher"
    And "span.rating-aggregate-label" "css_element" should exist
    And I should see "Average of ratings:"
    And I log out

    # Student1 user posts
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Testing rate individuals posts"
    Given I press "New blog post"
    And I should see "New blog post"
    And I set the following fields to these values:
      | Title | SC06 student1 post01 |
      | Message | SC06 student1 post01 content |
    And I press "Add post"
    And I wait to be redirected
    Then I should see "SC06 student1 post01"
    And I should see "SC06 student1 post01 content"
    And I log out

    # Teacher checks visibility
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Testing rate individuals posts"
    Then I should see "SC06 student1 post01"
    And I should see "SC06 student1 post01 content"
    And I should see "SC06 teacher post01"
    And I should see "SC06 teacher post01 content visible to admin and teacher"

    # Identify rating label & selector for student (wont see teachers selector)
    And ".rating-aggregate-label" "css_element" should exist
    And I should see "Average of ratings:"
    And "//div[@class='oublog-post-rating']/form/div/input[@class='ratinginput']" "xpath_element" should exist
    And the "rating" select box should contain "0"

   # Teacher sets ratings for student
   Given I set the field "rating" to "10"
   And I click on "Rate" "button"
   Then the "rating" select box should contain "10"
   And "span.rating-aggregate-label" "css_element" should exist
   And I should see "Average of ratings: 10 (1)"
