# Slim Bootstrap

These classes provide a simple way to bootstrap a Slim application with authentication.

It is an abstraction of the [Slim Framework](http://slimframework.com/) and handles some stuff like output generation in different formats and authentication / acl handling.

## installation

~~~
composer require bigpoint/slim-bootstrap
~~~

## webserver configuration
In order to configure your webserver to pass all requests in a proper way to the slim application please read the [Route URL Rewriting](http://docs.slimframework.com/#Route-URL-Rewriting) section of the Slim documentation.

## Setup Skeleton API
Create a folder for your new api and run the follwing command there.

Set `<YOUR_NAMESPACE>` in the following one liner to your API namespace (camel case) name and execute this line. It will load the framework and create a sceleton structure:

~~~
NAMESPACE="<YOUR_NAMESPACE>" && composer init -n && composer require "bigpoint/slim-bootstrap:*" && ./vendor/bin/slim-bootstrap-generator "${NAMESPACE}" && composer dumpautoload
~~~

## How to implement manually
In order to create a rest api based on this framework you need a structure similar to the following in your project:

    ├── composer.json
    ├── config
    │   ├── acl.json
    │   ├── application.json
    ├── include
    │   └── DummyApi
    └── www
        └── index.php

### config/acl.json
The ACL is optional. If you don't need an authentication and authorization you can just ignore the ACL config. However if you want authentication the ACL config has to look something like this:
~~~json
    {
        "roles": {
            "role_dummy": {
                "index": true,
                "dummy": true
            }
        },
        "access": {
            "myDummyClientId": "role_dummy",
        }
    }
~~~

This is mapping the clientId "myDummyClientId" to the role "role_dummy" which has access to the "index" and the "dummy" endpoints.

### config/application.json
This file holds the main configuration for the implementation and the framework.
For documentation on the `"monolog"` block in the config see [MonologCreator](https://github.com/Bigpoint/monolog-creator).

The following structure has to be present:
~~~json
    {
        "shortName": "dummyapi",
        "cacheDuration": 900,
        "debug": false,
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
                    "type" : "SlimBootstrap-dummyapi"
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
~~~

The `shortName` is used to prefix the endpoint names in the welcome endpoint when hal+json is used as output format.

The `cacheDuration` defines the interval (in seconds) used for the cache expire headers of the response.

If the `debug` flag is set to true the slim framework will print out a stack trace if an error occurs. Otherwise it will just show a 500 Internal Server Error.

**optional parameters:**  
If you want to use the authentication against an oauth /me endpoint you have to define the url to the /me endpoint in the config field `apiUrl`. At the end of that value the passed access token is concatinated.

### the include/ folder
This folder should contain your endpoint implementation. Read below about how to define an endpoint.

### the www/index.php
This file is the main entry piont for the application. Here is an example how this file should look like:

~~~php
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
$loggerFactory        = new \MonologCreator\Factory($applicationConfig['monolog']);
$authenticationLogger = $loggerFactory->createLogger('authentication');
$phpLogger            = $loggerFactory->createLogger('php');

// register php error logger
\Monolog\ErrorHandler::register($phpLogger);

$authFactory    = new \SlimBootstrap\Authentication\Factory(
    $applicationConfig,
    $authenticationLogger
);
$authentication = $authFactory->createOauth();

$bootstrap      = new \SlimBootstrap\Bootstrap(
    $applicationConfig,
    $authentication,
    $aclConfig
);
$bootstrap->init();
$bootstrap->addRessourceGetEndpoint(
    '/dummy/:name',
    'dummy',
    array(
        'name' => '\w+',
    ),
    new \Pinfo\Endpoint\Ressource\Dummy()
);
$bootstrap->addCollectionGetEndpoint(
    '/dummy',
    'dummy',
    new \Pinfo\Endpoint\Collection\Dummy()
);
$bootstrap->run();
~~~

## Create Endpoints
### Collection Endpoint
The framework supports two types of endpoints. Collection endpoints, to return multiple results and ressource endpoints to return / handle a special result.

**Collection endpoints**  
These endpoints should implement one of the _CollectionEndpoint_ interfaces located under [\SlimBootstrap\Endpoint](src/SlimBootstrap/Endpoint). It will then get an array of filter parameters which can be passed as GET parameters and if it is not a GET endpoint an array of data which will be the payload send with the request. The endpoint should return an array of [\SlimBootstrap\DataObject](src/SlimBootstrap/DataObject.php) where each DataObject holds one result.

**Ressource endpoints**  
These endpoints should implement one of the _RessourceEndpoint_ interfaces located under [\SlimBootstrap\Endpoint](src/SlimBootstrap/Endpoint). It will then get an array of the parameters in the URL the ressource is identified with and if it is not a GET endpoint an array of data which will be the payload send with the request. The endpoint should retnr a [\SlimBootstrap\DataObject](src/SlimBootstrap/DataObject.php) and it should throw a [\SlimBootstrap\Exception](src/SlimBootstrap/Exception.php) if the endpoint encounters an error. The message of that exception will be printed out as result and the code will be used as HTTP return code.

### Supported HTTP methods
At the moment the framework supports the following HTTP methods:

 - GET
 - POST
 - PUT

For each of these methods the framework supplies two interfaces for the Collection and Request endpoint under [\SlimBootstrap\Endpoint](src/SlimBootstrap/Endpoint).

### Registering endpoints to the framework
The written endpoints have to be registered to the framework and the underling Slim instance in order to be accessible. This can be done by calling the appropriate add methods on the [\SlimBootstrap\Bootstrap](src/SlimBootstrap/Bootstrap.php) instance after the `init()` call and before the `run()` call.

The framework is using the basic form of slim to [register a route](http://docs.slimframework.com/#Routing-Overview) and bind an endpoint to the route.

In order to do this the methods need some specific parameters which are explained here for the GET endpoints but are very similar for the other endpoints:

**addCollectionGetEndpoint**  
This methods needs a `route` which is the relativ url it can be called as so for example "/myendpoint".  
As second argument it needs a `name` which will be used to identify the route and which can then be used in the ACL config to configure access to this route / endpoint.  
The third parameter is an instance of [SlimBootstrap\Endpoint\CollectionGet](src/SlimBootstrap/Endpoint/CollectionGet.php).

**addRessourceGetEndpoint**  
This methods needs a `route` which is the relativ url it can be called as so for example "/myendpoint/:someId".  
As second argument it needs a `name` which will be used to identify the route and which can then be used in the ACL config to configure access to this route / endpoint.  
The third parameter is an array of conditions that can define constrains for the passed id (`someId`). These constrains are normal PHP regular expressions.  
Finally the fourth parameter is an instance of [SlimBootstrap\Endpoint\RessourceGet](src/SlimBootstrap/Endpoint/RessourceGet.php).

## License & Authors
- Authors:: Peter Ahrens (<pahrens@bigpoint.net>), Andreas Schleifer (<aschleifer@bigpoint.net>)

~~~
Copyright:: 2015 Bigpoint GmbH

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
~~~
