<?php

namespace Zitec\ApiZitecExtension\Data;

use Nelmio\Alice\Loader\NativeLoader;
use Nelmio\Alice\Parser\Chainable\YamlParser;
use Symfony\Component\Yaml\Parser;

/**
 * Class LoadData
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class LoadData
{

    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     */
    protected $dataSet;

    /**
     * @var string
     */
    protected $rootPath;

    /**
     * LoadData constructor.
     *
     * @param string $rootPath
     */
    public function __construct($rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * Default locale is used for faker initialization.
     *
     * @param string $file
     * @param string $defaultLocale
     *
     * @return self
     * @throws \Exception
     */
    public function loadData($file, $defaultLocale = "ro_RO")
    {
        $yaml = new YamlParser(new Parser());
        $fakerGenerator = \Faker\Factory::create($defaultLocale);
        $loader = new NativeLoader($fakerGenerator);
        $data = $yaml->parse($this->createAbsolutePath($file));

        if (array_key_exists('request', $data) && !empty($data['request']['Zitec\ApiZitecExtension\Data\Request'])) {
            $this->data['request'] = $loader->loadData($data['request'])->getObjects();
        }

        if (array_key_exists('response', $data) && !empty($data['response']['Zitec\ApiZitecExtension\Data\Response'])) {
            $response = $loader->loadData($data['response'])->getObjects();
            // Process each data set
            foreach ($response as &$dataSet) {
                $dataSet = $this->processCollections((array)$dataSet);
            }
            $this->data['response'] = $response;
        }

        return $this;
    }

    /**
     * Return the absolute path for the file given.
     *
     * @param string $file
     * @param string $format
     *
     * @return string $path
     * @throws \Exception
     */
    public function createAbsolutePath($file, $format = 'yml')
    {
        $path = '/features/data/';
        $filename = $this->checkFileFormat($file, $format);
        switch ($format) {
            case 'txt':
                $path .= "txt/";
                break;
            case 'xml':
            case 'xsd':
                $path .= "xml/";
                break;
            // YML is the original format, no path suffix required.
            case 'yml':
            default:
                break;
        }

        $absPath = $this->rootPath.$path.$filename;
        if (!$absPath) {
            throw new \Exception("File {$filename} not found in {$absPath}.");
        }

        return $absPath;
    }

    /**
     * Check if the yml format was set
     * If not, add the extensions
     *
     * @param string $file
     * @param string $format
     *
     * @return string filename
     */
    protected function checkFileFormat($file, $format = 'yml')
    {
        //take file extension
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        //append yml extension
        if (empty($extension)) {
            $file = "$file.$format";
        }

        return $file;
    }

    /**
     * @param string $requestMethod
     * @param string $dataSet
     *
     * @return array
     * @throws \Exception
     */
    public function getDataForRequest($requestMethod, $dataSet)
    {
        $requestData = $this->data['request'];
        $requestMethod = strtolower($requestMethod);
        $this->dataSet = $dataSet;

        if (!array_key_exists($dataSet, $requestData)) {
            throw new \Exception("Dataset \"{$dataSet}\" not found in request section from the data file.");
        }

        if (!array_key_exists('get', $requestData[$dataSet])) {
            $data['get'] = [];
        }

        if (!array_key_exists('post', $requestData[$dataSet])) {
            $data['post'] = [];
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
     * @param array $data
     *
     * @return array
     */
    private function setFiles(array $data)
    {
        $data['files'] = null;
        array_walk_recursive(
          $data,
          function ($value, $key) use (&$data) {
              if (strpos($value, '@') === 0) {
                  $data['files'][$key] = realpath(substr($value, 1));
                  unset($data[$key]);
              }
          }
        );

        return $data;
    }

    /**
     * Encode base64 images identified by base64_encode(@/path_to_image).
     *
     * @param array $data
     *
     * @return array
     */
    private function encodeImages(array $data)
    {
        array_walk_recursive(
          $data,
          function (&$value) {
              if (strpos($value, 'base64_encode(') === 0) {
                  // Create real path after remove the base64_encode identifiers.
                  $imgPath = realpath(substr(ltrim((rtrim($value, ')')), 'base64_encode('), 1));
                  $binaryImage = fread(fopen($imgPath, 'r'), filesize($imgPath));
                  $value = base64_encode($binaryImage);
              }
          }
        );

        return $data;
    }

    /**
     * @param string $dataSet
     * @param array $values
     */
    public function addDataToDataSet($dataSet, array $values)
    {
        $set = array_merge((array)$this->data['request'][$dataSet], $values);
        $this->data['request'][$dataSet] = (object)$set;
    }

    /**
     * @param string $dataSet
     *
     * @return array
     * @throws \Exception
     */
    public function getResponseData($dataSet)
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

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getLastDataSet()
    {
        return $this->dataSet;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function processCollections($data)
    {
        foreach ($data as $key => $value) {
            if (substr($key, 0, strpos($key, '(')) == "__collection") {
                // set min max
                preg_match_all("/\((.*?)\)/u", $key, $collectionArgs);
                $collectionArgs = explode(',', $collectionArgs[1][0]);
                $infoKey = '__info_collection_'.$collectionArgs[0];
                $data[$infoKey] = [
                  'name' => $collectionArgs[0],
                  'min' => isset($collectionArgs[1]) ? $collectionArgs[1] : null,
                  'max' => isset($collectionArgs[2]) ? $collectionArgs[2] : null,
                ];
                unset($data[$key]);
                $key = '__collection_'.$collectionArgs[0];
            }
            if (is_array($value)) {
                $value = $this->processCollections($value);
            }
            $data[$key] = $value;
        }

        return $data;
    }
}
