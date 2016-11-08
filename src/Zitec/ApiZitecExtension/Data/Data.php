<?php
/**
 * Created by PhpStorm.
 * User: bianca.vadean
 * Date: 6/16/2016
 * Time: 12:04 PM
 */

namespace Zitec\ApiZitecExtension\Data;


class Data
{
    private static $instance = null;
    /**
     * @var LoadData $dataLoaded
     */
    private $dataLoaded= null;

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
    
    public function setDataLoaded($dataLoaded)
    {
        $this->dataLoaded = $dataLoaded;
    }
    
    public function getDataLoaded()
    {
        return $this->dataLoaded;
    }
    
    public function addDataToDataSet($dataSet, $values)
    {
        $this->dataLoaded->addDataToDataSet($dataSet, $values);
    }
    
    public function getDataForRequest($method, $dataSet)
    {
        return $this->dataLoaded->getDataForRequest($method, $dataSet);
    }
    
    public function getResponseData($dataSet)
    {
        return $this->dataLoaded->getResponseData($dataSet);
    }
}
