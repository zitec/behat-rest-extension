<?php

namespace Zitec\ApiZitecExtension\Services\Authentication\Algorithms;

/**
 * Class TokenAuthentication
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class TokenAuthentication extends AbstractAlgorithm
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

    /**
     * TokenAuthentication constructor.
     *
     * @param string $apiKey
     * @param string $apiClient
     * @param string $token
     * @param string $secret
     * @param string $httpVerb
     * @param string $queryString
     * @param string $timeDifference
     */
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

    /**
     * @return array
     */
    public function getAuthHeaders()
    {
        $authHeaders = [
            'date'      => $this->date,
            'apiClient' => $this->apiClient,
            'sha1'      => $this->getSignatureString(),
            'token'     => $this->token,
        ];
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
