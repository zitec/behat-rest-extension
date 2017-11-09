<?php

namespace Zitec\ApiZitecExtension;

use Behat\Testwork\ServiceContainer\Extension as TestworkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
    }
}
