<?php

declare(strict_types=1);

namespace Survos\LocationBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use JsonException;
use Survos\LocationBundle\Entity\Location;
use Survos\LocationBundle\Repository\LocationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Intl\Countries;

#[AsCommand(
    name: 'survos:location:load',
    description: 'Load countries, subdivisions, and cities into the Location hierarchy.',
    aliases: ['survos:location:seed'],
)]
final class LoadCommand
{
    private const COUNTRY_LEVEL = 1;
    private const SUBDIVISION_LEVEL = 2;
    private const CITY_LEVEL = 3;
    private const LEVEL_LABELS = [
        self::COUNTRY_LEVEL => 'Country',
        self::SUBDIVISION_LEVEL => 'Subdivision',
        self::CITY_LEVEL => 'City',
    ];

    public function __construct(
        private readonly ManagerRegistry $registry,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option('Append to existing locations instead of truncating the table first.')]
        bool $append = false,
        #[Option('Path or URL to the ISO 3166-2 subdivisions JSON document.')]
        ?string $isoSource = null,
        #[Option('Path or URL to the normalized world cities JSON document.')]
        ?string $citiesSource = null,
    ): int {
        $entityManager = $this->getEntityManager();
        $repository = $this->getRepository($entityManager);

        if (!$append) {
            $deleted = $repository->createQueryBuilder('location')
                ->delete()
                ->getQuery()
                ->execute();

            $entityManager->clear();
            $io->writeln(sprintf('Deleted %d existing locations.', $deleted));
        }

        $this->loadCountries($entityManager, $io);
        $this->loadSubdivisions($entityManager, $repository, $io, $isoSource ?? $this->defaultIsoSource());
        $this->loadCities($entityManager, $repository, $io, $citiesSource ?? $this->defaultCitiesSource());

        $io->success('Location hierarchy loaded.');

        return Command::SUCCESS;
    }

    private function loadCountries(EntityManagerInterface $entityManager, SymfonyStyle $io): void
    {
        $countries = Countries::getNames();
        $io->section(sprintf('Loading %d countries', count($countries)));

        foreach ($countries as $countryCode => $name) {
            $entityManager->persist(
                (new Location($countryCode, $name, self::COUNTRY_LEVEL))
                    ->setCountryCode($countryCode)
            );
        }

        $this->flush($entityManager, self::COUNTRY_LEVEL, $io);
    }

    private function loadSubdivisions(
        EntityManagerInterface $entityManager,
        LocationRepository $repository,
        SymfonyStyle $io,
        string $source,
    ): void {
        /** @var array<string, array{name?: string, divisions?: array<string, string>}> $subdivisions */
        $subdivisions = $this->readJson($source);
        $countries = $this->countryReferences($repository);

        $loaded = 0;
        $io->section(sprintf('Loading subdivisions from %s', $source));

        foreach ($subdivisions as $countryCode => $countryData) {
            if (!isset($countries[$countryCode])) {
                continue;
            }

            foreach ($countryData['divisions'] ?? [] as $code => $name) {
                $stateCode = (string) preg_replace('/^.*?-/', '', $code);
                $parent = $entityManager->getReference(Location::class, $countries[$countryCode]['id']);

                $entityManager->persist(
                    (new Location((string) $code, (string) $name, self::SUBDIVISION_LEVEL))
                        ->setCountryCode($countryCode)
                        ->setStateCode($stateCode)
                        ->setParent($parent)
                );
                ++$loaded;
            }
        }

        $this->flush($entityManager, self::SUBDIVISION_LEVEL, $io, $loaded);
    }

    private function loadCities(
        EntityManagerInterface $entityManager,
        LocationRepository $repository,
        SymfonyStyle $io,
        string $source,
    ): void {
        /** @var list<array{country: string, geonameid: int|string, name: string, subcountry: string}> $cities */
        $cities = $this->readJson($source);
        $states = $this->subdivisionReferences($repository);

        $loaded = 0;
        $io->section(sprintf('Loading %d cities from %s', count($cities), $source));

        foreach ($cities as $index => $city) {
            $subcountry = $city['subcountry'];
            if (!isset($states[$subcountry])) {
                continue;
            }

            $parentData = $states[$subcountry];
            $entityManager->persist(
                (new Location((string) $city['geonameid'], $city['name'], self::CITY_LEVEL))
                    ->setCountryCode($parentData['countryCode'])
                    ->setStateCode($parentData['stateCode'])
                    ->setParent($entityManager->getReference(Location::class, $parentData['id']))
            );

            ++$loaded;

            if (0 === ($index + 1) % 2500) {
                $entityManager->flush();
                $entityManager->clear();
                $io->writeln(sprintf('Processed %d cities...', $index + 1));
            }
        }

        $this->flush($entityManager, self::CITY_LEVEL, $io, $loaded);
    }

    /**
     * @return array<string, array{id: int}>
     */
    private function countryReferences(LocationRepository $repository): array
    {
        $countries = [];
        foreach ($repository->createQueryBuilder('location')
            ->select('location.id, location.countryCode')
            ->where('location.lvl = :level')
            ->setParameter('level', self::COUNTRY_LEVEL)
            ->getQuery()
            ->getArrayResult() as $row) {
            $countryCode = $row['countryCode'];
            if (null === $countryCode) {
                continue;
            }

            $countries[$countryCode] = ['id' => (int) $row['id']];
        }

        return $countries;
    }

    /**
     * @return array<string, array{id: int, stateCode: ?string, countryCode: ?string}>
     */
    private function subdivisionReferences(LocationRepository $repository): array
    {
        $states = [];
        foreach ($repository->createQueryBuilder('location')
            ->select('location.id, location.name, location.stateCode, location.countryCode')
            ->where('location.lvl = :level')
            ->setParameter('level', self::SUBDIVISION_LEVEL)
            ->getQuery()
            ->getArrayResult() as $row) {
            $states[$row['name']] = [
                'id' => (int) $row['id'],
                'stateCode' => $row['stateCode'],
                'countryCode' => $row['countryCode'],
            ];

            if ('DC' === $row['stateCode']) {
                $states['Washington, D.C.'] = $states[$row['name']];
            }
        }

        return $states;
    }

    private function flush(
        EntityManagerInterface $entityManager,
        int $level,
        SymfonyStyle $io,
        int $count = 0,
    ): void {
        $entityManager->flush();
        $entityManager->clear();

        $io->writeln(sprintf(
            'Flushed level %d (%s)%s',
            $level,
            self::LEVEL_LABELS[$level] ?? 'Unknown',
            $count > 0 ? sprintf(' with %d records.', $count) : '.',
        ));
    }

    /**
     * @return array<mixed>
     */
    private function readJson(string $source): array
    {
        $contents = @file_get_contents($source);
        if (false === $contents) {
            throw new \RuntimeException(sprintf('Unable to read JSON from "%s".', $source));
        }

        try {
            /** @var array<mixed> $decoded */
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        } catch (JsonException $exception) {
            throw new \RuntimeException(sprintf('Invalid JSON in "%s": %s', $source, $exception->getMessage()), 0, $exception);
        }
    }

    private function getEntityManager(): EntityManagerInterface
    {
        $entityManager = $this->registry->getManagerForClass(Location::class);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \RuntimeException('Unable to resolve an entity manager for Location.');
        }

        return $entityManager;
    }

    private function getRepository(EntityManagerInterface $entityManager): LocationRepository
    {
        $repository = $entityManager->getRepository(Location::class);
        if (!$repository instanceof LocationRepository) {
            throw new \RuntimeException('Unable to resolve the Location repository.');
        }

        return $repository;
    }

    private function defaultIsoSource(): string
    {
        return dirname(__DIR__, 2) . '/data/iso-3166-2.json';
    }

    private function defaultCitiesSource(): string
    {
        return dirname(__DIR__, 2) . '/data/world-cities.json';
    }
}
