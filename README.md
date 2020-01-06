# CravlerMaxMindGeoIpBundle

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
        asn: 'GeoIP2-ASN.mmdb'
        connection_type: 'GeoIP2-Connection-Type.mmdb'
        anonymous_ip: 'GeoIP2-Anonymous-IP.mmdb'
        enterprise: 'GeoIP2-Enterprise.mmdb'
        domain: 'GeoIP2-Domain.mmdb'
        isp: 'GeoIP2-ISP.mmdb'
    source:
        country: ~
        city: ~
        asn: ~
        connection_type: ~
        anonymous_ip: ~
        enterprise: ~
        domain: ~
        isp: ~
    md5_check:
        country: ~
        city: ~
        asn: ~
        connection_type: ~
        anonymous_ip: ~
        enterprise: ~
        domain: ~
        isp: ~
```

If you need a `GeoLite2` licence:

1. [Sign up for a MaxMind account](https://www.maxmind.com/en/geolite2/signup) (no purchase required)
2. Set your password and create a [license key](https://www.maxmind.com/en/accounts/current/license-key)
    `Will this key be used for GeoIP Update? > No`

```yaml
parameters:
    max_mind.license_key: '<YOUR_LICENSE_KEY>'

cravler_max_mind_geo_ip:
    source:
        country: 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&suffix=tar.gz&license_key=%max_mind.license_key%'
        city: 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&suffix=tar.gz&license_key=%max_mind.license_key%'
        asn: 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-ASN&suffix=tar.gz&license_key=%max_mind.license_key%'
    md5_check:
        country: 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&suffix=tar.gz.md5&license_key=%max_mind.license_key%'
        city: 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&suffix=tar.gz.md5&license_key=%max_mind.license_key%'
        asn: 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-ASN&suffix=tar.gz.md5&license_key=%max_mind.license_key%'
```

If you have bought a licence:

``` yaml
parameters:
    max_mind.user_id: '<YOUR_USER_ID>'
    max_mind.license_key: '<YOUR_LICENSE_KEY>'

cravler_max_mind_geo_ip:
    client:
        user_id: '%max_mind.user_id%'
        license_key: '%max_mind.license_key%'
        ...
    source:
        country: 'https://download.maxmind.com/app/geoip_download?edition_id=GeoIP2-Country&suffix=tar.gz&license_key=%max_mind.license_key%'
        ...
    md5_check:
        country: ~
        ...
```

> **NB!** Do not forget to change your licence data.

## Download and update the MaxMind GeoIp2 database


``` bash
php bin/console cravler:maxmind:geoip-update
```

You can use the *--no-md5-check* option if you want to skip MD5 check.
``` bash
php bin/console cravler:maxmind:geoip-update --no-md5-check
```

> On Symfony 2.8 or older use `app/console` instead of `bin/console`

## How to use

### Database Reader

``` php
$geoIpService = $container->get('cravler_max_mind_geo_ip.service.geo_ip_service');

// Replace "city" with the appropriate method for your database, e.g., "country".
$record = $geoIpService->getRecord('128.101.101.101', 'city');

print($record->country->isoCode . "\n"); // 'US'
print($record->country->name . "\n"); // 'United States'
print($record->city->name . "\n"); // 'Minneapolis'

```

### Web Service Client

``` php
$geoIpService = $container->get('cravler_max_mind_geo_ip.service.geo_ip_service');

$client = $geoIpService->getClient();

$record = $client->city('128.101.101.101');

```

## License

This bundle is under the MIT license. See the complete license in the bundle:

```
LICENSE
```
