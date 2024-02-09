<?php

namespace Cravler\MaxMindGeoIpBundle\Service;

use Cravler\MaxMindGeoIpBundle\Exception\GeoIpException;
use GeoIp2\Database\Reader;
use GeoIp2\Model\AnonymousIp;
use GeoIp2\Model\Asn;
use GeoIp2\Model\City;
use GeoIp2\Model\ConnectionType;
use GeoIp2\Model\Country;
use GeoIp2\Model\Domain;
use GeoIp2\Model\Isp;
use GeoIp2\WebService\Client;
use MaxMind\Db\Reader\InvalidDatabaseException;

/**
 * @author Sergei Vizel <sergei.vizel@gmail.com>
 */
class GeoIpService
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private readonly array $config = [])
    {
    }

    /**
     * @param string[] $locales
     */
    public function getClient(array $locales = ['en']): Client
    {
        /** @var array{'user_id': ?int, 'license_key': ?string, 'options': ?array<string, mixed>} $client */
        $client = \is_array($this->config['client'] ?? null) ? $this->config['client'] : [];

        if (!$client['user_id']) {
            throw new GeoIpException('"user_id" not defined');
        }

        if (!$client['license_key']) {
            throw new GeoIpException('"license_key" not defined');
        }

        $options = \is_array($client['options'] ?? null) ? $client['options'] : [];

        return new Client($client['user_id'], $client['license_key'], $locales, $options);
    }

    /**
     * @param string[] $locales
     *
     * @throws GeoIpException|InvalidDatabaseException
     */
    public function getReader(string $type = 'country', array $locales = ['en']): Reader
    {
        $type = \preg_replace_callback('/([A-Z])/', fn (array $matches): string => '_' . \strtolower($matches[1]), $type);

        /** @var array<string, string> $db */
        $db = \is_array($this->config['db'] ?? null) ? $this->config['db'] : [];

        if (!isset($db[$type])) {
            throw new GeoIpException(\sprintf('Unknown database type %s', $type));
        }

        /** @var string $path */
        $path = $this->config['path'];

        return new Reader($path . '/' . $db[$type], $locales);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws GeoIpException|InvalidDatabaseException
     */
    public function getRecord(string $ipAddress = 'me', string $type = 'country', array $options = []): AnonymousIp|Asn|City|ConnectionType|Country|Domain|Isp|\JsonSerializable
    {
        $provider = \is_string($options['provider'] ?? null) ? $options['provider'] : 'reader';

        /** @var string[] $locales */
        $locales = \is_array($options['locales'] ?? null) ? $options['locales'] : ['en'];

        if ('client' === $provider) {
            $provider = $this->getClient($locales);
        } else {
            $provider = $this->getReader($type, $locales);
        }

        $method = \preg_replace_callback('/_([a-z])/', fn (array $matches): string => \strtoupper($matches[1]), $type);

        if (!$method || !\method_exists($provider, $method)) {
            throw new GeoIpException(\sprintf('The method "%s" does not exist for %s', $method, \get_class($provider)));
        }

        return $provider->{$method}($ipAddress);
    }
}
