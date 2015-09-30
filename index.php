<?php

/******************************* LOADING & INITIALIZING BASE APPLICATION ****************************************/

// Configuration for error reporting, useful to show every little problem during development
error_reporting(E_ALL);
ini_set("display_errors", 1);

// Load Composer's PSR-4 autoloader (necessary to load Slim, Mini etc.)
require './vendor/autoload.php';

// Initialize Slim (the router/micro framework used)
$app = new \Slim\Slim();

// and define the engine used for the view @see http://twig.sensiolabs.org
$app->view = new \Slim\Views\Twig();
$app->view->setTemplatesDirectory("inc/view");

/******************************************* THE CONFIGS *******************************************************/

require_once 'inc/config.php';
require_once 'inc/helper.php';

/******************************************** THE MODEL ********************************************************/

require_once 'inc/entities.php';

// Initialize the model, pass the database configs. $model can now perform all methods from Mini\model\model.php
$model = new DataModel($app->config('database'));

/************************************ THE ROUTES / CONTROLLERS *************************************************/

require_once 'inc/routes.php';

/******************************************* RUN THE APP *******************************************************/

$app->run();

//include 'test.php';