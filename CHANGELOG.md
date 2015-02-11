# Changelog

## 1.1.0
 - bugfix: generator can now work with sub namespaces
 - bugfix: payload is passed again to endpoints when using http method `POST` or `PUT`
 - feature: `\SlimBootstrap\Exception` can now define the log level with which it wants to be logged
~~~php
class Exception extends \Exception
{
    public function __construct($message = '', $code = 0, $logLevel = \Slim\Log::ERROR);
}
~~~
