<?php
require __DIR__ . '/../vendor/autoload.php';

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

$bootstrap = new \SlimBootstrap\Bootstrap($applicationConfig);
$bootstrap->init();

// --- V1 Endpoints - begin ---
$bootstrap->addResourceEndpoint(
    \SlimBootstrap\Bootstrap::HTTP_METHOD_GET,
    '/v1/dummy/:dummyId',
    'dummy',
    array(
        'dummyId' => '\\d+',
    ),
    new \###NAMESPACE###\Endpoint\V1\Resource\Dummy()
);
$bootstrap->addCollectionEndpoint(
    \SlimBootstrap\Bootstrap::HTTP_METHOD_GET,
    '/v1/dummy',
    'dummy',
    new \###NAMESPACE###\Endpoint\V1\Collection\Dummy()
);
// --- V1 Endpoints - end ---

$bootstrap->run();
