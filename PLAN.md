# Geo Bundle Transition Plan

## Goal

Move from the current `location-bundle` design to a simpler geo authority bundle that:

- drops Gedmo tree support
- drops ApiPlatform integration
- treats loading as an offline/admin concern
- moves toward SQLite-backed authority data
- eventually allows raw DBAL or DTO-based lookups without Doctrine ORM at runtime

The likely destination is a new package and workflow centered on `survos/geo-bundle`, with this bundle acting as the transition ground.

## Product Direction

Target workflow:

1. Install `survos/geo-bundle`
2. Run `bin/console survos:geo`
3. The command fetches a prebuilt geo authority database from a remote source such as Hugging Face
4. Default fetch gives a compact 2-level admin database
5. `bin/console survos:geo country=us` fetches a deeper country-specific database with 3 levels
6. `bin/console survos:geo --all` fetches the full large database
7. Runtime usage becomes service-based, for example:

```php
$geoId = $this->geoService->find('Oslo');
```

The runtime side should not need to build or mutate the authority graph. It should consume a prebuilt authority.

## Core Architecture Decisions

### 1. Stop using Gedmo

Gedmo nested-set support is not the right long-term fit here.

Reasons:

- it adds complexity to writes
- it requires entity lifecycle coordination for a dataset that is mostly immutable
- tree maintenance is unnecessary if the authority is fetched prebuilt
- parent-child traversal can be represented directly with codes or flat lookup tables

Replacement direction:

- use `parent_code` instead of ORM parent/child associations
- store `lvl` explicitly
- compute or persist `child_count` during the import phase only
- prefer indexed flat records over ORM graph mutation

### 2. Stop using ApiPlatform

ApiPlatform is not central to the geo authority use case.

Reasons:

- this bundle is becoming a data authority/provider, not an API product
- runtime consumers mostly need lookup/search access
- exposing the entity as an API resource couples persistence decisions to delivery concerns

Replacement direction:

- keep retrieval behind a `GeoService`
- expose search/query methods explicitly
- let consuming applications decide whether to wrap results in controllers, APIs, or forms

### 3. Separate loading from usage

Loading data should be an admin operation, not a runtime application responsibility.

That means:

- admin/fetch commands can use Doctrine ORM, DBAL, or raw SQLite writes as needed
- host applications should not need full write-time infrastructure just to query a place name
- the standalone admin tool remains useful during migration
- the eventual `survos:geo` command should download ready-to-use data, not rebuild from raw files every time

## Transitional Model

During the transition, introduce a new `Geo` entity for the new admin loader path.

Purpose:

- isolate the new simplified shape from the current `Location` entity
- allow migration work without immediately breaking existing consumers
- prove out the SQLite/flat-table model before replacing `Location`

Initial shape should be close to:

```php
<?php
namespace Survos\LocationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'location')]
#[ORM\Index(columns: ['parent_code'], name: 'idx_location_parent')]
#[ORM\Index(columns: ['country_code'], name: 'idx_location_country')]
#[ORM\Index(columns: ['lvl'], name: 'idx_location_lvl')]
#[ORM\UniqueConstraint(columns: ['code'])]
class Location implements \Stringable
{
    public private(set) int $childCount = 0;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 20)]
        public private(set) string $code,

        #[ORM\Column(length: 180)]
        public private(set) string $name,

        #[ORM\Column]
        public private(set) int $lvl,

        #[ORM\Column(length: 20, nullable: true)]
        public private(set) ?string $parentCode = null,

        #[ORM\Column(length: 2, nullable: true)]
        public private(set) ?string $countryCode = null,

        #[ORM\Column(nullable: true)]
        public private(set) ?int $population = null,

        #[ORM\Column(nullable: true)]
        public private(set) ?float $latitude = null,

        #[ORM\Column(nullable: true)]
        public private(set) ?float $longitude = null,
    ) {}

    public function setChildCount(int $childCount): static
    {
        $this->childCount = $childCount;
        return $this;
    }

    public function __toString(): string { return $this->name; }
}
```

Notes on this draft:

- `code` as the identifier is the right direction
- `parentCode` is preferable to a self-referencing ORM association
- latitude/longitude/population belong in the authority model
- `childCount` can remain derived-at-load-time metadata
- eventually this class may become `Geo`, and later even disappear behind a DTO/DBAL layer

## Proposed Phases

### Phase 1: Transition inside this bundle

Objective: create the new loading path without breaking existing integrations yet.

Tasks:

