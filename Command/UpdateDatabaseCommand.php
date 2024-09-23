<?php

namespace Cravler\MaxMindGeoIpBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sergei Vizel <sergei.vizel@gmail.com>
 */
#[AsCommand(
    name: 'cravler:maxmind:geoip-update',
    description: 'Downloads and updates the MaxMind GeoIp2 database',
)]
class UpdateDatabaseCommand extends Command
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private readonly array $config = [])
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('no-md5-check', null, InputOption::VALUE_NONE, 'Disable MD5 check');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var array<string, string> $db */
        $db = \is_array($this->config['db'] ?? null) ? $this->config['db'] : [];

        /** @var array<string, ?string> $md5Check */
        $md5Check = \is_array($this->config['md5_check'] ?? null) ? $this->config['md5_check'] : [];

        /** @var array<string, ?string> $sources */
        $sources = \is_array($this->config['source'] ?? null) ? $this->config['source'] : [];

        foreach ($sources as $key => $source) {
            if (!$source) {
                continue;
            }

            $output->writeln('');
            $output->write(\sprintf('Downloading %s... ', $source));

            $tmpFile = $this->downloadFile($source);
            if (!$tmpFile) {
                $output->writeln('FAILED');
                $output->writeln(\sprintf('<error>Error during file download occurred on %s</error>', $source));
                continue;
            }

            $output->writeln('<info>Done</info>');
            $output->write('Unzipping the downloaded data... ');
            $tmpFileUnzipped = \dirname($tmpFile) . DIRECTORY_SEPARATOR . $db[$key];

            $success = $this->decompressFile($tmpFile, $tmpFileUnzipped);

            $calculatedMD5 = null;
            if (!$input->getOption('no-md5-check')) {
                if (\str_contains($tmpFile, '.tar.gz')) {
                    $calculatedMD5 = \md5_file($tmpFile);
                } else {
                    $calculatedMD5 = \md5_file($tmpFileUnzipped);
                }
            }

            \unlink($tmpFile);

            if ($success) {
                $output->writeln('<info>Done</info>');
            } else {
                $output->writeln(\sprintf('<error>An error occured when decompressing %s</error>', \basename($tmpFile)));
                continue;
            }

            // MD5 check
            if (!$input->getOption('no-md5-check')) {
                $output->write('Checking file hash... ');
                if (\is_string($md5Check[$key] ?? null)) {
                    $expectedMD5 = \file_get_contents($md5Check[$key]);

                    if (!$expectedMD5 || 32 !== \strlen($expectedMD5)) {
                        \unlink($tmpFileUnzipped);
                        $output->writeln(\sprintf('<error>Unable to check MD5 for %s</error>', $source));
                        continue;
                    } elseif ($expectedMD5 !== $calculatedMD5) {
                        \unlink($tmpFileUnzipped);
                        $output->writeln(\sprintf('<error>MD5 for %s does not match</error>', $source));
                        continue;
                    } else {
                        $output->writeln('<info>File hash OK</info>');
                    }
                } else {
                    $output->writeln('<comment>Skipped</comment>');
                }
            }

            /** @var string $path */
            $path = $this->config['path'];
            if (!\file_exists($path)) {
                \mkdir($path, 0777, true);
            }

            $outputFilePath = $this->config['path'] . DIRECTORY_SEPARATOR . $db[$key];
            \chmod(\dirname($outputFilePath), 0777);
            $success = @\rename($tmpFileUnzipped, $outputFilePath);

            if ($success) {
                $output->writeln(\sprintf('<info>Update completed for %s</info>', $key));
            } else {
                $output->writeln(\sprintf('<error>Unable to update %s</error>', $key));
            }
        }
        $output->writeln('');

        return 0;
    }

    private function downloadFile(string $source): ?string
    {
        $tmpFile = \tempnam(\sys_get_temp_dir(), 'maxmind_geoip2_');
        if (\is_string($tmpFile) && \str_contains($source, 'tar.gz')) {
            @\rename($tmpFile, $tmpFile . '.tar.gz');
            $tmpFile .= '.tar.gz';
        }

        if (!\is_string($tmpFile) || !@\copy($source, $tmpFile)) {
            return null;
        }

        return $tmpFile;
    }

    private function decompressFile(string $fileName, string $outputFilePath): bool
    {
        if (\str_contains($fileName, '.tar.gz')) {
            $tmpDir = \tempnam(\sys_get_temp_dir(), 'MaxMind_');
            if (!\is_string($tmpDir)) {
                return false;
            }
            \unlink($tmpDir);
            \mkdir($tmpDir);

            $p = new \PharData($fileName);
            $p->decompress();

            $tarFileName = \str_replace('.gz', '', $fileName);
            $phar = new \PharData($tarFileName);
            $phar->extractTo($tmpDir);
            \unlink($tarFileName);

            $foundFiles = \glob($tmpDir . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.mmdb');
            if (\is_array($foundFiles) && \count($foundFiles)) {
                @\rename($foundFiles[0], $outputFilePath);
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tmpDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $fileInfo) {
                /** @var \SplFileInfo $fileInfo */
                if ($fileInfo->isDir()) {
                    \rmdir($fileInfo->getRealPath());
                } else {
                    \unlink($fileInfo->getRealPath());
                }
            }
            \rmdir($tmpDir);
        } else {
            $gz = \gzopen($fileName, 'rb');
            if (!\is_resource($gz)) {
                return false;
            }
            $outputFile = \fopen($outputFilePath, 'wb');
            if (!\is_resource($outputFile)) {
                return false;
            }
            while (!\gzeof($gz)) {
                $data = \gzread($gz, 4096);
                if (false === $data) {
                    return false;
                }
                \fwrite($outputFile, $data);
            }
            \fclose($outputFile);
            \gzclose($gz);
        }

        return \is_readable($outputFilePath);
    }
}
