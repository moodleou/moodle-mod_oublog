@ou @ou_vle @mod @mod_oublog @javascript
Feature: Test multiple export feature on OUBlog with Share feature
  In order to use OUBblog features
  As a user
  I need to be able to complete multiple export operations

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activities" exist:
      | activity | name           | course | idnumber      | individual | idsharedblog  |
      | oublog   | OU Blog Master | C1     | OUBLOG_MASTER | 2          |               |
      | oublog   | OU Blog Child  | C1     | OUBLOG_SLAVE  | 2          | OUBLOG_MASTER |
    And the following config values are set as admin:
      | enableportfolios | 1 |
    And I log in as "admin"
    And I am on site homepage
    And I navigate to "Manage portfolios" node in "Site administration > Plugins > Portfolios"
    And I set the field with xpath "//form[@id='applytodownload']//select" to "Enabled and visible"
    And I press "Save"
    And I log out

  Scenario: The export button from individual posts in the blog view not exist
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "OU Blog Child"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | Post 1         |
      | Message | Post 1 content |
    And I press "Add post"
    Then "Export" "link" should not exist in the ".oublog-post-links" "css_element"
    When I follow "Permalink"
    Then "Export" "link" should exist in the ".oublog-post-links" "css_element"

  Scenario: The export button on individual posts won't show when clicking on 'permalink' on OSEP theme only
    Given I log in as "teacher1"
    And I am using the OSEP theme
    And I am on "Course 1" course homepage
    And I follow "OU Blog Child"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | Post 1         |
      | Message | Post 1 content |
    And I press "Add post"
    Then "Export" "link" should not exist in the ".oublog-post-links" "css_element"
    When I follow "Permalink"
    Then "Export" "link" should not exist in the ".oublog-post-links" "css_element"

  Scenario: The large export icon to the right-hand lower corner of the same page (OSEP theme only)
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "OU Blog Child"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | Post 1         |
      | Message | Post 1 content |
    And I press "Add post"
    When I follow "Permalink"
    Then "#osep-bottombutton-export" "css_element" should not exist
    Given I am using the OSEP theme
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "OU Blog Child"
    When I follow "Permalink"
    Then "#osep-bottombutton-export" "css_element" should exist in the "#osep-bottombuttons" "css_element"

  Scenario: The export page will be shown when users click export button
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "OU Blog Child"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title   | Post Test         |
      | Message | Post Test content |
    And I press "Add post"
    When I follow "Export"
    Then I should see "You can export individual blog posts from your online module content using the links below"
    And the "Select all" "button" should be enabled
    And the "Select none" "button" should be disabled
    And the "Export" "button" should be disabled
    And I log out

    And I log in as "student1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "OU Blog Child"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title   | Post 1                                     |
      | Message | Post 1 content                             |
      | Tags    | this is a very long tag with 40 characters |
    And I press "Add post"
    And I log out

    And I log in as "student2"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "OU Blog Child"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title   | Post 2              |
      | Message | Post 2 content      |
      | Tags    | tag1,tag2,tag3,tag4 |
    And I press "Add post"
    And I log out

    # Test individual mode to Student 1.
    And I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "OU Blog Child"
    When I set the field "Visible individuals" to "Student 1"
    Then I should see "Post 1"
    And I should not see "Post 2"
    When I follow "Export"
    Then I should see "Post 1"
    And I should see "Student 1" in the "Post 1" "table_row"
    And I should not see "Post 2"

    # Test individual mode to Student 2.
    Given I follow "OU Blog Child"
    When I set the field "Visible individuals" to "Student 2"
    Then I should see "Post 2"
    And I should not see "Post 1"
    When I follow "Export"
    Then I should see "Post 2"
    And I should see "Student 2" in the "Post 2" "table_row"
    And I should not see "Post 1"

    # Test individual mode for all.
    And I follow "OU Blog Child"
    When I set the field "Visible individuals" to "View all users"
    Then I should see "Post 1"
    And I should see "Post 2"
    When I follow "Export"
    Then I should see "Post 1"
    And I should see "Student 1" in the "Post 1" "table_row"
    # Limit 40 characters of tags field.
    And I should see "..." in the "Post 1" "table_row"
    And I should not see "this is a very long tag with 40 characters" in the "Post 1" "table_row"
    And I should see "Post 2"
    And I should see "Student 2" in the "Post 2" "table_row"
    # Limit 40 characters of tags field.
    And I should see "tag1" in the "Post 2" "table_row"
    And I should see "tag2" in the "Post 2" "table_row"
    And I should see "tag3" in the "Post 2" "table_row"
    And I should see "tag4" in the "Post 2" "table_row"
    And I should not see "..." in the "Post 2" "table_row"
    # Default sort posts by date.
    And I should see "Post 2" in the "#oublog_export_posts_table_r0" "css_element"
    And I should see "Post 1" in the "#oublog_export_posts_table_r1" "css_element"
    # Sort by title.
    When I click on "Title" "link"
    Then I should see "Post 1" in the "#oublog_export_posts_table_r0" "css_element"
    And I should see "Post 2" in the "#oublog_export_posts_table_r1" "css_element"
    And I should see "Post Test" in the "#oublog_export_posts_table_r2" "css_element"
    When I click on "Title" "link"
    Then I should see "Post Test" in the "#oublog_export_posts_table_r0" "css_element"
    Then I should see "Post 2" in the "#oublog_export_posts_table_r1" "css_element"
    Then I should see "Post 1" in the "#oublog_export_posts_table_r2" "css_element"
    And I click on "Reset table preferences" "link"
    # Sort by date.
    When I click on "Date posted" "link"
    Then I should see "Post Test" in the "#oublog_export_posts_table_r0" "css_element"
    And I should see "Post 1" in the "#oublog_export_posts_table_r1" "css_element"
    And I should see "Post 2" in the "#oublog_export_posts_table_r2" "css_element"
    When I click on "Date posted" "link"
    Then I should see "Post 2" in the "#oublog_export_posts_table_r0" "css_element"
    And I should see "Post 1" in the "#oublog_export_posts_table_r1" "css_element"
    And I should see "Post Test" in the "#oublog_export_posts_table_r2" "css_element"
    # Sort by author.
    When I click on "Author" "link"
    Then I should see "Student 2" in the "#oublog_export_posts_table_r0" "css_element"
    And I should see "Student 1" in the "#oublog_export_posts_table_r1" "css_element"
    And I should see "Teacher 1" in the "#oublog_export_posts_table_r2" "css_element"
    When I click on "Author" "link"
    Then I should see "Teacher 1" in the "#oublog_export_posts_table_r0" "css_element"
    And I should see "Student 2" in the "#oublog_export_posts_table_r1" "css_element"
    And I should see "Student 1" in the "#oublog_export_posts_table_r2" "css_element"
    And I click on "Reset table preferences" "link"
    # View the user’s blog when click name under 'Author' column.
    When I click on "Student 1" "link" in the "Post 1" "table_row"
    Then I should see "OU Blog Child" in the ".page-header-headings" "css_element"
    And I should not see "OU Blog Master" in the ".page-header-headings" "css_element"
    And I should see "Post 1" in the ".oublog-post" "css_element"
    And I should see "Post 1 content" in the ".oublog-post" "css_element"
    And I should not see "Post 2" in the ".oublog-post" "css_element"
    And I set the field "Visible individuals" to "View all users"
    And I follow "Export"
    When I click on "Student 2" "link" in the "Post 2" "table_row"
    Then I should see "OU Blog Child" in the ".page-header-headings" "css_element"
    And I should not see "OU Blog Master" in the ".page-header-headings" "css_element"
    And I should see "Post 2" in the ".oublog-post" "css_element"
    And I should see "Post 2 content" in the ".oublog-post" "css_element"
    And I should not see "Post 1" in the ".oublog-post" "css_element"
    # View the tags page when click tag under 'Tags' column.
    And I set the field "Visible individuals" to "View all users"
    And I follow "Export"
    When I click on "tag1" "link" in the "Post 2" "table_row"
    Then I should see "OU Blog Child" in the ".page-header-headings" "css_element"
    And I should not see "OU Blog Master" in the ".page-header-headings" "css_element"
    And I should see "Post 2" in the ".oublog-post" "css_element"
    And I should see "Post 2 content" in the ".oublog-post" "css_element"
    And I should not see "Post 1" in the ".oublog-post" "css_element"
    # View the post page when click post title under 'Title' column.
    And I follow "OU Blog Child"
    And I set the field "Visible individuals" to "View all users"
    And I follow "Export"
    When I click on "Post 1" "link" in the "Post 1" "table_row"
    Then I should see "OU Blog Child" in the "#oublog-arrowback" "css_element"
    And I should not see "OU Blog Master" in the "#oublog-arrowback" "css_element"
    And I should see "Post 1" in the ".oublog-post" "css_element"
    And I should see "Post 1 content" in the ".oublog-post" "css_element"
    And I should see "this is a very long tag with 40 characters" in the ".oublog-post" "css_element"
    And I should not see "Post 2" in the ".oublog-post" "css_element"

  Scenario: The export page will be paginated
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "OU Blog Child"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title   | Post Test         |
      | Message | Post Test content |
    And I press "Add post"
    When I follow "Export"
    Then I should see "You can export individual blog posts from your online module content using the links below"
    And ".oublog-paging" "css_element" should not exist
    And I follow "OU Blog Child"
    And I create "50" sample posts for blog with id "OUBLOG_MASTER"
    And I wait "2" seconds
    And I reload the page
    When I follow "Export"
    Then ".oublog-paging" "css_element" should exist
    And I should see "Next" in the ".oublog-paging" "css_element"
    And I should not see "Previous" in the ".oublog-paging" "css_element"
    And I should not see "Post Test"
    When I click on "2" "link" in the ".oublog-paging" "css_element"
    Then I should see "Post Test"

  Scenario: User can select many posts to download
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "OU Blog Child"
    And I create "50" sample posts for blog with id "OUBLOG_MASTER"
    And I wait "2" seconds
    And I reload the page
    And I follow "Export"
    And I click on "Title" "link"
    When I click on "select" "checkbox" in the "Test post 0" "table_row"
    Then the "Select none" "button" should be enabled
    And the "Export" "button" should be enabled
    When I click on "select" "checkbox" in the "Test post 0" "table_row"
    Then the "Select none" "button" should be disabled
    And the "Export" "button" should be disabled
    When I press "Select all"
    Then the "Select all" "button" should be disabled
    And the "Select none" "button" should be enabled
    And the "Export" "button" should be enabled
    And I press "Export"
    Then I should see "Downloading ..."
    And I should see "Return to where you were"
    When I follow "Return to where you were"
    Then I should see "OU Blog Child"
