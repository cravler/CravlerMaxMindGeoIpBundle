<?php

namespace Cravler\MaxMindGeoIpBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Cravler\MaxMindGeoIpBundle\DependencyInjection\CravlerMaxMindGeoIpExtension;
use GeoIp2\Database\Reader;
use GeoIp2\Model\City;
use GeoIp2\Model\Country;
use GeoIp2\Model\ConnectionType;
use GeoIp2\Model\Domain;
use GeoIp2\Model\Isp;

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
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->getParameter(CravlerMaxMindGeoIpExtension::CONFIG_KEY);
    }

    /**
     * @param string $type
     * @return Reader
     */
    public function getReader($type = 'country', $locales = array('en'))
    {
        if (!isset($this->config['db'][$type])) {
            throw new \RuntimeException(sprintf('Unknown database type %s', $type));
        }
        return new Reader($this->config['path'] . '/' . $this->config['db'][$type], $locales);
    }

    /**
     * @param string $ipAddress
     * @param string $type
     * @param array $locales
     * @return City|Country|ConnectionType|Domain|Isp
     */
    public function getRecord($ipAddress, $type = 'country', $locales = array('en'))
    {
        $reader = $this->getReader($type, $locales);
        switch ($type) {
            case 'country':
                $record = $reader->country($ipAddress);
                break;
            case 'city':
                $record = $reader->city($ipAddress);
                break;
            case 'connection_type':
                $record = $reader->connectionType($ipAddress);
                break;
            case 'domain':
                $record = $reader->domain($ipAddress);
                break;
            case 'isp':
                $record = $reader->isp($ipAddress);
                break;
        }

        return $record;
    }
}
