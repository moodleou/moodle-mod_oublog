@ou @ou_vle @mod @mod_oublog @oublog_importblog
Feature: Test import posts function for blog
  In order to add OUBblog personal posts and OUBblog posts to current blog
  As an user
  I need to be able to import posts from another blogs

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student1  | 1        | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | format      | category | numsections |
      | Course 1 | C1        | oustudyplan | 0        | 0           |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | name                 | intro                        | course | idnumber | allowimport | individual |
      | oublog   | student 1 blog one   | The first blog of student 1  | C1     | oublog1  | 1           | 2          |
      | oublog   | student 1 blog two   | The second blog of student 2 | C1     | oublog2  | 1           | 2          |
      | oublog   | student 1 blog three | The second blog of student 3 | C1     | oublog3  | 1           | 2          |

    # Student 1 add posts to the first blog
    And I log in as "student1"
    And I am on site homepage
    And I am using the OSEP theme
    And I am on "Course 1" course homepage
    And I press "Expand all"
    And I follow "student 1 blog one"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title      | Post 0 title                        |
      | Message    | Post 0 message                      |
      | Tags       | C#, Java                            |
      | Attachment | lib/tests/fixtures/upload_users.csv |
    And I press "Add post"
    When I follow "Add your comment"
    And I set the following fields to these values:
      | Title            | Post 0 comment 1         |
      | Add your comment | Post 0 Comment 1 message |
    Then I click on "Add comment" "button"
    When I follow "Add your comment"
    And I set the following fields to these values:
      | Title            | Post 0 comment 2         |
      | Add your comment | Post 0 Comment 2 message |
    Then I click on "Add comment" "button"
    When I follow "student 1 blog one"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title   | Post 1 title   |
      | Message | Post 1 message |
      | Tags    | JS, PHP        |
    And I press "Add post"

  @javascript
  Scenario: Display import selected posts and import blog links for each blog that has post.
    Given I am on "Course 1" course homepage
    And I follow "student 1 blog two"
    When I click on "Import" "button"
    Then I should see "student 1 blog three (0 posts)"
    Then I should see "student 1 blog one (2 posts)"
    Then I should see "Import selected posts"
    Then I should see "Import blog"

  @javascript
  Scenario: Navigate to post listing page when clicking on import selected posts link.
    Given I am on "Course 1" course homepage
    And I follow "student 1 blog two"
    And I click on "Import" "button"
    When I follow "Import selected posts"
    Then I should see "student 1 blog one"
    Then I should see "Post 1 title"
    Then I should see "Post 0 title"
    And I follow "Select all"
    And I click on "Import" "button"
    Then I should see "2 post(s) imported successfully"
    And I click on "Continue" "button"
    Then I should see "Post 0 title"
    Then I should see "Post 0 message"
    Then I should see "Post 1 title"
    Then I should see "Post 1 message"
    Then I should see "js, php, c1"
    Then I should see "c#, java, c1"
    Then I should see "2 comments"
    Then I should see "upload_users.csv"
    And I follow "2 comments"
    Then I should see "Post 0 comment 1"
    Then I should see "Post 0 Comment 1 message"
    Then I should see "Post 0 comment 2"
    Then I should see "Post 0 Comment 2 message"

  @javascript
  Scenario: Perform the import all posts of the selected blog when clicking on import blog link.
    Given I am on "Course 1" course homepage
    And I follow "student 1 blog two"
    And I click on "Import" "button"
    When I follow "Import blog"
    Then I should see "2 post(s) imported successfully"
    And I click on "Continue" "button"
    Then I should see "Post 0 title"
    Then I should see "Post 0 message"
    Then I should see "Post 1 title"
    Then I should see "Post 1 message"
    Then I should see "js, php, c1"
    Then I should see "c#, java, c1"
    Then I should see "2 comments"
    Then I should see "upload_users.csv"
    And I follow "2 comments"
    Then I should see "Post 0 comment 1"
    Then I should see "Post 0 Comment 1 message"
    Then I should see "Post 0 comment 2"
    Then I should see "Post 0 Comment 2 message"
