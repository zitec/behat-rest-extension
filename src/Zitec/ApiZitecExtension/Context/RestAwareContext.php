<?php

namespace Zitec\ApiZitecExtension\Context;

use Behat\Behat\Context\Context;
use Zitec\ApiZitecExtension\Data\Data;
use Zitec\ApiZitecExtension\Data\LoadData;
use Zitec\ApiZitecExtension\Data\Parameters;
use Zitec\ApiZitecExtension\Data\Storage;
use Zitec\ApiZitecExtension\Services\Response\Compare;

/**
 * Interface RestAwareContext
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
interface RestAwareContext extends Context
{
    /**
     * @param Parameters $parameters
     * @return $this
     */
    public function setParameters(Parameters $parameters);

    /**
     * @param Compare $compare
     * @return $this
     */
    public function setCompare(Compare $compare);

    /**
     * @param LoadData $loader
     * @return $this
     */
    public function setLoader(LoadData $loader);

    /**
     * @param Storage $storage
     * @return $this
     */
    public function setStorage(Storage $storage);

    /**
     * @param Data $data
     * @return $this
     */
    public function setData(Data $data);
}
