<?php
/**
 * Created by PhpStorm.
 * User: bianca.vadean
 * Date: 1/11/2016
 * Time: 11:14 AM
 */

namespace Zitec\ApiZitecExtension\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\MinkExtension\Context\MinkContext;
use Symfony\Component\Config\Definition\Exception\Exception;
use Zitec\ApiZitecExtension\Data\Data;
use Zitec\ApiZitecExtension\Data\LoadParameters;
use Zitec\ApiZitecExtension\Data\LoadData;
use Behat\Mink\Driver\Goutte\Client;
use Zitec\ApiZitecExtension\Data\Storage;
use Zitec\ApiZitecExtension\Util\TypeChecker;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;
use PHPUnit_Framework_Assert;

class RestContext extends MinkContext implements SnippetAcceptingContext
{
    const HDR_TOKEN_DEPRECATED = 'userId';
    const HDR_TOKEN = 'token';

    protected $restObjectMethod = 'get';
    protected $modifyDate = '+0 seconds';
    protected $queryString;
    protected $response = array();
    protected $expectedResponse = array();
    protected $authentication = array();
    protected $headers = array();
    protected $ignoreHeaders = array();
    protected $debug = false;
    protected $token = false;
    protected $secret = false;
    protected $signatureMessage;
    protected static $storeResponse = array();
    /**
     * @var Storage
     */
    protected $storage;
    protected $defaultLocale = "ro_RO";


    public function __construct($parameters = array())
    {
        $this->params = new LoadParameters($parameters);
        if (!isset($this->params->authentication)) {
            throw new \Exception("You must define your authentication parameters in behat.yml.");
        }

        if (!isset($this->params->headers)) {
            throw new \Exception("You must define your headers parameters in behat.yml.");
        }
        $this->setCredentials($this->params->authentication);
        $this->storage = Storage::getInstance();
        $this->data = Data::getInstance();
    }

    /**
     * @param $credentials
     */
    protected function setCredentials($credentials)
    {
        if (!isset($credentials->apiClient)) {
            $this->authentication['apiClient'] = getenv('apiClient');
        } else {
            $this->authentication['apiClient'] = $credentials->apiClient;
        }

        if (!isset($credentials->apiKey)) {
            $this->authentication['apiKey'] = getenv('apiKey');
        } else {
            $this->authentication['apiKey'] = $credentials->apiKey;
        }
    }

    /**
     * @Given /^(?:|I )set the request method to (POST|DELETE|GET|PUT)$/
     */
    public function iSetTheRequestMethod($objectType)
    {
        $this->restObjectMethod = $objectType;
    }

    /**
     * @Given /^(?:|I )load data from file "([^"]*)"$/
     */
    public function iLoadDataFromFile($file)
    {
        $loader = new LoadData($this->params->root_path);
        $data = $loader->loadData($file, $this->defaultLocale);
        $this->data->setData($data);
    }


