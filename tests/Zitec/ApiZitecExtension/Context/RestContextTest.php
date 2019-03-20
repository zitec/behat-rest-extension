<?php

namespace Tests\Zitec\ApiZitecExtension\Context;

use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\GoutteDriver;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\Testwork\Environment\Environment;
use Goutte\Client;
use PHPUnit\Framework\TestCase;
use Zitec\ApiZitecExtension\Context\RestContext;
use Zitec\ApiZitecExtension\Data\Data;
use Zitec\ApiZitecExtension\Data\LoadData;
use Zitec\ApiZitecExtension\Data\Parameters;
use Zitec\ApiZitecExtension\Data\Storage;
use Zitec\ApiZitecExtension\Services\Authentication\Algorithms\TokenAuthentication;
use Zitec\ApiZitecExtension\Services\Request;
use Zitec\ApiZitecExtension\Services\Response\Compare;
use Zitec\ApiZitecExtension\Services\Response\Content\Json;
use Zitec\ApiZitecExtension\Services\Response\Response;

/**
 * Class RestContextTest
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class RestContextTest extends TestCase
{
    /**
     * @var RestContext
     */
    protected $restContext;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mink;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $parameters;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $compare;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $data;

    /**
     * Sets up the needed properties.
     * Runs before each test is executed.
     */
    public function setUp()
    {
        $this->restContext = $this->setupRestContext();
        $this->mink = $this->getMockBuilder(Mink::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->restContext->setMink($this->mink);
    }

    /**
     * @param RestContext $context
     * @param Parameters $parameters
     * @param Storage $storage
     * @param Data $data
     * @param Compare $compare
     * @return RestContext
     */
    protected function setupContext($context, $parameters, $storage, $data, $compare)
    {
        $reflection = new \ReflectionClass(RestContext::class);
        $reflectedParameters = $reflection->getProperty('parameters');
        $reflectedParameters->setAccessible(true);
        $reflectedParameters->setValue($context, $parameters);
        $reflectedStorage = $reflection->getProperty('storage');
        $reflectedStorage->setAccessible(true);
        $reflectedStorage->setValue($context, $storage);
        $reflectedData = $reflection->getProperty('data');
        $reflectedData->setAccessible(true);
        $reflectedData->setValue($context, $data);
        $reflectedCompare = $reflection->getProperty('compare');
        $reflectedCompare->setAccessible(true);
        $reflectedCompare->setValue($context, $compare);

        return $context;
    }

    /**
     * @param RestContext $context
     * @param Response $response
     * @return RestContext
     */
    protected function setResponse($context, $response)
    {
        $reflection = new \ReflectionClass(RestContext::class);
        $reflectedParameters = $reflection->getProperty('response');
        $reflectedParameters->setAccessible(true);
        $reflectedParameters->setValue($context, $response);

        return $context;
    }

    /**
     * @param RestContext $context
     * @param Request $request
     * @return RestContext
     */
    protected function setRequest($context, $request)
    {
        $reflection = new \ReflectionClass(RestContext::class);
        $reflectedParameters = $reflection->getProperty('request');
        $reflectedParameters->setAccessible(true);
        $reflectedParameters->setValue($context, $request);

        return $context;
    }

    /**
     * Create RestContext object
     *  ans set properties originally created in constructor as mock objects through Reflection magic
     *
     * @return RestContext
     */
    protected function setupRestContext()
    {
        $restContext = new RestContext([]);
        $this->parameters = $this->getMockBuilder(Parameters::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->parameters->root_path = $this->getRootPath();
        $this->compare = $this->getMockBuilder(Compare::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storage = $this->getMockBuilder(Storage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->data = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $restContext->setParameters($this->parameters)
            ->setCompare($this->compare)
            ->setStorage($this->storage)
            ->setData($this->data);

        return $restContext;
    }

    /**
     * Tests the prepare function when the request is not set.
     */
    public function testPrepare()
    {

        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mink->expects($this->once())
            ->method('getSession')
            ->willReturn($session);

        $driver = $this->getMockBuilder(GoutteDriver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $session->expects($this->once())
            ->method('getDriver')
            ->willReturn($driver);

        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $driver->expects($this->once())
            ->method('getClient')
            ->willReturn($client);
        $env = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $featureNode = $this->getMockBuilder(FeatureNode::class)
            ->disableOriginalConstructor()
            ->getMock();

        $stepNode = $this->getMockBuilder(StepNode::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Cannot create mock object because BeforeStepScope is final class
        $scope = new BeforeStepScope($env, $featureNode, $stepNode);
        $this->restContext->prepare($scope);
        $this->assertAttributeInstanceOf(Request::class, 'request', $this->restContext);
    }

    /**
     * Test the prepare function when the request property is already set.
     */
    public function testPrepareOnRequestNotNull()
    {
        //Sets the request.
        $this->testPrepare();

        $env = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $featureNode = $this->getMockBuilder(FeatureNode::class)
            ->disableOriginalConstructor()
            ->getMock();

        $stepNode = $this->getMockBuilder(StepNode::class)
            ->disableOriginalConstructor()
            ->getMock();
        // Cannot create mock object because BeforeStepScope is final class
        $scope = new BeforeStepScope($env, $featureNode, $stepNode);

        $this->restContext->prepare($scope);
    }


    /**
     * @dataProvider getData
     * @param InputData $input
     */
    public function testISetTheRequestMethod(InputData $input)
    {
        $this->parameters->expects($this->once())
            ->method('setRequestMethod')
            ->with($input->http_method);

        $this->restContext->iSetTheRequestMethod($input->http_method);
    }


    /**
     * @dataProvider getData
     * @param InputData $input
     */
    public function testILoadDataFromFile(InputData $input)
    {
        $loader = $this->getMockBuilder(LoadData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->restContext->setLoader($loader);
        $loader->expects($this->once())
            ->method('loadData')
            ->with($input->data_file_name, 'ro_RO');

        $this->data
            ->expects($this->once())
            ->method('setDataLoaded')
            ->with($loader);

        $this->restContext->iLoadDataFromFile($input->data_file_name);
    }

    /**
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testAddCustomHeaders(InputData $inputData)
    {
        $table = $this->getMockBuilder(TableNode::class)
            ->disableOriginalConstructor()
            ->getMock();
        $headers = $inputData->headers;
        $table->expects($this->once())
            ->method('getRows')
            ->willReturn($headers);
        list($headerName, $headerValue) = reset($headers);

        $this->parameters
            ->expects($this->once())
            ->method('addHeader')
            ->with($headerName, $headerValue);

        $this->restContext->iAddTheFollowingHeaders($table);
    }

    /**
     * Try to add empty name headers
     */
    public function testAddCustomEmptyNameHeaders()
    {
        $table = $this->getMockBuilder(TableNode::class)
            ->disableOriginalConstructor()
            ->getMock();
        $headers = [
            ['', 'value',],
        ];
        $table->expects($this->once())
            ->method('getRows')
            ->willReturn($headers);

        $this->restContext->iAddTheFollowingHeaders($table);
    }

    /**
     * Try to add empty value headers
     */
    public function testAddEmptyValueCustomHeaders()
    {
        $table = $this->getMockBuilder(TableNode::class)
            ->disableOriginalConstructor()
            ->getMock();
        $headers = [
            ['name', '',],
        ];
        $table->expects($this->once())
            ->method('getRows')
            ->willReturn($headers);
        list($headerName, $headerValue) = reset($headers);

        $this->parameters
            ->expects($this->once())
            ->method('addHeader')
            ->with($headerName, $headerValue);

        $this->restContext->iAddTheFollowingHeaders($table);
    }

    /**
     *  Test the method that resets auth tokens.
     */
    public function testIResetAccessTokens()
    {
        $this->parameters
            ->expects($this->once())
            ->method('setAuthentication')
            ->with([]);

        $this->restContext->iResetTheAccessTokens();
    }

    /**
     * Test we can set apiKey and apiUser
     *
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testApiKeyAndApiUserSetup(InputData $inputData)
    {
        $this->parameters
            ->expects($this->once())
            ->method('getAuthentication')
            ->willReturn([]);

        $this->parameters
            ->expects($this->once())
            ->method('setAuthentication')
            ->with($inputData->auth);

        $this->restContext->iSetTheApiKeyAndApiUser($inputData->auth['apiKey'], $inputData->auth['apiClient']);
    }

    /**
     * Test the method that removes one or more given headers.
     *
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testRemoveHeader(InputData $inputData)
    {
        $headersToRemove = $inputData->headers_to_remove;
        list($firstHeader, $secondHeader) = array_map('trim', explode(',', $headersToRemove));

        $this->parameters
            ->expects($this->exactly(2))
            ->method('removeHeader')
            ->withConsecutive(
                [$firstHeader],
                [$secondHeader]
            );

        $this->restContext->iRemoveAHeader($headersToRemove);
    }

    /**
     * Test the method that adds time to request.
     *
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testAddRequestTime(InputData $inputData)
    {
        $this->parameters
            ->expects($this->once())
            ->method('setTimeDifference')
            ->with($inputData->added_request_time);

        $this->restContext->iAddToRequestTime($inputData->added_request_time);
    }

    /**
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testRequestWithoutDataSet(InputData $inputData)
    {
        $mockContext = $this->getMockBuilder(RestContext::class)
            ->setMethods(['doHttpRequest',])
            ->disableOriginalConstructor()
            ->getMock();

        $parameters = $this->getMockBuilder(Parameters::class)
            ->disableOriginalConstructor()
            ->getMock();
        $parameters->root_path = $this->getRootPath();
        $compare = $this->getMockBuilder(Compare::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storage = $this->getMockBuilder(Storage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setupContext($mockContext, $parameters, $storage, $mockData, $compare);
        $queryString = '';
        $dataSet = null;
        $requestMethod = $inputData->http_method;

        $parameters->expects($this->once())
            ->method('getRequestMethod')
            ->willReturn($requestMethod);

        $data[strtolower($requestMethod)] = [];
        $mockContext->expects($this->once())
            ->method('doHttpRequest')
            ->with($queryString, $data);

        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPage', 'getResponseHeaders'])
            ->getMock();

        $page = $this->getMockBuilder(DocumentElement::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContent'])
            ->getMock();

        $session->expects($this->once())
            ->method('getPage')
            ->willReturn($page);

        $page->expects($this->once())
            ->method('getContent')
            ->willReturn('');

        $headers = [];
        $session->expects($this->once())
            ->method('getResponseHeaders')
            ->willReturn($headers);


        $mockContext->setMink($this->mink);

        $this->mink->expects($this->exactly(2))
            ->method('getSession')
            ->willReturn($session);

        $storage->expects($this->once())
            ->method('setLastResponse')
            ->with($this->isInstanceOf(Response::class));

        $mockContext->iRequest($queryString, $dataSet);
    }

    /**
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testRequestWithDataSet(InputData $inputData)
    {
        $mockContext = $this->getMockBuilder(RestContext::class)
            ->setMethods(['doHttpRequest',])
            ->disableOriginalConstructor()
            ->getMock();

        $parameters = $this->getMockBuilder(Parameters::class)
            ->disableOriginalConstructor()
            ->getMock();
        $parameters->root_path = $this->getRootPath();
        $compare = $this->getMockBuilder(Compare::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storage = $this->getMockBuilder(Storage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setupContext($mockContext, $parameters, $storage, $mockData, $compare);
        $queryString = '';
        $dataSet = $inputData->data_set;
        $requestMethod = $inputData->http_method;
        $dataForRequest[strtolower($requestMethod)] = $inputData->data_for_request;

        $parameters->expects($this->exactly(2))
            ->method('getRequestMethod')
            ->willReturn($requestMethod);

        $mockData->expects($this->once())
            ->method('getDataForRequest')
            ->with($requestMethod, $dataSet)
            ->willReturn($dataForRequest);

        $mockContext->expects($this->once())
            ->method('doHttpRequest')
            ->with($queryString, $dataForRequest);

        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPage', 'getResponseHeaders'])
            ->getMock();

        $page = $this->getMockBuilder(DocumentElement::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContent'])
            ->getMock();

        $session->expects($this->once())
            ->method('getPage')
            ->willReturn($page);

        $page->expects($this->once())
            ->method('getContent')
            ->willReturn('');

        $headers = [];
        $session->expects($this->once())
            ->method('getResponseHeaders')
            ->willReturn($headers);


        $mockContext->setMink($this->mink);

        $this->mink->expects($this->exactly(2))
            ->method('getSession')
            ->willReturn($session);

        $storage->expects($this->once())
            ->method('setLastResponse')
            ->with($this->isInstanceOf(Response::class));

        $mockContext->iRequest($queryString, $dataSet);
    }

    /**
     * Test check response method when the response is not set yet.
     */
    public function testCheckResponseTypeNoResponse()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('There is no response set yet.');
        $this->restContext->checkResponseType('empty');
    }

    /**
     * Test check response type method when the response is empty.
     *
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testCheckResponseTypeEmptyResponseValid(InputData $inputData)
    {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setResponse($this->restContext, $response);
        $content = null;
        $response->expects($this->once())
            ->method('getContent')
            ->willReturn($content);

        $responseType = $inputData->empty_response_type;
        $this->restContext->checkResponseType($responseType);
    }

    /**
     * Test the check response type method when the response has been expected to be empty but it's not.
     *
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testCheckResponseTypeEmptyResponseInvalid(InputData $inputData)
    {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $content = '{"type": "empty"}';

        $this->setResponse($this->restContext, $response);
        $response->expects($this->at(0))
            ->method('getContent')
            ->willReturn($content);

        $jsonContent = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response->expects($this->at(1))
            ->method('getContent')
            ->willReturn($jsonContent);

        $jsonContent->expects($this->once())
            ->method('getRawContent')
            ->willReturn($content);

        $responseType = $inputData->empty_response_type;

        $this->expectException(\Exception::class);

        $this->expectExceptionMessage(
            sprintf("The content of the response is not empty!\n%s", $content)
        );
        $this->restContext->checkResponseType($responseType);
    }


    /**
     * Test the check response type method when the response has been expected to be json but it's not.
     *
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testCheckResponseTypeJsonResponseInvalid(InputData $inputData)
    {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setResponse($this->restContext, $response);
        $responseType = strtolower($inputData->json_response_type);

        $response->expects($this->once())
            ->method('contentTypeIs')
            ->with($responseType)
            ->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(sprintf('The response is not %s', $responseType));
        $this->restContext->checkResponseType($responseType);
    }


    /**
     * Test the check response type method when the response is a valid json.
     *
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testCheckResponseTypeJsonResponseValid(InputData $inputData)
    {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setResponse($this->restContext, $response);
        $responseType = strtolower($inputData->json_response_type);

        $response->expects($this->once())
            ->method('contentTypeIs')
            ->with($responseType)
            ->willReturn(true);

        $jsonContent = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response->expects($this->once())
            ->method('getContent')
            ->willReturn($jsonContent);

        $this->restContext->checkResponseType($responseType);

    }


    /**
     * Test the check response type method when the response has been expected to be json but it's empty.
     *
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testCheckResponseTypeJsonResponseEmpty(InputData $inputData)
    {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setResponse($this->restContext, $response);
        $responseType = strtolower($inputData->json_response_type);

        $response->expects($this->once())
            ->method('contentTypeIs')
            ->with($responseType)
            ->willReturn(true);

        $response->expects($this->once())
            ->method('getContent')
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The response is empty');
        $this->restContext->checkResponseType($responseType);
    }


    /**
     * Test extract access token when no auth is set.
     */
    public function testExtractAccessTokenNoAuth()
    {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->setRequest($this->restContext, $request);
        $this->setResponse($this->restContext, $response);

        $this->parameters->expects($this->once())
            ->method('getAuthentication')
            ->willReturn([]);

        $this->restContext->extractAccessTokenFromResponse();
    }

    /**
     * @dataProvider getData
     * Test extract access token when secret name is not set.
     */
    public function testExtractAccessTokenNoSecret(InputData $inputData)
    {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->setRequest($this->restContext, $request);
        $this->setResponse($this->restContext, $response);

        $authParams = $inputData->auth_no_secret;

        $this->parameters->expects($this->once())
            ->method('getAuthentication')
            ->willReturn($authParams);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            '"token" and "secret" authentication parameters must be set for token authentication type.'
        );
        $this->restContext->extractAccessTokenFromResponse();
    }


    /**
     * @dataProvider getData
     * @param $inputData InputData
     *
     * Test extract access token when token name is not set.
     */
    public function testExtractAccessTokenNoToken(InputData $inputData)
    {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->setRequest($this->restContext, $request);
        $this->setResponse($this->restContext, $response);

        $authParams = $inputData->auth_no_token;

        $this->parameters->expects($this->once())
            ->method('getAuthentication')
            ->willReturn($authParams);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            '"token" and "secret" authentication parameters must be set for token authentication type.'
        );
        $this->restContext->extractAccessTokenFromResponse();
    }

    /**
     * @dataProvider getData
     * @param $inputData InputData
     *
     * Test extract access token when token name is not set.
     */
    public function testExtractAccessTokenValid(InputData $inputData)
    {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setResponse($this->restContext, $response);

        $authParams = $inputData->auth_params;

        $this->parameters->expects($this->once())
            ->method('getAuthentication')
            ->willReturn($authParams);

        $jsonResponse = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn($jsonResponse);

        $jsonResponse->expects($this->at(0))
            ->method('getItem')
            ->with($authParams['token'])
            ->willReturn($authParams['tokenValue']);

        $jsonResponse->expects($this->at(1))
            ->method('getItem')
            ->with($authParams['secret'])
            ->willReturn($authParams['secretValue']);

        $this->parameters->expects($this->once())
            ->method('setAuthentication')
            ->with($authParams);

        $this->restContext->extractAccessTokenFromResponse();
    }

    /**
     * @dataProvider getData
     * @param $inputData InputData
     *
     * Test method that checks the expected response when the response object is not set yet.
     */
    public function testTheResponseMatchTheExpectedResponseNoResponse(InputData $inputData)
    {
        $dataSet = $inputData->dataset;
        $responseData = $inputData->response_data;

        $this->data->expects($this->once())
            ->method('getResponseData')
            ->with($dataSet)
            ->willReturn($responseData);

        $this->compare->expects($this->exactly(0))
            ->method('matchResponse');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The response is not set yet');

        $this->restContext->theResponseMatchTheExpectedResponse($dataSet);
    }


    /**
     * @dataProvider getData
     * @param $inputData InputData
     *
     * Test method that checks the expected structure.
     */
    public function testTheResponseMatchTheExpectedResponse(InputData $inputData)
    {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setResponse($this->restContext, $response);
        $dataSet = $inputData->dataset;
        $expectedResponse = $inputData->response_data;

        $this->data->expects($this->once())
            ->method('getResponseData')
            ->with($dataSet)
            ->willReturn($expectedResponse);

        $this->compare->expects($this->exactly(1))
            ->method('matchResponse')
            ->with($expectedResponse, $response);

        $this->restContext->theResponseMatchTheExpectedResponse($dataSet);
    }

    /**
     * @dataProvider getData
     * @param $inputData InputData
     *
     * Test method that checks the expected structure when the response object is not set yet.
     */
    public function testTheResponseMatchExpectedStructureNoResponse(InputData $inputData)
    {
        $dataSet = $inputData->dataset;
        $responseData = $inputData->response_data;

        $this->data->expects($this->once())
            ->method('getResponseData')
            ->with($dataSet)
            ->willReturn($responseData);

        $this->compare->expects($this->exactly(0))
            ->method('matchStructure');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The response is not set yet');

        $this->restContext->theResponseMatchExpectedStructure($dataSet);
    }


    /**
     * @dataProvider getData
     * @param $inputData InputData
     *
     * Test extract access token when token name is not set.
     */
    public function testTheResponseMatchTheStructureResponse(InputData $inputData)
    {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setResponse($this->restContext, $response);
        $dataSet = $inputData->dataset;
        $expectedResponse = $inputData->response_data;

        $this->data->expects($this->once())
            ->method('getResponseData')
            ->with($dataSet)
            ->willReturn($expectedResponse);

        $this->compare->expects($this->exactly(1))
            ->method('matchStructure')
            ->with($expectedResponse, $response);

        $this->restContext->theResponseMatchExpectedStructure($dataSet);
    }

    /**
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testISaveTheAs(InputData $inputData)
    {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setResponse($this->restContext, $response);

        $jsonResponse = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response->expects($this->once())
            ->method('getContent')
            ->willReturn($jsonResponse);

        $index = $inputData->index_to_save;
        $value = $inputData->value_to_save;

        $jsonResponse->expects($this->once())
            ->method('getItem')
            ->with($index)
            ->willReturn($value);

        $name = $inputData->name_to_save;
        $this->storage->expects($this->once())
            ->method('storeValue')
            ->with($name, $value);

        $this->restContext->iSaveTheAs($index, $name);
    }

    /**
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testISaveTheAsNoResponse(InputData $inputData)
    {
        $index = $inputData->index_to_save;
        $name = $inputData->name_to_save;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The response is not set yet');

        $this->restContext->iSaveTheAs($index, $name);
    }

    /**
     * @dataProvider getData
     * @param InputData $inputData
     *
     * Tests the method that saves the key of the response in storage when the key was not found in response.
     */
    public function testISaveTheAsNoKeyInResponse(InputData $inputData)
    {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setResponse($this->restContext, $response);

        $jsonResponse = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response->expects($this->once())
            ->method('getContent')
            ->willReturn($jsonResponse);
        $index = $inputData->index_to_save;
        $value = null;

        $jsonResponse->expects($this->once())
            ->method('getItem')
            ->with($index)
            ->willReturn($value);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The given key was not found in the response.');

        $name = $inputData->name_to_save;
        $this->restContext->iSaveTheAs($index, $name);
    }


    /**
     * Test method that makes a request by replacing the placeholder in the request url with a stored value,
     * when the $name is a string
     *
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testIRequestUsingWithDatasetStringName(InputData $inputData)
    {
        $reguestUrl = $inputData->request_with_placeholder;
        $name = $inputData->stored_key;

        $value = $inputData->response_data['id'];

        $this->storage->expects($this->once())
            ->method('getValue')
            ->with($name)
            ->willReturn($value);

        $mockContext = $this->getMockBuilder(RestContext::class)
            ->setMethods(['iRequest',])
            ->disableOriginalConstructor()
            ->getMock();

        $this->setupContext($mockContext, $this->parameters, $this->storage, $this->data, $this->compare);
        $mockContext->setMink($this->mink);

        $dataSet = null;
        $mockContext->expects($this->once())
            ->method('iRequest')
            ->with($inputData->request_url, $dataSet);

        $mockContext->iRequestUsingWithDataset($reguestUrl, $name, $dataSet);
    }

    /**
     * @dataProvider getData
     * @param InputData $inputData
     *
     */
    public function testSetStoredValueInDataSet(InputData $inputData)
    {
        $storedKey = $inputData->stored_key;
        $dataSet = $inputData->data_set;
        $dataSetKey = $inputData->data_set_key;
        $value = $inputData->stored_value;
        $this->storage->expects($this->once())
            ->method('getValue')
            ->with($storedKey)
            ->willReturn($value);
        $this->data->expects($this->once())
            ->method('addDataToDataset')
            ->with(
                $dataSet,
                [
                    $dataSetKey => $value,
                ]
            );

        $this->restContext->setStoredValueInDataSet($storedKey, $dataSet, $dataSetKey);
    }

    /**
     * Test method that makes a request by replacing the placeholder in the request url with a stored value,
     * when the $name is a TableNode
     *
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testIRequestUsingWithDataset(InputData $inputData)
    {
        $reguestUrl = $inputData->request_with_placeholder;
        $name = $this->getMockBuilder(TableNode::class)
            ->disableOriginalConstructor()
            ->getMock();

        $value = $inputData->table_column;
        $name->expects($this->once())
            ->method('getColumn')
            ->with(0)
            ->willReturn($value);

        $storedValue = $inputData->response_data['id'];

        $this->storage->expects($this->once())
            ->method('getValue')
            ->with($value[0])
            ->willReturn($storedValue);

        $mockContext = $this->getMockBuilder(RestContext::class)
            ->setMethods(['iRequest',])
            ->disableOriginalConstructor()
            ->getMock();

        $this->setupContext($mockContext, $this->parameters, $this->storage, $this->data, $this->compare);
        $mockContext->setMink($this->mink);

        $dataSet = null;
        $mockContext->expects($this->once())
            ->method('iRequest')
            ->with($inputData->request_url, $dataSet);

        $mockContext->iRequestUsingWithDataset($reguestUrl, $name, $dataSet);
    }

    /**
     * Test method that checks the location header when there is no response set.
     */
    public function testCheckLocationHeaderNoResponse()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The response is not set yet.');
        $this->restContext->checkLocationHeader(200);
    }

    /**
     * Test method that checks the location header when there is no location header.
     */
    public function testCheckLocationHeaderNoLocationHeader()
    {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setResponse($this->restContext, $response);
        $response->expects($this->once())
            ->method('getHeader')
            ->with('Location')
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No Location header received.');
        $this->restContext->checkLocationHeader(200);
    }

    /**
     * Test method that checks the location header when the status code received when
     * calling the url in the location header does not match the expected one.
     *
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testCheckLocationHeaderInvalidStatusCode(InputData $inputData)
    {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->once())
            ->method('getHeader')
            ->with('Location')
            ->willReturn($inputData->location_header);

        $mockContext = $this->getMockBuilder(RestContext::class)
            ->setMethods(['iRequest', 'assertResponseStatus',])
            ->disableOriginalConstructor()
            ->getMock();

        $this->setupContext($mockContext, $this->parameters, $this->storage, $this->data, $this->compare);

        $this->setResponse($mockContext, $response);
        $mockContext->setMink($this->mink);

        $mockContext->expects($this->once())
            ->method('iRequest')
            ->with($inputData->location_header);
        $mockContext->expects($this->once())
            ->method('assertResponseStatus')
            ->with(200)
            ->willThrowException(new \Exception('Current response status code is 100, but 200 expected.'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'The response status code after request on the Location header invalid. Current response status code is 100, but 200 expected.'
        );
        $mockContext->checkLocationHeader(200);
    }


    /**
     * Test method that checks the location header when the status code received when
     * calling the url in the location header matches the expected one.
     *
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testCheckLocationHeaderValid(InputData $inputData)
    {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->once())
            ->method('getHeader')
            ->with('Location')
            ->willReturn($inputData->location_header);

        $mockContext = $this->getMockBuilder(RestContext::class)
            ->setMethods(['iRequest', 'assertResponseStatus',])
            ->disableOriginalConstructor()
            ->getMock();

        $this->setupContext($mockContext, $this->parameters, $this->storage, $this->data, $this->compare);

        $this->setResponse($mockContext, $response);
        $mockContext->setMink($this->mink);

        $mockContext->expects($this->once())
            ->method('iRequest')
            ->with($inputData->location_header);
        $mockContext->expects($this->once())
            ->method('assertResponseStatus')
            ->with(200);

        $mockContext->checkLocationHeader(200);
    }

    /**
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testDoHttpRequestAuth(InputData $inputData)
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockContext = $this->getMockBuilder(RestContext::class)
            ->setMethods(['getMinkParameter', 'getAuth'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->setupContext($mockContext, $this->parameters, $this->storage, $this->data, $this->compare);

        $this->setRequest($mockContext, $request);

        $headers = $inputData->headers_token;
        $this->parameters->expects($this->once())
            ->method('getHeaders')
            ->willReturn($headers);

        $authParams = $inputData->auth_params;
        $this->parameters->expects($this->once())
            ->method('getAuthentication')
            ->willReturn($authParams);

        $queryString = '/';

        $auth = $this->getMockBuilder(TokenAuthentication::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockContext->expects($this->once())
            ->method('getAuth')
            ->with($authParams['auth_type'], $authParams, $queryString)
            ->willReturn($auth);

        $authData = $inputData->auth_data;

        $auth->expects($this->once())
            ->method('getAuthHeaders')
            ->willReturn($authData);

        $baseUrl = $inputData->base_url;
        $mockContext->expects($this->once())
            ->method('getMinkParameter')
            ->with('base_url')
            ->willReturn($baseUrl);

        $seenHeaders = $inputData->headers_token;

        $this->parameters->expects($this->once())
            ->method('getSeenHeaders')
            ->willReturn($seenHeaders);
        $completeHeaders = $inputData->complete_auth_headers;

        $request->expects($this->once())
            ->method('setHeaders')
            ->with($completeHeaders, $seenHeaders);

        $data = [];
        $requestMethod = $inputData->request_method;
        $request->expects($this->once())
            ->method('request')
            ->with($baseUrl, $queryString, $requestMethod, $data);

        $reflection = new \ReflectionMethod(RestContext::class, 'doHttpRequest');
        $reflection->setAccessible(true);
        $reflection->invoke($mockContext, $queryString, $data);
    }

    /**
     * @dataProvider getData
     * @param InputData $inputData
     */
    public function testDoHttpRequestNoAuth(InputData $inputData)
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockContext = $this->getMockBuilder(RestContext::class)
            ->setMethods(['getMinkParameter', 'getAuth'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->setupContext($mockContext, $this->parameters, $this->storage, $this->data, $this->compare);

        $this->setRequest($mockContext, $request);

        $headers = $inputData->headers_token;
        $this->parameters->expects($this->once())
            ->method('getHeaders')
            ->willReturn($headers);

        $authParams = [];
        $this->parameters->expects($this->once())
            ->method('getAuthentication')
            ->willReturn($authParams);

        $baseUrl = $inputData->base_url;
        $mockContext->expects($this->once())
            ->method('getMinkParameter')
            ->with('base_url')
            ->willReturn($baseUrl);

        $this->parameters->expects($this->once())
            ->method('getSeenHeaders')
            ->willReturn($headers);

        $request->expects($this->once())
            ->method('setHeaders')
            ->with($headers, $headers);

        $data = [];
        $queryString = '/';

        $requestMethod = $inputData->request_method;
        $request->expects($this->once())
            ->method('request')
            ->with($baseUrl, $queryString, $requestMethod, $data);

        $reflection = new \ReflectionMethod(RestContext::class, 'doHttpRequest');
        $reflection->setAccessible(true);
        $reflection->invoke($mockContext, $queryString, $data);
    }

    /**
     * @return array
     */
    protected function getParameters()
    {
        return [
            'authentication' => [
                'apiClient' => 'client',
                'apiKey'    => 'theSecretKey',
            ],
            'root_path'      => $this->getRootPath(),
        ];
    }

    /**
     * @return string
     */
    private function getRootPath()
    {
        chdir('..');

        return getcwd();
    }

    /**
     * return array
     */
    public function getData()
    {
        return [
            [
                new InputData(),
            ],
        ];
    }
}
