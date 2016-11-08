<?php

namespace Zitec\ApiZitecExtension\Services;


class KeyAuthentication extends Authentication
{
    /**
     * @var string
     */
    protected $apiKey;
    /**
     * @var string
     */
    protected $apiClient;
    /**
     * @var string
     */
    protected $httpVerb;
    /**
     * @var string
     */
    protected $queryString;
    /**
     * @var string
     */
    protected $signatureMessage;

    /**
     * KeyAuthentication constructor.
     * @param string $apiKey
     * @param string $apiClient
     * @param string $httpVerb
     * @param string $queryString
     * @param string $timeDifference
     */
    public function __construct($apiKey, $apiClient, $httpVerb, $queryString, $timeDifference)
    {
        $this->apiKey = $apiKey;
        $this->apiClient = $apiClient;
        $this->httpVerb = $httpVerb;
        $this->queryString = $queryString;
        parent::__construct($timeDifference);
    }

    /**
     * @inheritdoc
     *
     * @return array
     */
    public function getAuthHeaders()
    {
        $authHeaders = [];
        $authHeaders['date'] = $this->date;
        $authHeaders['apiClient'] = $this->apiClient;
        $authHeaders['sha1'] = $this->getSignatureString();
        return $authHeaders;
    }

    /**

     * @return string
     */
    protected function getSignatureString()
    {
        $message = strtoupper($this->httpVerb) . trim(urldecode($this->queryString),
                '/') . '/' . $this->apiClient . $this->date;
        $this->signatureMessage = $message;
        return $this->hmacSha1($this->apiKey, $message);
    }
}
