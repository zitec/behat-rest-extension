<?php

namespace Zitec\ApiZitecExtension\Services\Authentication;

use Zitec\ApiZitecExtension\Services\Authentication\Algorithms\AbstractAlgorithm;
use Zitec\ApiZitecExtension\Services\Authentication\Algorithms\KeyAuthentication;
use Zitec\ApiZitecExtension\Services\Authentication\Algorithms\TokenAuthentication;

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
     * @return AbstractAlgorithm
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
     * @return AbstractAlgorithm
     * @throws \Exception
     */
    public function createTokenAuth($parameters, $httpVerb, $queryString, $timeDifference)
    {
        $values = [
            'apiKey' => null,
            'apiClient' => null,
            'tokenValue' => null,
            'secretValue' => null,
        ];
        $this->populateInputData($values, $parameters);

        list($apiKey, $apiClient, $token, $secret) = array_values($values);

        return new TokenAuthentication($apiKey, $apiClient, $token, $secret, $httpVerb, $queryString, $timeDifference);
    }

    /**
     * @param array $parameters
     * @param string $httpVerb
     * @param string $queryString
     * @param string $timeDifference
     * @return AbstractAlgorithm
     * @throws \Exception
     */
    public function createKeyAuth($parameters, $httpVerb, $queryString, $timeDifference)
    {
        $values = [
            'apiKey' => null,
            'apiClient' =>null,
        ];
        $this->populateInputData($values, $parameters);

        list($apiKey, $apiClient) = array_values($values);

        return new KeyAuthentication($apiKey, $apiClient, $httpVerb, $queryString, $timeDifference);
    }

    /**
     * @param array $structure
     * @param array $parameters
     * @throws \Exception
     */
    protected function populateInputData(&$structure, $parameters)
    {
        $error = [];
        foreach ($structure as $key => $value) {
            if (isset($parameters[$key])) {
                $structure[$key] = $parameters[$key];
            } else {
                $error[] = $key;
            }
        }

        if (!empty($error)) {
            throw new \Exception("The following authentication parameters are missing: " . implode(' ,', $error));
        }
    }
}
