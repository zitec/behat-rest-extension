<?php

/**
 * Created by PhpStorm.
 * User: bianca.vadean
 * Date: 1/11/2016
 * Time: 1:00 PM
 */

namespace Zitec\ApiZitecExtension\Data;

use Nelmio\Alice\Fixtures\Parser\Methods\Yaml;

class LoadData
{

    protected $data;
    protected $dataSet;
    protected $rootPath;

    public function __construct ($rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * @param $file
     * @param string $defaultLocale default locale to use with faker if none is
     *                  specified in the expression
     * @return $this
     * @throws \Exception
     */
    public function loadData ($file, $defaultLocale = "ro_RO")
    {
        $this->checkFileFormat($file);
        $yaml = new Yaml();
        $fileInfo = $this->checkFileFormat($file);
        $data = $yaml->parse($this->createAbsolutePath($fileInfo));
        $loader = new ZitecLoader($defaultLocale, [], null);

        if (array_key_exists('request', $data) && !empty($data['request']['Zitec\ApiZitecExtension\Data\Request'])) {
            $this->data['request'] = $loader->load($data['request']);
        }

        if (array_key_exists('response', $data) && !empty($data['response']['Zitec\ApiZitecExtension\Data\Response'])) {
            $this->data['response'] = $loader->load($data['response']);
        }

        return $this;
    }

    /**
     * Check if the yml format was set
     * If not, add the extensions
     *
     * @param string $file
     * @return string filename
     * */
    protected function checkFileFormat ($file)
    {
        //take file extension
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        //append yml extension
        if (empty($extension)) {
            $file = $file . '.yml';
        }
        return $file;
    }

    /**
     * @param $file
     * @return string $path, absolute path for the file given
     * @throws \Exception
     */
    protected function createAbsolutePath ($file)
    {
        $path = $this->rootPath . '/features/data/' . $file;
        if (!$path) {
            throw new \Exception("File {$file} not found in {$path}.");
        }

        return $path;
    }

    /**
     * @param $requestMethod
     * @param $dataSet
     * @return array
     * @throws \Exception
     */
    public function getDataForRequest ($requestMethod, $dataSet)
    {
        $requestData = $this->data['request'];
        $requestMethod = strtolower($requestMethod);
        $this->dataSet = $dataSet;

        if (!array_key_exists($dataSet, $requestData)) {
            throw new \Exception("Dataset \"{$dataSet}\" not found in request section from the data file.");
        }

        if (!array_key_exists('get', $requestData[$dataSet])) {
            $data['get'] = array();
        }

        if (!array_key_exists('post', $requestData[$dataSet])) {
            $data['post'] = array();
        }

        if (empty($data['get']) && empty($data['post'])) {
            $requestDataSet = (array)$requestData[$dataSet];
            $data[$requestMethod] = $requestDataSet;
        }
        // Set files for request.
        $data = $this->setFiles($data);
        // Encode base64 images.
        $data = $this->encodeImages($data);
        return $data;
    }

    /**
     * Create real path for files and set them under $data['files'] key.
     *
     * @param $data array
     * @return array
     */
    private function setFiles ($data)
    {
        $data['files'] = null;
        array_walk_recursive($data, function ($value, $key) use (&$data) {
            if (strpos($value, '@') === 0) {
                $data['files'][$key] = realpath(substr($value, 1));
                unset($data[$key]);
            }
        });
        return $data;
    }

    /**
     * Encode base64 images identified by base64_encode(@/path_to_image).
     *
     * @param $data array
     * @return array
     */
    private function encodeImages ($data)
    {
        array_walk_recursive($data, function (&$value) {
           if (strpos($value, 'base64_encode(') === 0) {
               // Create real path after remove the base64_encode identifiers.
               $imgPath = realpath(substr(ltrim((rtrim($value, ')')), 'base64_encode('), 1));
               $binaryImage = fread(fopen($imgPath, 'r'), filesize($imgPath));
               $value = base64_encode($binaryImage);
           }
        });
        return $data;
    }

    public function addDataToDataSet ($dataSet, array $values)
    {
        $set = array_merge((array)$this->data['request'][$dataSet], $values);
        $this->data['request'][$dataSet] = (object)$set;
    }

    public function getResponseData ($dataSet)
    {

        if ($dataSet == null) {
            $dataSet = $this->dataSet;
        }

        if (!array_key_exists($dataSet, $this->data['response'])) {
            throw new \Exception("Dataset \"{$dataSet}\" not found in response section from the data file.");
        }
        $responseData = (array)$this->data['response'][$dataSet];
        return $responseData;
    }

    public function getData ()
    {
        return $this->data;
    }
}
