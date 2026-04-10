<?php

declare(strict_types=1);

namespace Survos\LocationBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'survos:location:fetch-geonames',
    description: 'Download raw GeoNames reference files for location authority data.',
    aliases: ['survos:location:geonames-import'],
)]
final class ImportGeonamesCommand
{
    private const PROGRESS_FORMAT = '%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% Mem: %memory:6s% %message%';

    /**
     * @var list<string>
     */
    private const DEFAULT_FILES = [
        'countryInfo.txt',
        'admin1CodesASCII.txt',
        'admin2Codes.txt',
        'timeZones.txt',
        'hierarchy.zip',
        'allCountries.zip',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Filesystem $filesystem,
        private readonly string $cacheDir,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option('GeoNames base download URL.')]
        string $endpoint = 'https://download.geonames.org/export/dump/',
        #[Option('Directory where downloaded files should be stored.')]
        ?string $downloadDir = null,
        #[Option('Download again even when the local file already exists.')]
        bool $force = false,
        #[Option('Specific GeoNames files to fetch. Repeat the option to limit the download set.')]
        array $file = [],
    ): int {
        $downloadDir ??= $this->cacheDir . '/geonames';
        $this->filesystem->mkdir($downloadDir, 0700);

        $files = $file !== [] ? array_values(array_unique($file)) : self::DEFAULT_FILES;
        $io->title('GeoNames download');
        $io->text(sprintf('Endpoint: %s', rtrim($endpoint, '/') . '/'));
        $io->text(sprintf('Target directory: %s', $downloadDir));

        foreach ($files as $filename) {
            $this->downloadFile($io, rtrim($endpoint, '/') . '/' . ltrim($filename, '/'), $downloadDir . '/' . basename($filename), $force);
        }

        $io->success('GeoNames reference files are available locally.');
        $io->note('The standalone alternative remains available at php admin/load-geonames.php.');

        return Command::SUCCESS;
    }

    private function downloadFile(SymfonyStyle $io, string $url, string $targetPath, bool $force): void
    {
        if (!$force && is_file($targetPath)) {
            $io->writeln(sprintf('Skipping %s, already present.', basename($targetPath)));

            return;
        }

        $progress = new ProgressBar($io, 100);
        $progress->setFormat(self::PROGRESS_FORMAT);
        $progress->setMessage(sprintf('Downloading %s', basename($targetPath)));
        $progress->start();

        $response = $this->httpClient->request('GET', $url, [
            'on_progress' => static function (int $downloadedSize, int $totalSize) use ($progress): void {
                if ($totalSize > 0) {
                    $progress->setProgress((int) min(100, round(($downloadedSize / $totalSize) * 100)));
                }
            },
        ]);

        $stream = $this->httpClient->stream($response);
        $handle = fopen($targetPath, 'wb');
        if (false === $handle) {
            throw new \RuntimeException(sprintf('Unable to open "%s" for writing.', $targetPath));
        }

        foreach ($stream as $chunk) {
            if ($chunk->isTimeout()) {
                continue;
            }

            fwrite($handle, $chunk->getContent());
        }

        fclose($handle);
        $progress->setProgress(100);
        $progress->finish();
        $io->newLine(2);
    }
}
