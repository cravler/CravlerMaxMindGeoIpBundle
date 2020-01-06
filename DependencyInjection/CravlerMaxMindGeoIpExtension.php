<?php

namespace Cravler\MaxMindGeoIpBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\FileLocator;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class CravlerMaxMindGeoIpExtension extends Extension
{
    const CONFIG_KEY = 'cravler_max_mind_geo_ip.config';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter(self::CONFIG_KEY, $config); // BC

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $container->findDefinition('cravler_max_mind_geo_ip.service.geo_ip_service')->setArguments(array($config));
        $container->findDefinition('cravler_max_mind_geo_ip.command.update_database_command')->setArguments(array($config));
    }
}
