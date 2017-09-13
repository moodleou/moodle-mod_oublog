@ou @ou_vle @mod @mod_oublog @oublog_separate
Feature: Test Post and Comment on Seperate Individual Blogs
  In order to engage with students posts and comments
  As a teacher
  I need to be able to see separate individual post entries

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student1 | 1 | student1@asd.com |
      | student2 | Student2 | 1 | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And the following "activities" exist:
      | activity | name | intro  | course | idnumber |
      | oublog | Testing separate individuals oublogs | Testing separate blogs as multipe users | C1 | oublog1 |

    # Admin changes settings for separate individuals
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Testing separate individuals oublogs"
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Individual blogs | Separate individual blogs |
    And I press "Save and display"
    And I should see "Separate individuals"
    And ".oublog-individualselector" "css_element" should exist
    And I should see "There are no visible posts in this blog"
    And I log out

  Scenario: Teacher tests the separate blog visibility
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Testing separate individuals oublogs"
    And I press "New blog post"
    And I should see "New blog post"
    And I set the following fields to these values:
      | Title | SC05 teacher post01 |
      | Message | SC05 teacher post01 content visible to admin and teacher |
      | Tags | dtag4sc05, ctag3sc05  |
    And I press "Add post"
    Then I should see "SC05 teacher post01"
    And I should see "SC05 teacher post01 content visible to admin and teacher"
    Given I follow "Add your comment"
    And I set the field "Add your comment" to "SC05 Teacher post01 comment visible to admin and teacher"
    And I press "Add comment"
    Then I should see "Comments" in the ".oublog-commentstitle" "css_element"
    And I log out

    # Student1 user posts
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Testing separate individuals oublogs"
    And I should see "There are no visible posts in this blog"
    Given I press "New blog post"
    And I should see "New blog post"
    And I set the following fields to these values:
      | Title | SC05 student1 post01 |
      | Message | SC05 student1 post01 content |
      | Tags | ctag3sc05, btag2sc05 |
    And I press "Add post"
    And I wait to be redirected
    Then I should see "SC05 student1 post01"
    And I should see "SC05 student1 post01 content"
    And I should not see "SC05 teacher post01"
    And I should not see "SC05 teacher post01 content visible to admin and teacher"
    And I log out

    # Student2 user posts
    Given I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Testing separate individuals oublogs"
    Then I should see "There are no visible posts in this blog"
    Given I press "New blog post"
    Then I should see "New blog post"
    Given I set the following fields to these values:
      | Title | SC05 student2 post01 |
      | Message | SC05 student2 post01 content |
      | Tags | btag2sc05, atag1sc05 |
    And I press "Add post"
    And I wait to be redirected
    Then I should see "SC05 student2 post01 "
    And I should see "SC05 student2 post01 content"
    And I should not see "SC05 student1 post01"
    And I should not see "SC05 student1 post01 content"
    And I should not see "SC05 teacher post01"
    And I should not see "SC05 teacher post01 content visible to admin and teacher"
    And I log out

    # Teacher check visibility
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Testing separate individuals oublogs"
    Then I should see "SC05 student2 post01"
    And I should see "SC05 student2 post01 content"
    And I should see "SC05 student1 post01"
    And I should see "SC05 student1 post01 content"
    And I should see "SC05 teacher post01"
    And I should see "SC05 teacher post01 content visible to admin and teacher"

    # Should see tags in default Alphabetical order
    And I should see "atag1sc05(1) btag2sc05(2) ctag3sc05(2) dtag4sc05(1)"

    # Should see posts in the participation block
    And "Participation" "text" should exist
    And ".oublogstats_posts_posttitle" "css_element" should exist
    And I should see "SC05 student2 post01" in the ".oublogstats_posts_posttitle" "css_element"
    And ".oublog_statsview_innercontent_participation" "css_element" should exist
    And I should see "Recent posts" in the ".oublog_statsview_innercontent_participation" "css_element"
    And I should see "Recent comments" in the ".oublog_statsview_innercontent_participation" "css_element"
    And ".oublogstats_commentposts_posttitle" "css_element" should exist
    And I should see "Untitled comment" in the ".oublogstats_commentposts_posttitle" "css_element"
    And ".oublogstats_commentposts_blogname" "css_element" should exist
    And "View all participation" "link" should exist
