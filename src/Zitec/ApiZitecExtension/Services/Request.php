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
     * @throws \Exception
     */
    public function resetTokens(Client $client)
    {
        $headers = $this->getHeaders()->getHeaders();
        $tokenHeader = "";
        if (isset($this->getHeaders()->getAuthParams()['token'])) {
            $tokenName = $this->getHeaders()->getAuthParams()['token'];
        } else {
            throw new \Exception("There is no token to reset.");
        }
        foreach ($headers as $key => $value) {
            if ($value ==  $tokenName) {
                $tokenHeader = $key;
                break;
            }
        }
        $this->headers->removeHeader($tokenHeader);
        $client->removeHeader($tokenHeader);
    }

    /**
     * @param string $queryString
     * @param array $data
     * @param Client $client
     * @param string $baseUrl
     */
    public function request($queryString, array $data, Client $client, $baseUrl)
    {
        $files = $this->getFiles($data);
        if (!empty($data['get'])) {
            $queryString = trim($queryString, '/') . '/?' . http_build_query($data['get'], null, '&',
                    PHP_QUERY_RFC3986);
        }

        $this->headers->generateHeaders($this->requestMethod, $queryString);
        $this->setClientHeaders($client);
        $uri = $this->locatePath($baseUrl, $queryString);
        $client->request($this->requestMethod, $uri, $data, $files);
    }

    /**
     * Set the headers to client
     *
     * @param Client $client
     */
    protected function setClientHeaders(Client $client)
    {
        foreach ($this->headers->getHeaders() as $name => $value) {
            $client->setHeader($name, $value);
        }
    }

    /**
     * Returns files for request.
     *
     * @param array $data
     * @return array
     */
    protected function getFiles($data)
    {
        if (isset($data['files'])) {
            return $data['files'];
        }
        return [];
    }

    /**
     * @param string $baseUrl
     * @param string $queryString
     * @return string
     */
    public function locatePath($baseUrl, $queryString)
    {
        $startUrl = rtrim($baseUrl, '/') . '/';

        return 0 !== strpos($queryString, 'http') ? $startUrl . ltrim($queryString, '/') : $queryString;
    }
}
