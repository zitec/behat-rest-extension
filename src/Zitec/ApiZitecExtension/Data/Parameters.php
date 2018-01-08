<?php

namespace Zitec\ApiZitecExtension\Data;

/**
 * Class Parameters
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 * @property $root_path
 */
class Parameters
{
    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var array
     */
    protected $authentication = [];

    /**
     * @var array
     */
    protected $seenHeaders = [];

    /**
     * @var string
     */
    protected $requestMethod = 'GET';

    /**
     * @var string
     */
    protected $timeDifference;

    /**
     * Set initial parameters.
     *
     * @param array $parameters
     */
    public function setup(array $parameters)
    {
        $this->headers = [];
        if (!empty($parameters)) {
            foreach ($parameters as $key => $param) {
                if ($key == 'headers') {
                    $this->setHeaders($param);
                } else {
                    $this->$key = $param;
                }
            }
        }
    }

    /**
     * Removed header by given name.
     *
     * @param string $name
     */
    public function removeHeader($name)
    {
        unset($this->headers[$name]);
    }

    /**
     * Adds a header with given name and value and marks it as seen.
     *
     * @param string $name
     * @param string $value
     */
    public function addHeader($name, $value)
    {
        $this->seenHeaders[] = $name;
        $this->headers[$name] = $value;
    }

    /**
     * @return array
     */
    public function getAuthentication()
    {
        return $this->authentication;
    }

    /**
     * @param $authentication
     */
    public function setAuthentication($authentication)
    {
        $this->authentication = $authentication;
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
        $this->requestMethod = strtoupper($requestMethod);
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders($headers)
    {
        foreach ($headers as $name => $value) {
            $this->addHeader($name, $value);
        }
    }

    /**
     * @return array
     */
    public function getSeenHeaders()
    {
        return array_unique($this->seenHeaders);
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
}
