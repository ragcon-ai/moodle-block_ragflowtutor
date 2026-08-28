@block @block_ragflowtutor @javascript
Feature: RAGflow Tutor block
  In order to offer a course AI tutor
  As a site administrator
  I need to add the RAGflow Tutor block and be prompted to configure a knowledge base first

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |

  Scenario: The block prompts a site admin to configure a knowledge base
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    When I add the "RAGflow Tutor" block
    Then I should see "RAGflow Tutor is not configured yet"
    And I should see "in this block's settings"
    And I should not see "Ask a site administrator"

  Scenario: A trainer without the KB capability is directed to a site administrator
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I add the "RAGflow Tutor" block
    Then I should see "Ask a site administrator to choose a knowledge base"
    And I should not see "in this block's settings"

  Scenario: Editing a configured block locks the knowledge base / assistant and the document source
    Given a configured RAGflow Tutor block exists in course "C1"
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    When I configure the "RAGflow Tutor" block
    Then I should see "cannot be changed afterwards"
    And I should not see "Create new knowledge base"
