<?php

// User's service configuration file
// This file is loaded into the Symfony DI container

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        ->set('ai_mate_symfony.cache_dir', '%mate.root_dir%/var/cache')
        ->set('ai_mate_symfony.profiler_dir', '%mate.root_dir%/var/cache/dev/profiler')
        ->set('ai_mate_monolog.log_dir', '%mate.root_dir%/var/log')
    ;

    $container->services()
        // Register your custom services here
    ;
};
