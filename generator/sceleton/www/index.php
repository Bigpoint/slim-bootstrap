<?php
require __DIR__ . '/../vendor/autoload.php';

use \Rest\Api;

$applicationConfig = json_decode(
    file_get_contents(__DIR__ . '/../config/application.json'),
    true
);

// create logger
$loggerFactory        = new \MonologCreator\Factory(
    $applicationConfig['monolog']
);
$authenticationLogger = $loggerFactory->createLogger('authentication');
$phpLogger            = $loggerFactory->createLogger('php');

// register php error logger
\Monolog\ErrorHandler::register($phpLogger);

$bootstrap = new Api\Bootstrap($applicationConfig);
$bootstrap->init();

// --- V1 Endpoints - begin ---
$bootstrap->addRessourceGetEndpoint(
    '/v1/dummy/:dummyId',
    'dummy',
    array(
        'dummyId' => '\\d+',
    ),
    new \###NAMESPACE###\Endpoint\V1\Ressource\Dummy()
);
$bootstrap->addCollectionGetEndpoint(
    '/v1/dummy',
    'dummy',
    new \###NAMESPACE###\Endpoint\V1\Collection\Dummy()
);
// --- V1 Endpoints - end ---

$bootstrap->run();
