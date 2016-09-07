<?php

namespace Cravler\MaxMindGeoIpBundle\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * @author Sergei Vizel <sergei.vizel@gmail.com>
 */
class ScriptHandler
{
    /**
     * @param Event $event
     */
    public static function maxMindGeoIpUpdate(Event $event)
    {
        $options = static::getOptions($event);
        $appDir = $options['symfony-app-dir'];

        if (!is_dir($appDir)) {
            echo 'The symfony-app-dir ('.$appDir.') specified in composer.json was not found in '.getcwd().', can not update schema.'.PHP_EOL;

            return;
        }

        echo '>>> CravlerMaxMindGeoIpBundle: Running command "cravler:maxmind:geoip-update"'.PHP_EOL;

        static::executeCommand($event, $appDir, 'cravler:maxmind:geoip-update', $options['process-timeout']);

        echo '>>> CravlerMaxMindGeoIpBundle: done'.PHP_EOL;
    }

    /**
     * @param Event $event
     * @param $appDir
     * @param $cmd
     * @param int $timeout
     *
     * @throws \RuntimeException
     */
    protected static function executeCommand(Event $event, $appDir, $cmd, $timeout = 300)
    {
        $php = escapeshellarg(static::getPhp());
        $console = escapeshellarg($appDir.'/console');
        if ($event->getIO()->isDecorated()) {
            $console .= ' --ansi';
        }

        $process = new Process($php.' '.$console.' '.$cmd, null, null, null, $timeout);
        $process->run(function ($type, $buffer) { echo $buffer; });
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('An error occurred when executing the "%s" command.', escapeshellarg($cmd)));
        }
    }

    /**
     * @param Event $event
     *
     * @return array
     */
    protected static function getOptions(Event $event)
    {
        $options = array_merge(array(
            'symfony-app-dir' => 'app',
        ), $event->getComposer()->getPackage()->getExtra());

        $options['process-timeout'] = $event->getComposer()->getConfig()->get('process-timeout');

        return $options;
    }

    /**
     * @return false|string
     *
     * @throws \RuntimeException
     */
    protected static function getPhp()
    {
        $phpFinder = new PhpExecutableFinder();
        if (!$phpPath = $phpFinder->find()) {
            throw new \RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
        }

        return $phpPath;
    }
}