- add `Geo.php` as the new simplified authority entity
- keep `Location` available temporarily for compatibility
- make the admin command load into `Geo`, not `Location`
- stop adding new Gedmo-dependent behavior
- stop adding new ApiPlatform metadata
- introduce a `GeoService` abstraction for lookup-oriented reads

Success criteria:

- new authority data can be imported without Gedmo
- existing consumers remain functional during migration
- read-path experiments can begin against the new schema

### Phase 2: Replace runtime assumptions

Objective: stop treating ORM entities as the public API.

Tasks:

- update consumers to call `GeoService`
- define lookup methods such as:
  - `find(string $query): ?string`
  - `search(string $query, int $limit = 10): array`
  - `byCode(string $code): ?GeoResult`
  - `childrenOf(string $code): array`
- decide whether return values are entities, DTOs, or arrays
- remove direct dependence on `LocationRepository` in consuming code

Success criteria:

- consumers work from a service contract
- storage backend can change without breaking callers

### Phase 3: Move to fetched SQLite authority

Objective: stop importing raw source files during normal setup.

Tasks:

- publish prebuilt SQLite authority files
- define variants:
  - default compact admin database
  - country-specific databases
  - full global database
- implement `bin/console survos:geo`
- support inputs such as:
  - `bin/console survos:geo`
  - `bin/console survos:geo country=us`
  - `bin/console survos:geo --all`
- cache the downloaded database locally
- point `GeoService` at that SQLite source

Success criteria:

- host projects do not need to run expensive imports
- setup is mostly download + query
- lookup performance is predictable

### Phase 4: Remove legacy pieces

Objective: finish the migration and reduce the bundle to the minimal useful core.

Tasks:

- remove Gedmo mappings and dependencies
- remove ApiPlatform metadata and dependencies
- remove legacy `Location` entity if `Geo` fully replaces it
- remove ORM entirely if DBAL/SQLite proves sufficient
- rename the package or promote `survos/geo-bundle` as the primary package

Success criteria:

- the bundle is authority-focused, small, and easy to install
- the data pipeline is clearly offline
- runtime code depends only on stable lookup APIs

## Data Source Strategy

There are really two separate concerns:

1. Raw source material
2. Distributed authority artifact

Raw source material:

- GeoNames upstream files
- country and admin datasets
- optional enrichment sources

Distributed artifact:

- prebuilt SQLite databases
- versioned and downloadable
- hosted somewhere stable, such as Hugging Face

The command `survos:geo` should fetch the distributed artifact, not rebuild from raw GeoNames by default.

## Recommended Service Shape

The eventual runtime center should be `GeoService`.

Responsibilities:

- ensure authority database is available
- search by name
- resolve a name to a canonical geo id
- fetch records by code
- optionally fetch parent/child chains

Non-responsibilities:

- rebuilding raw datasets
- maintaining nested-set structures
- exposing generic CRUD

## Risks And Decisions To Resolve

### 1. Is `Geo` persistent or just transitional?

Current view:

- `Geo` should exist now as a transitional entity
- long-term, a DTO plus raw DBAL/SQLite queries may be better than ORM entities

### 2. What is the canonical ID?

Candidates:

- GeoNames id
- a prefixed synthetic code
- ISO/admin/city hybrid codes

Recommendation:

- keep one canonical `code`
- preserve upstream GeoNames ids where possible
- avoid integer surrogate ids for the authority model

### 3. What is the minimum useful hierarchy?

Proposed profiles:

- default: country + admin1/admin2
- country-specific: include city level
- full: global city authority

This aligns with the proposed command UX.

### 4. What remains in this repository versus a new repo?

Likely answer:

- use this repository to prototype the transition
- move the final product identity to `survos/geo-bundle`

## Immediate Next Steps

1. Add `Geo.php` with the simplified schema.
2. Introduce a new loader path that writes `Geo` records without Gedmo.
3. Keep existing `Location` behavior only as a migration bridge.
4. Strip ApiPlatform from new code paths.
5. Add a first `GeoService` contract focused on lookup, not CRUD.
6. Design the SQLite artifact format and metadata.
7. Prototype `survos:geo` as a fetch-first command.

## Opinion

The direction is sound.

The strongest part of the proposal is treating geo authority as a distributed data asset, not as a tree that every consuming app rebuilds locally. That reduces setup cost, removes most of the value of Gedmo, and makes SQLite a much better fit than full ORM graph persistence.

The key discipline will be not trying to preserve every old abstraction. If the end state is fetchable SQLite plus a small lookup service, then new transition work should be optimized toward that outcome and not toward keeping the old `Location` model comfortable.
