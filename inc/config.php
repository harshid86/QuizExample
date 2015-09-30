<?php

// Configs for mode "development" 
$app->configureMode('development', function () use ($app) {

    // pre-application hook, performs stuff before real action happens 
    $app->hook('slim.before', function () use ($app) {


        // CSS minifier @see https://github.com/matthiasmullie/minify
        $minifier = new MatthiasMullie\Minify\CSS('css/style.css');
        $minifier->minify('css/style.min.css');

    });

    // Set the configs for development environment
    $app->config(array(
        'debug' => true,
        'database' => array(
            'db_host' => 'localhost',
            'db_port' => '',
            'db_name' => 'quiz',
            'db_user' => 'root',
            'db_pass' => 'assword'
        )
    ));
});

// Configs for mode "production"
$app->configureMode('production', function () use ($app) {
    // Set the configs for production environment
    $app->config(array(
        'debug' => false,
        'database' => array(
            'db_host' => 'localhost',
            'db_port' => '',
            'db_name' => 'quiz',
            'db_user' => 'root',
            'db_pass' => 'password'
        )
    ));
});