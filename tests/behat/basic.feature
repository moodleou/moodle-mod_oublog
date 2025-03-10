@ou @ou_vle @mod @mod_oublog @oublog_basic
Feature: Test Post and Comment on OUBlog entry
  In order to use OUBblog features
  As a user
  I need to be able to complete basic operations

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
      | student3 | Student   | 3        | student3@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following "groups" exist:
      | name | course | idnumber |
      | G1   | C1     | G1       |
      | G2   | C1     | G2       |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student2 | G2    |
    And the following "activities" exist:
      | activity | name                             | intro                         | course | idnumber | restricttags | tagslist       |
      | oublog   | Test oublog basics               | Test oublog basics intro text | C1     | oublog1  |              |                |
      | oublog   | Test oublog with default tags    | Test oublog with default tags | C1     | oublog2  | 4            | tag1,tag2,tag3 |
      | oublog   | Test oublog with no default tags | Test oublog with default tags | C1     | oublog2  |              | tag1,tag2,tag3 |

  Scenario: Multiple blog type tests - basic access etc
    Given I log in as "teacher1"
    And the following "activities" exist:
      | activity | name   | course | section | groupmode | individual |
      | oublog   | B.SG   | C1     | 1       | 1         |            |
      | oublog   | B.VG   | C1     | 1       | 2         |            |
      | oublog   | B.SI   | C1     | 1       |           | 1          |
      | oublog   | B.VI   | C1     | 1       |           | 2          |
      | oublog   | B.SISG | C1     | 1       | 1         | 1          |
      | oublog   | B.SIVG | C1     | 1       | 2         | 1          |
      | oublog   | B.VISG | C1     | 1       | 1         | 2          |
      | oublog   | B.VIVG | C1     | 1       | 2         | 2          |
    And I am on homepage
    And I am on "Course 1" course homepage
    And I turn editing mode on
    Then I should see "Test oublog basics"
    # Editing teacher adds posts to all the blogs.
    Given I follow "Test oublog basics"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P0 |
      | Message | P0 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.SG"
    And I set the field "Separate groups" to "G1"
    And I press "Go"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P2 |
      | Message | P2 |
    And I press "Add post"
    Given I set the field "Separate groups" to "G2"
    And I press "Go"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P3 |
      | Message | P3 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.SI"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P10 |
      | Message | P10 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.VI"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P13 |
      | Message | P13 |
    And I press "Add post"
    Then I log out
    # Student 1 adds posts.
    Given I log in as "student1"
    And I am on homepage
    And I am on "Course 1" course homepage
    Given I follow "Test oublog basics"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P1 |
      | Message | P1 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.SG"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P4 |
      | Message | P4 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.VG"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P6 |
      | Message | P6 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.SI"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P8 |
      | Message | P8 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.VI"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P11 |
      | Message | P11 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.SISG"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P15 |
      | Message | P15 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.SIVG"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P16 |
      | Message | P16 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.VISG"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P17 |
      | Message | P17 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.VIVG"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P18 |
      | Message | P18 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Then I log out
    # Student 2 adds posts.
    Given I log in as "student2"
    And I am on homepage
    And I am on "Course 1" course homepage
    Given I follow "B.SG"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P5 |
      | Message | P5 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.VG"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P7 |
      | Message | P7 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.SI"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P9 |
      | Message | P9 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.VI"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P12 |
      | Message | P12 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.SISG"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P19 |
      | Message | P19 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.SIVG"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P20 |
      | Message | P20 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.VISG"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P21 |
      | Message | P21 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Given I follow "B.VIVG"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P22 |
      | Message | P22 |
    And I press "Add post"
    And I am on "Course 1" course homepage
    Then I log out
    # Editing teacher - check view.
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    When I follow "B.SG"
    Then the "Separate groups" select box should contain "G1"
    And the "Separate groups" select box should contain "G2"
    And the field "Separate groups" matches value "All participants"
    And I should see "P2" in the "#oublog-posts" "css_element"
    And I should see "P3" in the "#oublog-posts" "css_element"
    Given I set the field "Separate groups" to "G1"
    When I press "Go"
    Then I should see "P4" in the "#oublog-posts" "css_element"
    And I should not see "P5" in the "#oublog-posts" "css_element"
    Given I am on "Course 1" course homepage
    When I follow "B.SI"
    Then the "jump" select box should contain "Student 1"
    And the field "jump" matches value "View all users"
    And I should see "P10" in the "#oublog-posts" "css_element"
    And I should see "P8" in the "#oublog-posts" "css_element"
    And I should see "P9" in the "#oublog-posts" "css_element"
    Given I set the field "jump" to "Teacher 1"
    When I press "Go"
    Then I should see "P10" in the "#oublog-posts" "css_element"
    And I should not see "P9" in the "#oublog-posts" "css_element"
    Given I am on "Course 1" course homepage
    When I follow "B.SISG"
    Then the "Separate groups" select box should contain "All participants"
    And the "Separate groups" select box should contain "G2"
    And the field "Separate groups" matches value "G1"
    And I should see "P15" in the "#oublog-posts" "css_element"
    And I should not see "P19" in the "#oublog-posts" "css_element"
    Given I set the field "Separate groups" to "All participants"
    When I press "Go"
    Then the "jump" select box should contain "Student 1"
    And the field "jump" matches value "Teacher 1"
    And I should not see "P15" in the "#region-main" "css_element"
    Given I set the field "jump" to "Student 2"
    When I click on "#selectindividual input[type=submit]" "css_element"
    Then I should see "P19" in the "#oublog-posts" "css_element"
    And I should not see "P15" in the "#oublog-posts" "css_element"
    Given I am on "Course 1" course homepage
    When I follow "B.SIVG"
    Then the "Visible groups" select box should contain "G1"
    And the "Visible groups" select box should contain "G2"
    And the field "Visible groups" matches value "All participants"
    And the "jump" select box should contain "Student 2"
    And I should see "P20" in the "#oublog-posts" "css_element"
    And I should not see "P16" in the "#oublog-posts" "css_element"
    Given I set the field "jump" to "View all users"
    When I click on "#selectindividual input[type=submit]" "css_element"
    Then I should see "P20" in the "#oublog-posts" "css_element"
    And I should see "P16" in the "#oublog-posts" "css_element"
    Given I set the field "Visible groups" to "G1"
    When I press "Go"
    Then I should see "P16" in the "#oublog-posts" "css_element"
    And I should not see "P20" in the "#oublog-posts" "css_element"
    Given I am on "Course 1" course homepage
    When I follow "B.VISG"
    Then the "Separate groups" select box should contain "All participants"
    And the "Separate groups" select box should contain "G2"
    And the field "Separate groups" matches value "G1"
    And I should see "P17" in the "#oublog-posts" "css_element"
    And I should not see "P21" in the "#oublog-posts" "css_element"
    Given I set the field "Separate groups" to "All participants"
    When I press "Go"
    Then I should see "P21" in the "#oublog-posts" "css_element"
    And I should see "P17" in the "#oublog-posts" "css_element"
    Given I set the field "jump" to "Student 1"
    When I click on "#selectindividual input[type=submit]" "css_element"
    Then I should see "P17" in the "#oublog-posts" "css_element"
    And I should not see "P21" in the "#oublog-posts" "css_element"
    Given I am on "Course 1" course homepage
    When I follow "B.VIVG"
    Then the "Visible groups" select box should contain "G1"
    And the "Visible groups" select box should contain "G2"
    And the field "Visible groups" matches value "All participants"
    And the "jump" select box should contain "View all users"
    And I should see "P18" in the "#oublog-posts" "css_element"
    Given I set the field "jump" to "View all users"
    When I click on "#selectindividual input[type=submit]" "css_element"
    Then I should see "P18" in the "#oublog-posts" "css_element"
    And I should see "P22" in the "#oublog-posts" "css_element"
    Given I set the field "Visible groups" to "G2"
    When I press "Go"
    Then I should see "P22" in the "#oublog-posts" "css_element"
    And I should not see "P18" in the "#oublog-posts" "css_element"
    Given I am on "Course 1" course homepage
    When I log out
    # Student 1 - check view.
    Given I log in as "student1"
    And I am on homepage
    And I am on "Course 1" course homepage
    When I follow "B.SG"
    Then I should see "P4" in the "#oublog-posts" "css_element"
    And I should see "P2" in the "#oublog-posts" "css_element"
    And ".groupselector select" "css_element" should not exist
    And I am on "Course 1" course homepage
    When I follow "B.VG"
    Then ".groupselector select" "css_element" should exist
    And the field "Visible groups" matches value "G1"
    And the "Visible groups" select box should contain "G2"
    And the "Visible groups" select box should contain "All participants"
    And I should see "P6" in the "#oublog-posts" "css_element"
    And I should not see "P7" in the "#oublog-posts" "css_element"
    Given I set the field "Visible groups" to "G2"
    When I press "Go"
    Then I should see "P7" in the "#oublog-posts" "css_element"
    And I should not see "P6" in the "#oublog-posts" "css_element"
    And I am on "Course 1" course homepage
    When I follow "B.SI"
    Then I should see "P8" in the "#oublog-posts" "css_element"
    And ".oublog-individualselector select" "css_element" should not exist
    And I am on "Course 1" course homepage
    When I follow "B.VI"
    Then I should see "P11" in the "#oublog-posts" "css_element"
    And the field "jump" matches value "View all users"
    And the "jump" select box should contain "Student 2"
    Given I set the field "jump" to "Student 2"
    When I press "Go"
    Then I should see "P12" in the "#oublog-posts" "css_element"
    And I should not see "P11" in the "#oublog-posts" "css_element"
    Given I am on "Course 1" course homepage
    When I follow "B.SISG"
    Then I should see "P15" in the "#oublog-posts" "css_element"
    And I should not see "P19" in the "#oublog-posts" "css_element"
    And ".oublog-individualselector select" "css_element" should not exist
    And ".groupselector select" "css_element" should not exist
    Given I am on "Course 1" course homepage
    When I follow "B.SIVG"
    Then I should see "P16" in the "#oublog-posts" "css_element"
    And I should not see "P20" in the "#oublog-posts" "css_element"
    And ".oublog-individualselector select" "css_element" should not exist
    Given I am on "Course 1" course homepage
    When I follow "B.VISG"
    Then I should see "P17" in the "#oublog-posts" "css_element"
    And I should not see "P21" in the "#oublog-posts" "css_element"
    And ".groupselector select" "css_element" should not exist
    Given I am on "Course 1" course homepage
    When I follow "B.VIVG"
    Then I should see "P22" in the "#oublog-posts" "css_element"
    And I should not see "P18" in the "#oublog-posts" "css_element"
    Given I set the field "Visible groups" to "All participants"
    When I press "Go"
    Given I set the field "jump" to "View all users"
    When I click on "#selectindividual input[type=submit]" "css_element"
    Then I should see "P18" in the "#oublog-posts" "css_element"
    Given I am on "Course 1" course homepage
    When I log out
    # Student2 - check view.
    Given I log in as "student2"
    And I am on homepage
    And I am on "Course 1" course homepage
    When I follow "Test oublog basics"
    Then I should see "P0" in the ".oublog-post.oublog-even .oublog-post-content" "css_element"
    And I should see "P1" in the ".oublog-post.oublog-odd .oublog-post-content" "css_element"

  @javascript @_file_upload
  Scenario: Check tag sorting and filter by tag as student
    Given I log in as "teacher1"
    When I am on the "Test oublog basics" "oublog activity" page
    And I press "New blog post"
    # Before the 'Set' tags restriction
    And I should not see "You may only enter the 'Set' tags:"
    And I set the following fields to these values:
      | Title   | SC02 OUBlog post01 from teacher1   |
      | Message | SC02 Teacher OUBlog post01 content |
      | Tags    | ctag3sc02                          |
    And I press "Add post"
    And I log out

    # Student tests without Set tags restrictions.
    Given I log in as "student1"
    And I am on the "Test oublog basics" "oublog activity" page
    And I press "New blog post"
    # Before the 'Set' tags restriction
    And I should not see "You may only enter the 'Set' tags:"
    And I set the following fields to these values:
      | Title   | SC02 OUBlog post01 from student    |
      | Message | SC02 Student OUBlog post01 content |
      | Tags    | ctag3sc02, btag2sc02               |
    And I press "Add post"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title   | SC02 OUBlog post02 from student                    |
      | Message | SC02 Student OUBlog post02 content filtered by tag |
      | Tags    | atag1sc02, btag2sc02, ctag3sc02                    |
    And I press "Add post"

    # Should see tags in default Alphabetical order.
    Then I should see "atag1sc02(1) btag2sc02(2) ctag3sc02(3)"
    And I follow "Most used"
    And I should see "ctag3sc02(3) btag2sc02(2) atag1sc02(1)"

    # Check filter by tag.
    Given I follow "atag1sc02"
    And I should not see "OUBlog post from teacher1"
    And I should not see "Teacher OUBlog post01 content"
    And I should not see "OUBlog post from student1"
    And I should not see "Student OUBlog post01 content"

    # Check post edit with attachment.
    Given I follow "Edit"
    And I set the following fields to these values:
      | Title   | SC02 OUBlog post02 from student edited post subject                 |
      | Message | SC02 Student OUBlog post02 content filtered by tag edited post body |
      | Tags    | atag1sc02, btag2sc02, ctag3sc02, dtag3sc02                          |
    And I upload "lib/tests/fixtures/empty.txt" file to "Attachments" filemanager
    And I press "Save changes"
    And I wait to be redirected
    Then I should see "SC02 OUBlog post02 from student edited post subject"
    And I should see "SC02 Student OUBlog post02 content filtered by tag edited post body"
    And I should see "dtag3sc02"
    And I should see "empty.txt"
    And I should see "Edited by Student 1"
    Given I follow "Permalink"
    And I click on ".oublog-post-editsummary a" "css_element"
    Then I should not see "SC02 Student OUBlog post02 content filtered by tag edited post body"

    # Check post comments with comments enabled.
    Given I am on the "Test oublog basics" "oublog activity" page
    Then I should see "SC02 OUBlog post02 from student edited post subject"
    And I should see "SC02 Student OUBlog post02 content filtered by tag edited post body"
    Given I follow "Add your comment"
    And I set the field "Title" to "$My own >nasty< \"string\"!"
    And I set the field "Add your comment" to "$My own >nasty< \"string\"!"
    And I press "Add comment"
    Then I should see "SC02 OUBlog post02 from student"
    And I should see "$My own >nasty< \"string\"!"
    And I log out

    # Check post with restrictions enabled as Teacher.
    Given I log in as "teacher1"
    And I am on the "Test oublog basics" "oublog activity" page
    And I navigate to "Settings" in current page administration
    # Add the 'Set' tags restriction
    When I set the following fields to these values:
      | Tags        | ctag4sc02, btag5sc02, dogtag |
      | Tag options | 1                            |
    And I press "Save and display"

    # Test only the predefined tags are allowed.
    And I press "New blog post"
    Then I should see "You may only enter the 'Set' tags:"
    And I set the following fields to these values:
      | Title   | SC02 OUBlog post02 from teacher1   |
      | Message | SC02 OUBlog post02 teacher content |
      | Tags    | ctag4sc02, btag5sc02, catsndogs    |
    And I press "Add post"

    # Warning tags are now restricted
    Then I should see "Only 'Set' tags are allowed to be entered"
    And I set the following fields to these values:
      | Tags | dogtag |
    And I press "Add post"
    Then I should see "SC02 OUBlog post02 from teacher1"
    And I should see "SC02 OUBlog post02 teacher content"

    # Should see tags in default Alphabetical order.
    Then I should see "ctag3sc02(3) dogtag(1) dtag3sc02(1)"

  @javascript
  Scenario: Check deleting posts
    Given I create "2" sample posts for blog with id "oublog1"
    And I log in as "teacher1"
    And I am on the "Test oublog basics" "oublog activity" page
    And I should see "Test post 0"
    And I should see "Test post 1"

    # Delete a post as teacher1.
    When I click on "Delete" "link" in the ".oublog-post:nth-child(1) .oublog-post-links" "css_element"
    And I wait to be redirected
    Then I should see "Select to delete the post"
    And I click on "Delete" "button" in the "Confirm" "dialogue"
    And I wait to be redirected
    # Check deleted post details.
    Then I should see "Test post 0"
    And I should see "Test post 1"
    And ".oublog-deleted" "css_element" should exist
    And ".oublog-post-deletedby" "css_element" should exist
    And I should see "Deleted by Teacher 1"
    And "Delete" "link" should not exist in the ".oublog-deleted" "css_element"
    And "Add your comment" "link" should not exist in the ".oublog-deleted" "css_element"
    And I log out

    # Check posts as student1.
    Given I log in as "student1"
    And I am on the "Test oublog basics" "oublog activity" page
    And I should see "Test post 0"
    And I should not see "Test post 1"
    And I log out

    # Check posts as admin.
    Given I log in as "admin"
    And I am on the "Test oublog basics" "oublog activity" page
    And I should see "Test post 0"
    And I should see "Test post 1"
    And I should see "Deleted by Teacher 1"

    # Delete a post as admin.
    When I click on "Delete" "link" in the ".oublog-post:nth-child(2) .oublog-post-links" "css_element"
    And I wait to be redirected
    Then I should see "Are you sure you want to delete this post?"
    And I press "Delete"
    And I wait to be redirected
    And I should see "Deleted by Admin User"

  @javascript @editor_tiny
  Scenario: Check deleting post and email
    Given I create "1" sample posts for blog with id "oublog1"
    And I log in as "teacher1"
    And I am on the "Test oublog basics" "oublog activity" page
    And I should see "Test post 0"

    # Delete a post as teacher1.
    And I click on "Delete" "link" in the ".oublog-post:nth-child(1) .oublog-post-links" "css_element"
    And I wait to be redirected
    And I should see "Select to delete the post"
    When I click on "Delete and email" "button" in the "Confirm" "dialogue"
    And I wait to be redirected
    # Check deleted post details.
    And I should see "Delete and email"
    And I switch to the "Message" TinyMCE editor iframe
    Then I should see "This is a notification to advise you that your Blog post with the following details has been deleted by 'Teacher 1':"
    And I should see "Subject: Test post 0"
    And I should see "Blog: Test oublog basics"
    And I should see "Course: Course 1"
    And I switch to the main frame
    And I press "Send and delete"
    And I wait to be redirected
    And ".oublog-deleted" "css_element" should exist
    And ".oublog-post-deletedby" "css_element" should exist
    And I should see "Deleted by Teacher 1"
    And "Delete" "link" should not exist in the ".oublog-deleted" "css_element"
    And I log out

    # Check posts as student1.
    Given I log in as "student1"
    And I am on the "Test oublog basics" "oublog activity" page
    And I should not see "Test post 0"
    And I log out

    # Check posts as admin.
    Given I log in as "admin"
    And I am on the "Test oublog basics" "oublog activity" page
    And I should see "Test post 0"
    And I should see "Deleted by Teacher 1"

  @javascript
  Scenario: Check deleting comments
    Given I create "1" sample posts for blog with id "oublog1"
    And I create "2" sample comments for blog with id "oublog1"
    And I log in as "teacher1"
    And I am on the "Test oublog basics" "oublog activity" page
    And I follow "2 comments"
    And I should see "Post 0 - Test comment 0"
    And I should see "Post 0 - Test comment 1"

    # Delete a comment.
    When I click on "Delete" "link" in the ".oublog-comment:nth-child(1) .oublog-post-links" "css_element"
    And I wait to be redirected
    Then I should see "Are you sure you want to delete this comment?"
    And I click on "Continue" "button" in the "Confirm" "dialogue"
    And I wait to be redirected
    # Check deleted comment details.
    Then I should see "Post 0 - Test comment 0"
    And I should see "Post 0 - Test comment 1"
    And ".oublog-deleted" "css_element" should exist
    And ".oublog-comment-deletedby" "css_element" should exist
    And I should see "Deleted by Teacher 1"
    And "Delete" "link" should not exist in the ".oublog-deleted" "css_element"
    And I log out

    # Check comments as student1.
    Given I log in as "student1"
    And I am on the "Test oublog basics" "oublog activity" page
    And I follow "1 comment"
    And I should not see "Post 0 - Test comment 0"
    And I should see "Post 0 - Test comment 1"
    And I log out

    # Check comments as admin.
    And I log in as "admin"
    And I am on the "Test oublog basics" "oublog activity" page
    And I follow "1 comment"
    And I should see "Post 0 - Test comment 0"
    And I should see "Post 0 - Test comment 1"
    And I should see "Deleted by Teacher 1"

    # Delete a comment as admin.
    When I click on "Delete" "link" in the ".oublog-comment:nth-child(2) .oublog-post-links" "css_element"
    And I wait to be redirected
    Then I should see "Are you sure you want to delete this comment?"
    And I click on "Continue" "button" in the "Confirm" "dialogue"
    And I wait to be redirected
    And I should see "Deleted by Admin User"

  Scenario: Further standard regression/basic tests - non-js.
    # Check post with comments disabled as Teacher.
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    When I follow "Test oublog basics"
    And I navigate to "Settings" in current page administration
    When I set the following fields to these values:
      | Allow comments | Comments not allowed |
    And I press "Save and display"
    And I press "New blog post"
    Then "#fitem_id_visibility" "css_element" should not exist
    And I set the following fields to these values:
      | Title   | Post01 from teacher   |
      | Message | OUBlog post01 content |
    And I press "Add post"

    # Check related links 'block'.
    Given I follow "Add link"
    And I set the following fields to these values:
      | Title            | Teachers Personal blog test                 |
      | Full Web address | http://127.0.0.1/mod/oublog/view.php?user=3 |
    When I press "id_submitbutton"
    Then "Teachers Personal blog test" "link" should exist
    And "#oublog-links form" "css_element" should not exist
    Given I follow "Add link"
    And I set the following fields to these values:
      | Title            | Teachers Personal blog link2 |
      | Full Web address | http://www.open.ac.uk        |
    When I press "id_submitbutton"
    Then "#oublog-links form" "css_element" should exist
    And "Personal blog link2" "link" should exist

    Given I click on "form[title='Move up'] input[type=image]" "css_element" in the "Teachers Personal blog link2" "list_item"

    Then I should see "Teachers Personal blog link2" in the "//section[@id='oublog-links']//li[1]//a[1]" "xpath_element"
    Given I click on "Delete" "link" in the "Teachers Personal blog link2" "list_item"
    When I press "Continue"
    Then I should not see "Teachers Personal blog link2"
    And "#oublog-links form" "css_element" should not exist
    And I log out

    # Student test comments disabled and related link.
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    When I follow "Test oublog basics"
    Then "Add your comment" "link" should not exist in the ".oublog-post" "css_element"
    And "Teachers Personal blog test" "link" should exist
    Given I follow "Permalink"
    Then "Add your comment" "link" should not exist in the ".oublog-post" "css_element"
    And I log out
    # Test commenting.
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    When I follow "Test oublog basics"
    And I navigate to "Settings" in current page administration
    When I set the following fields to these values:
      | Allow comments | Yes, from logged-in users |
    And I press "Save and display"
    Then I should not see "Add your comment"
    Given I click on "div.oublog-post-links a:nth-child(2)" "css_element"
    And I set the field "Allow comments" to "Yes, from logged-in users"
    And I press "Save changes"
    When I follow "Add your comment"
    And I set the field "Add your comment" to "Teacher comment"
    And I press "Add comment"
    Then I should see "Comments" in the ".oublog-commentstitle" "css_element"
    And I should see "Teacher comment"
    Given I follow "Add your comment"
    And I set the field "Title" to "Title:Teacher comment 2"
    And I set the field "Add your comment" to "Teacher comment 2"
    And I press "Add comment"
    Then I should see "Teacher comment 2"
    And I should see "Title:Teacher comment 2"
    Given I click on ".oublog-post-comments .oublog-comment:nth-child(2) .oublog-post-links a" "css_element"
    When I press "Continue"
    Then ".oublog-comment-deletedby" "css_element" should exist
    Given I follow "Test oublog basics"
    Then I should see "1 comment"
    And I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    When I follow "Test oublog basics"
    Given I follow "1 comment"
    Then I should not see "Teacher comment 2"
    When I follow "Add your comment"
    And I set the field "Title" to "Title:Student comment"
    And I set the field "Add your comment" to "Student comment"
    And I press "Add comment"
    Then I should see "Student comment"
    And I should see "Title:Student comment"
    And "#cid1.oublog-deleted" "css_element" should not exist
    Given I click on ".oublog-comment .oublog-post-links a" "css_element"
    When I press "Cancel"
    Then I should see "Comments" in the ".oublog-commentstitle" "css_element"
    When I follow "Test oublog basics"
    Then I should see "2 comments"
    And I log out

  @javascript
  Scenario: Check date validation for user participation
    Given I log in as "teacher1"
    And I create "1" sample posts for blog with id "oublog1"
    And I create "1" sample comments for blog with id "oublog1"
    And I wait "2" seconds
    And I reload the page

    Given I am on "Course 1" course homepage
    And I am on the "Test oublog basics" "oublog activity" page

    # Go to participation list page.
    And I click on "Participation" "text" in the ".oublog-accordion-view" "css_element"
    And I click on "View all participation" "link" in the ".oublog_statsview_content_participation" "css_element"
    # Start date.
    And I click on "#id_start_enabled" "css_element"
    And I set the field "id_start_day" to "15"
    # Set an invalid end date.
    And I click on "#id_end_enabled" "css_element"
    And I set the field "id_end_day" to "10"
    When I click on "Update" "button"
    Then I should see "Selection end date cannot be earlier than the start date"
    # Set a valid end date.
    And I set the field "id_end_day" to "15"
    When I click on "Update" "button"
    Then I should not see "Selection end date cannot be earlier than the start date"

    Given I am on "Course 1" course homepage
    And I am on the "Test oublog basics" "oublog activity" page

    # Go to participation page.
    Given I press "Participation by user"
    # Start date.
    And I click on "#id_start_enabled" "css_element"
    And I set the field "id_start_day" to "15"
    # Set an invalid end date.
    And I click on "#id_end_enabled" "css_element"
    And I set the field "id_end_day" to "10"
    When I click on "Update" "button"
    Then I should see "Selection end date cannot be earlier than the start date"
    # Set a valid end date.
    And I set the field "id_end_day" to "15"
    When I click on "Update" "button"
    Then I should not see "Selection end date cannot be earlier than the start date"

  Scenario: Check user participation
    # Post as student
    Given I log in as "student1"
    And I am on homepage
    And I am on "Course 1" course homepage
    When I follow "Test oublog basics"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title   | Student1 blog1    |
      | Message | Student1 message1 |
    And I press "Add post"
    And I should not see "Participation by user"
    And I log out
    # Post as teacher
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    When I follow "Test oublog basics"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title   | Teacher1 blog1    |
      | Message | Teacher1 message1 |
    And I press "Add post"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title   | Teacher1 blog2    |
      | Message | Teacher1 message2 |
    And I press "Add post"
    And I should see "Participation by user"
    # Go to participation page
    Given I press "Participation by user"
    Then I should see "Participation - All time"
    # Columns
    And I should see "User" in the "//th[@class='header c1 fullname']" "xpath_element"
    And I should see "Posts" in the "//th[@class='header c2 posts']" "xpath_element"
    And I should see "Comments" in the "//th[@class='header c3 comments']" "xpath_element"
    And I should see "Email address" in the "//th[@class='header c4 email']" "xpath_element"
    # Student 1
    And "//td[@id='mod-oublog-participation_r0_c1']/a[contains(text(), 'Student 1')]" "xpath_element" should exist
    And "//td[@id='mod-oublog-participation_r0_c1']/*/a[contains(text(), 'Details')]" "xpath_element" should exist
    And I should see "1" in the "#mod-oublog-participation_r0_c2" "css_element"
    And I should see "0" in the "#mod-oublog-participation_r0_c3" "css_element"
    And I should see "student1@asd.com" in the "#mod-oublog-participation_r0_c4" "css_element"
    # Student 2
    And "//td[@id='mod-oublog-participation_r1_c1']/a[contains(text(), 'Student 2')]" "xpath_element" should exist
    And I should see "0" in the "#mod-oublog-participation_r1_c2" "css_element"
    And I should see "0" in the "#mod-oublog-participation_r1_c3" "css_element"
    And I should see "student2@asd.com" in the "#mod-oublog-participation_r1_c4" "css_element"
    # Student 3
    And "//td[@id='mod-oublog-participation_r2_c1']/a[contains(text(), 'Student 3')]" "xpath_element" should exist
    And I should see "0" in the "#mod-oublog-participation_r2_c2" "css_element"
    And I should see "0" in the "#mod-oublog-participation_r2_c3" "css_element"
    And I should see "student3@asd.com" in the "#mod-oublog-participation_r2_c4" "css_element"
    # Teacher 1
    And "//td[@id='mod-oublog-participation_r3_c1']/a[contains(text(), 'Teacher 1')]" "xpath_element" should exist
    And "//td[@id='mod-oublog-participation_r3_c1']/*/a[contains(text(), 'Details')]" "xpath_element" should exist
    And I should see "2" in the "#mod-oublog-participation_r3_c2" "css_element"
    And I should see "0" in the "#mod-oublog-participation_r3_c3" "css_element"
    And I should see "teacher1@asd.com" in the "#mod-oublog-participation_r3_c4" "css_element"

  Scenario: Check back to main page link
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    When I follow "Test oublog basics"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title   | Teacher1 blog    |
      | Message | Teacher1 message |
    And I press "Add post"
    # Go to viewpost page and back to the main blog page
    When I follow "Permalink"
    Then "#oublog-arrowback" "css_element" should exist
    And I should see "Test oublog basics" in the "#oublog-arrowback" "css_element"
    Given I click on "#oublog-arrowback a" "css_element"
    Then "#addpostbutton" "css_element" should exist
    # Go to participation page and back to the main blog page
    Given I press "Participation by user"
    Then I should see "Participation - All time"
    And "#oublog-arrowback" "css_element" should exist
    And I should see "Test oublog basics" in the "#oublog-arrowback" "css_element"
    Given I click on "#oublog-arrowback a" "css_element"
    Then "#addpostbutton" "css_element" should exist
    # Go to userparticipatiom page and back to the main blog page
    When I expand "BLOG USAGE" node
    And I expand "My participation summary" node
    And I follow "View all participation"
    Then I should see "Participation - All time"
    And "#oublog-arrowback" "css_element" should exist
    And I should see "Test oublog basics" in the "#oublog-arrowback" "css_element"
    Given I click on "#oublog-arrowback a" "css_element"
    Then "#addpostbutton" "css_element" should exist
    # Go to participatiomlist page and back to the main blog page
    When I expand "Participation" node
    And I follow "View all participation"
    Then I should see "Participation - All time"
    And "#oublog-arrowback" "css_element" should exist
    And I should see "Test oublog basics" in the "#oublog-arrowback" "css_element"
    Given I click on "#oublog-arrowback a" "css_element"
    Then "#addpostbutton" "css_element" should exist
    And I log out

  Scenario: Check info block doesn't appear if there's no summary
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    When I follow "Test oublog basics"
    And "#oublog_info_block" "css_element" should exist
    And I follow "Settings"
    When I set the following fields to these values:
      | Intro |  |
    And I press "Save and display"
    And "#oublog_info_block" "css_element" should not exist

  Scenario: Check blog posts with setting Add tags by default
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    When I follow "Test oublog with default tags"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title   | Teacher1 blog                     |
      | Message | Teacher1 post with default tags 1 |
    And I press "Add post"
    Then I should see "tag1(1) tag2(1) tag3(1)"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title   | Teacher1 blog 2                   |
      | Message | Teacher1 post with default tags 2 |
    And I press "Add post"
    Then I should see "tag1(2) tag2(2) tag3(2)"
    And I am on "Course 1" course homepage
    # Test blog with no default tags
    When I follow "Test oublog with no default tags"
    And I press "New blog post"
    Then I should not see "tag1,tag2,tag3"

  Scenario: Check group level access when no groups
    Given I log in as "teacher1"
    And the following "activities" exist:
      | activity | name | course | section | groupmode |
      | oublog   | B.SG | C1     | 1       | 1         |
      | oublog   | B.VG | C1     | 1       | 2         |
    And I am on homepage
    And I am on "Course 1" course homepage
    # Editing teacher adds posts to all the blogs.
    Given I am on "Course 1" course homepage
    And I follow "B.SG"
    And I set the field "Separate groups" to "G1"
    And I press "Go"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P1 |
      | Message | P1 |
    And I press "Add post"
    Given I am on "Course 1" course homepage
    And I follow "B.VG"
    And I set the field "Visible groups" to "G1"
    And I press "Go"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title   | P2 |
      | Message | P2 |
    And I press "Add post"
    Given I log out
    And I log in as "student3"
    And I am on "Course 1" course homepage
    When I follow "B.SG"
    Then I should see "There are no visible posts in this blog"
    Given I am on "Course 1" course homepage
    When I follow "B.VG"
    Then I should see "P2"

  Scenario: Check filter label in tag block
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Test oublog basics"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title   | Teacher1 blog 1 |
      | Message | Teacher1 post   |
      | Tags    | tag1            |
    And I press "Add post"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title   | Teacher1 blog 2 |
      | Message | Teacher1 post 2 |
      | Tags    | tag2            |
    When I press "Add post"
    Then I should see "Teacher1 blog 1" in the "div#oublog-posts" "css_element"
    And I should see "Teacher1 blog 2" in the "div#oublog-posts" "css_element"
    When I follow "tag1"
    Then I should see "tag1" in the ".oublog-filter-tagname" "css_element"
    And I should not see "tag2" in the ".oublog-filter-tagname" "css_element"
    And I should see "Teacher1 blog 1" in the "div.oublog-post-top-details h2.oublog-title" "css_element"
    And I should not see "Teacher1 blog 2" in the "div.oublog-post-top-details h2.oublog-title" "css_element"
    When I follow "tag2"
    Then I should see "tag2" in the ".oublog-filter-tagname" "css_element"
    And I should see "Teacher1 blog 2" in the "div.oublog-post-top-details h2.oublog-title" "css_element"
    When I click on "#close-filter-icon" "css_element"
    Then I should see "Teacher1 blog 1" in the "div#oublog-posts" "css_element"
    And I should see "Teacher1 blog 2" in the "div#oublog-posts" "css_element"

  Scenario: Check view count is reset on restore
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test oublog basics"
    Then I should see "Total visits to this blog: 1"
    And I log out
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test oublog basics"
    Then I should see "Total visits to this blog: 2"
    Given I am on "Course 1" course homepage
    And I turn editing mode on
    And I duplicate "Test oublog basics" activity editing the new copy with:
      | Blog name | Test oublog basics - duplicate |
    And I follow "Test oublog basics - duplicate"
    Then I should see "Total visits to this blog: 1"


  @javascript
  Scenario: Check oublog completion feature in web.
    Given the following "courses" exist:
      | fullname | shortname | format      | enablecompletion |
      | Course 2 | C2        | oustudyplan | 1                |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C2     | student |
    And the following "activities" exist:
      | activity | name                   | introduction            | course | idnumber | completion | completionview | completionpostsenabled | completionposts |
      | oublog   | Test oublog completion | Test oublog description | C2     | oublog2  | 2          | 1              | 1                      | 1               |
    And I log in as "student1"
    And I am on "Course 2" course homepage
    Then I should see "0%"
    And I should not see "100%"
    And I follow "Test oublog completion"
    And I am on "Course 2" course homepage
    # Check activity is not completed because we haven't see the second session.
    Then I should see "0%"
    And I should not see "100%"
    And I follow "Test oublog completion"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title   | Student1 blog |
      | Message | Student1 post |
      | Tags    | tag1          |
    And I press "Add post"
    When I am on "Course 2" course homepage
    Then I should see "100%"

  @javascript
  Scenario: Check tag selectors.
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    When I follow "Test oublog with default tags"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title                      | Teacher1 blog                     |
      | Message                    | Teacher1 post with default tags 1 |
      | Tags (separated by commas) |                                   |
    Then ".autocomplete-dropdown-wrapper .autocomplete-dropdown" "css_element" should be visible
    And I should see "tag1" in the ".autocomplete-dropdown-wrapper ul.autocomplete-dropdown li:nth-child(1)" "css_element"
    And I should see "0 posts" in the ".autocomplete-dropdown-wrapper ul.autocomplete-dropdown li:nth-child(1)" "css_element"
    And I should see "tag2" in the ".autocomplete-dropdown-wrapper ul.autocomplete-dropdown li:nth-child(2)" "css_element"
    And I should see "0 posts" in the ".autocomplete-dropdown-wrapper ul.autocomplete-dropdown li:nth-child(2)" "css_element"
    And I should see "tag3" in the ".autocomplete-dropdown-wrapper ul.autocomplete-dropdown li:nth-child(3)" "css_element"
    And I should see "0 posts" in the ".autocomplete-dropdown-wrapper ul.autocomplete-dropdown li:nth-child(3)" "css_element"
    And I click on "tag1" "text" in the ".autocomplete-dropdown-wrapper ul.autocomplete-dropdown li:nth-child(1)" "css_element"
    And I should not see "tag1" in the ".autocomplete-dropdown-wrapper ul.autocomplete-dropdown li:nth-child(1)" "css_element"
    And I should see "tag2" in the ".autocomplete-dropdown-wrapper ul.autocomplete-dropdown li:nth-child(1)" "css_element"
    And I should see "tag3" in the ".autocomplete-dropdown-wrapper ul.autocomplete-dropdown li:nth-child(2)" "css_element"
    And I press "Add post"
    Then I should see "tag1(1)"

  @javascript
  Scenario: Check session on saving a post edit
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Test oublog with default tags"
    And I press "New blog post"
    And I set the following fields to these values:
      | Title                      | Teacher1 blog                     |
      | Message                    | Teacher1 post with default tags 1 |
      | Tags (separated by commas) |                                   |
    And I clear the session cookie in oublog
    When I press "Add post"
    Then I should see "[Course or activity not accessible. (You are not logged in)]" in the "Post cannot be saved" "dialogue"

  @javascript
  Scenario: Check the toggle comment warning when editing a post
    Given the following "activities" exist:
      | activity | name                      | introduction            | course | idnumber | allowcomments | maxvisibility |
      | oublog   | Test oublog allow comment | Test oublog description | C1     | oublog10 | 2             | 300           |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test oublog allow comment"
    And I press "New blog post"
    And I should not see "This is necessary in order to prevent spam."
    When I set the field "allowcomments" to "2"
    Then I should see "This is necessary in order to prevent spam."
