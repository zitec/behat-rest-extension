<?php

namespace Zitec\ApiZitecExtension\Services;


interface Response
{
    public function getResponse();
    public function getRawResponse();
    public function getResponseHeaders();

    /**
     * @param $name
     * @return mixed
     */
    public function getResponseHeader($name);

    public function isEmpty();
    public function getType();

}
