@ou @ou_vle @mod @mod_oublog
Feature: Test the stats update feature, ensure update is working.
  Background:
    Given the following "courses" exist:
    | fullname | shortname |
    | Course 1 | C1        |
    And the following "activities" exist:
    | activity | name  | intro             | course | statblockon | allowcomments |
    | oublog   | Ablog | A blog is a blog. | C1     | 1           | 1             |
    And the following "users" exist:
    | username  | firstname | lastname | email            |
    | student1  | Student   | one      | student@asd.com  |
    | student2  | Student   | two      | student2@asd.com |
    And the following "course enrolments" exist:
    | user     | course | role    |
    | student1 | C1     | student |
    | student2 | C1     | student |

    @javascript
    Scenario: Stats on standard blog.
      Given the following "mod_oublog > posts" exist:
        | blog  | title | user     | message | allowcomments | time          |
        | Ablog | post1 | student1 | postms1 | 1             |                     |
        | Ablog | post2 | student1 | postms2 | 1             |                     |
        | Ablog | post3 | student1 | postms3 | 1             |                     |
        | Ablog | post4 | student2 | postms4 | 1             | 2024-01-01 00:00:00 |
      And the following "mod_oublog > comments" exist:
        | blog  | post  | user     | title | messagecomment | time          |
        | Ablog | post1 | student2 | ctil1 | comment1       |                     |
        | Ablog | post2 | student2 | ctil2 | comment2       |                     |
        | Ablog | post3 | student2 | ctil3 | comment3       |                     |
        | Ablog | post4 | student1 | ctil4 | comment4       | 2024-01-01 00:01:00 |
        | Ablog | post4 | student2 | ctil5 | comment5       | 2024-01-01 00:02:00 |
        | Ablog | post4 | student1 | ctil6 | comment6       | 2024-01-01 00:03:00 |
      When I am on the "C1" Course page logged in as student1
      And I follow "Ablog"
      Then "#oublog-discover" "css_element" should exist
      And I should see "post1" in the "#oublog-discover .oublog_statsview_content_myparticipation" "css_element"
      And I should see "ctil4" in the "#oublog-discover .oublog_statsview_content_myparticipation" "css_element"
      And I should not see "post4" in the "#oublog-discover .oublog_statsview_content_myparticipation" "css_element"
      And I should not see "ctil1" in the "#oublog-discover .oublog_statsview_content_myparticipation" "css_element"
      Given I click on "#oublog-discover li.oublog-accordion-closed:last-child" "css_element"
      Then I should see "Past month" in the "#oublog-discover .oublog_statsview_content_commentpoststats" "css_element"
      And I should see "post1" in the "#oublog-discover .oublog_statsview_content_commentpoststats" "css_element"
      And I should not see "post4" in the "#oublog-discover .oublog_statsview_content_commentpoststats" "css_element"
      Given I set the field "id_timefilter_commentpoststats" to "All time"
      When I press "Update"
      Then I should see "post4" in the "#oublog-discover .oublog_statsview_content_commentpoststats" "css_element"
      And I should see "2 comments" in the "#oublog-discover .oublog_statsview_content_commentpoststats" "css_element"
      And I should see "1 January 2024" in the "#oublog-discover .oublog_statsview_content_commentpoststats" "css_element"

  @javascript
  Scenario: Stats on personal blog.
    Given the following "mod_oublog > posts" exist:
      | blog           | title | user     | message | allowcomments | time                | visibility |
      | Personal Blogs | post1 | student1 | postms1 | 1             |                     | 200        |
      | Personal Blogs | post2 | student1 | postms2 | 1             |                     | 200        |
      | Personal Blogs | post3 | student1 | postms3 | 1             | 2024-01-01 00:00:00 | 200        |
      | Personal Blogs | post4 | student2 | postms4 | 1             | 2024-01-01 00:00:00 | 200        |
    And the following "mod_oublog > comments" exist:
      | blog           | post  | user     | title | messagecomment | time                |
      | Personal Blogs | post1 | student2 | ctil1 | comment1       |                     |
      | Personal Blogs | post2 | student2 | ctil2 | comment2       |                     |
      | Personal Blogs | post3 | student2 | ctil3 | comment3       |                     |
      | Personal Blogs | post4 | student1 | ctil4 | comment4       | 2024-01-01 00:01:00 |
      | Personal Blogs | post4 | student2 | ctil5 | comment5       | 2024-01-01 00:02:00 |
      | Personal Blogs | post4 | student1 | ctil6 | comment6       | 2024-01-01 00:03:00 |
    And I log in as "admin"
    And I am on site homepage
    And I visit the personal blog for "admin"
    And I follow "Settings"
    And I expand all fieldsets
    And I set the field "id_statblockon" to "1"
    And I press "Save and display"
    And I visit the personal blog for "student1"
    Then I should see "post1"
    Given I click on "#oublog-discover li.oublog-accordion-closed:last-child" "css_element"
    Then I should see "Past month" in the "#oublog-discover .oublog_statsview_content_commentstats" "css_element"
    And I should see "Student one's blog" in the "#oublog-discover .oublog_statsview_content_commentstats" "css_element"
    And I should not see "Student two" in the "#oublog-discover .oublog_statsview_content_commentstats" "css_element"
    Given I set the field "id_timefilter_commentstats" to "All time"
    When I click on "#id_submitbutton_commentstats" "css_element"
    Then I should see "Student two's blog" in the "#oublog-discover .oublog_statsview_content_commentstats" "css_element"
    And I should see "2 comments" in the "#oublog-discover .oublog_statsview_content_commentstats" "css_element"
