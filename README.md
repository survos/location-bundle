SurvosLocationBundle
===================

The SurvosLocationBundle uses the data from https://download.geonames.org/export/dump/ to create a Nested Tree with location data.

```bash
composer req survos/location-bundle
```

![Alt text](Resources/doc/logo.png?raw=true "Screenshot")

Features include:

- ...

[![Build Status](https://travis-ci.org/survos/SurvosLocationBundle.svg?branch=master)](https://travis-ci.org/survos/SurvosLocationBundle) [![Code Coverage](https://scrutinizer-ci.com/g/survos/SurvosLocationBundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/survos/SurvosLocationBundle/?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/survos/SurvosLocationBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/survos/SurvosLocationBundle/?branch=master) [![Latest Stable Version](https://poser.pugx.org/survos/voting-bundle/v/stable.svg)](https://packagist.org/packages/survos/voting-bundle)

Documentation
-------------

The source of the documentation is stored in the `Resources/doc/` folder
in this bundle:

[Read the Documentation](Resources/doc/index.rst)

Installation
------------

All the installation instructions are located in the documentation.

composer config minimum-stability dev
composer config prefer-stable true

composer config repositories.survos_location_bundle '{"type": "vcs", "url": "git@github.com:survos/LocationBundle.git"}'
composer req survos/location-bundle:"*@dev"


License
-------

This bundle is under the MIT license. See the complete license [in the bundle](LICENSE)

About
-----

LocationBundle is a [survos](https://github.com/survos) initiative.
See also the list of [contributors](https://github.com/survos/SurvosLocationBundle/contributors).

Reporting an issue or a feature request
---------------------------------------

Issues and feature requests are tracked in the [Github issue tracker](https://github.com/survos/SurvosLocationBundle/issues).

When reporting a bug, it may be a good idea to reproduce it in a basic project
built using the [Symfony Standard Edition](https://github.com/symfony/symfony-standard)
to allow developers of the bundle to reproduce the issue by simply cloning it
and following some steps.
