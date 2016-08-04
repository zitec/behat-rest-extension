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
     * @var LoadData $container
     */
    private $data = null;

    private function __construct()
    {
    }

    static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new Data;
        }
        return self::$instance;
    }
    
    public function setData($data)
    {
        $this->data = $data;
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    public function addDataToDataset($dataset, $values)
    {
        $this->data->addDataToDataset($dataset, $values);
    }
    
    public function getDataForRequest($method, $dataset)
    {
        return $this->data->getDataForRequest($method, $dataset);
    }
    
    public function getResponseData($dataset)
    {
        return $this->data->getResponseData($dataset);
    }
}
