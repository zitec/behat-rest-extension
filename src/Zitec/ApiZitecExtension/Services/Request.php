<?php

namespace Zitec\ApiZitecExtension\Services;

use Goutte\Client;

/**
 * Class Request
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class Request
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * Request constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Cleans up the existing headers and sets the new ones.
     *
     * @param array $headers
     * @param array $seenHeaders
     */
    public function setHeaders($headers, $seenHeaders)
    {
        foreach ($seenHeaders as $sHeader) {
            $this->client->removeHeader($sHeader);
        }
        foreach ($headers as $name => $value) {
            $this->client->setHeader($name, $value);
        }
    }

    /**
     * @param string $baseUrl
     * @param string $queryString
     * @param string $requestMethod
     * @param array $data
     */
    public function request($baseUrl, $queryString, $requestMethod, array $data, array $headers)
    {
        if (!empty($data['get'])) {
            $queryString = $queryString . '?' . http_build_query($data['get'], null, '&',
                    PHP_QUERY_RFC3986);
        }

        $uri = $this->locatePath($baseUrl, $queryString);
        $httpVerb = strtolower($requestMethod);

        $files = isset($data['files']) ? $data['files'] : [];
        foreach ($headers as $key => $header) {
            $headers["HTTP_" . $key] = $header;
        }
        $headers['HTTP_Accept'] = 'json';
        $this->client->request($requestMethod, $uri, $data[$httpVerb], $files, $headers);
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
