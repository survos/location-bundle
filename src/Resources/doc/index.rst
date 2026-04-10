Survos Location Bundle
======================

The bundle provides a GeoNames-aligned location authority for Symfony applications.

Requirements
------------

- PHP 8.4+
- Symfony 7.4 or 8.0
- Doctrine ORM 3

Overview
--------

The bundle exposes a single ``Location`` entity arranged as a nested tree. It is intended for applications that need a stable place authority for countries, subdivisions, and cities.

Admin Tool
----------

Fetch raw GeoNames datasets without relying on a host application's ``bin/console``:

.. code-block:: bash

   php admin/load-geonames.php

Data Sources
------------

Normalized seed data is bundled in ``data/iso-3166-2.json`` and ``data/world-cities.json``. Raw GeoNames source files can be downloaded into a cache directory for inspection or downstream processing.

Notes
-----

The fetch workflow now lives in ``admin/load-geonames.php`` using Symfony's single-command console support. Loading normalized ``Location`` rows is intentionally deferred for a later pass.
