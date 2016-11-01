<?php

namespace Zitec\ApiZitecExtension\Services;

use Behat\Mink\Driver\Goutte\Client;

class Request
{
    /**
     * @var string POST|DELETE|GET|PUT
     */
    protected $requestMethod = 'GET';

    /**
     * @var Headers
     */
    protected $headers;

    public function __construct()
    {
        $this->headers = new Headers();
    }

    /**
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * @param string $requestMethod
     */
    public function setRequestMethod($requestMethod)
    {
        $this->requestMethod = $requestMethod;
    }

    /**
     * @return Headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param Headers $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }


    /**
     * Remove the token header.
     *
     * @param Client $client
     */
    public function resetTokens(Client $client)
    {
        $token = $this->headers->getToken();
        $this->headers->removeHeader($token);
        $client->removeHeader($token);
    }

    /**
     * @param string $queryString
     * @param array $data
     * @param Client $client
     */
    public function request($queryString, array $data, Client $client)
    {
        $files = isset($data['files']) ? $data['files'] : [];
        if (!empty($data['get'])) {
            $queryString = trim($queryString, '/') . '/?' . http_build_query($data['get'],  null, '&', PHP_QUERY_RFC3986);
        }

//        $client->request();
    }
}
