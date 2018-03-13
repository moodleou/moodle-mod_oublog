@ou @ou_vle @mod @mod_oublog @oublog_personal
Feature: Test Post and Comment on Personal OUBlog
  In order to engage with OUBblog personal posts and comments
  As an external user
  I need to be able to see OUBblog personal post entries
  # Note this test will only pass on OU systems as using an OU custom step.

  @javascript
  Scenario: Admin edits the blog options
    Given I log in as "admin"
    And I am using the OU theme
    And I am on site homepage
    And I follow "Personal Blogs"
    And I follow "Blog options"
    Then I should see "Blog name"
    And I should see "Summary"
    And I set the following fields to these values:
      | Blog name| Admin User's blog edited |
      | Summary | SC01 edited the Admin User's summary block |
    And I press "Save changes"
    And I should see "Admin User's blog edited"
    And I should see "SC01 edited the Admin User's summary block"

    # Admin adds posts.
    Given I press "New blog post"
    And I should see "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post01 |
      | Message | Admin Persblog post01 content |
    And I press "Add post"
    Then I should see "Personal OUBlog post01"
    And I should see "Admin Persblog post01 content"
    And I should see "Visible only to the blog owner (private)"
    Given I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post02 |
      | Message | Admin Persblog post02 content |
      | Who can read this | Visible to everyone who is logged in to the system |
    When I press "Add post"
    Then I should see "Visible to everyone who is logged in to the system"

    # Admin adds multiple comments.
    Given I follow "Add your comment"
    When I set the field "Add your comment" to "$My own >nasty< \"string\"!"
    And I press "Add comment"
    Then I should see "Comments" in the ".oublog-commentstitle" "css_element"
    And I follow "Add your comment"
    And I set the field "Add your comment" to "Another $Nasty <string?>"
    And I press "Add comment"
    And I follow "Admin User's blog edited"
    Then "2 comments" "link" should exist
    And I log out (in the OU theme)

    # User not logged in tests visibility of Admin Users personal post.
    Given I visit the personal blog for "admin"
    Then "You are not logged in" "text" should exist
    And I should not see "Personal OUBlog post"
    And I should not see "Admin User's blog"

    # Check logged-in visibility.
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |

    Given I log in as "student1"
    And I visit the personal blog for "admin"
    Then I should see "Personal OUBlog post02"
    When I click on "Permalink" "link" in the ".oublog-post .oublog-post-links" "css_element"
    Then I should see "Personal OUBlog post02"
    And I should see "Admin User's blog"
    Given I log out (in the OU theme)

    # Admin changes post to world visibility.
    Given I log in as "admin"
    And I am on site homepage
    And I follow "Personal Blogs"
    When I click on "Edit" "link" in the ".oublog-post .oublog-post-links" "css_element"
    And I set the following fields to these values:
      | Title | Personal OUBlog post01 WorldVis |
      | Message | Admin Persblog post01 content WorldVis |
      | Tags | edap01 |
      | Who can read this | Visible to anyone in the world |
      | Allow comments | Yes, from everybody (even if not logged in) |
    And I press "Save changes"
    And I wait to be redirected
    Then I should see "Personal OUBlog post01 WorldVis"
    And I should see "Admin Persblog post01 content WorldVis"
    And I should see "edap01"
    And I should see "Total visits to this blog: 3"
    And I log out (in the OU theme)

    # User not logged in tests Admin Users post and comments.
    And I wait to be redirected
    Then "You are not logged in" "text" should exist
    And I visit the personal blog for "admin"
    Then I should see "Admin User's blog edited"
    And I should see "log in for full access"
    And I should see "Total visits to this blog: 4"
    Given I follow "2 comments"
    Then I should see "Personal OUBlog post01 WorldVis"
    And I should see "Admin Persblog post01 content WorldVis"
    And I should see "edap01"
    And I should see "Comments" in the ".oublog-commentstitle" "css_element"
    And I should see "$My own >nasty< \"string\"!"
    And I should see "Another $Nasty"
    Given I follow "Add your comment"
    And I set the following fields to these values:
      | Your name | NoLog |
      | Title | NoLogTitle |
      | Add your comment | NoLogComent |
      | Confirmation | yes |
    When I press "Add comment"
    Then I should see "Thank you for adding your comment"
    Given I press "Continue"
    Then I should see "Comments"

    # Admin confirm moderated comment.
    Given I log in as "admin"
    And I am on site homepage
    And I follow "Personal Blogs"
    And I follow "2 comments, 1 awaiting approval"
    When I press "Approve this comment"
    Then I should see "approved by Admin User"
    And I should see "NoLogTitle"
    And I should see "NoLogComent"

    # Pagination on blog view + allposts.
    Given I follow "Admin User's blog edited"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post02 |
      | Message | Admin Persblog post02 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post03 |
      | Message | Admin Persblog post03 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post04 |
      | Message | Admin Persblog post04 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post05 |
      | Message | Admin Persblog post05 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post06 |
      | Message | Admin Persblog post06 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post07 |
      | Message | Admin Persblog post07 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post08 |
      | Message | Admin Persblog post08 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post09 |
      | Message | Admin Persblog post09 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post10 |
      | Message | Admin Persblog post10 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post11 |
      | Message | Admin Persblog post11 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post12 |
      | Message | Admin Persblog post12 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post13 |
      | Message | Admin Persblog post13 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post14 |
      | Message | Admin Persblog post14 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post15 |
      | Message | Admin Persblog post15 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post16 |
      | Message | Admin Persblog post16 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post17 |
      | Message | Admin Persblog post17 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post18 |
      | Message | Admin Persblog post18 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post19 |
      | Message | Admin Persblog post19 content |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    Then ".oublog-paging" "css_element" should not exist
    Given I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post20 |
      | Message | Admin Persblog post20 content |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title             | Personal OUBlog post21         |
      | Message           | Admin Persblog post21 content  |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title             | Personal OUBlog post22         |
      | Message           | Admin Persblog post22 content  |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title             | Personal OUBlog post23         |
      | Message           | Admin Persblog post23 content  |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title             | Personal OUBlog post24         |
      | Message           | Admin Persblog post24 content  |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title             | Personal OUBlog post25         |
      | Message           | Admin Persblog post25 content  |
      | Who can read this | Visible to anyone in the world |
    When I press "Add post"
    Then ".oublog-paging" "css_element" should exist
    And I should see "Next" in the ".oublog-paging" "css_element"
    And I should not see "Previous" in the ".oublog-paging" "css_element"
    And I click on "2" "link" in the ".oublog-paging" "css_element"
    Then I should see "Personal OUBlog post01"
    Given I follow "View site entries"
    Then I should not see "Personal OUBlog post20"
    And I should see "Personal OUBlog post19"
    Given I follow "Admin User's blog edited"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title             | Personal OUBlog post26         |
      | Message           | Admin Persblog post26 content  |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    When I press "New blog post"
    And I set the following fields to these values:
      | Title                      | Personal OUBlog post27         |
      | Message                    | Admin Persblog post27 content  |
      | Who can read this          | Visible to anyone in the world |
      | Tags (separated by commas) | Taggy1                         |
    And I press "Add post"
    And I follow "Next"
    And I should see "Previous" in the ".oublog-paging" "css_element"
    And I should not see "Next" in the ".oublog-paging" "css_element"
    Then I should see "Personal OUBlog post02" in the "div.oublog-post-top-details h2.oublog-title" "css_element"
    And I should not see "Personal OUBlog post27" in the "div.oublog-post-top-details h2.oublog-title" "css_element"

    # 'Edit' post01 ie 3rd post on the second page
    And I click on "Edit" "link" in the ".oublog-post:nth-child(3) .oublog-post-links" "css_element"
    And I wait to be redirected
    And I set the following fields to these values:
      | Title | Personal OUBlog post01 edited |
      | Message | Admin Persblog post01 content edited for return url test|
    And I press "Save changes"
    # Confirm return to correct page after edit
    And I should see "Previous" in the ".oublog-paging" "css_element"
    And I should not see "Next" in the ".oublog-paging" "css_element"
    And I should see "Personal OUBlog post02" in the "div.oublog-post-top-details h2.oublog-title" "css_element"
    And I should not see "Personal OUBlog post22" in the "div.oublog-post-top-details h2.oublog-title" "css_element"
    # 'Delete' post01, 3rd post on second page
    And I click on "Delete" "link" in the ".oublog-post:nth-child(3) .oublog-post-links" "css_element"
    And I wait to be redirected
    Then I should see "Are you sure you want to delete this post?"
    Given I press "Cancel"
    And I wait to be redirected
    # Confirm return to correct page after cancel
    And I should see "Previous" in the ".oublog-paging" "css_element"
    And I should see "Personal OUBlog post02" in the "div.oublog-post-top-details h2.oublog-title" "css_element"
    And I should not see "Personal OUBlog post22" in the "div.oublog-post-top-details h2.oublog-title" "css_element"
    And I click on "Delete" "link" in the ".oublog-post:nth-child(3) .oublog-post-links" "css_element"
    And I press "Delete"
    And I wait to be redirected
    # Confirm return to correct page after delete
    And I should see "Previous" in the ".oublog-paging" "css_element"
    And I should see "Personal OUBlog post02" in the "div.oublog-post-top-details h2.oublog-title" "css_element"
    And I should see "Personal OUBlog post01 edited" in the "div.oublog-post.oublog-deleted div.oublog-post-top-details h2.oublog-title" "css_element"
    And ".oublog-deleted" "css_element" should exist
    And ".oublog-post-deletedby" "css_element" should exist
    And I should see "Deleted by"
    And I follow "View site entries"
    Then I should see "Personal OUBlog post22"
    Then I should not see "Personal OUBlog post01 WorldVis"
    And I should see "Next" in the ".oublog-paging" "css_element"
    And I should not see "Previous" in the ".oublog-paging" "css_element"
    Given I follow "taggy1"
    Then I should see "Personal OUBlog post27"
    And I should not see "Personal OUBlog post26"
    And I should not see "Next" in the ".oublog-paging" "css_element"
    # 'Edit' the "taggy" post27
    And I click on "Edit" "link" in the ".oublog-post-links" "css_element"
    And I wait to be redirected
    And I set the following fields to these values:
      | Title | Personal OUBlog post27 edited|
      | Message | Admin Persblog post27 content edited for return url test|
    And I press "Save changes"
    # Confirm return to correct page after "taggy" edit
    Then I should see "Personal OUBlog post27 edited" in the "div.oublog-post-top-details h2.oublog-title" "css_element"
    And I should not see "Personal OUBlog post26" in the "div.oublog-post-top-details h2.oublog-title" "css_element"
    And I should not see "Next" in the ".oublog-paging" "css_element"
    # Test paging when post per page is 50.
    And I am on site homepage
    And I follow "Personal Blogs"
    And I click on "Edit settings" "link"
    When I set the following fields to these values:
      | postperpage | 50 |
    And I press "Save and display"
    Then ".oublog-paging" "css_element" should not exist
    Then I should see "Personal OUBlog post27"

  # New scenario tests the Socialmedia widgets availability
  Scenario: Admin tests the blog tweet facility
    Given I log in as "admin"
    And I am on site homepage
    And I follow "Personal Blogs"

    # Admin adds a Private post Tweet not available
    Given I press "New blog post"
    And I should see "New blog post"
    And I set the following fields to these values:
      | Title | SC02 Personal OUBlog post01 |
      | Message | SC02 Admin Persblog post01 content Private |
      | Tags | SC02edap01 |
      | Who can read this | Visible only to the blog owner (private) |
    And I press "Add post"
    Then I should see "SC02 Personal OUBlog post01"
    And I should see "SC02 Admin Persblog post01 content Private"
    And I should see "sc02edap01"
    And I should not see "Share this post"
    And I should not see "Tweet"
    And I should not see "Share"

    # Admin adds a WorldVis post and Socialmedia widgets are available
    Given I press "New blog post"
    And I should see "New blog post"
    And I set the following fields to these values:
      | Title | SC02 Personal OUBlog post02 |
      | Message | SC02 Admin Persblog post02 content WorldVis |
      | Tags | SC02edap02 |
      | Who can read this | Visible to anyone in the world |
    And I press "Add post"
    Then I should see "SC02 Personal OUBlog post02"
    And I should see "SC02 Admin Persblog post02 content WorldVis"
    And I should see "sc02edap02"
    # Changed below to make more specific.
    And I should see "Share post" in the "div.oublog-post-share-title" "css_element"
    And I should see "Tweet" in the "div.share-button:nth-child(1)" "css_element"
    And "div.share-button:nth-child(2) div.fb-share-button" "css_element" should exist
    And "div.share-button:nth-child(3)" "css_element" should exist
    # Admin opens viewpost page and Socialmedia widgets are available
    And I click on "Permalink" "link" in the ".oublog-post-links" "css_element"
    And I wait to be redirected
    Then I should see "SC02 Personal OUBlog post02"
    And I should see "SC02 Admin Persblog post02 content WorldVis"
    And I should see "sc02edap02"
    And I should see "Share post" in the "div.oublog-post-share-title" "css_element"
    And I should see "Tweet" in the "div.share-button:nth-child(1)" "css_element"
    And "div.share-button:nth-child(2) div.fb-share-button" "css_element" should exist
    And "div.share-button:nth-child(3)" "css_element" should exist
    And I should not see "SC02 Personal OUBlog post01"
    And I should not see "SC02 Admin Persblog post01 content Private"

  Scenario: Admin follows the link to the main page and back
    Given I log in as "admin"
    And I am on site homepage
    And I follow "Personal Blogs"
    And I follow "Blog options"
    Then I should see "Blog name"
    And I should see "Summary"
    And I set the following fields to these values:
      | Blog name| Admin User's blog follow the link to the main page |
    When I press "Save changes"
    Then I should see "Admin User's blog follow the link to the main page"
    Given I press "New blog post"
    And I set the following fields to these values:
      | Title | Personal OUBlog post01 |
      | Message | Admin Persblog post01 content |
    When I press "Add post"
    And I follow "Permalink"
    Then "#oublog-arrowback" "css_element" should exist
    And I should see "Admin User's blog follow the link to the main page" in the "#oublog-arrowback" "css_element"
    Given I click on "#oublog-arrowback a" "css_element"
    Then "#addpostbutton" "css_element" should exist
