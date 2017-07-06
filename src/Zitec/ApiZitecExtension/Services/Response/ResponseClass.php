<?php

namespace Zitec\ApiZitecExtension\Services\Response;

/**
 * Class ResponseClass
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class ResponseClass
{
    /**
     * @var string
     */
    protected $rawResponse;

    /**
     * @var array | null
     */
    protected $processedResponse = null;

    /**
     * @var integer
     */
    protected $statusCode;

    /**
     * @var array
     */
    protected $responseHeaders;


    /**
     * Response constructor.
     *
     * @param string $response
     */
    public function __construct($response)
    {
        $this->rawResponse = $response;
        if ($this->isJson($response)) {
            $this->processedResponse = json_decode($response, true);
        }
    }

    /**
     * @return string
     */
    public function getRawResponse()
    {
        return $this->rawResponse;
    }

    /**
     * @param string $rawResponse
     */
    public function setRawResponse($rawResponse)
    {
        $this->rawResponse = $rawResponse;
    }

    /**
     * @return array|null
     */
    public function getProcessedResponse()
    {
        return $this->processedResponse;
    }

    /**
     * @param array|null $processedResponse
     */
    public function setProcessedResponse($processedResponse)
    {
        $this->processedResponse = $processedResponse;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @return array
     */
    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    /**
     * @param array $responseHeaders
     */
    public function setResponseHeaders($responseHeaders)
    {
        $this->responseHeaders = $responseHeaders;
    }

    /**
     * Returns true if the $response is a valid JSON, else it returns false.
     *
     * @param string $response
     * @return bool
     */
    public function isJson($response)
    {
        if (is_string($response)) {
            json_decode($response, true);
            if (!json_last_error()) {
                return true;
            }
        }

        return false;
    }
}
