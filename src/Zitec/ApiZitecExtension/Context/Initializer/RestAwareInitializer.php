<?php

namespace Zitec\ApiZitecExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Zitec\ApiZitecExtension\Context\RestAwareContext;
use Zitec\ApiZitecExtension\Data\Data;
use Zitec\ApiZitecExtension\Data\LoadData;
use Zitec\ApiZitecExtension\Data\Parameters;
use Zitec\ApiZitecExtension\Data\Storage;
use Zitec\ApiZitecExtension\Services\Response\Compare;

/**
 * Class RestAwareInitializer
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class RestAwareInitializer implements ContextInitializer
{
    /**
     * @var Parameters
     */
    private $parameters;

    /**
     * @var Compare
     */
    private $compare;

    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var Data
     */
    private $data;

    /**
     * @var LoadData
     */
    private $loader;

    /**
     * RestAwareInitializer constructor.
     *
     * @param Parameters $parameters
     * @param Compare $compare
     * @param Storage $storage
     * @param Data $data
     * @param LoadData $loader
     */
    public function __construct(
        Parameters $parameters,
        Compare $compare,
        Storage $storage,
        Data $data,
        LoadData $loader
    ) {
        $this->parameters = $parameters;
        $this->compare = $compare;
        $this->storage = $storage;
        $this->data = $data;
        $this->loader = $loader;
    }

    /**
     * Initializes provided context.
     *
     * @param Context $context
     */
    public function initializeContext(Context $context)
    {
        if (!$context instanceof RestAwareContext) {
            return;
        }

        $context->setParameters($this->parameters)
            ->setCompare($this->compare)
            ->setStorage($this->storage)
            ->setData($this->data)
            ->setLoader($this->loader);
    }
}
