<?php

namespace Cravler\MaxMindGeoIpBundle\Command;

use Cravler\MaxMindGeoIpBundle\DependencyInjection\CravlerMaxMindGeoIpExtension;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sergei Vizel <sergei.vizel@gmail.com>
 */
class UpdateDatabaseCommand extends ContainerAwareCommand
{
    const PREFIX_DL_FILENAME = 'maxmind_geoip2_';       // prefixe for filename that we save after download
    private $geoIpTmpDirectory ;                        // tmp directory to manage files
    private $geoIpTmpExtractedFilesDirectory ;          // tmp directory to extract tar.gz file and manage sub directory
    private $verbosity;                                 // verbosity command

    protected function configure()
    {
        $this
            ->setName('cravler:maxmind:geoip-update')
            ->setDescription('Downloads and updates the MaxMind GeoIp2 database')
            ->addOption('no-md5-check', null, InputOption::VALUE_NONE, 'Disable MD5 check')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Keep verbosity asked in command line to display information in sub methods
        $this->verbosity = $output->getVerbosity();

        // Init tmp directories
        $this->geoIpTmpDirectory = sys_get_temp_dir() . '/MaxMind';
        $this->geoIpTmpExtractedFilesDirectory = $this->geoIpTmpDirectory . '/ExtractedFiles';

        $config = $this->getContainer()->getParameter(CravlerMaxMindGeoIpExtension::CONFIG_KEY);

        foreach($config['source'] as $key => $source) {
            if (!$source) {
                continue;
            }

            $output->write(sprintf('Downloading %s... ', $source));

            $tmpFile = $this->downloadFile($source, strpos($source, 'tar.gz') !== false);
            if (false === $tmpFile) {
                $output->writeln('FAILED');
                $output->writeln(sprintf('<error>Error during file download occurred on %s</error>', $source));
                continue;
            }

            $output->writeln('<info>Done</info>');
            $output->write('Unzipping the downloaded data... ');
            $tmpFileUnzipped = dirname($tmpFile) . DIRECTORY_SEPARATOR . $config['db'][$key];

            $tmpFileUnzipped = $this->decompressFile($tmpFile, $tmpFileUnzipped);

            if (is_readable($tmpFileUnzipped)) {
                $output->writeln('<info>Done</info>');
            }
            else {
                $output->writeln(sprintf('<error>An error occured when decompressing %s</error>', basename($tmpFile)));
                continue;
            }

            # MD5 check
            if (!$input->getOption('no-md5-check')) {

                $output->write('Checking file hash... ');
                $expectedMD5 = file_get_contents(str_replace('.mmdb.gz', '.md5', $source));

                if (!$expectedMD5 || strlen($expectedMD5) !== 32) {
                    $output->writeln(sprintf('<error>Unable to check MD5 for %s</error>', $source));
                    continue;
                }
                elseif ($expectedMD5 !== md5_file($tmpFileUnzipped)) {
                    $output->writeln(sprintf('<error>MD5 for %s does not match</error>', $source));
                    continue;
                }
                else {
                    $output->writeln('<info>File hash OK.</info>');
                }

            }

            if (!file_exists($config['path'])) {
                mkdir($config['path'], 0777, true);
            }

            $outputFilePath = $config['path'] . '/' . $config['db'][$key];
            chmod(dirname($outputFilePath), 0777);
            $success = @rename($tmpFileUnzipped, $outputFilePath);

            if ($success) {
                $output->writeln(sprintf('<info>Update completed for %s.</info>', $key));
            }

            else {
                $output->writeln(sprintf('<error>Unable to update %s</error>', $key));
            }
        }
    }

    /**
     * @param string $source
     * @param bool $isTarGz
     * @return bool|string
     */
    private function downloadFile($source, $isTarGz = false)
    {
        $tmpFile = tempnam($this->geoIpTmpDirectory, UpdateDatabaseCommand::PREFIX_DL_FILENAME);

        if ($isTarGz) {
            $tmpFile .= '.tar.gz' ;
        }

        if (!@copy($source, $tmpFile)) {
            return false;
        }

        return $tmpFile;
    }

