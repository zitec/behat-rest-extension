<?php

namespace Zitec\ApiZitecExtension\Services;


class Headers
{
    /**
     * @var null|string
     */
    protected $apiKey = null;

    /**
     * @var null|string
     */
    protected $apiClient = null;

    /**
     * @var string
     */
    protected $token = 'token';

    /**
     * @var string
     */
    protected $secret = 'secret';

    /**
     * @var
     */
    protected $timeDifference;

    /**
     * All headers.
     * @var array
     */
    protected $headers;

    /**
     * @param array $authentication
     */
    public function setCredentials(array $authentication)
    {
        if (isset($authentication['apiClient']) && isset($authentication['apiKey'])) {
            $this->apiClient = $authentication['apiClient'];
            $this->apiKey = $authentication['apiKey'];
        }
    }

    /**
     * @param array $headers
     * @throws \Exception
     */
    public function generateHeaders(array $headers)
    {
        $date = gmdate('r', strtotime(gmdate('r') . $this->timeDifference));
        foreach ($headers as $key => $value) {
            switch ($value) {
                case 'date':
                    $this->headers[$key] = $date;
                    break;
                case 'apiClient':
                    if (isset($this->credentials['apiClient'])) {
                        $this->headers[$key] = $this->credentials['apiClient'];
                    } else {
                        throw new \Exception(' "apiKey" not set in the authentication section.');
                    }
                    break;
                case 'sha1':
                    // TODO generate signature
//                    $this->headers[$key] = $this->getSignatureString($date);
                    break;
                // TODO manage token
//                case $this->token
//                    if ($this->token !== false) {
//                        $this->headers[$key] = $this->token;
//                    } else {
//                        unset($this->headers[$key]);
//                    }
//                    break;
                default:
                    $this->headers[$key] = $value;
            }
        }
    }

    /**
     *  Set parameters for login if set in the parameters from behat.yml (section login)
     *
     * @param array $login
     */
    public function setLogin(array $login)
    {
        if (isset($login['token']) && isset($login['secret']))
        {
            $this->token = $login['token'];
            $this->secret = $login['secret'];
        }
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    /**
     * @return mixed
     */
    public function getTimeDifference()
    {
        return $this->timeDifference;
    }

    /**
     * @param mixed $timeDifference
     */
    public function setTimeDifference($timeDifference)
    {
        $this->timeDifference = $timeDifference;
    }

    /**
     * @return null|string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param null|string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return null|string
     */
    public function getApiClient()
    {
        return $this->apiClient;
    }

    /**
     * @param null|string $apiClient
     */
    public function setApiClient($apiClient)
    {
        $this->apiClient = $apiClient;
    }



    /**
     * @param string $name
     * @param string $value
     */
    public function addHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * Remove header with given key.
     *
     * @param string $name
     */
    public function removeHeader($name)
    {
        unset($this->headers[$name]);
    }



}
