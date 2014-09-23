<?php

namespace Cravler\MaxMindGeoIpBundle\Command;

use Cravler\MaxMindGeoIpBundle\DependencyInjection\CravlerMaxMindGeoIpExtension;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sergei Vizel <sergei.vizel@gmail.com>
 */
class UpdateDatabaseCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('cravler:maxmind:geoip-update')
            ->setDescription('Downloads and updates the MaxMind GeoIp2 database')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getContainer()->getParameter(CravlerMaxMindGeoIpExtension::CONFIG_KEY);

        foreach($config['source'] as $key => $source) {
            if (!$source) {
                continue;
            }

            $output->writeln(sprintf('<info>Start downloading %s</info>', $source));
            $output->writeln('...');

            $tmpFile = $this->downloadFile($source);
            if (false === $tmpFile) {
                $output->writeln('<error>Error during file download occurred</error>');
                continue;
            }

            $output->writeln('<info>Download completed</info>');
            $output->writeln('Unzip the downloading data');
            $output->writeln('...');

            if (!file_exists($config['path'])) {
                mkdir($config['path'], 0777, true);
            }
            $outputFilePath = $config['path'] . '/' . $config['db'][$key];

            $this->decompressFile($tmpFile, $outputFilePath);
            chmod($outputFilePath, 0777);

            $output->writeln('<info>Unzip completed</info>');
        }
    }

    /**
     * @param string $source
     * @return bool|string
     */
    private function downloadFile($source)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'maxmind_geoip2_');
        if (!copy($source, $tmpFile)) {
            return false;
        }

        return $tmpFile;
    }

    /**
     * @param $fileName
     * @param $outputFilePath
     */
    private function decompressFile($fileName, $outputFilePath)
    {
        $gz = gzopen($fileName, 'rb');
        $outputFile = fopen($outputFilePath, 'wb');
        while (!gzeof($gz)) {
            fwrite($outputFile, gzread($gz, 4096));
        }
        fclose($outputFile);
        gzclose($gz);
    }
}