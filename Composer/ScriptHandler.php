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
        $consoleDir = static::getConsoleDir($event, 'update MaxMind DB');
        if (null === $consoleDir) {
            return;
        }

        echo '>>> CravlerMaxMindGeoIpBundle: Running command "cravler:maxmind:geoip-update"'.PHP_EOL;
        static::executeCommand($event, $consoleDir, 'cravler:maxmind:geoip-update', $options['process-timeout']);
        echo '>>> CravlerMaxMindGeoIpBundle: done'.PHP_EOL;
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    protected static function useNewDirectoryStructure(array $options)
    {
        return isset($options['symfony-var-dir']) && is_dir($options['symfony-var-dir']);
    }

    /**
     * @param Event $event
     * @param string $configName
     * @param string $path
     * @param string $actionName
     *
     * @return bool
     */
    protected static function hasDirectory(Event $event, $configName, $path, $actionName)
    {
        if (!is_dir($path)) {
            $event->getIO()->write(
                sprintf(
                    'The %s (%s) specified in composer.json was not found in %s, can not %s.',
                    $configName,
                    $path,
                    getcwd(),
                    $actionName
                )
            );
            return false;
        }
        return true;
    }

    /**
     * @param Event  $event
     * @param string $actionName
     *
     * @return string|null
     */
    protected static function getConsoleDir(Event $event, $actionName)
    {
        $options = static::getOptions($event);
        if (static::useNewDirectoryStructure($options)) {
            if (!static::hasDirectory($event, 'symfony-bin-dir', $options['symfony-bin-dir'], $actionName)) {
                return;
            }
            return $options['symfony-bin-dir'];
        }
        if (!static::hasDirectory($event, 'symfony-app-dir', $options['symfony-app-dir'], $actionName)) {
            return;
        }
        return $options['symfony-app-dir'];
    }

    /**
     * @param Event $event
     * @param string $consoleDir
     * @param string $cmd
     * @param int $timeout
     *
     * @throws \RuntimeException
     */
    protected static function executeCommand(Event $event, $consoleDir, $cmd, $timeout = 300)
    {
        $php = escapeshellarg(static::getPhp());
        $console = escapeshellarg($consoleDir.'/console');
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
            'symfony-bin-dir'=> 'bin',
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
