CravlerMaxMindGeoIpBundle
======================

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
    path: '%kernel.root_dir%/Resources/MaxMind'
    db:
        country: 'GeoIP2-Country.mmdb'
        city: 'GeoIP2-City.mmdb'
        connection_type: 'GeoIP2-Connection-Type.mmdb'
        domain: 'GeoIP2-Domain.mmdb'
        isp: 'GeoIP2-ISP.mmdb'
    source:
        country: 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz'
        city: 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz'
        connection_type: ~
        domain: ~
        isp: ~
```

## Download and update the MaxMind GeoIp2 database

``` bash
app/console cravler:maxmind:geoip-update
```

## License

This bundle is under the MIT license. See the complete license in the bundle:

```
LICENSE
```