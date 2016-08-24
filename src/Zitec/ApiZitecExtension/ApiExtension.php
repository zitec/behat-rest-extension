<?php
/**
 * Created by PhpStorm.
 * User: bianca.vadean
 * Date: 1/21/2016
 * Time: 6:26 PM
 */

namespace Zitec\ApiZitecExtension;

use Behat\Testwork\ServiceContainer\Extension as TestworkExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ApiExtension implements TestworkExtension
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process (ContainerBuilder $container)
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
                }
            }
        }
        $container->setParameter('suite.configurations', $config);
    }

    public function getConfigKey ()
    {
        return "zitecApiExtension";
    }

    public function initialize (ExtensionManager $extensionManager)
    {
    }

    public function configure (ArrayNodeDefinition $builder)
    {
    }

    public function load (ContainerBuilder $container, array $config)
    {
    }
}
