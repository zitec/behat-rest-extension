<?php

namespace Zitec\ApiZitecExtension\Services\Response\Content;

/**
 * Class AbstractResponse
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
abstract class AbstractContent
{
    /**
     * The actual result the API returns
     *
     * @var string
     */
    protected $rawContent;

    /**
     * The result returned by API decoded.
     *
     * @var array
     */
    protected $parsedContent;

    /**
     * Parse received data
     *
     * @throws \Exception
     */
    abstract protected function parseData();

    /**
     * AbstractContent constructor.
     *
     * @param string $responseData
     */
    public function __construct($responseData)
    {
        $this->rawContent = $responseData;
        $this->parseData();
    }

    /**
     * @return string
     */
    public function getRawContent()
    {
        return $this->rawContent;
    }

    /**
     * @return array
     */
    public function getParsedContent()
    {
        return $this->parsedContent;
    }

    /**
     * Walk through the response to retrieve the specific key given as $path.
     *
     * @param string $path
     * @return mixed
     * @throws \Exception
     */
    public function getItem($path)
    {
        $sqBracket = strpos($path, '[');
        $response = $this->parsedContent;
        if ($sqBracket !== false) {
            $indexes = $this->getIndexOutOfString($path);
            foreach ($indexes as $index) {
                if (isset($response[$index])) {
                    $response = $response[$index];
                    continue;
                }
                throw new \Exception(sprintf('Key %s not found in response.', $path));
            }

            return $response;

        } else {
            if (!isset($response[$path])) {
                throw new \Exception(sprintf('Key %s not found in response.', $path));
            }

            return $response[$path];
        }
    }

     /**
     * @param string $string
     * @return array
     */
    protected function getIndexOutOfString($string)
    {
        $indexes = array_map(
            function ($item) {
                return trim($item, ']');
            },
            explode('[', $string)
        );

        return $indexes;
    }
}

