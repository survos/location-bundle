<?php

namespace Survos\LocationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SurvosLocationExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {

//        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
//        $loader->load('services.xml');
//        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');

        $config = $this->processConfiguration(new Configuration(), $configs);




        // TODO: Set custom parameters
//         $container->setParameter('survos_location.bar', $config['bar']);
//         $container->setParameter('survos_location.integer_foo', $config['integer_foo']);
//         $container->setParameter('survos_location.integer_bar', $config['integer_bar']);
    }

    public function prepend(ContainerBuilder $container)
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

//        $container->loadFromExtension('doctrine', array(
//            'orm' => array(
//                'mappings' => array(
//                    'FooBarBundle' => null,
//                )
//            ),
//        ));

        $params =   array(
            'driver'    =>'pdo_sqlite',
            'url' => 'x.db'
        );

//$factory = $container->get('doctrine.dbal.connection_factory');
//$connection = $factory->createConnection($params);
//dd($connection);

        // TODO: Set custom doctrine config
        $doctrineConfig = [];
//        $doctrineConfig['orm']['resolve_target_entities']['Survos\LocationBundle\Entity\UserInterface'] = $config['user_provider'];
//        $doctrineConfig['orm']['mappings'][] = array(
//            'name' => 'SurvosLocationBundle',
//            'is_bundle' => true,
////            'type' => 'xml',
//            'prefix' => 'Survos\LocationBundle\Entity'
//        );
        $container->prependExtensionConfig('doctrine', $doctrineConfig);
//        dd($doctrineConfig);
        // TODO: Set custom twig config
        $twigConfig = [];
        $twigConfig['globals']['survos_location_bar_service'] = "@survos_location.service";
        $twigConfig['paths'][__DIR__.'/../Resources/views'] = "survos_location";
        $twigConfig['paths'][__DIR__.'/../Resources/public'] = "survos_location.public";
        $container->prependExtensionConfig('twig', $twigConfig);
    }

    public function getAlias(): string
    {
        return 'survos_location';
    }
}
