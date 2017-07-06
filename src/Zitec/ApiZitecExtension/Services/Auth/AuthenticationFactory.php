<?php

namespace Zitec\ApiZitecExtension\Services\Auth;

/**
 * Class AuthenticationFactory
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class AuthenticationFactory
{
    /**
     * @var array
     */
    protected $authType = [
        'token' => 'createTokenAuth',
        'key'   => 'createKeyAuth',
    ];

    /**
     * @param string $authType
     * @param array $parameters
     * @param string $httpVerb
     * @param string $queryString
     * @param string $timeDifference
     * @return Authentication
     * @throws \Exception
     */
    public function createAuth($authType, $parameters, $httpVerb, $queryString, $timeDifference)
    {
        if (isset($this->authType[$authType])) {
            $auth = call_user_func(
                [$this, $this->authType[$authType]],
                $parameters,
                $httpVerb,
                $queryString,
                $timeDifference
            );

            return $auth;
        }
        throw new \Exception("The registered authentication type ($authType) is not available.");
    }

    /**
     * @param array $parameters
     * @param string $httpVerb
     * @param string $queryString
     * @param string $timeDifference
     * @return Authentication
     * @throws \Exception
     */
    public function createTokenAuth($parameters, $httpVerb, $queryString, $timeDifference)
    {
        $error = [];
        $apiKey = null;
        $apiClient = null;
        $token = null;
        $secret = null;
        if (isset($parameters['apiKey'])) {
            $apiKey = $parameters['apiKey'];
        } else {
            $error[] = 'apiKey';
        }

        if (isset($parameters['apiClient'])) {
            $apiClient = $parameters['apiClient'];
        } else {
            $error[] = 'apiClient';
        }

        if (isset($parameters['tokenValue'])) {
            $token = $parameters['tokenValue'];
        } else {
            $error[] = 'token';
        }
        if (isset($parameters['secretValue'])) {
            $secret = $parameters['secretValue'];
        } else {
            $error[] = 'secret';
        }

        if (!empty($error)) {
            throw new \Exception("Authentication parameters are missing: " . json_encode($error));
        }

        return new TokenAuthentication($apiKey, $apiClient, $token, $secret, $httpVerb, $queryString, $timeDifference);
    }

    /**
     * @param array $parameters
     * @param string $httpVerb
     * @param string $queryString
     * @param string $timeDifference
     * @return Authentication
     * @throws \Exception
     */
    public function createKeyAuth($parameters, $httpVerb, $queryString, $timeDifference)
    {
        $error = [];
        $apiKey = null;
        $apiClient = null;
        if (isset($parameters['apiKey'])) {
            $apiKey = $parameters['apiKey'];
        } else {
            $error[] = 'apiKey';
        }

        if (isset($parameters['apiClient'])) {
            $apiClient = $parameters['apiClient'];
        } else {
            $error[] = 'apiClient';
        }

        if (!empty($error)) {
            throw new \Exception("Authentication parameters are missing: " . json_encode($error));
        }

        return new KeyAuthentication($apiKey, $apiClient, $httpVerb, $queryString, $timeDifference);
    }
}