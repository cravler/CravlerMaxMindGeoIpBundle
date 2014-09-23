<?php

namespace Cravler\MaxMindGeoIpBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('cravler_max_mind_geo_ip');

        $rootNode
            ->children()
                ->scalarNode('path')
                    ->defaultValue('%kernel.root_dir%/Resources/MaxMind')
                ->end()
                ->arrayNode('db')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('country')
                            ->cannotBeEmpty()
                            ->defaultValue('GeoIP2-Country.mmdb')
                        ->end()
                        ->scalarNode('city')
                            ->cannotBeEmpty()
                            ->defaultValue('GeoIP2-City.mmdb')
                        ->end()
                        ->scalarNode('connection_type')
                            ->cannotBeEmpty()
                            ->defaultValue('GeoIP2-Connection-Type.mmdb')
                        ->end()
                        ->scalarNode('domain')
                            ->cannotBeEmpty()
                            ->defaultValue('GeoIP2-Domain.mmdb')
                        ->end()
                        ->scalarNode('isp')
                            ->cannotBeEmpty()
                            ->defaultValue('GeoIP2-ISP.mmdb')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('source')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('country')
                            ->defaultValue('http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz')
                        ->end()
                        ->scalarNode('city')
                            ->defaultValue('http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz')
                        ->end()
                        ->scalarNode('connection_type')
                            ->defaultValue(null)
                        ->end()
                        ->scalarNode('domain')
                            ->defaultValue(null)
                        ->end()
                        ->scalarNode('isp')
                            ->defaultValue(null)
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
