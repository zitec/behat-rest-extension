<?php
/**
 * Created by PhpStorm.
 * User: bianca.vadean
 * Date: 8/31/2016
 * Time: 3:10 PM
 */

namespace Zitec\ApiZitecExtension\Context;


use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;
use Zitec\ApiZitecExtension\Data\Data;
use Zitec\ApiZitecExtension\Data\LoadData;
use Zitec\ApiZitecExtension\Data\LoadParameters;
use Zitec\ApiZitecExtension\Data\Storage;
use Zitec\ApiZitecExtension\Services\Request;

class RestContext_new extends MinkContext implements SnippetAcceptingContext
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
     * RestContext_new constructor.
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        $this->parameters = new LoadParameters($parameters); // TODO decide if you keep or delete the LoadParameters class
        $this->request = new Request();
        $this->storage = Storage::getInstance();
        $this->data = Data::getInstance();

        if (!empty($parameters['headers']) && is_array($parameters['headers'])) {
            $this->request->getHeaders()->generateHeaders($parameters['headers']);
        }

        if (!empty($parameters['authentication']) && is_array($parameters['headers'])) {
            $this->request->getHeaders()->setCredentials($parameters['authentication']);
        }

        if (!empty($parameters['login']) && is_array($parameters['login'])) {
            $this->request->getHeaders()->setLogin($parameters['login']);
        }
    }

    /**
     * @Given I set the request method to (POST|DELETE|GET|PUT)
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
        $this->data->setData($data);
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
    public function iSetTheApikeyAndApiuser($apiKey, $apiClient)
    {
        $this->request->getHeaders()->setApiClient($apiClient);
        $this->request->getHeaders()->setApiKey($apiKey);
    }
}
