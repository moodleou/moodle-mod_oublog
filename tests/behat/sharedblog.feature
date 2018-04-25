@ou @ou_vle @mod @mod_oublog @oublog_sharedblog
Feature: Test shared data from Master blog on OUBlog
  In order to use OUBlog features
  As a user
  I need to be able to have a learning log with the same functionality as the master blog

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format      | category | numsections |
      | Course 1 | C1        | oustudyplan | 0        | 0           |
    # Create masterblog.
    And the following "activities" exist:
      | activity | name          | intro                                     | course | idnumber    |
      | oublog   | Master Blog   | A blog can share content to other blog    | C1     | masterblog  |
    # Create child blog.
    And the following "activities" exist:
      | activity | name          | intro                            | course | individual | idsharedblog | idnumber   |
      | oublog   | Child Blog    | A blog get content from master   | C1     | 2          | masterblog   | childblog  |
    And I log in as "admin"
    And I am on site homepage
    And I am on "Course 1" course homepage with editing mode on
    And I follow "Master Blog"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title      | Content Master Blog 1               |
      | Message    | This is Content Master Blog 1       |
      | Tags       | master, blog                        |
      | Attachment | lib/tests/fixtures/upload_users.csv |
    And I press "Add post"

  @javascript
  Scenario: Child blog only show data relating to master blog
    When I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Child Blog"
    Then I should see "Content Master Blog 1"
    And I should see "upload_users.csv"
    And I should see "blog(1)" in the "#oublog-tags" "css_element"

  @javascript
  Scenario: Child blog show links from master blog
    When I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Master Blog"
    And I follow "Add link"
    And I set the following fields to these values:
      | Title                 | Teachers Personal blog test                 |
      | Full Web address      | http://127.0.0.1/mod/oublog/view.php?user=3 |
    And I press "id_submitbutton"
    And I am on "Course 1" course homepage
    And I follow "Child Blog"
    And I should see "Teachers Personal blog test" in the "#oublog-links" "css_element"

  @javascript
  Scenario: Fail to create child blog because wrong ID number.
    When I am on "Course 1" course homepage
    And I add a "OU blog" to section "0" and I fill the form with:
      | Blog name         | Child Blog 2              |
      | Intro             | Can not create this blog  |
      | Individual blogs  | Visible individual blogs  |
      | Shared blog       | masterblog2               |
    Then I press "Save and display"
    And I should see "No matching ID number"

  @javascript
  Scenario: Fail to create child blog because that blog is child of the other.
    When I am on "Course 1" course homepage
    And I add a "OU blog" to section "0" and I fill the form with:
      | Blog name         | Child Blog 3              |
      | Intro             | Can not create this blog  |
      | Individual blogs  | Visible individual blogs  |
      | Shared blog       | childblog                 |
    Then I press "Save and display"
    And I should see "This is an ID number of a child blog"
