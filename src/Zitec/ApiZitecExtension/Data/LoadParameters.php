<?php


namespace Zitec\ApiZitecExtension\Data;


class LoadParameters
{
    public function __construct(array $parameters)
    {
        if (!empty($parameters)) {
            foreach ($parameters as $key => $param) {
                if (is_array($param)) {
                    $this->$key = new \stdClass();
                    $this->$key = (object)$param;
                } else {
                    $this->$key = $param;
                }
            }
        }
    }
}
