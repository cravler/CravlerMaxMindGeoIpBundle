CravlerMaxMindGeoIpBundle
======================

## Requirements

This bundle works with version of Symfony > 2.3.

## Installation

### Step 1: update your vendors by running

``` bash
$ php composer.phar require cravler/maxmind-geoip-bundle:dev-master
```

### Step2: Enable the bundle

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...

        new Cravler\MaxMindGeoIpBundle\CravlerMaxMindGeoIpBundle(),
    );
}
```

## Configuration

The default configuration for the bundle looks like this:

``` yaml
cravler_max_mind_geo_ip:
    client:
        user_id: ~
        license_key: ~
        options: {}
    path: '%kernel.root_dir%/Resources/MaxMind'
    db:
        country: 'GeoIP2-Country.mmdb'
        city: 'GeoIP2-City.mmdb'
        connection_type: 'GeoIP2-Connection-Type.mmdb'
        anonymous_ip: 'GeoIP2-Anonymous-IP.mmdb'
        domain: 'GeoIP2-Domain.mmdb'
        isp: 'GeoIP2-ISP.mmdb'
    source:
        country: 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz'
        city: 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz'
        connection_type: ~
        anonymous_ip: ~
        domain: ~
        isp: ~
```

## Download and update the MaxMind GeoIp2 database

``` bash
app/console cravler:maxmind:geoip-update
```

## How to use

``` php
$geoIpService = $container->get('cravler_max_mind_geo_ip.service.geo_ip_service');

// Replace "city" with the appropriate method for your database, e.g., "country".
$record = $geoIpService->getRecord('128.101.101.101', 'city');

print($record->country->isoCode . "\n"); // 'US'
print($record->country->name . "\n"); // 'United States'
print($record->city->name . "\n"); // 'Minneapolis'

```

## License

This bundle is under the MIT license. See the complete license in the bundle:

```
LICENSE
```