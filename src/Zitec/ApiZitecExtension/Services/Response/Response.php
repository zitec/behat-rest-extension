<?php

namespace Zitec\ApiZitecExtension\Services\Response;

/**
 * Interface Response
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
interface Response
{
    /**
     * @return string
     */
    public function getResponse();

    /**
     * @return array
     */
    public function getRawResponse();

    /**
     * @return array
     */
    public function getResponseHeaders();

    /**
     * @param string $name
     * @return string|null
     */
    public function getResponseHeader($name);

    /**
     * @return bool
     */
    public function isEmpty();

    /**
     * @return string
     */
    public function getType();

    /**
     * @param string $name
     * @return mixed
     */
    public function getItem($name);

    /**
     * @param array $expectedResponse
     * @throws \Exception
     */
    public function matchResponse(array $expectedResponse);

    /**
     * @param array $expectedResponse
     * @throws \Exception
     */
    public function matchStructure(array $expectedResponse);
}
