<?php

namespace Cravler\MaxMindGeoIpBundle\Service;

use GeoIp2\WebService\Client;
use GeoIp2\Database\Reader;
use GeoIp2\Model\Asn;
use GeoIp2\Model\City;
use GeoIp2\Model\Country;
use GeoIp2\Model\Insights;
use GeoIp2\Model\AnonymousIp;
use GeoIp2\Model\ConnectionType;
use GeoIp2\Model\Domain;
use GeoIp2\Model\Isp;
use Cravler\MaxMindGeoIpBundle\Exception\GeoIpException;

/**
 * @author Sergei Vizel <sergei.vizel@gmail.com>
 */
class GeoIpService
{
    /**
     * @var array
     */
    private $config = array();

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
    public function getClient($locales = array('en'))
    {
        return new Client(
            $this->config['client']['user_id'],
            $this->config['client']['license_key'],
            $locales,
            $this->config['client']['options'] ?: array()
        );
    }

    /**
     * @param string $type
     * @param array  $locales
     *
     * @return Reader
     *
     * @throws GeoIpException
     */
    public function getReader($type = 'country', $locales = array('en'))
    {
        $type = preg_replace_callback('/([A-Z])/', function ($match) {
            return '_'.strtolower($match[1]);
        }, $type);

        if (!isset($this->config['db'][$type])) {
            throw new GeoIpException(
                sprintf('Unknown database type %s', $type)
            );
        }

        return new Reader($this->config['path'].'/'.$this->config['db'][$type], $locales);
    }

    /**
     * @param string $ipAddress
     * @param string $type
     * @param array  $options
     *
     * @return City|Country|ConnectionType|Domain|Isp|AnonymousIp|Insights|Asn
     *
     * @throws GeoIpException
     */
    public function getRecord($ipAddress = 'me', $type = 'country', array $options = array())
    {
        $provider = isset($options['provider']) ? $options['provider'] : 'reader';
        $locales = isset($options['locales']) ? $options['locales'] : array('en');

        if ('client' == $provider) {
            $provider = $this->getClient($locales);
        } else {
            $provider = $this->getReader($type, $locales);
        }

        $method = preg_replace_callback('/_([a-z])/', function ($match) {
            return strtoupper($match[1]);
        }, $type);

        if (!method_exists($provider, $method)) {
            throw new GeoIpException(
                sprintf('The method "%s" does not exist for %s', $method, get_class($provider))
            );
        }

        return $provider->{$method}($ipAddress);
    }
}
