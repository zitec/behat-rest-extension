<?php

namespace Zitec\ApiZitecExtension;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\ServiceContainer\Extension as TestworkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Zitec\ApiZitecExtension\Context\Initializer\RestAwareInitializer;
use Zitec\ApiZitecExtension\Data\Data;
use Zitec\ApiZitecExtension\Data\LoadData;
use Zitec\ApiZitecExtension\Data\Parameters;
use Zitec\ApiZitecExtension\Data\Storage;
use Zitec\ApiZitecExtension\Services\Response\Compare;

/**
 * Class ApiExtension
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class ApiExtension implements TestworkExtension
{
        /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $config = $container->getParameter('suite.configurations');
        // Set the root path in each context parameters.
        foreach ($config as &$suites) {
            $contexts = &$suites['settings']['contexts'];
            foreach ($contexts as &$context) {
                if (is_array($context)) {
                    foreach($context as &$item) {
                        $item['parameters']['root_path'] = '%paths.base%';
                    }
                    unset($item);
                }
            }
            unset($context);
        }
        unset($suites);

        $container->setParameter('suite.configurations', $config);
    }

    /**
     * @return string
     */
    public function getConfigKey ()
    {
        return "zitecApiExtension";
    }

    /**
     * @param ExtensionManager $extensionManager
     */
    public function initialize (ExtensionManager $extensionManager)
    {
    }

    /**
     * @param ArrayNodeDefinition $builder
     */
    public function configure (ArrayNodeDefinition $builder)
    {
    }

    /**
     * @param ContainerBuilder $container
     * @param array $config
     */
    public function load (ContainerBuilder $container, array $config)
    {
        $this->loadParameters($container);
        $this->loadCompare($container);
        $this->loadData($container);
        $this->loadStorage($container);
        $this->loadLoader($container);
        $this->loadContextInitializer($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    public function loadParameters(ContainerBuilder $container)
    {
        $container->setDefinition('zitec.api.parameters', new Definition(Parameters::class, [$container->getParameter('suite.configurations')]));
    }

    /**
     * @param ContainerBuilder $container
     */
    public function loadData(ContainerBuilder $container)
    {
        $definition = new Definition(Data::class);
        $definition->setFactory("Zitec\ApiZitecExtension\Data\Data::getInstance");
        $container->setDefinition('zitec.api.data', $definition);
    }

    /**
     * @param ContainerBuilder $container
     */
    public function loadStorage(ContainerBuilder $container)
    {
        $definition = new Definition(Storage::class);
        $definition->setFactory("Zitec\ApiZitecExtension\Data\Storage::getInstance");
        $container->setDefinition('zitec.api.storage', $definition);
    }

    /**
     * @param ContainerBuilder $container
     */
    public function loadCompare(ContainerBuilder $container)
    {
        $container->setDefinition('zitec.api.compare', new Definition(Compare::class));
    }

    /**
     * @param ContainerBuilder $container
     */
    public function loadLoader(ContainerBuilder $container)
    {
        $container->setDefinition('zitec.api.loader', new Definition(LoadData::class, ["%paths.base%"]));
    }

    /**
     * @param ContainerBuilder $container
     */
    public function loadContextInitializer(ContainerBuilder $container)
    {
        $definition = new Definition(RestAwareInitializer::class, [
            new Reference('zitec.api.parameters'),
            new Reference('zitec.api.compare'),
            new Reference('zitec.api.storage'),
            new Reference('zitec.api.data'),
            new Reference('zitec.api.loader'),
        ]);
        $definition->addTag(ContextExtension::INITIALIZER_TAG, ['priority' => '0']);
        $container->setDefinition('zitec.api.context_initializer', $definition);
    }
}

