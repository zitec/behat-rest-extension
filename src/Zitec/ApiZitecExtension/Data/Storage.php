<?php
/**
 * Created by PhpStorm.
 * User: bianca.vadean
 * Date: 6/15/2016
 * Time: 3:20 PM
 */

namespace Zitec\ApiZitecExtension\Data;

use Behat\Testwork\Suite\Exception\ParameterNotFoundException;

class Storage
{
    private static $instance = null;
    /**
     * @var array $container
     */
    private $container = [];
    
    private $lastResponse;

    private function __construct()
    {
    }

    static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new Storage();
        }
        return self::$instance;
    }

    /**
     * @param $key string
     * @param $value string
     */
    public function storeValue($key, $value)
    {
        $this->container[$key] = $value;
    }

    /**
     * @param $key string
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
     * @param $key string
     * @return bool
     */
    public function valueExists($key)
    {
        if (isset($this->container[$key])) {
            return true;

        } else {
            return false;
        }
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
