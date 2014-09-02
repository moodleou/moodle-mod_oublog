@ou @ou_vle @mod @mod_oublog @oublog_personal
Feature: Test Post and Comment on Personal OUBlog
  In order to engage with OUBblog personal posts and comments
  As an external user
  I need to be able to see OUBblog personal post entries

  Scenario: Admin edits the blog options
    Given I log in as "admin"
    And I am on homepage
    And I follow "Personal Blogs"
    And I follow "Blog options"
    Then I should see "Blog name"
    And I should see "Summary"
    And I set the following fields to these values:
      | Blog name| Admin User's blog edited|
      | Summary | SC01 edited the Admin User's summary block |
    And I press "Save changes"
    And I should see "Admin User's blog edited"
    And I should see "SC01 edited the Admin User's summary block"

    # Admin adds a post
    Given I press "New blog post"
    And I should see "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post01 |
      | Message | Admin Persblog post01 content |
    And I press "Add post"
    Then I should see "Personal OUBlog post01"
    And I should see "Admin Persblog post01 content"

    # Admin adds multiple comments
    Given I follow "Add your comment"
    When I set the field "Add your comment" to "$My own >nasty< \"string\"!"
    And I press "Add comment"
    Then I should see "Comments" in the ".oublog-commentstitle" "css_element"
    And I follow "Add your comment"
    And I set the field "Add your comment" to "Another $Nasty <string?>"
    And I press "Add comment"
    And I follow "Admin User's blog edited"
    Then "2 comments" "link" should exist
    And I log out

    # User not logged in tests visibility of Admin Users personal post
    And I type in the relative URL "mod/oublog/view.php?user=2"
    Then "You are not logged in" "text" should exist

    # Admin changes post to world visibility
    Given I log in as "admin"
    And I follow "Personal Blogs"
    And I follow "Edit"
    And I set the following fields to these values:
      | Title | Personal OUBlog post01 WorldVis |
      | Message | Admin Persblog post01 content WorldVis |
      | Tags | edap01 |
      | Who can read this | Visible to anyone in the world |
    And I press "Save changes"
    And I wait to be redirected
    Then I should see "Personal OUBlog post01 WorldVis"
    And I should see "Admin Persblog post01 content WorldVis"
    And I should see "edap01"
    And I log out

    # User not logged in tests Admin Users post and comment visibility
    And I wait to be redirected
    Then "You are not logged in" "text" should exist
    And I type in the relative URL "mod/oublog/view.php?user=2"
    Then I should see "Admin User's blog edited"
    Given I follow "2 comments"
    And I should see "Personal OUBlog post01 WorldVis"
    And I should see "Admin Persblog post01 content WorldVis"
    And I should see "edap01"
    And I should see "Comments" in the ".oublog-commentstitle" "css_element"
    And I should see "$My own >nasty< \"string\"!"
    And I should see "Another $Nasty"
