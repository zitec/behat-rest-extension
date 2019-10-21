<?php

namespace Zitec\ApiZitecExtension\Services\Response;

use PHPUnit\Framework\Assert;
use Zitec\ApiZitecExtension\Util\TypeChecker;

/**
 * Class Compare
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class Compare
{

    /**
     * Checks if the response match the expected structure.
     *
     * @param array $expectedStructure
     * @param Response $response
     *
     * @throws \Exception
     */
    public function matchStructure(array $expectedStructure, Response $response)
    {
        $checker = new TypeChecker();
        $checked = $checker->checkType($response->getContent()->getParsedContent(), $expectedStructure);
        if (!empty($checked)) {
            throw new \Exception(
              sprintf("The following values do not match the expected type: %s", json_encode($checked))
            );
        }
    }

    /**
     * @param string $xsdFile
     * @param Response $response
     *
     * @throws \Exception
     */
    public function matchXMLStructure($xsdFile, Response $response)
    {
        libxml_use_internal_errors(true);
        $xdoc = new \DomDocument();
        $xdoc->loadXML($response->getContent()->getRawContent());
        if (!$xdoc->schemaValidate($xsdFile)) {
            $exceptionMessage = '';
            foreach (libxml_get_errors() as $libxmlError) {
                $exceptionMessage .= $this->formatLibxmlError($libxmlError);
            }
            throw new \Exception(
              "Response does not match $xsdFile schema: $exceptionMessage"
            );
        }
    }

    /**
     * Check if the response match the expected response.
     * If not the exception message will show the differences.
     *
     * @param array $expectedResponse
     * @param Response $response
     *
     * @throws \Exception
     */
    public function matchResponse(array $expectedResponse, Response $response)
    {
        $parsedData = $response->getContent()->getParsedContent();
        if ($parsedData != $expectedResponse) {
            $diffResponse = $this->responseDiff($parsedData, $expectedResponse);
            $diffExpected = $this->responseDiff($expectedResponse, $parsedData);
            $differences = array_merge($diffExpected, $diffResponse);
            $message = sprintf(
              "The response doesn't meet the expectations.
             There have been found differences on: %s",
              json_encode($differences)
            );

            throw new \Exception($message);
        }
    }

    /**
     * @param string $xmlFile
     * @param Response $response
     *
     * @throws \Exception
     */
    public function matchXMLResponse($xmlFile, Response $response)
    {
        Assert::assertXmlStringEqualsXmlFile($xmlFile, $response->getContent()->getRawContent());
    }

    /**
     * @param $txtFile
     * @param Response $response
     *
     * @throws \Exception
     */
    public function matchRawContent($txtFile, Response $response)
    {
        Assert::assertEquals(
          file_get_contents($txtFile),
          $response->getContent()->getRawContent(),
          'Raw response content does not match contents of file' . $txtFile
        );
    }

    /**
     * @param $error
     *
     * @return string
     */
    protected function formatLibxmlError($error)
    {
        $return = "\n";
        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= "Warning $error->code: ";
                break;
            case LIBXML_ERR_ERROR:
                $return .= "Error $error->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "Fatal Error $error->code: ";
                break;
        }
        $return .= trim($error->message);

        return $return;
    }

    /**
     * Returns the differences between $array1 and $array2
     *
     * @param array $array1
     * @param array $array2
     *
     * @return array
     */
    private function responseDiff($array1, $array2)
    {
        $difference = [];
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key]) || !is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = $this->responseDiff($value, $array2[$key]);
                    if (!empty($new_diff)) {
                        $difference[$key] = $new_diff;
                    }
                }
            } else {
                if (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
                    $difference[$key] = $value;
                }
            }
        }

        return $difference;
    }
}
