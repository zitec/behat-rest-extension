<?php

namespace Zitec\ApiZitecExtension\Services;



class Headers
{
    /**
     * @var string
     */
    protected $timeDifference;

    /**
     * All headers
     * @var array
     */
    protected $initialHeaders;

    /**
     * @var array
     */
    protected $authParams;

    /**
     * All headers with appropriate values
     * @var array
     */
    protected $headers;


    /**
     * @param array $authentication
     */
    public function setAuthParams(array $authentication)
    {
        $this->authParams = $authentication;
    }

    /**
     * @return array
     */
    public function getAuthParams()
    {
        return $this->authParams;
    }



    /**
     * @param string $method
     * @param string $queryString
     */
    public function generateHeaders($method, $queryString)
    {
        $authHeaders = $this->getAuth($method, $queryString);

        foreach ($this->initialHeaders as $key => $value) {
            if (isset($authHeaders[$value])) {
                $this->headers[$key] = $authHeaders[$value];
            } else {
                $this->headers[$key] = $value;
            }
        }
    }

    /**
     * @param string $httpVerb
     * @param string $queryString
     * @return array
     */
    public function getAuth($httpVerb, $queryString)
    {
        if (isset($this->authParams['auth_type'])) {
            $type = $this->authParams['auth_type'];
        } else {
            $type = "key";
        }
        $authFactory = new AuthenticationFactory();
        $auth = $authFactory->createAuth($type, $this->authParams, $httpVerb, $queryString, $this->timeDifference);
        return $auth->getAuthHeaders();
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
     * @param string $name
     * @param string $value
     */
    public function addHeader($name, $value)
    {
        $this->initialHeaders[$name] = $value;
    }

    /**
     * Remove header with given key.
     * Will remove the header both from $this->initialHeaders and $this->headers (headers after processing)
     *
     * @param string $name
     */
    public function removeHeader($name)
    {
        if (isset($this->headers[$name])) {
            unset($this->headers[$name]);
        }
        if (isset($this->initialHeaders[$name])) {
            unset($this->initialHeaders[$name]);
        }
    }

    /**
     * @param array $headers
     */
    public function setInitialHeaders(array $headers)
    {
        $this->initialHeaders = $headers;
    }


    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return array
     */
    public function getInitialHeaders()
    {
        return $this->initialHeaders;
    }
}
