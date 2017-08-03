@ou @ou_vle @mod @mod_oublog @oublog_time
Feature: Test time limited posts and comments
  In order to limit students posts and comments to time periods
  As a teacher
  I need to be able to set time limits

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student1 | 1 | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
    And the following "activities" exist:
      | activity | name                   | intro                             | course | idnumber | postfrom   | postuntil  | commentfrom | commentuntil |
      | oublog   | blog post start past   | A blog when posts start in past   | C1     | oublog1  | 1262307600 | 0          | 1262307600  | 0            |
      | oublog   | blog post start future | A blog when posts start in future | C1     | oublog2  | 2524611600 | 0          | 2524611600  | 0            |
      | oublog   | blog post end past     | A blog when posts start in past   | C1     | oublog3  | 0          | 1262307600 | 0           | 1262307600   |
      | oublog   | blog post end future   | A blog when posts start in future | C1     | oublog4  | 0          | 2524611600 | 0           | 2524611600   |
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "blog post start future"
    And I press "New blog post"
    And I set the field "Message" to "Test"
    And I press "Add post"
    And I am on "Course 1" course homepage
    And I follow "blog post end past"
    And I press "New blog post"
    And I set the field "Message" to "Test"
    And I press "Add post"
    And I log out

  Scenario: Admin tests timed blogs
   Given I log in as "admin"
   And I am on "Course 1" course homepage
   # Test blog1.
   When I follow "blog post start past"
   Then I should not see "Students cannot create their own posts until"
   And I should not see "Students cannot comment on posts until"
   And "New blog post" "button" should exist
   Given I am on "Course 1" course homepage
   # Test blog2.
   When I follow "blog post start future"
   Then I should see "Students cannot create their own posts until"
   And I should see "Students cannot comment on posts until"
   And "New blog post" "button" should exist
   And "Add your comment" "link" should exist
   Given I am on "Course 1" course homepage
   # Test blog3.
   When I follow "blog post end past"
   Then I should see "Students were able to create their own posts until"
   And I should see "Students were able to comment on posts until"
   And "New blog post" "button" should exist
   And "Add your comment" "link" should exist
   Given I am on "Course 1" course homepage
   # Test blog4.
   When I follow "blog post end future"
   Then I should see "Students are able to create their own posts until"
   # Test no comment message as no posts.
   And I should not see "Students are able to comment on posts until"
   And "New blog post" "button" should exist
   Given I log out

  Scenario: Admin tests timed blogs as student
   Given I log in as "student1"
   And I am on "Course 1" course homepage
   # Test blog1.
   When I follow "blog post start past"
   Then I should not see "You cannot create posts at this time"
   And I should not see "You cannot comment on posts at this time"
   And "New blog post" "button" should exist
   Given I am on "Course 1" course homepage
   # Test blog2.
   When I follow "blog post start future"
   Then I should see "You cannot create posts at this time"
   And I should see "You cannot comment on posts at this time"
   And "New blog post" "button" should not exist
   And "Add your comment" "link" should not exist
   Given I am on "Course 1" course homepage
   # Test blog3.
   When I follow "blog post end past"
   Then I should see "You cannot create posts at this time"
   And I should see "You cannot comment on posts at this time"
   And "New blog post" "button" should not exist
   And "Add your comment" "link" should not exist
   Given I am on "Course 1" course homepage
   # Test blog4.
   When I follow "blog post end future"
   Then I should see "You can only create posts until"
   # Test no comment message as no posts.
   And I should not see "You can only comment on posts until"
   And "New blog post" "button" should exist
   Given I am on "Course 1" course homepage
