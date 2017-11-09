<?php


namespace Tests\Zitec\ApiZitecExtension\Context;

/**
 * Class InputData
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class InputData
{
    /**
     * @var array
     */
    private $data = [
        'http_method'    => 'POST',
        'data_file_name' => 'testFile',
    ];

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
    }
}
