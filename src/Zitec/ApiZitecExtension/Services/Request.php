<?php

namespace Zitec\ApiZitecExtension\Services;

use Symfony\Component\BrowserKit\Client;

/**
 * Class Request
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class Request
{
    /** @var Client */
    protected $client;

    /** @var array */
    protected $headers;

    /**
     * Request constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->headers = [];
    }

    /**
     * @param string $baseUrl
     * @param string $queryString
     * @param string $requestMethod
     * @param array $data
     * @param array $serverParams
     */
    public function request($baseUrl, $queryString, $requestMethod, array $data, array $serverParams = [])
    {
        if (!empty($data['get'])) {
            $queryString = $queryString . '?' . http_build_query($data['get'], null, '&',
                    PHP_QUERY_RFC3986);
        }

        $uri = $this->locatePath($baseUrl, $queryString);
        $httpVerb = strtolower($requestMethod);

        $files = isset($data['files']) ? $data['files'] : [];

        $this->client->request($requestMethod, $uri, $data[$httpVerb], $files, $serverParams);
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
