<?php

namespace Zitec\ApiZitecExtension\Data;

/**
 * Class LoadParameters
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class LoadParameters
{
    /**
     * LoadParameters constructor.
     *
     * @param array $parameters
     */
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
