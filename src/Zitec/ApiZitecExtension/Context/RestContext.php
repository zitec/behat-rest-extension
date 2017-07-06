<?php

namespace Zitec\ApiZitecExtension\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;
use Goutte\Client;
use Zitec\ApiZitecExtension\Data\Data;
use Zitec\ApiZitecExtension\Data\LoadData;
use Zitec\ApiZitecExtension\Data\LoadParameters;
use Zitec\ApiZitecExtension\Data\Storage;
use Zitec\ApiZitecExtension\Services\Request;
use Zitec\ApiZitecExtension\Services\Response\Response;
use Zitec\ApiZitecExtension\Services\Response\ResponseFactory;

/**
 * Class RestContext
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @author Marius BALTEANU marius.balteanu@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class RestContext extends MinkContext implements SnippetAcceptingContext
{
    /**
     * @var LoadParameters
     */
    protected $parameters;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $defaultLocale = "ro_RO";

    /**
     * @var Data
     */
    protected $data;

    /**
     * @var Storage
     */
    protected $storage;

    /**
     * @var Response
     */
    protected $response;


    /**
     * RestContext constructor.
     *
     * @param array $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = new LoadParameters($parameters); // TODO keep or delete the LoadParameters class??
        $this->request = new Request();
        $this->storage = Storage::getInstance();
        $this->data = Data::getInstance();

        if (!empty($parameters['headers']) && is_array($parameters['headers'])) {
            $this->request->getHeaders()->setInitialHeaders($parameters['headers']);
        }

        if (!empty($parameters['authentication']) && is_array($parameters['headers'])) {
            $this->request->getHeaders()->setAuthParams($parameters['authentication']);
        }
    }

    /**
     * @Given /^(?:|I )set the request method to (POST|DELETE|GET|PUT)$/
     *
     * @param string $objectType
     */
    public function iSetTheRequestMethod($objectType)
    {
        $this->request->setRequestMethod($objectType);
    }

    /**
     * @Given I load data from file :file
     *
     * @param string $file
     */
    public function iLoadDataFromFile($file)
    {
        $loader = new LoadData($this->parameters->root_path); // TODO manage better root_path parameter
        $data = $loader->loadData($file, $this->defaultLocale);
        $this->data->setDataLoaded($data);
    }

    /**
     * @Given /I add the following headers:
     *
     * @param TableNode $table
     */
    public function iAddTheFollowingHeaders(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            list($name, $value) = $row;
            $this->request->getHeaders()->addHeader($name, $value);
        }
    }

    /**
     * @Given I reset the access tokens
     */
    public function iResetTheAccessTokens()
    {
        $client = $this->getSession()->getDriver()->getClient();
        $this->request->resetTokens($client);
    }

    /**
     * @Given I set the apiKey :apiKey and apiClient :apiClient
     *
     * @param string $apiKey
     * @param string $apiClient
     */
    public function iSetTheApiKeyAndApiUser($apiKey, $apiClient)
    {
        $authParams = $this->request->getHeaders()->getAuthParams();
        $authParams['apiClient'] = $apiClient;
        $authParams['apiKey'] = $apiKey;
        $this->request->getHeaders()->setAuthParams($authParams);
    }

    /**
     * @Given I set the following :headers empty
     * @Given I remove the following headers :headers
     *
     * Remove the specified headers. They should be coma separated.
     *
     * @param string $headers
     */
    public function iRemoveAnAuthHeader($headers)
    {
        $toRemove = array_map('trim', explode(',', $headers));
        foreach ($toRemove as $header) {
            $this->request->getHeaders()->removeHeader($header);
        }
    }

    /**
     * @Given I modify the request time with :time
     *
     * Set the time difference.
     *
     * @param string $time
     */
    public function iAddToRequestTime($time)
    {
        $this->request->getHeaders()->setTimeDifference($time);
    }

    /**
     * @When I request :queryString
     * @When I request :queryString with dataset :dataSet
     *
     * @param string $queryString
     * @param string|null $dataSet
     */
    public function iRequest($queryString, $dataSet = null)
    {
        $data[strtolower($this->request->getRequestMethod())] = [];
        if (!empty($dataSet)) {
            $data = $this->data->getDataForRequest($this->request->getRequestMethod(), $dataSet);
        }

        /**
         * @var Client
         */
        $client = $this->getSession()->getDriver()->getClient();

        $baseUrl = $this->getMinkParameter('base_url');
        $this->request->request($queryString, $data, $client, $baseUrl);
        $response = $this->getSession()->getPage()->getContent();
        $headers = $this->getSession()->getResponseHeaders();

        $responseFactory = new ResponseFactory();
        $this->response = $responseFactory->createResponse($response, $headers);
        $this->storage->setLastResponse($this->response);
    }

    /**
     * @Given /^the response is (JSON|XML|empty)$/
     *
     * @param string $responseType
     * @throws \Exception
     */
    public function checkResponseType($responseType)
    {
        if (!isset($this->response)) {
            throw new \Exception("There is no response set yet.");
        }
        switch ($responseType) {
            case "empty":
                if (!$this->response->isEmpty()) {
                    $response = !is_string($this->response->getResponse()) ? $this->response->getRawResponse(
                    ) : $this->response->getResponse();
                    throw new \Exception("The content of the response is not empty!\n" . $response);
                }
                break;
            default:
                if (strtolower($this->response->getType()) !== strtolower($responseType)) {
                    throw new \Exception('The response is not ' . $responseType);
                }
                break;
        }
    }

    /**
     * @Then /^I extract access token from the response$/
     */
    public function extractAccessTokenFromResponse()
    {
        $authParams = $this->request->getHeaders()->getAuthParams();
        $tokenName = $authParams['token'];
        $secretName = $authParams['secret'];
        $token = $this->response->getItem($tokenName);
        $secret = $this->response->getItem($secretName);
        $authParams['tokenValue'] = $token;
        $authParams['secretValue'] = $secret;
        $this->request->getHeaders()->setAuthParams($authParams);
    }


    /**
     * @param string|null $dataSet
     * @throws \Exception
     *
     * @Given /^the response match the expected response(?:| from "([^"]*)" dataset)$/
     */
    public function theResponseMatchTheExpectedResponse($dataSet = null)
    {
        $expectedResponse = $this->data->getResponseData($dataSet);
        if (isset($this->response)) {
            $this->response->matchResponse($expectedResponse);
        } else {
            throw new \Exception("The response is not set yet.");
        }
    }

    /**
     * @param string|null $dataSet
     * @throws \Exception
     *
     * @Then /^the response match the expected structure(?:| from "([^"]*)" dataset)$/
     * @Then /^each response from the collection match the expected structure(?:| from "([^"]*)" dataset)$/
     */
    public function theResponseMatchExpectedStructure($dataSet = null)
    {
        $expectedResponse = $this->data->getResponseData($dataSet);
        if (isset($this->response)) {
            $this->response->matchStructure($expectedResponse);
        } else {
            throw new \Exception("The response is not set yet.");
        }
    }

    /**
     * Saves the $responseKey in storage under the $name key.
     *
     * @param string $responseKey
     * @param string $name
     * @throws \Exception
     *
     * @Given /^I save the "([^"]*)" as "([^"]*)"$/
     */
    public function iSaveTheAs($responseKey, $name)
    {
        if (!isset($this->response)) {
            throw new \Exception('The response is not set yet.');
        }

        $index = $this->response->getItem($responseKey);
        if (!isset($index)) {
            throw new \Exception('The given key was not found in the response.');
        }
        $this->storage->storeValue($name, $index);
    }

    /**
     * Set the request parameter in url from the saved response under key $name
     * In case of multiple parameters in url the response keys will be fund in the TableNode
     * The request should look like this: /method/%d
     *
     * @param string $request
     * @param string | TableNode $name
     * @param string|null $dataSet
     *
     * @When I request :request using :varKey with dataset :dataSet
     * @When I request :request using :varKey
     * @When I request :request with dataset :dataSet using:
     * @When I request :request using:
     */
    public function iRequestUsingWithDataset($request, $name, $dataSet = null)
    {
        if (is_a($name, '\Behat\Gherkin\Node\TableNode')) {
            $params = [];
            foreach ($name->getColumn(0) as $value) {
                $params[] = $this->storage->getValue($value);
            }
            $queryString = vsprintf($request, $params);
        } else {
            $param = $this->storage->getValue($name);
            $queryString = sprintf($request, $param);
        }

        $this->iRequest($queryString, $dataSet);
    }

    /**
     *  Makes a request on the path given in the location header and checks the response status code.
     *
     * @param int $status
     * @throws \Exception
     *
     * @Then I check location header to return :status
     */
    public function checkLocationHeader($status)
    {
        if (!isset($this->response)) {
            throw new \Exception('The response is not set yet.');
        }

        $locationHeader = $this->response->getResponseHeader('Location');

        if (!isset($locationHeader)) {
            throw new \Exception('No Location header received.');
        }

        $this->iRequest($locationHeader);
        try {
            $this->assertResponseStatus($status);
        } catch (\Exception $exception) {
            throw new \Exception(
                'The response status code after request on the Location header invalid. '
                . $exception->getMessage()
            );
        }
    }
}
