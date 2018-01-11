Feature: Test RestContext custom steps

  @noAuth
  Scenario: Test basic GET
    Given I set the request method to GET
    When I request "/users"
    And the response status code should be 200
    And the response is JSON

  @noAuth
  Scenario: Test GET with data set and check response
    Given I set the request method to GET
    And I load data from file "users"
    When I request "/users" with dataset "user_1"
    And the response status code should be 200
    And the response is JSON
    And the response match the expected response

  @noAuth
  Scenario Outline: Test headers are isolated
    Given I set the request method to GET
    And I load data from file "users"
    And I add the following headers:
      | <key> | <value> |
    When I request "/addresses"
    And the response status code should be <code>
    And the response is JSON

    Examples:
      | key    | value    | code |
      | Header | myheader | 200  |
      |        |          | 401  |

  @noAuth
  Scenario: Test simple collection structure
    Given I set the request method to GET
    And I load data from file "users"
    When I request "/users"
    And the response status code should be 200
    And the response is JSON
    And the response match the expected structure from "all_users" dataset

  @auth
  Scenario: Reset access token
    Given I set the request method to GET
    And I reset the access tokens
    When I request "/addresses"
    And the response status code should be 401
    And the response is JSON

  @noAuth
  Scenario: Test api key and client setup
    Given I set the request method to GET
    And I set the apiKey "Key" and apiClient "myheader"
    And I add the following headers:
      | Header | apiClient |
    When I request "/addresses"
    And the response status code should be 200
    And the response is JSON

  @auth
  Scenario: Remove headers
    Given I set the request method to GET
    And I remove the following headers "Header"
    When I request "/addresses"
    And the response status code should be 401
    And the response is JSON

  @auth
  Scenario: Set headers empty - remove a header using different step definition
    Given I set the request method to GET
    And I set the following "Header" empty
    When I request "/addresses"
    And the response status code should be 401
    And the response is JSON

  @noAuth
  Scenario: Check empty response
    Given I set the request method to POST
    And I load data from file "addresses"
    When I request "/addresses" with dataset "new_address"
    And the response status code should be 204
    And the response is empty


  @tokenAuth
  Scenario: Test extract token from response
    Given I set the request method to POST
    And I load data from file "login"
    When I request "/login" with dataset "valid"
    And the response status code should be 200
    And the response is JSON
    And I extract access token from the response

  @noAuth
  Scenario: Check the exact response - default data set
    Given I set the request method to POST
    And I load data from file "login"
    When I request "/login" with dataset "invalid_password"
    And the response status code should be 400
    And the response is JSON
    And the response match the expected response

  @noAuth
  Scenario: Check the exact response - explicit data set
    Given I set the request method to POST
    And I load data from file "login"
    When I request "/login" with dataset "invalid_email"
    And the response status code should be 400
    And the response is JSON
    And the response match the expected response from "wrong_email" dataset

  @noAuth
  Scenario: Check templates in response data section
    Given I set the request method to GET
    And I load data from file "users"
    When I request "/admins"
    And the response status code should be 200
    And the response is JSON
    And the response match the expected structure from "all_admins" dataset

  @noAuth
  Scenario: Test that I can save a variable from response and use it later in a request - single variable
    Given I set the request method to POST
    When I request "/admins"
    And the response status code should be 201
    And the response is JSON
    And I save the "user_id" as "user_id"
    And I set the request method to DELETE
    And I request "/admins/%d" using "user_id"
    And the response status code should be 204
    And the response is empty

  @noAuth
  Scenario: Test that I can save a variable from response and use it later in a request - multiple variables
    Given I set the request method to POST
    When I request "/admins"
    And the response status code should be 201
    And the response is JSON
    And I save the "user_id" as "user_id"
    And I set the request method to POST
    When I request "/admins/%d/account" using "user_id"
    And the response status code should be 201
    And the response is JSON
    And I save the "account_id" as "account_id"
    And I set the request method to DELETE
    And I request "/admins/%d/account/%d" using:
      | user_id    |
      | account_id |
    And the response status code should be 204
    And the response is empty

  @noAuth
  Scenario: Check location header
    Given I set the request method to GET
    When I request "/account"
    And the response status code should be 302
    And I check location header to return 200
