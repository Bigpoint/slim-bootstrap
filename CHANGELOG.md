# 2.0.0
- replace `libraries/logger` with `bigpoint/monolog-creator`

## Upgrade from 4.\* to 5.\*

- www/index.php
 - replace `\Logger\Factory` with `\MonologCreator\Factory`

```diff
 // create logger
-$loggerFactory = new \Logger\Factory($config['monolog']);
+$loggerFactory = new \MonologCreator\Factory($config['monolog']);
```


# 1.0.0

- `applicationConfig` and `aclConfig` now has to be an array
- implement monolog for application and php error logging, see [monolog](https://github.com/Seldaek/monolog/blob/master/doc/usage.md) and [Logger library](https://gitlab.bigpoint.net/libraries/logger/blob/master/README.md) documentation

## Upgrade from 0.\* to 1.\*

- You now need to provider a configuration entry called `monolog` in order to configure where the logs of slim should go to:

```json
    {
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
```

- www/index.php
 - When loading the config files you now need to make sure that they are loaded as array
 - You now need to create the logger through the `\Logger\Factory` and pass an authentication logger to the authentication factory
 - optionally you can register a logger as PHP error handler. This should happen before the initialization of the Bootstrap class

```diff
 $applicationConfig = json_decode(
-    file_get_contents(__DIR__ . '/../config/application.json')
+    file_get_contents(__DIR__ . '/../config/application.json'),
+    true
 );
 $aclConfig         = json_decode(
-    file_get_contents(__DIR__ . '/../config/acl.json')
+    file_get_contents(__DIR__ . '/../config/acl.json'),
+    true
 );
```
```diff
+// create logger
+$loggerFactory        = new \Logger\Factory($applicationConfig['monolog']);
+$authenticationLogger = $loggerFactory->createLogger('authentication');

 $authenticationFactory = new restapi\Authentication\Factory(
-    $applicationConfig
+    $applicationConfig,
+    $authenticationLogger
 );
```
```diff
+$phpLogger = $loggerFactory->createLogger('php');

+// register php error logger
+\Monolog\ErrorHandler::register($phpLogger);
```
