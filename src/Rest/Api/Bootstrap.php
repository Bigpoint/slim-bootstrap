<?php
namespace Rest\Api;

use \Rest\Api\Endpoint\Collection;
use \Rest\Api\Endpoint\Index;
use \Rest\Api\Endpoint\Ressource;
use \Rest\Api\Response\Factory;
use \Slim\Slim;

/**
 * Class Bootstrap
 *
 * @package Rest\Api
 */
class Bootstrap
{
    /**
     * @var \stdClass
     */
    private $_applicationConfig = null;

    /**
     * @var \stdClass
     */
    private $_aclConfig = null;

    /**
     * @var array
     */
    private $_collectionEndpoints = array();

    /**
     * @var array
     */
    private $_ressourceEndpoints = array();

    /**
     * @param \stdClass $applicationConfig
     * @param \stdClass $aclConfig
     */
    public function __construct(
        \stdClass $applicationConfig, \stdClass $aclConfig
    ) {
        $this->_applicationConfig = $applicationConfig;
        $this->_aclConfig         = $aclConfig;
    }

    /**
     * @return Slim
     */
    public function setUp()
    {
        $applicationConfig = $this->_applicationConfig;

        $app = new Slim(
            array(
                'debug' => $applicationConfig->debug,
            )
        );

        /** @var \Rest\Api\Response $response */
        $response = null;

        $acl            = new Acl($this->_aclConfig);
        $authentication = new Authentication(
            $applicationConfig->apiUrl
        );

        $app->hook(
            'slim.before.dispatch',
            function () use (
                $app,
                &$response,
                $authentication,
                $acl,
                $applicationConfig
            ) {
                $app->response->headers->set(
                    'Access-Control-Allow-Origin',
                    '*'
                );
                $app->expires(
                    date(
                        'D, d M Y H:i:s O',
                        time() + $applicationConfig->cacheDuration
                    )
                );

                try {
                    $responseFactory = new Factory(
                        $app->request,
                        $app->response,
                        $app->response->headers,
                        $applicationConfig->shortName
                    );
                    $response        = $responseFactory->create(
                        $app->request->headers->get('Accept')
                    );

                    $clientId = $authentication->authenticate(
                        $app->request->get('token')
                    );

                    $acl->access(
                        $clientId,
                        $app->router()->getCurrentRoute()->getName()
                    );
                } catch (Exception $e) {
                    $app->response->setStatus($e->getCode());
                    $app->response->setBody($e->getMessage());

                    $app->stop();
                }
            }
        );
        $app->hook(
            'slim.after.router',
            function () use ($app) {
                $app->etag(md5($app->response->getBody()));
            }
        );


        $indexEndpoint = new Index();
        $indexEndpoint->setData($this->_collectionEndpoints);

        $app->get(
            '/',
            function () use (&$response, $indexEndpoint) {
                $response->output($indexEndpoint->get());
            }
        )->name('index');


        $params = $app->request->get();
        unset($params['token']);

        foreach ($this->_collectionEndpoints as $route => $routeData) {
            /** @var \Rest\Api\Endpoint\Collection $endpoint */
            $endpoint = $routeData['endpoint'];

            $app->get(
                $route,
                function () use (&$response, $endpoint, $params) {
                    if (false === ($endpoint instanceof Collection)) {
                        throw new Exception(
                            'endpoint "' . get_class($endpoint) . '" not valid'
                        );
                    }

                    $response->output($endpoint->get($params));
                }
            )->name($routeData['name']);
        }
        foreach ($this->_ressourceEndpoints as $route => $routeData) {
            /** @var \Rest\Api\Endpoint\Ressource $endpoint */
            $endpoint = $routeData['endpoint'];

            $app->get(
                $route,
                function ($param1, $param2 = null) use (
                    &$response,
                    $endpoint,
                    $app
                ) {
                    if (false === ($endpoint instanceof Ressource)) {
                        throw new Exception(
                            'endpoint "' . get_class($endpoint) . '" not valid'
                        );
                    }

                    try {
                        $response->output($endpoint->get($param1, $param2));
                    } catch (Exception $e) {
                        $app->response->setStatus($e->getCode());
                        $app->response->setBody($e->getMessage());

                        $app->stop();
                    }
                }
            )->name($routeData['name'])->conditions($routeData['conditions']);
        }

        return $app;
    }

    /**
     * @param String     $route
     * @param String     $name
     * @param Collection $endpoint
     */
    public function addCollectionEndpoint($route, $name, Collection $endpoint)
    {
        $this->_collectionEndpoints[$route] = array(
            'name'     => $name,
            'endpoint' => $endpoint,
        );
    }

    /**
     * @param String    $route
     * @param String    $name
     * @param array     $conditions
     * @param Ressource $endpoint
     */
    public function addRessourceEndpoint(
        $route,
        $name,
        array $conditions,
        Ressource $endpoint
    ) {
        $this->_ressourceEndpoints[$route] = array(
            'name'       => $name,
            'conditions' => $conditions,
            'endpoint'   => $endpoint,
        );
    }
}
