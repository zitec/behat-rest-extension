<?php

namespace Tests\Zitec\ApiZitecExtension\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Mink\Driver\GoutteDriver;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\Testwork\Environment\Environment;
use Goutte\Client;
use Symfony\Component\Console\Input\Input;
use Zitec\ApiZitecExtension\Context\RestContext;
use Zitec\ApiZitecExtension\Data\Data;
use Zitec\ApiZitecExtension\Data\LoadData;
use Zitec\ApiZitecExtension\Data\Parameters;
use Zitec\ApiZitecExtension\Data\Storage;
use Zitec\ApiZitecExtension\Services\Request;
use Tests\Zitec\ApiZitecExtension\Context\InputData;
use Tests\Zitec\ApiZitecExtension\Context\ExpectedData;
use Zitec\ApiZitecExtension\Services\Response\Compare;

/**
 * Class RestContextTest
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class RestContextTest extends \PHPUnit_Framework_TestCase
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
        $reflection = new \ReflectionClass(RestContext::class);
        $reflectedParameters = $reflection->getProperty('parameters');
        $reflectedParameters->setAccessible(true);
        $reflectedParameters->setValue($restContext, $this->parameters);
        $reflectedStorage = $reflection->getProperty('storage');
        $reflectedStorage->setAccessible(true);
        $reflectedStorage->setValue($restContext, $this->storage);
        $reflectedData = $reflection->getProperty('data');
        $reflectedData->setAccessible(true);
        $reflectedData->setValue($restContext, $this->data);
        $reflectedCompare = $reflection->getProperty('compare');
        $reflectedCompare->setAccessible(true);
        $reflectedCompare->setValue($restContext, $this->compare);

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
     * @param ExpectedData $expectedData
     */
    public function testISetTheRequestMethod(InputData $input, ExpectedData $expectedData)
    {
        $this->parameters->expects($this->once())
            ->method('setRequestMethod')
            ->with($input->http_method);

        $this->restContext->iSetTheRequestMethod($expectedData->httpMethod);
    }


    /**
     * @dataProvider getData
     * @param InputData $input
     */
    public function testILoadDataFromFile(InputData $input)
    {

//        $loader->expects($this->once())
//            ->method('loadData')
//            ->with([$input->data_file_name, 'ro_RO'])
//            ->willReturn($loader);
//        $reflection = new \ReflectionClass(RestContext::class);
//        $reflectionData = $reflection->getProperty('data');
//        $reflectionData->setAccessible(true);
//        /** @var Data $data */
//        $data = $reflectionData->getValue($this->restContext);
//        $data->setDataLoaded($loader);
//
//        $this->restContext->iLoadDataFromFile($input->data_file_name);
//        $reflectionTest = new \ReflectionClass(RestContext::class);
//        $reflectionDataTest = $reflectionTest->getProperty('data');
//        $reflectionDataTest->setAccessible(true);
//        /** @var Data $dataTest */
//        $dataTest = $reflectionDataTest->getValue($this->restContext);
//        $loadedData = $dataTest->getDataLoaded();
//        $this->assertInstanceOf(LoadData::class, $loadedData);


//        $loader = $this->getMockBuilder(LoadData::class)
//            ->disableOriginalConstructor()
//            ->getMock();

//        $loader->expects($this->once())
//            ->method('loadData')
//            ->with(
//                 [$input->data_file_name,
//                     "ro_RO"]
//            )
//            ->willReturnSelf();

        // We need to replace the new LoadData and inject the object 
        $this->data
            ->expects($this->once())
            ->method('setDataLoaded')
            ->with($this->isInstanceOf(LoadData::class));

        $this->restContext->iLoadDataFromFile($input->data_file_name);
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
                new ExpectedData(),
            ],
        ];
    }
}
