# REST API

This library is the core for an easy to build REST API.

It is an abstraction of the [Slim Framework](http://slimframework.com/) and handles some stuff like output generation in different formats and authentication / acl handling.

## Dependencies
 - [PHP](http://php.net/) >= 5.4.4
 - [Debian](http://debian.org/) 7.6
 - [NginX](http://nginx.org)
 - [PHP-FPM](http://php-fpm.org/)
 - **Composer**:
    - [slim/slim](https://packagist.org/packages/slim/slim) 2.4.*
    - [flynsarmy/slim-monolog](https://packagist.org/packages/flynsarmy/slim-monolog) 1.*
    - [libraries/logger](https://packagist.org/packages/libraries/logger) 0.*
    - [nocarrier/hal](https://packagist.org/packages/nocarrier/hal) 0.9.*
    - [phpunit/phpunit](https://packagist.org/packages/phpunit/phpunit) 3.7.* (only on dev)

## Deployment
Can be deployed by use of the [chef/restapi](https://gitlab.bigpoint.net/chef/restapi) chef cookbook.

## API Documentation
To generate the API documentation you need [apigen](http://apigen.org/) on your system. Then run the following command in the project root:

    apigen -c apigen.conf

This will generate the API documentation as HTML documents in the `docs/` folder.

## Unit Tests
To run the unit tests of the project you need [phpunit](https://packagist.org/packages/phpunit/phpunit) on your system. Then run the following command in the project root:

    phpunit -c tests/phpunit.xml

## Setup Skeleton API
Create a folder for your new api and run the follwing command there.

Set <YOUR_NAMESPACE> in the following one liner to your API namespace name and execute this line. It will load the framework and create a sceleton structure:

    NAMESPACE="<YOUR_NAMESPACE>" && composer init -n --name "restapi/$(echo ${NAMESPACE} | tr '[:upper:]' '[:lower:]')" && composer config repositories.bigpoint composer https://packagist.bigpoint.net/ && composer require "libraries/restapi:*" && ./vendor/bin/restapi-generator "${NAMESPACE}" && composer dumpautoload

## How to implement manually
In order to create a REST API based on this framework you need a structure similar to the following in your project:

    ├── composer.json
    ├── config
    │   ├── acl.json
    │   ├── application.json
    ├── include
    │   └── Pinfo
    └── www
        └── index.php

### composer.json
    {
        "name": "account/pinfo",
        "description": "Project Information Service (Pinfo)",
        "authors": [
            {
                "name": "Andreas Schleifer",
                "email": "aschleifer@bigpoint.net"
            }
        ],
        "repositories": {
            "bigpoint": {
                "type": "composer",
                "url": "https://packagist.bigpoint.net/"
            }
        },
        "require": {
            "php": ">=5.4.4",
            "libraries/restapi": "0.2.*"
        },
        "require-dev": {
            "phpunit/phpunit": "3.7.*"
        },
        "autoload": {
            "psr-0": {
                "Pinfo\\": ["include/", "tests/"]
            }
        }
    }


### config/acl.json
The ACL is optional. If you don't need an authentication and authorization you can just ignore the ACL config. However if you want authentication the ACL config has to look something like this:

    {
        "roles": {
            "role_myRole": {
                "index": true,
                "endpoint1": true
            }
        },
        "access": {
            "myClientId": "role_myRole",
        }
    }

This is mapping the clientId "myClientId" to the role "role_myRole" which as access to the "index" and the "endpoint1" endpoints.

### config/application.json
This file holds the main configuration for the implementation and the framework.

The following structure has to be present:

    {
        "shortName": "pinfo",
        "cacheDuration": 900,
        "debug": true,
        "monolog": {
            "handler" : {
                "udp" : {
                    "host"      : "192.168.50.48",
                    "port"      : 9999,
                    "formatter" : "logstash"
                }
            },
            "formatter" : {
                "logstash" : {
                    "type" : "restapi-pinfo"
                }
            },
            "logger": {
                "_default": {
                    "handler": ["udp"],
                    "level": "DEBUG"
                },
                "slim": {
                    "handler": ["udp"],
                    "level": "DEBUG"
                }
            }
        }
    }

The `shortName` is used to prefix the endpoint names in the welcome endpoint when hal+json is used as output format.

The `cacheDuration` defines the interval (in seconds) used for the cache expire headers of the response.

If the `debug` flag is set to true the slim framework will print out a stack trace if an error occurs. Otherwise it will just show a 500 Internal Server Error.

**optional parameters:**  
If you want to use the authentication against an oauth /me endpoint you have to define the url to the /me endpoint in the config field `apiUrl`. At the end of that value the passed access token is concatinated.

### the include/ folder
This folder should contain your endpoint implementation. Read below about how to define an endpoint.

### the www/index.php
This file is the main entry piont for the application. Here is an example how this file should look like:

```php
<?php
require __DIR__ . '/../vendor/autoload.php';

$applicationConfig = json_decode(
    file_get_contents(__DIR__ . '/../config/application.json'),
    true
);
$aclConfig         = json_decode(
    file_get_contents(__DIR__ . '/../config/acl.json'),
    true
);

// create logger
$loggerFactory        = new \Logger\Factory($applicationConfig['monolog']);
$authenticationLogger = $loggerFactory->createLogger('authentication');
$phpLogger            = $loggerFactory->createLogger('php');

// register php error logger
\Monolog\ErrorHandler::register($phpLogger);

$authFactory    = new \Rest\Api\Authentication\Factory(
    $applicationConfig,
    $authenticationLogger
);
$authentication = $authFactory->createOauth();

$bootstrap      = new \Rest\Api\Bootstrap(
    $applicationConfig,
    $authentication,
    $aclConfig
);
$bootstrap->init();
$bootstrap->addRessourceGetEndpoint(
    '/projects/:projectId',
    'projects',
    array(
        'projectId' => '\d+',
    ),
    new \Pinfo\Endpoint\Ressource\Projects()
);
$bootstrap->addCollectionGetEndpoint(
    '/projects',
    'projects',
    new \Pinfo\Endpoint\Collection\Projects()
);
$bootstrap->run();
```

## Create Endpoints
### Collection Endpoint
The framework supports two types of endpoints. Collection endpoints, to return multiple results and ressource endpoints to return / handle a special result.

**Collection endpoints**  
These endpoints should implement one of the _CollectionEndpoint_ interfaces located under [\Rest\Api\Endpoint](src/Rest/Api/Endpoint). It will then get an array of filter parameters which can be passed as GET parameters and if it is not a GET endpoint an array of data which will be the payload send with the request. The endpoint should return an array of [\Rest\Api\DataObject](Rest/Api/DataObject.php) where each DataObject holds one result.

**Ressource endpoints**  
These endpoints should implement one of the _RessourceEndpoint_ interfaces located under [\Rest\Api\Endpoint](src/Rest/Api/Endpoint). It will then get an array of the parameters in the URL the ressource is identified with and if it is not a GET endpoint an array of data which will be the payload send with the request. The endpoint should retnr a [\Rest\Api\DataObject](Rest/Api/DataObject.php) and it should throw a [\Rest\Api\Exception](Rest/Api/Exception.php) if the endpoint encounters an error. The message of that exception will be printed out as result and the code will be used as HTTP return code.

### Supported HTTP methods
At the moment the framework supports the following HTTP methods:

 - GET
 - POST
 - PUT

For each of these methods the framework supplies two interfaces for the Collection and Request endpoint under [\Rest\Api\Endpoint](src/Rest/Api/Endpoint).

### Registering endpoints to the framework
The written endpoints have to be registered to the framework and the underling Slim instance in order to be accessible. This can be done by calling the appropriate add methods on the [\Rest\Api\Bootstrap](Rest/Api/Bootstrap.php) instance after the `init()` call and before the `run()` call.

The framework is using the basic form of slim to [register a route](http://docs.slimframework.com/#Routing-Overview) and bind an endpoint to the route.

In order to do this the methods need some specific parameters which are explained here for the GET endpoints but are very similar for the other endpoints:

**addCollectionGetEndpoint**  
This methods needs a `route` which is the relativ url it can be called as so for example "/myendpoint".  
As second argument it needs a `name` which will be used to identify the route and which can then be used in the ACL config to configure access to this route / endpoint.  
The third parameter is an instance of [Rest\Api\Endpoint\CollectionGet](Rest/Api/Endpoint/CollectionGet.php).

**addRessourceGetEndpoint**  
This methods needs a `route` which is the relativ url it can be called as so for example "/myendpoint/:someId".  
As second argument it needs a `name` which will be used to identify the route and which can then be used in the ACL config to configure access to this route / endpoint.  
The third parameter is an array of conditions that can define constrains for the passed id (`someId`). These constrains are normal PHP regular expressions.  
Finally the fourth parameter is an instance of [Rest\Api\Endpoint\RessourceGet](Rest/Api/Endpoint/RessourceGet.php).