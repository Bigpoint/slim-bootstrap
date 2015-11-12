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
Create a folder for your new api and run the following command there.

Set `<YOUR_NAMESPACE>` in the following one liner to your API namespace (camel case) name and execute this line. It will load the framework and create a skeleton structure:

~~~
NAMESPACE="<YOUR_NAMESPACE>" && composer init -n && composer require "bigpoint/slim-bootstrap:*" && ./vendor/bin/slim-bootstrap-generator "${NAMESPACE}" && composer dumpautoload
~~~

## How to implement manually
In order to create a rest api based on this framework you need a structure similar to the following in your project.

    ├── composer.json
    ├── config
    │   └── application.json
    ├── include
    │   └── ###Namespace###
    │       └── Endpoint
    │           └── V1
    │               ├── Collection
    │               │   └── EndpointA.php
    │               └── Resource
    │                   └── EndpointA.php
    └── www
        └── index.php

### config/application.json
This file holds the main configuration for the implementation and the framework.
For documentation on the `"monolog"` block in the config see [MonologCreator](https://github.com/Bigpoint/monolog-creator).

The following structure has to be present:
~~~json
    {
        "shortName": "###NAMESPACE_LOWER###",
        "cacheDuration": 900,
        "debug": false,
        "csv": {
            "delimiter": ",",
            "enclosure": "\"",
            "linebreak": "\r\n",
            "keyspaceDelimiter": "_",
            "encloseAll": false,
            "null": "NULL"
        },
        "monolog": {
            "handler": {
                "udp": {
                    "host": "127.0.0.1",
                    "port": 6666,
                    "formatter": "logstash"
                }
            },
            "formatter": {
                "logstash": {
                    "type": "SlimBootstrap-###NAMESPACE_LOWER###"
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

### the include/ folder
This folder should contain your endpoint implementation. Read below about how to define an endpoint.

### the www/index.php
This file is the main entry point for the application. Here is an example how this file should look like:

~~~php
<?php
require __DIR__ . '/../vendor/autoload.php';

$applicationConfig = json_decode(
    file_get_contents(__DIR__ . '/../config/application.json'),
    true
);

// create logger
$loggerFactory        = new \MonologCreator\Factory($applicationConfig['monolog']);
$authenticationLogger = $loggerFactory->createLogger('authentication');
$phpLogger            = $loggerFactory->createLogger('php');

// register php error logger
\Monolog\ErrorHandler::register($phpLogger);

$bootstrap      = new \SlimBootstrap\Bootstrap(
    $applicationConfig
);
$bootstrap->init();
$bootstrap->addResourceEndpoint(
    \SlimBootstrap\Bootstrap::HTTP_METHOD_GET,
    '/dummy/:name',
    'dummy',
    array(
        'name' => '\w+',
    ),
    new \DummyApi\Endpoint\Resource\Dummy()
);
$bootstrap->addCollectionEndpoint(
    \SlimBootstrap\Bootstrap::HTTP_METHOD_GET,
    '/dummy',
    'dummy',
    new \DummyApi\Endpoint\Collection\Dummy()
);
$bootstrap->run();
~~~

## Create Endpoints
### Collection Endpoint
The framework supports two types of endpoints. Collection endpoints, to return multiple results and resource endpoints to return / handle a special result.

**Collection endpoints**

These endpoints should implement one of the _CollectionEndpoint_ interfaces located under [\SlimBootstrap\Endpoint](src/SlimBootstrap/Endpoint). It will then get an array of filter parameters which can be passed as GET parameters and if it is not a GET endpoint an array of data which will be the payload send with the request. The endpoint should return an array of [\SlimBootstrap\DataObject](src/SlimBootstrap/DataObject.php) where each DataObject holds one result.

**Resource endpoints**

These endpoints should implement one of the _ResourceEndpoint_ interfaces located under [\SlimBootstrap\Endpoint](src/SlimBootstrap/Endpoint). It will then get an array of the parameters in the URL the resource is identified with and if it is not a GET endpoint an array of data which will be the payload send with the request. The endpoint should return a [\SlimBootstrap\DataObject](src/SlimBootstrap/DataObject.php) and it should throw a [\SlimBootstrap\Exception](src/SlimBootstrap/Exception.php) if the endpoint encounters an error. When an exception is thrown, the optional third parameter defines the log level with which this exception will be logged. The default is "ERROR". The message of that exception will be printed out as result and the code will be used as HTTP status code.

### Supported HTTP methods
At the moment the framework supports the following HTTP methods:

 - DELETE
 - GET
 - POST
 - PUT

For each of these methods the framework supplies two interfaces for the Collection and Resource endpoint under [\SlimBootstrap\Endpoint](src/SlimBootstrap/Endpoint).

### Registering endpoints to the framework
The written endpoints have to be registered to the framework and the underling Slim instance in order to be accessible. This can be done by calling the appropriate add methods on the [\SlimBootstrap\Bootstrap](src/SlimBootstrap/Bootstrap.php) instance after the `init()` call and before the `run()` call. The framework is using the basic form of slim to [register a route](http://docs.slimframework.com/#Routing-Overview) and bind an endpoint to the route. In order to do this the methods need some specific parameters which are explained here for the GET endpoints but are very similar for the other endpoints:

**addCollectionEndpoint**

This methods needs a HTTP protocol for which this endpoint should be registered. This should be one of the `\SlimBootstrap\Bootstrap::HTTP_METHOD_*` constants. As second argument it needs a  `route` which is the relative url it can be called as so for example "/myendpoint". As third argument it needs a `name` which will be used to identify the route and which can then be used in the ACL config to configure access to this route / endpoint. The fourth parameter is an instance of [\SlimBootstrap\Endpoint\Collection*](src/SlimBootstrap/Endpoint/). As the sixth parameter you can optionally pass a boolean to define whether authentication should be enabled or disabled for this one endpoint. This overwrites the global authentication definition.

**addResourceEndpoint**

This methods needs a HTTP protocol for which this endpoint should be registered. This should be one of the `\SlimBootstrap\Bootstrap::HTTP_METHOD_*` constants. As second argument it needs a `route` which is the relative url it can be called as so for example "/myendpoint/:someId". As third argument it needs a `name` which will be used to identify the route and which can then be used in the ACL config to configure access to this route / endpoint. The fourth parameter is an array of conditions that can define constrains for the passed id (`someId`). These constrains are normal PHP regular expressions. Finally the fifth parameter is an instance of [\SlimBootstrap\Endpoint\Resource*](src/SlimBootstrap/Endpoint/). As the sixth parameter you can optionally pass a boolean to define whether authentication should be enabled or disabled for this one endpoint. This overwrites the global authentication definition.

## Response Output

Slim-Bootstrap supports multiple response output types, which can be requested via header attribute "Accept":

- [application/hal+json](http://stateless.co/hal_specification.html) __(default)__
- application/json
- [text/csv](https://tools.ietf.org/html/rfc4180)

### Regarding `text/csv` Output

The properties of the CSV are configurable in the 'csv' section of the `application.json`. If not existent the following defaults will be used:

| Configvalue           | Default  | Description                                                      |
| --------------------- | -------- | ---------------------------------------------------------------- |
| `delimiter`           | `","`    | Field delimiter                                                  |
| `enclosure`           | `"\""`   | Field Enclosure                                                  |
| `linebreak`           | `"\r\n"` | Linebreak                                                        |
| `keyspaceDelimiter`   | `"_"`    | Used to delimit merged structure-keys                            |
| `encloseAll`          | `false`  | Enclose every field (true) or only where it is necessary (false) |
| `null`                | `"NULL"` | Replace a null value in the dataset with this string.            |

**Attention:** CSV outpunt can only show one level of data hierarchy. Fields with another level will be ignored.

## Authentication

It's possible to enable an authentication against an oauth server, to secure your api and set endpoint specific permissions. The oauth server has to provide the clientId as `entity_id` in its /me endpoint of assigned token, to work properly with slim-bootstraps authentication.

### How it works

When authentication is enabled, you have to add the url parameter `access_token` to api calls with an access token given from your oauth server. The authentication logic validate this access token against the configured oauth server via its /me endpoint. Next the collected clientId from /me endpoint is going to be validated against requested endpoint and configured acl. If all is fine, access is granted to requester. Otherwise request is aborted with an 401 or 403.

### Enable Authentication

If you want to use the authentication against an oauth /me endpoint you have to define the url to the /me endpoint in the config field `authenticationUrl`. At the end of that value the passed access token is concatenated.

~~~
https://myserver.com/me?access_token=
~~~

Also you have to add a config/acl.json, which defines accessible endpoints for a clientId.
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

Last, you have to add following code parts at your ww/index.php file.

~~~diff
<?php
require __DIR__ . '/../vendor/autoload.php';

$applicationConfig = json_decode(
    file_get_contents(__DIR__ . '/../config/application.json'),
    true
);
+$aclConfig         = json_decode(
+    file_get_contents(__DIR__ . '/../config/acl.json'),
+    true
+);

// create logger
$loggerFactory        = new \MonologCreator\Factory($applicationConfig['monolog']);
$authenticationLogger = $loggerFactory->createLogger('authentication');
$phpLogger            = $loggerFactory->createLogger('php');

// register php error logger
\Monolog\ErrorHandler::register($phpLogger);

+$authFactory    = new \SlimBootstrap\Authentication\Factory(
+    $applicationConfig,
+    $authenticationLogger
+);
+$authentication = $authFactory->createOauth();

$bootstrap      = new \SlimBootstrap\Bootstrap(
    $applicationConfig,
+    $authentication,
+    $aclConfig
);
$bootstrap->init();
$bootstrap->addResourceEndpoint(
    \SlimBootstrap\Bootstrap::HTTP_METHOD_GET,
    '/dummy/:name',
    'dummy',
    array(
        'name' => '\w+',
    ),
    new \DummyApi\Endpoint\Resource\Dummy()
);
$bootstrap->addCollectionEndpoint(
    \SlimBootstrap\Bootstrap::HTTP_METHOD_GET,
    '/dummy',
    'dummy',
    new \DummyApi\Endpoint\Collection\Dummy()
);
$bootstrap->run();
~~~

This is mapping the clientId "myDummyClientId" to the role "role_dummy" which has access to the "index" and the "dummy" endpoints.

### Custom Authentication

If you want, you can define your own authentication class which for example reads from a database. If you want to do this you have to implement the [Authentication interface](src/SlimBootstrap/Authentication.php).


## License & Authors
- Authors:: Peter Ahrens (<pahrens@bigpoint.net>), Andreas Schleifer (<aschleifer@bigpoint.net>), Hendrik Meyer (<hmeyer@bigpoint.net>)

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
