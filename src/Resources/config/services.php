<?php

declare(strict_types=1);


namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\ORM\EntityManager;
use Gedmo\Tree\TreeListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('survos_location.bar', '');

    $parameters->set('survos_location.integer_foo', '');

    $parameters->set('survos_location.integer_bar', '');

    $services = $containerConfigurator->services();

//    $services->set('doctrine.orm.survos_location_entity_manager', EntityManager::class)
//        ->tag()

    $services->set('survos_location.repository.location_repository',
        'Survos\LocationBundle\Repository\LocationRepository')
        ->arg('$registry', service('doctrine.orm.survos_location_entity_manager'));

    $services->set('survos_location.country_import', 'Survos\LocationBundle\Service\CountryImport')
        ->arg('$registry', service('Doctrine\Common\Persistence\ManagerRegistry'));

    $services->set('gedmo.listener.tree', TreeListener::class)
        ->tag('doctrine.event_subscriber', ['connection' => 'location'])
        ->call('setAnnotationReader', [new Reference('annotation_reader')]);

//    gedmo.listener.tree:
//        class: Gedmo\Tree\TreeListener
//        tags:
//            - { name: doctrine.event_subscriber, connection: default }
//        calls:
//            - [ setAnnotationReader, [ "@annotation_reader" ] ]

    $services->set('survos_location.service.administrative_import', 'Survos\LocationBundle\Service\AdministrativeImport')
        ->arg('$registry', service('Doctrine\Common\Persistence\ManagerRegistry'));

    $services->set('survos_location.service', 'Survos\LocationBundle\Service\Service')
        ->arg('$em', service('doctrine.orm.entity_manager'))
        ->arg('$token', service('security.token_storage'))
        ->arg('$requestStack', service('request_stack'))
        ->arg('$translator', service('translator.default'))
        ->arg('$bar', '%survos_location.bar%')
        ->arg('$integerFoo', '%survos_location.integer_foo%')
        ->arg('$integerBar', '%survos_location.integer_bar%');

    $services->set('survos_location.controller', 'Survos\LocationBundle\Controller\SurvosLocationController')
        ->public()
        ->tag('container.service_subscriber')
        ->tag('controller.service_arguments')
        ->autowire(true)
        ->arg('$service', service('survos_location.service'));

    $services->set('survos_location.load_command', 'Survos\LocationBundle\Command\LoadCommand')
        ->public()
        ->tag('console.command')
        ->arg('$registry', service('Doctrine\Common\Persistence\ManagerRegistry'))
//        ->arg('$validator', service('debug.validator'))
    ;

    $services->set('survos_location.command.import_geonames_command', 'Survos\LocationBundle\Command\ImportGeonamesCommand')
        ->public()
        ->tag('console.command')
        ->args([service('parameter_bag'), service('survos_location.country_import'), service('survos_location.service.administrative_import')]);

    $services->alias('Survos\LocationBundle\Service\Service', 'survos_location.service');

    $services->alias('Survos\LocationBundle\Repository\LocationRepository', 'survos_location.repository.location_repository');

    $services->alias('Survos\LocationBundle\Controller\SurvosLocationController', 'survos_location.controller')
        ->public();
};
