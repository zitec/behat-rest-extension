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
use Zitec\ApiZitecExtension\Services\Response;
use Zitec\ApiZitecExtension\Services\ResponseFactory;

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
     * RestContext_new constructor.
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        $this->parameters = new LoadParameters($parameters); // TODO keep or delete the LoadParameters class??
        $this->request = new Request();
        $this->storage = Storage::getInstance();
        $this->data = Data::getInstance();

        if (!empty($parameters['headers']) && is_array($parameters['headers'])) {
            $this->request->getHeaders()->setHeaders($parameters['headers']);
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
     * @param $apiKey
     * @param $apiClient
     */
    public function iSetTheApiKeyAndApiUser($apiKey, $apiClient)
    {
        $this->request->getHeaders()->setApiClient($apiClient);
        $this->request->getHeaders()->setApiKey($apiKey);
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
        $toRemove = array_map('trim', explode(',' ,$headers));
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
     * @param $queryString
     * @param $dataSet
     */
    public function iRequest($queryString, $dataSet = null)
    {
        $data = [];
        if (!empty($dataSet)) {
            $data = $this->data->getDataForRequest($this->request->getRequestMethod(), $dataSet);
        }

        /**
         * @var Client
         */
        $client = $this->getSession()->getDriver()->getClient();
        $baseUrl =$this->getMinkParameter('base_url');
        $this->request->request($queryString, $data, $client, $baseUrl);
        $response = $this->getSession()->getPage()->getContent();
        $headers = $this->getSession()->getResponseHeaders();
        $responseFactory = new ResponseFactory();
        $this->response = $responseFactory->createResponse($response, $headers);
    }

    /**
     * @Given /^the response is (JSON|XML|empty)$/
     *
     * @param $responseType
     * @throws \Exception
     */
    public function checkResponseType($responseType)
    {
        if (!isset($this->response)) {
            throw new \Exception("There is no response set yet.");
        }
        if ($responseType !== 'empty' && !$this->response->isEmpty()) {
            if (strtolower($this->response->getType()) !== strtolower($responseType)) {
                throw new \Exception('The response is not ' . $responseType);
            }
        }
    }
}
