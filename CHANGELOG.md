# Changelog
## 1.6.1
- fixed setting http header and status code for streamable csv output

## 1.6.0
 - implemented streamable csv output, to handle large response output

## 1.5.1
 - improved memory usage of csv output writer

## 1.5.0
 - feature: it is now possible to generate a CSV-Response ([RFC 4180](https://tools.ietf.org/html/rfc4180) compliant) by setting the `Accept`-header to `text/csv`.
    The properties are configurable in the 'csv' section of the `application.json`.
 - documentation: Fixed some typos and added `text/csv` to supported formats.
 - removed access_tokens from error logs at oauth authentication curls

## 1.4.0
 - feature: introduce "access_token" parameter to handle the access token. The "token" parameter is now deprecated and may be removed in some future version. Using the old "token" parameter will now throw a notice.

## 1.3.0
 - feature: it is now possible to create resource delete endpoints with the interface [\SlimBootstrap\Endpoint\ResourceDelete](src/SlimBootstrap/Endpoint/ResourceDelete.php)
 - feature: endpoints can now specify explicitly that they don't want authorization even if it is globally enabled. This can be done by passing a new optional parameter to the register endpoint functions.
~~~php
/**
 * @param string $type           should be one of
 *                               \SlimBootstrap\Bootstrap::HTTP_METHOD_*
 * @param string $route
 * @param string $name           name of the route to add (used in ACL)
 * @param object $endpoint       should be one of
 *                               \SlimBootstrap\Endpoint\Collection*
 * @param bool   $authentication set this to false if you want no
 *                               authentication for this endpoint
 *                               (default: true)
 *
 * @throws SlimBootstrap\Exception
 */
public function addCollectionEndpoint(
    $type,
    $route,
    $name,
    $endpoint,
    $authentication = true
);

/**
 * @param string $type           should be one of
 *                               \SlimBootstrap\Bootstrap::HTTP_METHOD_*
 * @param string $route
 * @param string $name           name of the route to add (used in ACL)
 * @param array  $conditions
 * @param object $endpoint       should be one of
 *                               \SlimBootstrap\Endpoint\Resource*
 * @param bool   $authentication set this to false if you want no
 *                               authentication for this endpoint
 *                               (default: true)
 *
 * @throws SlimBootstrap\Exception
 */
public function addResourceEndpoint(
    $type,
    $route,
    $name,
    array $conditions,
    $endpoint,
    $authentication = true
);
~~~

## 1.2.0
 - feature: every implementation of this library now provides an `/info` endpoint which shows what composer dependencies are present. Further more it shows the git tag version and the git repo url if it finds a git environment

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
