@local @local_syllabusworkflow
Feature: Syllabus lifecycle in a course
  An institution uses different roles to prepare, review and publish syllabi.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Academic course | ACADEMIC1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | syteacher | Teacher | Example | syteacher@example.test |
      | systudent | Student | Example | systudent@example.test |
    And the following "course enrolments" exist:
      | user | course | role |
      | syteacher | ACADEMIC1 | editingteacher |
      | systudent | ACADEMIC1 | student |

  Scenario: Teacher creates a draft using a structured editor
    Given I log in as "syteacher"
    And I am on "Academic course" course homepage
    When I follow "Syllabus workflow"
    And I follow "Create syllabus"
    And I set the following fields to these values:
      | Discipline name | Example topic |
      | Syllabus | Example syllabus |
      | General objectives | Example goals |
      | Assessment criteria | Example assessment |
    And I press "Save draft"
    Then I should see "Draft"

  Scenario: Teacher formally submits a syllabus to a reviewer
    Given a syllabus of "syteacher" for course "ACADEMIC1" exists in state "draft"
    And I log in as "syteacher"
    When I open the syllabus for course "ACADEMIC1"
    And I follow "Submit for review"
    And I press "Submit for review"
    Then I should see "Submitted"

  Scenario: Coordinator receives a submission and starts review
    Given a syllabus of "syteacher" for course "ACADEMIC1" exists in state "submitted"
    And I log in as "admin"
    When I open the syllabus for course "ACADEMIC1"
    And I follow "Begin review"
    And I press "Begin review"
    Then I should see "Under review"

  Scenario: Coordinator requests changes with a justification
    Given a syllabus of "syteacher" for course "ACADEMIC1" exists in state "under_review"
    And I log in as "admin"
    When I open the syllabus for course "ACADEMIC1"
    And I follow "Request changes"
    And I set the following fields to these values:
      | Comment or justification | Clarify assessment instruments |
    And I press "Request changes"
    Then I should see "Changes requested"

  Scenario: Teacher creates another revision after requested changes
    Given a syllabus of "syteacher" for course "ACADEMIC1" exists in state "changes_requested"
    And I log in as "syteacher"
    When I open the syllabus for course "ACADEMIC1"
    And I follow "Create new revision"
    And I press "Create new revision"
    Then I should see "Draft"

  Scenario: Coordinator approves a reviewed syllabus
    Given a syllabus of "syteacher" for course "ACADEMIC1" exists in state "under_review"
    And I log in as "admin"
    When I open the syllabus for course "ACADEMIC1"
    And I follow "Approve"
    And I press "Approve"
    Then I should see "Approved"

  Scenario: Publication makes the official syllabus visible to enrolled students
    Given a syllabus of "syteacher" for course "ACADEMIC1" exists in state "approved"
    And I log in as "admin"
    And I open the syllabus for course "ACADEMIC1"
    And I follow "Publish"
    And I press "Publish"
    And I log out
    And I log in as "systudent"
    When I open the syllabus for course "ACADEMIC1"
    Then I should see "Published"
    And I should see "Sample syllabus content"
