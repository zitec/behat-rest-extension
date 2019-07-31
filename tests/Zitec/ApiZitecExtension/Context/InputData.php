<?php


namespace Tests\Zitec\ApiZitecExtension\Context;

/**
 * Class InputData
 *
 * @property-read $http_method
 * @property-read $data_file_name
 * @property-read $headers
 * @property-read $auth
 * @property-read $headers_to_remove
 * @property-read $added_request_time
 * @property-read $data_for_request
 * @property-read $data_set
 * @property-read $empty_response_type
 * @property-read $json_response_type
 * @property-read $auth_params
 * @property-read $auth_no_secret
 * @property-read $auth_no_token
 * @property-read $response_data
 * @property-read $index_to_save
 * @property-read $value_to_save
 * @property-read $name_to_save
 * @property-read $stored_key
 * @property-read $dataSetKey
 * @property-read $stored_value
 * @property-read $request_with_placeholder
 * @property-read $request_url
 * @property-read $response_key
 * @property-read $table_column
 * @property-read $location_header
 * @property-read $headers_token
 * @property-read $auth_data
 * @property-read $complete_auth_headers
 * @property-read $base_url
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
        'http_method'              => 'POST',
        'data_file_name'           => 'testFile',
        'headers'                  => [
            ['name', 'value',],
        ],
        'auth'                     => [
            'apiKey'    => 'secretKey',
            'apiClient' => 'awesomeUser',
        ],
        'headers_to_remove'        => 'Location, Content-Type',
        'added_request_time'       => '2 seconds',
        'data_for_request'         => [],
        'dataset'                 => 'dataSet',
        'empty_response_type'      => 'empty',
        'json_response_type'       => 'JSON',
        'auth_params'              => [
            'auth_type'   => 'token',
            'token'       => 'responseToken',
            'secret'      => 'responseSecret',
            'tokenValue'  => 'tokenValue',
            'secretValue' => 'secretValue',
        ],
        'auth_no_secret'           => [
            'auth_type' => 'token',
            'token'     => 'tokenName',
        ],
        'auth_no_token'            => [
            'auth_type' => 'token',
            'secret'    => 'secretName',
        ],
        'response_data'            => [
            'id'   => '1',
            'name' => 'Ana',
        ],
        'index_to_save'            => 'id',
        'value_to_save'            => '10',
        'name_to_save'             => 'id',
        'stored_key'               => 'key',
        'dataSetKey'               => 'datasetKey',
        'stored_value'             => 'value',
        'request_with_placeholder' => 'request/%s',
        'request_url'              => 'request/1',
        'response_key'             => 'response_key',
        'table_column'             => [
            'value',
        ],
        'location_header'          => '/location_header',
        'headers_token'            => [
            'X-Token-Header' => 'token',
        ],
        'auth_data' => [
            'token' => '123456',
        ],
        'complete_auth_headers' => [
            'X-Token-Header' => '123456',
        ],
        'base_url'                 => 'http://unit-testing.test',
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
