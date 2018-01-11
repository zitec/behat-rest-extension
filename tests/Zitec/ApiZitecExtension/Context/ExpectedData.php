<?php


namespace Tests\Zitec\ApiZitecExtension\Context;

/**
 * Class ExpectedData
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class ExpectedData
{
    /**
     * @var array
     */
    private $data = [
        'httpMethod' => 'POST',
    ];

    /**
     * @param $name
     * @return mixed
     */
    function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
    }
}