    /**
     * @param $fileName
     * @param $outputFilePath
     * @return string output path + filename
     * @throws \Exception when a directory is whereas it must not
     */
    private function decompressFile($fileName, $outputFilePath)
    {
        // If it is a tar.gz file we have to find mmdb file that is in a subdirectory
        if (strpos($fileName, '.tar.gz') !== false) {

            // Remove .tar files before decompression
            foreach (glob($this->geoIpTmpDirectory . '/' . UpdateDatabaseCommand::PREFIX_DL_FILENAME . "*.tar") as $filename) {
                if (OutputInterface::VERBOSITY_VERBOSE <= $this->verbosity) {
                    echo "$filename size " . filesize($filename) . "\n";
                }
                unlink($filename);
            }

            $p = new \PharData($fileName);
            $p->decompress(); // creates the .tar file by ungziping it

            // Delete old uncompressed directory  -- no recursive function to prevent bad deletion. We must have 2 directory level
            if (is_dir($this->geoIpTmpExtractedFilesDirectory)) {   // /tmp/MaxMind/ExtractedFiles
                if ($dh = opendir($this->geoIpTmpExtractedFilesDirectory)) {
                    while (($file = readdir($dh)) !== false) {
                        if ($file != '.' && $file != '..') {
                            if (OutputInterface::VERBOSITY_VERBOSE <= $this->verbosity) {
                                echo "file : $file : type : " . filetype($this->geoIpTmpExtractedFilesDirectory . '/' . $file) . "\n";
                            }

                            if (is_dir($this->geoIpTmpExtractedFilesDirectory . '/' . $file)) { // ex: /tmp/MaxMind/ExtractedFiles/GeoIP2-Country_20160830
                                if ($dh2 = opendir($this->geoIpTmpExtractedFilesDirectory . '/' . $file)) {
                                    while (($file2 = readdir($dh2)) !== false) {
                                        if ($file2 != '.' && $file2 != '..') {
                                            if (OutputInterface::VERBOSITY_VERBOSE <= $this->verbosity) {
                                                echo "---file : $file2 : type : " . filetype($this->geoIpTmpExtractedFilesDirectory . '/' . $file . '/' . $file2) . "\n";
                                            }
                                           if (is_file($this->geoIpTmpExtractedFilesDirectory . '/' . $file . '/' . $file2)) {
                                                unlink($this->geoIpTmpExtractedFilesDirectory . '/' . $file . '/' . $file2);
                                            } elseif (is_dir($this->geoIpTmpExtractedFilesDirectory . '/' . $file . '/' . $file2)) {
                                                // Throw an error. We do not have a directory here
                                               throw new \Exception("Directory must not be here: " . $this->geoIpTmpExtractedFilesDirectory . '/' . $file . '/' . $file2) ;
                                            }
                                        }
                                    }
                                }
                                closedir($dh2);
                                // Remove directory
                                rmdir ($this->geoIpTmpExtractedFilesDirectory . '/' . $file);
                            }
                        }
                    }
                    closedir($dh);
                }
            }

            $tarFile = new \PharData(str_replace ('.gz', '', $fileName));
            $tarFile->extractTo($this->geoIpTmpExtractedFilesDirectory, null, true);

            // Get directory created to finally have the mmdb file to copy
            if (is_dir($this->geoIpTmpExtractedFilesDirectory)) {   // /tmp/MaxMind/ExtractedFiles
                if ($dh = opendir($this->geoIpTmpExtractedFilesDirectory)) {
                    while (($file = readdir($dh)) !== false) {
                        if ($file != '.' && $file != '..') {
                            if (OutputInterface::VERBOSITY_VERBOSE <= $this->verbosity) {
                                echo "fichier : $file : type : " . filetype($this->geoIpTmpExtractedFilesDirectory . '/' . $file) . "\n";
                            }
                            if (is_dir($this->geoIpTmpExtractedFilesDirectory . '/' . $file)) { // ex: /tmp/MaxMind/ExtractedFiles/GeoIP2-Country_20160830
                                $outputFilePath = $this->geoIpTmpExtractedFilesDirectory . '/' . $file . '/GeoIP2-Country.mmdb';
                            }
                        }
                    }
                    closedir($dh);
                }
            }
        } else {  // If not a tar.gz file, it is a gz file
            $gz = gzopen($fileName, 'rb');
            $outputFile = fopen($outputFilePath, 'wb');
            while (!gzeof($gz)) {
                fwrite($outputFile, gzread($gz, 4096));
            }
            fclose($outputFile);
            gzclose($gz);
        }

        return $outputFilePath;
    }
}