    /**
     * @Given /^I add the following headers:$/
     */
    public function iAddTheFollowingHeaders(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            list($header, $headerValue) = $row;
            $this->getSession()->getDriver()->getClient()->setHeader($header, $headerValue);
        }
    }

    /**
     * @Given /^I reset the access tokens/
     */
    public function iResetTheAccessTokens()
    {
        $this->secret = $this->token = false;
        $header = null;
        foreach ($this->params->headers as $key => $value) {
            if ($value == self::HDR_TOKEN_DEPRECATED || $value == self::HDR_TOKEN) {
                $header = $key;
                break;
            }
        }
        if ($header) {
            $this->getSession()->getDriver()->getClient()->removeHeader($header);
        } else {
            throw new \Exception('Missing token header declaration in config.');
        }
    }


    /**
     * @When /^(?:|I )request "([^"]*)"(?:| with dataset "([^"]*)")$/
     */
    public function iRequest($queryString, $dataSet = false)
    {
        $data[strtolower($this->restObjectMethod)] = array();
        $files = [];

        if ($dataSet) {
            if (!property_exists($this, 'data')) {
                throw new \Exception("A file with data must be loaded before using this method.");
            }
            $data = $this->data->getDataForRequest($this->restObjectMethod, $dataSet);
            $files = isset($data['files']) ? $data['files'] : [];
        }
        $this->queryString = $queryString;
        if (!empty($data['get'])) {
            $this->queryString = trim($this->queryString, '/') . '/?' . http_build_query($data['get'], null, '&', PHP_QUERY_RFC3986);
            $data['get'] = [];
        }
        $this->setAuthHeaders();
        $client = $this->getSession()->getDriver()->getClient();
        $client->request(
            strtoupper($this->restObjectMethod), $this->locatePath($this->queryString),
            $data[strtolower($this->restObjectMethod)], $files
        );

        if ($this->debug == true) {
            print $this->getSession()->getPage()->getContent();

        }
        $this->storage->setLastResponse($this->getSession()->getPage()->getContent());
    }


    /**
     * @Given /^the response is (JSON|XML|empty)$/
     */
    public function theResponseIsJson($responseType)
    {
        switch ($responseType) {
            case 'JSON':
                $this->checkJsonResponse();
                break;
            case 'XML':
                $this->checkXMLResponse();
                break;
            case 'empty':
                $this->theResponseIsEmpty();
                break;
            default:
                throw new \Exception("Invalid format for response type. Expected JSON, XML or empty");
        }
    }

    /**
     * @Then /^I extract access token from the response$/
     */
    public function iExtractAccessTokenFromTheResponse()
    {
        $token = @$this->params->login->token ?: 'user_id';
        $secret = @$this->params->login->secret ?: 'access_token';
        $response = json_decode($this->storage->getLastResponse(), true);
        if (empty($response)) {
            throw new \Exception("Response was not JSON\n" . $this->storage->getLastResponse());
        }
        if (isset($response[$token])) {
            $this->token = $response[$token];
        } else {
            throw new \Exception("Response is missing '$token'\n" . $this->storage->getLastResponse());
        }
        if (isset($response[$secret])) {
            $this->secret = $response[$secret];
        } else {
            throw new \Exception("Response is missing '$secret'\n" . $this->storage->getLastResponse());
        }
    }

    /**
     * @Then /^the response match the expected structure(?:| from "([^"]*)" dataset)$/
     * @Then /^each response from the collection match the expected structure(?:| from "([^"]*)" dataset)$/
     */
    public function theResponseMatchExpectedStructure($dataSet = null)
    {
        $expectedResponse = $this->data->getResponseData($dataSet);
        $response = is_array($this->storage->getLastResponse()) ? $this->storage->getLastResponse() : json_decode($this->storage->getLastResponse(),
            true);
        $checker = new TypeChecker();
        $checked = $checker->checkType($response, $expectedResponse);
        if (!empty($checked)) {
            throw new \Exception(sprintf('The following values do not match the expected type: %s',
                json_encode($checked)));
        }
    }

    protected function compareResponse($actualResponse, $expectedResponse)
    {
        if (!$this->checkArrayStructure($actualResponse, $expectedResponse)) {
            $message = 'The response doesn\'t contain all the expected parameters.';
            $message .= 'Response: ' . json_encode($actualResponse) . PHP_EOL;
            $message .= 'Expected response: ' . json_encode($expectedResponse) . PHP_EOL;

            throw new \Exception($message);
        }

        if (!$this->checkArrayStructure($expectedResponse, $actualResponse)) {
            $message = 'The response contains parameters that are not expected.';
            $message .= 'Response: ' . json_encode($actualResponse) . PHP_EOL;
            $message .= "Expected response: " . json_encode($expectedResponse) . PHP_EOL;

            throw new \Exception($message);
        }
    }


    /**
     * @Given /^the response match the expected response(?:| from "([^"]*)" dataset)$/
     */
    public function theResponseMatchTheExpectedResponse($dataSet = null)
    {
        $expectedResponse = $this->data->getResponseData($dataSet);
        if ($this->storage->getLastResponse() != $expectedResponse) {
            $diffResponse = $this->array_diff_assoc_recursive($this->storage->getLastResponse(), $expectedResponse);
            $diffExpected = $this->array_diff_assoc_recursive($expectedResponse, $this->storage->getLastResponse());
            $message = "The response doesn't meet the expected response. \nData received but not expected: " . json_encode($diffResponse)
                . "\nData expected but not received: " . json_encode($diffExpected);
            throw new \Exception($message);
        }

    }

    function array_diff_assoc_recursive($array1, $array2)
    {
        $difference = array();
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key]) || !is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = $this->array_diff_assoc_recursive($value, $array2[$key]);
                    if (!empty($new_diff)) {
                        $difference[$key] = $new_diff;
                    }
                }
            } else {
                if (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
                    $difference[$key] = $value;
                }
            }
        }
        return $difference;
    }

    /**
     *  Check the last response to be JSON.
     *  If the response is valid JSON the decoded response is stored.
     *
     * @throws \UnexpectedValueException
     */
    protected function checkJsonResponse()
    {
        if (is_string($this->storage->getLastResponse())) {
            $decodedResponse = json_decode($this->storage->getLastResponse(), true);
            if (json_last_error()) {
                throw new \UnexpectedValueException("Response is not valid JSON: \n" .json_encode($this->storage->getLastResponse() . ' (' . json_last_error_msg() . ')'));
            }
        } else {
            throw new \UnexpectedValueException('Response is not valid JSON: ' . json_encode($this->storage->getLastResponse()));
        }

        $this->storage->setLastResponse($decodedResponse);
    }

    protected function checkXMLResponse()
    {
        libxml_use_internal_errors(true);
        $xmlResponse = simplexml_load_string($this->storage->getLastResponse());
        if ($xmlResponse === false) {
            throw new \Exception("Response is not XML\n" . $this->storage->getLastResponse());
        }
    }

    protected function checkArrayStructure($result, $sample)
    {
        foreach ($sample as $key => $value) {

            if (!array_key_exists($key, $result)) {
                return false;
            }

            if (is_array($value) && !empty($value)) {
                if (!is_array($result[$key])) {
                    return false;
                }

                if (!$this->checkArrayStructure($result[$key], $value)) {
                    return false;
                }
            }
            unset($result[$key]);
        }
        return true;
    }

    /**
     * @BeforeFeature @deleteImagesTmp
     */
    public static function deleteImagesTmp()
    {
        $imagePattern = "/\.(jpg|jpeg|png|gif|bmp|tiff)$/";
        $directory = realpath("./features/data/images/tmp");

        if (($handle = opendir($directory)) != false) {
            while (($file = readdir($handle)) != false) {
                $filename = "$directory/$file";
                if (preg_match($imagePattern, $filename)) {
                    unlink($filename);
                }
            }

            closedir($handle);
        }
    }

    /**
     * @Given /^I set the apiKey "([^"]*)" and apiClient "([^"]*)"$/
     */
    public function iSetTheApikeyAndApiuser($apiKey, $apiClient)
    {
        $this->authentication['apiKey'] = $apiKey;
        $this->authentication['apiClient'] = $apiClient;
    }

    /**
     * @Given /^I set the following "([^"]*)" empty$/
     */
    public function iRemoveAnAuthHeader($headers)
    {
        $ignoredHeaders = array_map('trim', explode(',', $headers));
        $this->ignoreHeaders = array_flip($ignoredHeaders);
    }

    /**
     * @Given /^I modify the request time with "([^"]*)"$/
     */
    public function iAddToRequestTime($time)
    {
        $this->modifyDate = $time;
    }

    /**
     * Add headers to request
     * */
    protected function setAuthHeaders()
    {
        if ($this->params->headers == 'none') {
            return;
        }

        $this->generateHeaders($this->params->headers);
        if (!empty($this->ignoreHeaders)) {
            foreach ($this->headers as $key => $value) {
                $this->getSession()->getDriver()->getClient()->setHeader($key, '');
            }
            $this->headers = array_diff_key($this->headers, $this->ignoreHeaders);
        }

        foreach ($this->headers as $key => $value) {
            $this->getSession()->getDriver()->getClient()->setHeader($key, $value);
        }
    }

    protected function generateHeaders($headers)
    {
        $date = gmdate('r', strtotime(gmdate('r') . $this->modifyDate));
        foreach ($headers as $key => $value) {
            switch ($value) {
                case 'date':
                    $this->headers[$key] = $date;
                    break;
                case 'apiClient':
                    $this->headers[$key] = $this->authentication['apiClient'];
                    break;
                case 'sha1':
                    $this->headers[$key] = $this->getSignatureString($date);
                    break;
                case self::HDR_TOKEN_DEPRECATED:
                case self::HDR_TOKEN:
                    if ($this->token !== false) {
                        $this->headers[$key] = $this->token;
                    } else {
                        unset($this->headers[$key]);
                    }
                    break;
                default:
                    $this->headers[$key] = $value;
            }
        }
    }

    /**
     * Generate a HMAC-SHA1 encrypted string
     *
     * @param string $key
     * @param string $message
     * @return string
     * */
    protected static function hmacSha1($key, $message)
    {
        $blocksize = 64;
        $opad = str_repeat(chr(0x5c), $blocksize);
        $ipad = str_repeat(chr(0x36), $blocksize);

        if (strlen($key) < $blocksize) {
            $key = $key . str_repeat(chr(0), ($blocksize - strlen($key)));
        }

        $hmac = sha1(($key ^ $opad) . sha1(($key ^ $ipad) . $message, true), true);

        return base64_encode($hmac);
    }

    /**
     * Generate a signature for authentication
     *
     * @param date $date
     * @return string
     *
     * */
    protected function getSignatureString($date)
    {
        $message = strtoupper($this->restObjectMethod) . trim(urldecode($this->queryString),
                '/') . '/' . $this->authentication['apiClient'] . $date;
        if ($this->token) {
            $message .= $this->secret;
        }
        $this->signatureMessage = $message;
        return $this->hmacSha1($this->authentication['apiKey'], $message);
    }

    /**
     * @Given /^I save the "([^"]*)" as "([^"]*)"$/
     */
    public function iSaveTheAs($responseKey, $varKey)
    {
        /**
         * $responseKey is the key of the element from the response you want to save.
         * $varKey is the key of the array where you'll store the response
         */
        $response = json_decode($this->storage->getLastResponse(), true);
        $this->storage->storeValue($varKey, $response[$responseKey]);
    }

    /**
     * The request should look like this: method/%d
     *
     * @When I request :request using :varKey with dataset :dataSet
     * @When I request :request using :varKey
     * @When I request :request with dataset :dataSet using:
     * @When I request :request using:
     */
    public function iRequestUsingWithDataset($request, $varKey, $dataSet = null)
    {
        if (is_a($varKey, '\Behat\Gherkin\Node\TableNode')) {
            $params = array();
            foreach ($varKey->getColumn(0) as $value) {
                $params[] = $this->storage->getValue($value);
            }
            $queryString = vsprintf($request, $params);
        } else {
            $param = $this->storage->getValue($varKey);
            $queryString = sprintf($request, $param);
        }

        $this->iRequest($queryString, $dataSet);
    }

    /**
     * Verify if the response field is populated and if yes throw exception
     * @throws \Exception
     */
    protected function theResponseIsEmpty()
    {
        if (!empty($this->response)) {
            $response = !is_string($this->response) ? json_encode($this->response) : $this->response;
            throw new \Exception("The content of the response is not empty!\n" . $response);
        }
    }

    /**
     * Makes a request on the path given in the location header and checks the response status code.
     *
     * @Then I check location header to return :arg1
     */
    public function checkLocationHeader($status)
    {
        $locationHeader = $this->getSession()->getResponseHeader('Location');
        $this->iSetTheRequestMethod('GET');
        $this->iRequest($locationHeader);
        if ($this->getSession()->getStatusCode() != $status) {
            $message = sprintf('The resource created return %s status code but %s was expected.',
                $this->getSession()->getStatusCode(), $status);
            throw new ExpectationException($message, $this->getSession()->getDriver());
        }
    }
}
