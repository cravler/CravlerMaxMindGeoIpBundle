<?php

use Cravler\MaxMindGeoIpBundle\Command\UpdateDatabaseCommand;
use Cravler\MaxMindGeoIpBundle\Service\GeoIpService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->alias(GeoIpService::class, 'cravler_max_mind_geo_ip.service.geo_ip_service');

    $services->set('cravler_max_mind_geo_ip.service.geo_ip_service', GeoIpService::class)
        ->public();

    $services->set('cravler_max_mind_geo_ip.command.update_database_command', UpdateDatabaseCommand::class)
        ->tag('console.command');
};
