<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Survos\LocationBundle\Controller\SurvosLocationController;

return static function (ContainerConfigurator $configurator): void {

    $services = $configurator->services();

    dd('x');
    $services->defaults()
        ->private()
        ->autowire(true)
        ->autoconfigure(false);

    $services->set(SurvosLocationController::class)
        ->tag('controller.service_arguments')
        ->tag('container.service_subscriber');

};
