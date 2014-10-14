@ou @ou_vle @mod @mod_oublog @oublog_basic
Feature: Test Post and Comment on OUBlog entry
  In order to use OUBblog features
  As a user
  I need to be able to complete basic operations

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And the following "activities" exist:
      | activity | name | intro  | course | idnumber |
      | oublog | Test oublog basics | Test oublog basics intro text | C1 | oublog1 |

  Scenario: Check teacher post student access
    Given I log in as "teacher1"
    And I am on homepage
    And I follow "Course 1"
    When I follow "Test oublog basics"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title | SC01 OUBlog post01 by teacher |
      | Message | SC01 Teacher OUBlog post01 content |
      | Tags | btagsc01 |
    And I press "Add post"
    And I log out

    # Check visibility as student
    And I log in as "student1"
    And I follow "Course 1"
    And I follow "Test oublog basics"
    Then I should see "SC01 OUBlog post01 by teacher"
    And I should see "SC01 Teacher OUBlog post01 content"
    And I should see "btagsc01"
    And I log out

  @javascript
  Scenario: Check tag sorting and filter by tag as student
    Given I log in as "teacher1"
    And I am on homepage
    And I follow "Course 1"
    When I follow "Test oublog basics"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title | SC02 OUBlog post01 from teacher1 |
      | Message | SC02 Teacher OUBlog post01 content |
      | Tags | ctag3sc02 |
    And I press "Add post"
    And I log out

    # Student tests
    Given I log in as "student1"
    And I follow "Course 1"
    And I follow "Test oublog basics"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title | SC02 OUBlog post01 from student |
      | Message | SC02 Student OUBlog post01 content |
      | Tags | ctag3sc02, btag2sc02 |
    And I press "Add post"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title | SC02 OUBlog post02 from student |
      | Message | SC02 Student OUBlog post02 content filtered by tag|
      | Tags | atag1sc02, btag2sc02, ctag3sc02 |
    And I press "Add post"

    # Should see tags in default Alphabetical order
    Then I should see "atag1sc02(1) btag2sc02(2) ctag3sc02(3)"
    And I follow "Most used"
    And I should see "ctag3sc02(3) btag2sc02(2) atag1sc02(1)"

    # Check filter by tag
    Given I follow "atag1sc02"
    And I should not see "OUBlog post from teacher1"
    And I should not see "Teacher OUBlog post01 content"
    And I should not see "OUBlog post from student1"
    And I should not see "Student OUBlog post01 content"

    # Check post edit with attachment
    Given I follow "Edit"
    And I set the following fields to these values:
      | Title | SC02 OUBlog post02 from student edited post subject |
      | Message | SC02 Student OUBlog post02 content filtered by tag edited post body |
      | Tags | dtag3sc02 |
    And I upload "lib/tests/fixtures/empty.txt" file to "Attachments" filemanager
    And I press "Save changes"
    And I wait to be redirected
    Then I should see "SC02 OUBlog post02 from student edited post subject"
    And I should see "SC02 Student OUBlog post02 content filtered by tag edited post body"
    And I should see "dtag3sc02"
    And I should see "empty.txt"

    # Delete student post01 the second post made
    Given I click on "//a[contains(@href,'deletepost.php?blog=2&post=2&delete=1')]" "xpath_element"
    And I wait to be redirected
    Then I should see "Are you sure you want to delete this post?"
    And I press "Delete"
    And I wait to be redirected
    Then I should see "SC02 OUBlog post02 from student edited post subject"
    And ".oublog-deleted" "css_element" should exist
    And ".oublog-post-deletedby" "css_element" should exist
    And I should see "Deleted by"
    And "Delete" "link" should not exist in the ".oublog-deleted" "css_element"
    And "Add your comment" "link" should not exist in the ".oublog-deleted" "css_element"

    # Check post comments with comments enabled
    Then I should see "SC02 OUBlog post02 from student edited post subject"
    And I should see "SC02 Student OUBlog post02 content filtered by tag edited post body"
    Given I follow "Add your comment"
    And I set the field "Title" to "$My own >nasty< \"string\"!"
    And I set the field "Add your comment" to "$My own >nasty< \"string\"!"
    And I press "Add comment"
    Then I should see "SC02 OUBlog post02 from student"
    And I should see "$My own >nasty< \"string\"!"
    And I log out

    # Check post with comments disabled as Teacher
    Given I log in as "teacher1"
    And I am on homepage
    And I follow "Course 1"
    When I follow "Test oublog basics"
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Allow comments | Comments not allowed |
    And I press "Save and display"

    # Check related links 'block'
    Given I follow "Add link"
    And I set the following fields to these values:
      | Title | Teachers Personal blog test |
      | Full Web address | http://127.0.0.1/mod/oublog/view.php?user=3 |
    And I press "id_submitbutton"
    Then "Teachers Personal blog test" "link" should be visible
    And I log out

    # Student test comments disabled and related link
    Given I log in as "student1"
    And I follow "Course 1"
    When I follow "Test oublog basics"
    Then "Add your comment" "link" should not exist in the ".oublog-post" "css_element"
    And "Teachers Personal blog test" "link" should be visible
    And I log out
