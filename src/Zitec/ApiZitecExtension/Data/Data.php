<?php

namespace Zitec\ApiZitecExtension\Data;

/**
 * Class Data
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class Data
{
    /**
     * @var Data|null
     */
    private static $instance = null;
    /**
     * @var LoadData $dataLoaded
     */
    private $dataLoaded = null;

    /**
     * Data constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return Data
     */
    static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new Data;
        }
        return self::$instance;
    }

    /**
     * @param LoadData $dataLoaded
     */
    public function setDataLoaded(LoadData $dataLoaded)
    {
        $this->dataLoaded = $dataLoaded;
    }

    /**
     * @return LoadData
     */
    public function getDataLoaded()
    {
        return $this->dataLoaded;
    }

    /**
     * @param string $dataSet
     * @param array $values
     */
    public function addDataToDataSet($dataSet, array $values)
    {
        $this->dataLoaded->addDataToDataSet($dataSet, $values);
    }

    /**
     * @param string $method
     * @param string $dataSet
     * @return array
     */
    public function getDataForRequest($method, $dataSet)
    {
        return $this->dataLoaded->getDataForRequest($method, $dataSet);
    }

    /**
     * @param string $dataSet
     * @return array
     */
    public function getResponseData($dataSet)
    {
        return $this->dataLoaded->getResponseData($dataSet);
    }
}
