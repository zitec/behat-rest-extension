<?php

namespace Zitec\ApiZitecExtension\Services\Response;

use Zitec\ApiZitecExtension\Util\TypeChecker;

/**
 * Class JsonResponse
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class JsonResponse implements Response
{
    /**
     * The actual result the API returns
     *
     * @var string
     */
    protected $rawResponse;

    /**
     * The result returned by API decoded.
     *
     * @var array
     */
    protected $response;

    /**
     * @var array
     */
    protected $responseHeaders;

    /**
     * JsonResponse constructor.
     * @param $stringResponse
     * @param array $responseHeaders
     */
    public function __construct($stringResponse, array $responseHeaders)
    {
        $this->rawResponse = $stringResponse;
        $this->response = json_decode($stringResponse, true);
        $this->responseHeaders = $responseHeaders;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRawResponse()
    {
        return $this->rawResponse;
    }

    /**
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return array
     */
    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    /**
     * Returns a response headers by given $name.
     * Returns null if there is no header with that given name.
     *
     * @param string $name
     * @return string|null
     */
    public function getResponseHeader($name)
    {
        if (isset($this->responseHeaders[$name])) {
            return $this->responseHeaders[$name];
        }

        return null;
    }

    /**
     * Checks if the response is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        if (!empty($this->response)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the response match the expected structure.
     *
     * @param array $expectedStructure
     * @throws \Exception
     */
    public function matchStructure(array $expectedStructure)
    {
        $checker = new TypeChecker();
        $checked = $checker->checkType($this->response, $expectedStructure);
        if (!empty($checked)) {
            throw new \Exception(sprintf("The following values do not match the expected type: %s", json_encode($checked)));
        }
    }

    /**
     * Check if the response match the expected response.
     * If not the exception message will show the differences.
     *
     * @param array $expectedResponse
     * @throws \Exception
     */
    public function matchResponse(array $expectedResponse)
    {
        if ($this->response != $expectedResponse) {
            $diffResponse = $this->responseDiff($this->response, $expectedResponse);
            $diffExpected = $this->responseDiff($expectedResponse, $this->response);
            $differences = array_merge($diffExpected, $diffResponse);
            $message = sprintf("The response doesn't meet the expectations.
             There have been found differences on: %s", json_encode($differences));
            throw new \Exception($message);

        }
    }

    /**
     * Returns the differences between $array1 and $array2
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    private function responseDiff($array1, $array2)
    {
        $difference = array();
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key]) || !is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = $this->responseDiff($value, $array2[$key]);
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
     * Return the response type.
     *
     * @return string
     */
    public function getType()
    {
        return "json";
    }


    /**
     * Walk through the response to retrieve the specific key given as $path.
     *
     * @param string $path
     * @return mixed
     * @throws \Exception
     */
    public function getItem($path)
    {
        $sqBracket = strpos($path, '[');
        if ($sqBracket !== false) {
            $indexes = $this->getIndexOutOfString($path);
            $response = $this->response;
            foreach ($indexes as $index) {
                if (isset($response[$index])) {
                    $response = $response[$index];
                    continue;
                }
                throw new \Exception(sprintf('Key %s not found in response.', $path));
            }

            return $response;

        } else {
            if (!isset($response[$path])) {
                throw new \Exception(sprintf('Key %s not found in response.', $path));
            }

            return $response[$path];
        }
    }

    /**
     * @param string $string
     * @return array
     */
    protected function getIndexOutOfString($string)
    {
        $indexes = array_map(
            function ($item) {
                return trim($item, ']');
            },
            explode('[', $string)
        );

        return $indexes;
    }
}
