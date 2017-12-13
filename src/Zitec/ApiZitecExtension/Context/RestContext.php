<?php

namespace Zitec\ApiZitecExtension\Context;

use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;
use Zitec\ApiZitecExtension\Data\Data;
use Zitec\ApiZitecExtension\Data\LoadData;
use Zitec\ApiZitecExtension\Data\Parameters;
use Zitec\ApiZitecExtension\Data\Storage;
use Zitec\ApiZitecExtension\Services\Authentication\Algorithms\AbstractAlgorithm;
use Zitec\ApiZitecExtension\Services\Authentication\AuthenticationFactory;
use Zitec\ApiZitecExtension\Services\Request;
use Zitec\ApiZitecExtension\Services\Response\Compare;
use Zitec\ApiZitecExtension\Services\Response\Response;

/**
 * Class RestContext
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @author Marius BALTEANU marius.balteanu@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class RestContext extends MinkContext implements RestAwareContext
{
    /**
     * @var Parameters | array
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
     * @var Compare
     */
    private $compare;

    /**
     * @var LoadData
     */
    private $loader;


    /**
     * RestContext constructor.
     *
     * @param array $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * @param Parameters $parameters
     * @return $this
     */
    public function setParameters(Parameters $parameters)
    {
        $parameters->setup($this->parameters);
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @param Data $data
     * @return $this
     */
    public function setData(Data $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param Storage $storage
     * @return $this
     */
    public function setStorage(Storage $storage)
    {
        $this->storage = $storage;

        return $this;
    }

    /**
     * @param Compare $compare
     * @return $this
     */
    public function setCompare(Compare $compare)
    {
        $this->compare = $compare;

        return $this;
    }

    /**
     * @param LoadData $loader
     * @return $this
     */
    public function setLoader(LoadData $loader)
    {
        $this->loader = $loader;

        return $this;
    }

    /**
     * @param BeforeStepScope $scope
     * @BeforeStep
     */
    public function prepare(BeforeStepScope $scope)
    {
        if ($this->request === null) {
            $client = $this->getSession()->getDriver()->getClient();
            $this->request = new Request($client);
        }
    }

    /**
     * @Given /^(?:|I )set the request method to (POST|DELETE|GET|PUT)$/
     *
     * @param string $method
     */
    public function iSetTheRequestMethod($method)
    {
        $this->parameters->setRequestMethod($method);
    }

    /**
     * @Given I load data from file :file
     *
     * @param string $file
     */
    public function iLoadDataFromFile($file)
    {
        $this->loader->loadData($file, $this->defaultLocale);
        $this->data->setDataLoaded($this->loader);
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
            $this->parameters->addHeader($name, $value);
        }
    }

    /**
     * @Given I reset the access tokens
     */
    public function iResetTheAccessTokens()
    {
        $this->parameters->setAuthentication([]);
    }

    /**
     * @Given I set the apiKey :apiKey and apiClient :apiClient
     *
     * @param string $apiKey
     * @param string $apiClient
     */
    public function iSetTheApiKeyAndApiUser($apiKey, $apiClient)
    {
        $authParams = $this->parameters->getAuthentication();
        $authParams['apiClient'] = $apiClient;
        $authParams['apiKey'] = $apiKey;
        $this->parameters->setAuthentication($authParams);
    }

    /**
     * @Given I set the following :headers empty
     * @Given I remove the following headers :headers
     *
     * Remove the specified headers. They should be coma separated.
     *
     * @param string $headers
     */
    public function iRemoveAHeader($headers)
    {
        $toRemove = array_map('trim', explode(',', $headers));
        foreach ($toRemove as $header) {
            $this->parameters->removeHeader($header);
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
        $this->parameters->setTimeDifference($time);
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
        $data[strtolower($this->parameters->getRequestMethod())] = [];
        if (!empty($dataSet)) {
            $data = $this->data->getDataForRequest($this->parameters->getRequestMethod(), $dataSet);
        }

        $this->doHttpRequest($queryString, $data);

        $content = $this->getSession()->getPage()->getContent();
        $headers = $this->getSession()->getResponseHeaders();

        $this->response = new Response($content, $headers);

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
        $responseType = strtolower($responseType);
        switch ($responseType) {
            case 'empty':
                if ($this->response->getContent() !== null) {
                    throw new \Exception(
                        sprintf(
                            "The content of the response is not empty!\n%s",
                            $this->response->getContent()->getRawContent()
                        )
                    );
                }
                break;
            default:
                if (!$this->response->contentTypeIs($responseType)) {
                    throw new \Exception(sprintf('The response is not %s', $responseType));
                }
                if ($this->response->getContent() === null) {
                    throw new \Exception(sprintf('The response is empty'));
                }
                break;
        }
    }

    /**
     * @Then /^I extract access token from the response$/
     */
    public function extractAccessTokenFromResponse()
    {
        $authParams = $this->parameters->getAuthentication();
        if (!empty($authParams)) {
            if (isset($authParams['auth_type']) && $authParams['auth_type'] === 'token') {
                if (!isset($authParams['token']) || !isset($authParams['secret'])) {
                    throw new \Exception(
                        '"token" and "secret" authentication parameters must be set for token authentication type.'
                    );
                }
                $tokenName = $authParams['token'];
                $secretName = $authParams['secret'];
                $token = $this->response->getContent()->getItem($tokenName);
                $secret = $this->response->getContent()->getItem($secretName);
                $authParams['tokenValue'] = $token;
                $authParams['secretValue'] = $secret;
                $this->parameters->setAuthentication($authParams);
            }
        }
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
            $this->compare->matchResponse($expectedResponse, $this->response);
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
            $this->compare->matchStructure($expectedResponse, $this->response);
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

        $value = $this->response->getContent()->getItem($responseKey);
        if (!isset($value)) {
            throw new \Exception('The given key was not found in the response.');
        }
        $this->storage->storeValue($name, $value);
    }

    /**
     * @Given I set the stored value :storedKey in dataset :dataSet as :dataSetKey
     *
     * @param string $storedKey
     * @param string $dataSet
     * @param string $dataSetKey
     */
    public function setStoredValueInDataSet($storedKey, $dataSet, $dataSetKey)
    {
        $value = $this->storage->getValue($storedKey);
        $this->data->addDataToDataset(
            $dataSet,
            [
                $dataSetKey => $value,
            ]
        );
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
        if ($name instanceof TableNode) {
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

        $locationHeader = $this->response->getHeader('Location');

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

    /**
     * Makes the actual request.
     *
     * @param string $queryString
     * @param  $data
     */
    protected function doHttpRequest($queryString, $data)
    {
        $headers = $this->parameters->getHeaders();

        $authParams = $this->parameters->getAuthentication();
        if (!empty($authParams)) {
            if (isset($authParams['auth_type'])) {
                $type = $authParams['auth_type'];
            } else {
                $type = "key";
            }

            $auth = $this->getAuth($type, $authParams, $queryString);

            $authData = $auth->getAuthHeaders();
            foreach ($headers as $key => $value) {
                if (isset($authData[$value])) {
                    $headers[$key] = $authData[$value];
                }
            }
        }

        $baseUrl = $this->getMinkParameter('base_url');
        $this->request->setHeaders($headers, $this->parameters->getSeenheaders());
        $this->request->request($baseUrl, $queryString, $this->parameters->getRequestMethod(), $data);
    }

    /**
     * @param $type
     * @param $params
     * @param $queryString
     * @return AbstractAlgorithm
     */
    protected function getAuth($type, $params, $queryString)
    {
        $authFactory = new AuthenticationFactory();
        $auth = $authFactory->createAuth(
            $type,
            $params,
            $this->parameters->getRequestMethod(),
            $queryString,
            $this->parameters->getTimeDifference()
        );

        return $auth;
    }
}
