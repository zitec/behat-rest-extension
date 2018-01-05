@noAuth
Feature: Test basic api functions
  Scenario: Test basic GET
    Given I set the request method to GET
    When I request "/users"
    And the response status code should be 200
    And the response is JSON

  Scenario: Test GET with dataset and check response
    Given I set the request method to GET
    And I load data from file "get_users"
    When I request "/users" with dataset "user_1"
    And the response status code should be 200
    And the response is JSON
    And the response match the expected response

  Scenario: Test simple collection structure
    Given I set the request method to GET
    And I load data from file "get_users"
    When I request "/users"
    And the response status code should be 200
    And the response is JSON
    And the response match the expected structure from "all_users" dataset

    @wip
  Scenario: Test GET with headers added
    Given I set the request method to GET
    And I load data from file "get_users"
    And I add the following headers:
    | Authorization | 1234 |
    When I request "/new"
    And the response status code should be 200
    And the response is JSON
    And the response match the expected structure from "contacts" dataset







