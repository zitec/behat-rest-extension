<?php

namespace Zitec\ApiZitecExtension\Data;

use Behat\Testwork\Suite\Exception\ParameterNotFoundException;
use Zitec\ApiZitecExtension\Services\Response\Response;

/**
 * Class Storage
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class Storage
{
    private static $instance = null;
    /**
     * @var array $container
     */
    private $container = [];

    /**
     * @var Response
     */
    private $lastResponse;

    /**
     * Storage constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return null|Storage
     */
    static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new Storage();
        }

        return self::$instance;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function storeValue($key, $value)
    {
        $this->container[$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getValue($key)
    {
        if (isset($this->container[$key])) {
            return $this->container[$key];
        }

        throw new ParameterNotFoundException(
            "No parameter $key found in storage.", "Element not found in storage", $key
        );
    }

    /**
     * @param string $key
     * @return bool
     */
    public function valueExists($key)
    {
        if (isset($this->container[$key])) {
            return true;
        }

        return false;
    }

    /**
     * @return Response
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * @param Response $lastResponse
     */
    public function setLastResponse(Response $lastResponse)
    {
        $this->lastResponse = $lastResponse;
    }

    /**
     * @param string $value
     * @return string
     * @throws \Exception
     */
    public function getResponseItem($value)
    {
        if (isset($this->lastResponse)) {
            return $this->getLastResponse()->getContent()->getItem($value);
        }

        throw new \Exception('No response stored.');
    }
}
