<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Survos\LocationBundle\Command\ImportGeonamesCommand;
use Survos\LocationBundle\Command\LoadCommand;
use Survos\LocationBundle\Controller\SurvosLocationController;
use Survos\LocationBundle\Repository\LocationRepository;
use Survos\LocationBundle\Service\Service;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('survos_location.bar', '');

    $parameters->set('survos_location.integer_foo', '');

    $parameters->set('survos_location.integer_bar', '');

    $services = $containerConfigurator->services();
    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(LocationRepository::class)
        ->arg('$registry', service('doctrine'));

    $services->set(Service::class)
        ->arg('$em', service('doctrine.orm.entity_manager'))
        ->arg('$token', service('security.token_storage'))
        ->arg('$requestStack', service('request_stack'))
        ->arg('$translator', service('translator.default'))
        ->arg('$bar', '%survos_location.bar%')
        ->arg('$integerFoo', '%survos_location.integer_foo%')
        ->arg('$integerBar', '%survos_location.integer_bar%');

    $services->set(SurvosLocationController::class)
        ->public()
        ->arg('$service', service(Service::class));

    $services->set(LoadCommand::class);
    $services->set(ImportGeonamesCommand::class)
        ->arg('$cacheDir', '%kernel.cache_dir%');

    $services->alias('survos_location.service', Service::class);
    $services->alias('survos_location.repository.location_repository', LocationRepository::class);
    $services->alias('survos_location.controller', SurvosLocationController::class)
        ->public();
};
