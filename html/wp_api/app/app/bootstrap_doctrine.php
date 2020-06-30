<?php
// bootstrap_doctrine.php

use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Silex\Provider\DoctrineServiceProvider;

require __DIR__.'/lib/DebugStack.php';

$app->register(new DoctrineServiceProvider, array(
    "db.options" => array(
        'driver' => 'pdo_mysql',
        'dbname' => 'wp_api',
        'user' => 'root',
        'password' => 'rvtech123',
        'host' => 'localhost',
    ),
));

$app->register(new DoctrineOrmServiceProvider, array(
    "orm.proxies_dir" => __DIR__."/Proxies",
    "orm.em.options" => array(
        "mappings" => array(
            // Using actual filesystem paths
            array(
                "type" => "annotation",
                "namespace" => "Entities",
                "path" => __DIR__."/Entities",
            ),
            // Using PSR-0 namespaceish embedded resources
            // (requires registering a PSR-0 Resource Locator
            // Service Provider)
            // array(
            //     "type" => "annotations",
            //     "namespace" => "Entities",
            //     "resources_namespace" => "Entities",
            // ),
        ),
    ),
));

if ($app['debug']) {
    $app['db']->getConfiguration()->setSQLLogger(new DebugStack($app['monolog']));
}

?>