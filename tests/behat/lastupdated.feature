@ou @ou_vle @mod @mod_oublog @lastmodified
Feature: Show last updated information on OU blog activity link
  In know when a blog was last updated
  As a user
  I need to see the last post date on the blog link

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |
      | student2 | Student | 2 | student2@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And the following "groups" exist:
      | name | course | idnumber |
      | G1 | C1 | G1 |
      | G2 | C1 | G2 |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
      | student2 | G2 |

  Scenario: Test course blog
    Given I log in as "admin"
    And the following "activity" exists:
      | activity | oublog             |
      | course   | C1                 |
      | name     | Test course oublog |
      | section  | 1                  |
    And I am on site homepage
    When I am on "Course 1" course homepage
    And I turn editing mode on
    Then I should see "Test course oublog"
    And ".lastmodtext.oubloglmt" "css_element" should not exist
    Given I follow "Test course oublog"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title | P0 |
      | Message | P0 |
    And I press "Add post"
    When I am on "Course 1" course homepage
    Then ".lastmodtext.oubloglmt" "css_element" should exist

  Scenario: Group blog
    Given I log in as "admin"
    And the following "activity" exists:
      | activity  | oublog |
      | course    | C1     |
      | name      | B.SG   |
      | section   | 1      |
      | groupmode | 1      |
    And the following "activity" exists:
      | activity  | oublog |
      | course    | C1     |
      | name      | B.VG   |
      | section   | 1      |
      | groupmode | 2      |
    And I am on "Course 1" course homepage
    And I turn editing mode on
    Given I follow "B.SG"
    And I set the field "Separate groups" to "G1"
    And I press "Go"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title | P1 |
      | Message | P1 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.VG"
    And I set the field "Visible groups" to "G1"
    And I press "Go"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title | P2 |
      | Message | P2 |
    And I press "Add post"
    Given I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    # Student should see both indicators.
    Then "/descendant::div[@class='activityinstance'][1]//span[@class='lastmodtext oubloglmt']" "xpath_element" should exist
    And "/descendant::div[@class='activityinstance'][2]//span[@class='lastmodtext oubloglmt']" "xpath_element" should exist
    Given I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    # Student should see only visible group indicators.
    Then "/descendant::div[@class='activityinstance'][1]//span[@class='lastmodtext oubloglmt']" "xpath_element" should not exist
    And "/descendant::div[@class='activityinstance'][2]//span[@class='lastmodtext oubloglmt']" "xpath_element" should exist

  Scenario: Indivdual blogs
    Given I log in as "admin"
    And the following "activity" exists:
      | activity   | oublog |
      | course     | C1     |
      | name       | B.SI   |
      | section    | 1      |
      | individual | 1      |
    And the following "activity" exists:
      | activity   | oublog |
      | course     | C1     |
      | name       | B.VI   |
      | section    | 1      |
      | individual | 2      |
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I follow "B.SI"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title | P1 |
      | Message | P1 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    And I follow "B.VI"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title | P2 |
      | Message | P2 |
    And I press "Add post"
    Given I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    # Student should see visible indicator only.
    Then "/descendant::div[@class='activityinstance'][1]//span[@class='lastmodtext oubloglmt']" "xpath_element" should not exist
    And "/descendant::div[@class='activityinstance'][2]//span[@class='lastmodtext oubloglmt']" "xpath_element" should exist

  Scenario: Mixed blogs
    Given I log in as "admin"
    And the following "activity" exists:
      | activity   | oublog |
      | course     | C1     |
      | name       | B.SISG |
      | section    | 1      |
      | individual | 1      |
      | groupmode  | 1      |
    And the following "activity" exists:
      | activity   | oublog |
      | course     | C1     |
      | name       | B.SIVG |
      | section    | 1      |
      | individual | 1      |
      | groupmode  | 2      |
    And the following "activity" exists:
      | activity   | oublog |
      | course     | C1     |
      | name       | B.VISG |
      | section    | 1      |
      | individual | 2      |
      | groupmode  | 1      |
    And the following "activity" exists:
      | activity   | oublog |
      | course     | C1     |
      | name       | B.VIVG |
      | section    | 1      |
      | individual | 2      |
      | groupmode  | 2      |
    And I am on "Course 1" course homepage
    And I turn editing mode on
    # Admin adds post to every blog.
    And I follow "B.SISG"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title | P1 |
      | Message | P1 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    And I follow "B.SIVG"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title | P2 |
      | Message | P2 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    And I follow "B.VISG"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title | P3 |
      | Message | P3 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    And I follow "B.VIVG"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title | P1 |
      | Message | P1 |
    And I press "Add post"
    When I am on "Course 1" course homepage
    Then "/descendant::div[@class='activityinstance'][1]//span[@class='lastmodtext oubloglmt']" "xpath_element" should exist
    And "/descendant::div[@class='activityinstance'][2]//span[@class='lastmodtext oubloglmt']" "xpath_element" should exist
    And "/descendant::div[@class='activityinstance'][3]//span[@class='lastmodtext oubloglmt']" "xpath_element" should exist
    And "/descendant::div[@class='activityinstance'][4]//span[@class='lastmodtext oubloglmt']" "xpath_element" should exist
    And I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    # Student should see visible indicator only.
    Then "/descendant::div[@class='activityinstance'][1]//span[@class='lastmodtext oubloglmt']" "xpath_element" should not exist
    And "/descendant::div[@class='activityinstance'][2]//span[@class='lastmodtext oubloglmt']" "xpath_element" should not exist
    And "/descendant::div[@class='activityinstance'][3]//span[@class='lastmodtext oubloglmt']" "xpath_element" should not exist
    And "/descendant::div[@class='activityinstance'][4]//span[@class='lastmodtext oubloglmt']" "xpath_element" should exist
    Given I follow "B.VISG"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title | P3 |
      | Message | P3 |
    And I press "Add post"
    When I am on "Course 1" course homepage
    Then "/descendant::div[@class='activityinstance'][3]//span[@class='lastmodtext oubloglmt']" "xpath_element" should exist
    And I log out
    Given I log in as "student2"
    And I am on "Course 1" course homepage
    # Student should see visible group+individuals only.
    Then "/descendant::div[@class='activityinstance'][1]//span[@class='lastmodtext oubloglmt']" "xpath_element" should not exist
    And "/descendant::div[@class='activityinstance'][2]//span[@class='lastmodtext oubloglmt']" "xpath_element" should not exist
    And "/descendant::div[@class='activityinstance'][3]//span[@class='lastmodtext oubloglmt']" "xpath_element" should not exist
    And "/descendant::div[@class='activityinstance'][4]//span[@class='lastmodtext oubloglmt']" "xpath_element" should exist
