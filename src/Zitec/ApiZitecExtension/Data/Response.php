<?php
/**
 * Created by PhpStorm.
 * User: marius.balteanu
 * Date: 26.03.2014
 * Time: 12:28
 */

namespace Zitec\ApiZitecExtension\Data;


class Response
{
  public $data = array();

  /**
   * @param array $data
   */
  public function setData ($data)
  {
    $this->data = $data;
  }
}
