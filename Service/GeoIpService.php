<?php

namespace Cravler\MaxMindGeoIpBundle\Service;

use GeoIp2\Database\Reader;
use GeoIp2\WebService\Client;
use GeoIp2\Model\AbstractModel;
use Cravler\MaxMindGeoIpBundle\Exception\GeoIpException;

/**
 * @author Sergei Vizel <sergei.vizel@gmail.com>
 */
class GeoIpService
{
    /**
     * @var array
     */
    private array $config = [];

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param array $locales
     *
     * @return Client
     */
    public function getClient(array $locales = ['en']): Client
    {
        return new Client(
            $this->config['client']['user_id'],
            $this->config['client']['license_key'],
            $locales,
            $this->config['client']['options'] ?: []
        );
    }

    /**
     * @param string $type
     * @param array $locales
     *
     * @return Reader
     *
     * @throws GeoIpException
     */
    public function getReader(string $type = 'country', $locales = ['en']): Reader
    {
        $type = preg_replace_callback('/([A-Z])/', fn(array $matches): string => '_' . strtolower($matches[1]), $type);

        if (!isset($this->config['db'][$type])) {
            throw new GeoIpException(sprintf('Unknown database type %s', $type));
        }

        return new Reader($this->config['path'] . '/' . $this->config['db'][$type], $locales);
    }

    /**
     * @param string $ipAddress
     * @param string $type
     * @param array $options
     *
     * @return AbstractModel
     *
     * @throws GeoIpException
     */
    public function getRecord(string $ipAddress = 'me', string $type = 'country', array $options = []): AbstractModel
    {
        $provider = isset($options['provider']) ? $options['provider'] : 'reader';
        $locales = isset($options['locales']) ? $options['locales'] : ['en'];

        if ('client' == $provider) {
            $provider = $this->getClient($locales);
        } else {
            $provider = $this->getReader($type, $locales);
        }

        $method = preg_replace_callback('/_([a-z])/', fn(array $matches): string => strtoupper($matches[1]), $type);

        if (!method_exists($provider, $method)) {
            throw new GeoIpException(sprintf('The method "%s" does not exist for %s', $method, get_class($provider)));
        }

        return $provider->{$method}($ipAddress);
    }
}
