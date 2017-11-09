<?php

namespace Zitec\ApiZitecExtension\Services\Response;

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
     * Check if the response match the expected response.
     * If not the exception message will show the differences.
     *
     * @param array $expectedResponse
     * @param Response $response
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
     * Returns the differences between $array1 and $array2
     *
     * @param array $array1
     * @param array $array2
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
