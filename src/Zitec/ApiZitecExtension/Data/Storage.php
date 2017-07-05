<?php

namespace Zitec\ApiZitecExtension\Data;

use Behat\Testwork\Suite\Exception\ParameterNotFoundException;

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
     * @var mixed
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
        if(isset($this->container[$key])) {
            return $this->container[$key];
        }

        throw new ParameterNotFoundException("No parameter $key found in storage.", "Element not found in storage", $key);
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
     * @return mixed
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * @param mixed $lastResponse
     */
    public function setLastResponse($lastResponse)
    {
        $this->lastResponse = $lastResponse;
    }
}
