<?php

namespace Zitec\ApiZitecExtension\Services\Response;

/**
 * Class ResponseFactory
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class ResponseFactory
{
    /**
     * @var array
     */
    protected $responseType = [
        'isXml' => 'XmlResponse',
        'isJson' => 'JsonResponse',
    ];

    /**
     * @param string $responseString
     * @param array $headers
     * @return Response
     * @throws \Exception
     */
    public function createResponse($responseString, array $headers)
    {
        foreach ($this->responseType as $type => $class) {
            $validType = call_user_func(array($this, $type), $responseString);
            if ($validType) {
                $className = 'Zitec\ApiZitecExtension\Services\Response\\' . $class;
                return new $className($responseString, $headers);
            }
        }
        throw new \Exception("The response is nor JSON neither XML.");
    }

    /**
     * Returns true if the $response is a valid JSON, else it returns false.
     *
     * @param string $response
     * @return bool
     */
    public function isJson($response)
    {
        if (is_string($response)) {
            json_decode($response, true);
            if (!json_last_error()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if the $response is a valid JSON, else it returns false.
     *
     * @param string $responseString
     * @return bool
     */
    public function isXml($responseString)
    {
        libxml_use_internal_errors(true);
        $xmlResponse = simplexml_load_string($responseString);
        if ($xmlResponse) {
            return true;
        }
        return false;
    }
}
