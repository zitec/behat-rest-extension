<?php

namespace Zitec\ApiZitecExtension\Services\Auth;


class TokenAuthentication extends Authentication
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
    protected $token;
    /**
     * @var string
     */
    protected $secret;
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
    protected $timeDifference;
    /**
     * @var string
     */
    protected $signatureMessage;

    public function __construct($apiKey, $apiClient, $token, $secret, $httpVerb, $queryString, $timeDifference)
    {
        $this->apiKey = $apiKey;
        $this->apiClient = $apiClient;
        $this->token = $token;
        $this->secret = $secret;
        $this->httpVerb = $httpVerb;
        $this->queryString = $queryString;
        parent::__construct($timeDifference);

    }

    public function getAuthHeaders()
    {
        $authHeaders = [];
        $authHeaders['date'] = $this->date;
        $authHeaders['apiClient'] = $this->apiClient;
        $authHeaders['sha1'] = $this->getSignatureString();
        $authHeaders['token'] = $this->token;
        return $authHeaders;
    }

    /**

     * @return string
     */
    protected function getSignatureString()
    {
        $message = strtoupper($this->httpVerb) . trim(urldecode($this->queryString),
                '/') . '/' . $this->apiClient . $this->date;
        if ($this->token) {
            $message .= $this->secret;
        }
        $this->signatureMessage = $message;
        return $this->hmacSha1($this->apiKey, $message);
    }
}
