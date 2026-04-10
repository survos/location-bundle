
# Survos Location Bundle




DEPRECATED:  See survos/geonames-bundle instead.








`survos/location-bundle` provides a Symfony `Location` authority built from GeoNames-aligned reference data. The bundle ships a Doctrine entity, repository, API metadata, and an external admin tool for fetching raw GeoNames datasets without depending on a host application's `bin/console`.

The current bundle targets PHP 8.4+, Doctrine ORM 3, and Symfony 7.4 or 8.0.

## What It Does

- Stores locations as a nested tree using Gedmo Tree metadata.
- Seeds countries, first-level subdivisions, and cities into a single `Location` entity.
- Downloads canonical GeoNames source files for future refreshes and import workflows.
- Exposes the `Location` entity through Doctrine and API Platform metadata.

## Installation

```bash
composer require survos/location-bundle
```

Your host application should also enable Doctrine and, if you rely on tree operations, StofDoctrineExtensionsBundle or an equivalent Gedmo Tree listener setup.

## Admin Tool

Fetch raw GeoNames source files from the repository root:

```bash
php admin/load-geonames.php
```

Useful options:

- `--download-dir=/path/to/cache/geonames` controls where files are stored.
- `--force` re-downloads files that already exist locally.
- `--file=countryInfo.txt --file=admin1CodesASCII.txt` limits the download set.

This tool is implemented with Symfony's `SingleCommandApplication`, so it remains usable even when the consuming application does not provide a local `Kernel` or `bin/console`.

## Data Model

The bundle centers on `Survos\LocationBundle\Entity\Location`, which stores:

- a unique authority `code`
- the display `name`
- a numeric hierarchy level
- optional `countryCode` and `stateCode`
- parent/child relationships for nested traversal

Bundled seed data lives in [data/iso-3166-2.json](/home/tac/g/sites/mono/bu/location-bundle/data/iso-3166-2.json) and [data/world-cities.json](/home/tac/g/sites/mono/bu/location-bundle/data/world-cities.json).

## Development

Run the test suite with:

```bash
vendor/bin/phpunit
```

The admin fetcher depends only on Composer autoloading and the Symfony Console component.

## Status

This repository has been modernized around the bundle’s current value proposition: authoritative location naming backed by GeoNames reference data. The fetch workflow now lives under `admin/` so it can stay outside any consuming application. Loading normalized `Location` data is intentionally deferred for a later pass.

## License

MIT. See [LICENSE](/home/tac/g/sites/mono/bu/location-bundle/LICENSE).
