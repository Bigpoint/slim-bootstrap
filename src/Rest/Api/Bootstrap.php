<?php
namespace Rest\Api;

use \Rest\Api;
use \Slim;

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
     * @var Api\Authentication
     */
    private $_authentication = null;

    /**
     * @var \stdClass
     */
    private $_aclConfig = null;

    /**
     * @var array
     */
    private $_collectionGetEndpoints = array();

    /**
     * @var Slim\Slim
     */
    private $_app = null;

    /**
     * @var Api\Response
     */
    private $_response = null;

    /**
     * @var array
     */
    private $_params = array();

    /**
     * @param \stdClass $applicationConfig
     * @param Api\Authentication $authentication
     * @param \stdClass $aclConfig
     */
    public function __construct(
        \stdClass $applicationConfig,
        Api\Authentication $authentication = null,
        \stdClass $aclConfig = null
    ) {
        $this->_applicationConfig = $applicationConfig;
        $this->_authentication    = $authentication;
        $this->_aclConfig         = $aclConfig;
        $this->_app               = new Slim\Slim(
            array(
                'debug' => $applicationConfig->debug,
            )
        );

        $this->_params = $this->_app->request->get();
        unset($this->_params['token']);
    }

    /**
     * @return Slim\Slim
     */
    public function setUp()
    {
        $applicationConfig = $this->_applicationConfig;
        $app               = $this->_app;
        $response          = &$this->_response;

        $acl            = null;
        $authentication = null;

        if (null !== $this->_aclConfig
            && null !== $this->_authentication
        ) {
            $acl            = new Api\Acl($this->_aclConfig);
            $authentication = $this->_authentication;
        }

        /**
         * This hook is run before the actual route is dispatched and enforces
         * the authentication and ACL if these are provided.
         * Furthermore it sets the Access-Control-Allow-Origin to * and sets
         * the cache duration to the value specified in the config.
         */
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
                    $responseFactory = new Api\Response\Factory(
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


        $indexEndpoint = new Api\Endpoint\Index($this->_collectionGetEndpoints);

        $app->get(
            '/',
            function () use (&$response, $indexEndpoint) {
                $response->output($indexEndpoint->get());
            }
        )->name('index');


        return $app;
    }

    /**
     * @param String                     $route
     * @param String                     $name
     * @param Api\Endpoint\CollectionGet $endpoint
     */
    public function addCollectionGetEndpoint(
        $route,
        $name,
        Api\Endpoint\CollectionGet $endpoint
    ) {
        $params   = $this->_params;
        $response = &$this->_response;

        $this->_app->get(
            $route,
            function () use (&$response, $endpoint, $params) {
                if (false === ($endpoint instanceof Api\Endpoint\CollectionGet)) {
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
     * @param String                    $route
     * @param String                    $name
     * @param array                     $conditions
     * @param Api\Endpoint\RessourceGet $endpoint
     */
    public function addRessourceGetEndpoint(
        $route,
        $name,
        array $conditions,
        Api\Endpoint\RessourceGet $endpoint
    ) {
        $app      = $this->_app;
        $response = &$this->_response;

        $app->get(
            $route,
            function () use (&$response, $endpoint, $app) {
                $params = func_get_args();

                if (false === ($endpoint instanceof Api\Endpoint\RessourceGet)) {
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
     * @param String                      $route
     * @param String                      $name
     * @param Api\Endpoint\CollectionPost $endpoint
     */
    public function addCollectionPostEndpoint(
        $route,
        $name,
        Api\Endpoint\CollectionPost $endpoint
    ) {
        $app      = $this->_app;
        $params   = $this->_params;
        $response = &$this->_response;

        $this->_app->post(
            $route,
            function () use (&$response, $endpoint, $params, $app) {
                if (false === ($endpoint instanceof Api\Endpoint\CollectionPost)) {
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
     * @param String                     $route
     * @param String                     $name
     * @param array                      $conditions
     * @param Api\Endpoint\RessourcePost $endpoint
     */
    public function addRessourcePostEndpoint(
        $route,
        $name,
        array $conditions,
        Api\Endpoint\RessourcePost $endpoint
    ) {
        $app      = $this->_app;
        $response = &$this->_response;

        $app->post(
            $route,
            function () use (&$response, $endpoint, $app) {
                $params = func_get_args();

                if (false === ($endpoint instanceof Api\Endpoint\RessourcePost)) {
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
     * @param String                     $route
     * @param String                     $name
     * @param Api\Endpoint\CollectionPut $endpoint
     */
    public function addCollectionPutEndpoint(
        $route,
        $name,
        Api\Endpoint\CollectionPut $endpoint
    ) {
        $app      = $this->_app;
        $params   = $this->_params;
        $response = &$this->_response;

        $this->_app->put(
            $route,
            function () use (&$response, $endpoint, $params, $app) {
                if (false === ($endpoint instanceof Api\Endpoint\CollectionPut)) {
                    throw new Exception(
                        'endpoint "' . get_class($endpoint)
                        . '" is not a valid collection PUT endpoint'
                    );
                }

                $response->output(
                    $endpoint->put($params, $app->request->put())
                );
            }
        )->name($name);

        $this->_collectionGetEndpoints[] = $name;
    }

    /**
     * @param String       $route
     * @param String       $name
     * @param array        $conditions
     * @param Api\Endpoint\RessourcePut $endpoint
     */
    public function addRessourcePutEndpoint(
        $route,
        $name,
        array $conditions,
        Api\Endpoint\RessourcePut $endpoint
    ) {
        $app      = $this->_app;
        $response = &$this->_response;

        $app->put(
            $route,
            function () use (&$response, $endpoint, $app) {
                $params = func_get_args();

                if (false === ($endpoint instanceof Api\Endpoint\RessourcePut)) {
                    throw new Exception(
                        'endpoint "' . get_class($endpoint)
                        . '" is not a valid ressource PUT endpoint'
                    );
                }

                try {
                    $response->output(
                        $endpoint->put($params, $app->request->put())
                    );
                } catch (Exception $e) {
                    $app->response->setStatus($e->getCode());
                    $app->response->setBody($e->getMessage());

                    $app->stop();
                }
            }
        )->name($name)->conditions($conditions);
    }
}
