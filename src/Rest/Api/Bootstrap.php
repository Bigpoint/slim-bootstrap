<?php
namespace Rest\Api;

use \Rest\Api\Endpoint\CollectionGet;
use \Rest\Api\Endpoint\CollectionPost;
use \Rest\Api\Endpoint\CollectionPut;
use \Rest\Api\Endpoint\Index;
use \Rest\Api\Endpoint\RessourceGet;
use \Rest\Api\Endpoint\RessourcePost;
use \Rest\Api\Endpoint\RessourcePut;
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
    private $_collectionGetEndpoints = array();

    /**
     * @var Slim
     */
    private $_app = null;

    /**
     * @var Response
     */
    private $_response = null;

    /**
     * @var array
     */
    private $_params = array();

    /**
     * @param \stdClass $applicationConfig
     * @param \stdClass $aclConfig
     */
    public function __construct(
        \stdClass $applicationConfig, \stdClass $aclConfig = null
    ) {
        $this->_applicationConfig = $applicationConfig;
        $this->_aclConfig         = $aclConfig;
        $this->_app               = new Slim(
            array(
                'debug' => $applicationConfig->debug,
            )
        );

        $this->_params = $this->_app->request->get();
        unset($this->_params['token']);
    }

    /**
     * @return Slim
     */
    public function setUp()
    {
        $applicationConfig = $this->_applicationConfig;
        $app               = $this->_app;
        $response          = &$this->_response;

        if (null !== $this->_aclConfig) {
            $acl            = new Acl($this->_aclConfig);
            $authentication = new Authentication(
                $applicationConfig->apiUrl
            );
        } else {
            $acl            = null;
            $authentication = null;
        }

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

                    if (null !== $authentication && null !== $acl) {
                        $clientId = $authentication->authenticate(
                            $app->request->get('token')
                        );

                        $acl->access(
                            $clientId,
                            $app->router()->getCurrentRoute()->getName()
                        );
                    }
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


        $indexEndpoint = new Index($this->_collectionGetEndpoints);

        $app->get(
            '/',
            function () use (&$response, $indexEndpoint) {
                $response->output($indexEndpoint->get());
            }
        )->name('index');


        return $app;
    }

    /**
     * @param String        $route
     * @param String        $name
     * @param CollectionGet $endpoint
     */
    public function addCollectionGetEndpoint(
        $route,
        $name,
        CollectionGet $endpoint
    ) {
        $params   = $this->_params;
        $response = &$this->_response;

        $this->_app->get(
            $route,
            function () use (&$response, $endpoint, $params) {
                if (false === ($endpoint instanceof CollectionGet)) {
                    throw new Exception(
                        'endpoint "' . get_class($endpoint)
                        . '" is not a valid collection GET endpoint'
                    );
                }

                $response->output($endpoint->get($params));
            }
        )->name($name);

        $this->_collectionGetEndpoints[] = $name;
    }

    /**
     * @param String       $route
     * @param String       $name
     * @param array        $conditions
     * @param RessourceGet $endpoint
     */
    public function addRessourceGetEndpoint(
        $route,
        $name,
        array $conditions,
        RessourceGet $endpoint
    ) {
        $app      = $this->_app;
        $response = &$this->_response;

        $app->get(
            $route,
            function () use (&$response, $endpoint, $app) {
                $params = func_get_args();

                if (false === ($endpoint instanceof RessourceGet)) {
                    throw new Exception(
                        'endpoint "' . get_class($endpoint)
                        . '" is not a valid ressource GET endpoint'
                    );
                }

                try {
                    $response->output($endpoint->get($params));
                } catch (Exception $e) {
                    $app->response->setStatus($e->getCode());
                    $app->response->setBody($e->getMessage());

                    $app->stop();
                }
            }
        )->name($name)->conditions($conditions);
    }

    /**
     * @param String         $route
     * @param String         $name
     * @param CollectionPost $endpoint
     */
    public function addCollectionPostEndpoint(
        $route,
        $name,
        CollectionPost $endpoint
    ) {
        $app      = $this->_app;
        $params   = $this->_params;
        $response = &$this->_response;

        $this->_app->post(
            $route,
            function () use (&$response, $endpoint, $params, $app) {
                if (false === ($endpoint instanceof CollectionPost)) {
                    throw new Exception(
                        'endpoint "' . get_class($endpoint)
                        . '" is not a valid collection POST endpoint'
                    );
                }

                $response->output(
                    $endpoint->post($params, $app->request->post())
                );
            }
        )->name($name);

        $this->_collectionGetEndpoints[] = $name;
    }

    /**
     * @param String        $route
     * @param String        $name
     * @param array         $conditions
     * @param RessourcePost $endpoint
     */
    public function addRessourcePostEndpoint(
        $route,
        $name,
        array $conditions,
        RessourcePost $endpoint
    ) {
        $app      = $this->_app;
        $response = &$this->_response;

        $app->post(
            $route,
            function () use (&$response, $endpoint, $app) {
                $params = func_get_args();

                if (false === ($endpoint instanceof RessourcePost)) {
                    throw new Exception(
                        'endpoint "' . get_class($endpoint)
                        . '" is not a valid ressource POST endpoint'
                    );
                }

                try {
                    $response->output(
                        $endpoint->post($params, $app->request->post())
                    );
                } catch (Exception $e) {
                    $app->response->setStatus($e->getCode());
                    $app->response->setBody($e->getMessage());

                    $app->stop();
                }
            }
        )->name($name)->conditions($conditions);
    }

    /**
     * @param String        $route
     * @param String        $name
     * @param CollectionPut $endpoint
     */
    public function addCollectionPutEndpoint(
        $route,
        $name,
        CollectionPut $endpoint
    ) {
        $params   = $this->_params;
        $response = &$this->_response;

        $this->_app->put(
            $route,
            function () use (&$response, $endpoint, $params) {
                if (false === ($endpoint instanceof CollectionPut)) {
                    throw new Exception(
                        'endpoint "' . get_class($endpoint)
                        . '" is not a valid collection PUT endpoint'
                    );
                }

                $response->output($endpoint->put($params));
            }
        )->name($name);

        $this->_collectionGetEndpoints[] = $name;
    }

    /**
     * @param String       $route
     * @param String       $name
     * @param array        $conditions
     * @param RessourcePut $endpoint
     */
    public function addRessourcePutEndpoint(
        $route,
        $name,
        array $conditions,
        RessourcePut $endpoint
    ) {
        $app      = $this->_app;
        $response = &$this->_response;

        $app->put(
            $route,
            function () use (&$response, $endpoint, $app) {
                $params = func_get_args();

                if (false === ($endpoint instanceof RessourcePut)) {
                    throw new Exception(
                        'endpoint "' . get_class($endpoint)
                        . '" is not a valid ressource PUT endpoint'
                    );
                }

                try {
                    $response->output($endpoint->put($params));
                } catch (Exception $e) {
                    $app->response->setStatus($e->getCode());
                    $app->response->setBody($e->getMessage());

                    $app->stop();
                }
            }
        )->name($name)->conditions($conditions);
    }
}
