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
     * @var Api\ResponseOutputWriter
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
    }

    /**
     * This methods initializes the Slim object and defines a few hooks.
     */
    public function init()
    {
        $this->_app = new Slim\Slim(
            array(
                'debug' => $this->_applicationConfig->debug,
            )
        );

        $this->_params = $this->_app->request->get();
        unset($this->_params['token']);

        $app = $this->_app;

        $this->_app->hook(
            'slim.before.dispatch',
            array($this, 'authenticationHook')
        );
        $this->_app->hook(
            'slim.after.router',
            function () use ($app) {
                $app->etag(md5($app->response->getBody()));
            }
        );
    }

    /**
     * This function starts the Slim framework by calling it's run() method.
     */
    public function run()
    {
        $response = &$this->_response;

        $indexEndpoint = new Api\Endpoint\Index($this->_collectionGetEndpoints);

        $this->_app->get(
            '/',
            function () use (&$response, $indexEndpoint) {
                $response->write($indexEndpoint->get());
            }
        )->name('index');

        $this->_app->run();
    }

    /**
     * This hook is run before the actual route is dispatched and enforces
     * the authentication and ACL if these are provided.
     * Furthermore it sets the Access-Control-Allow-Origin to * and sets
     * the cache duration to the value specified in the config.
     */
    public function authenticationHook()
    {
        $this->_app->response->headers->set(
            'Access-Control-Allow-Origin',
            '*'
        );
        $this->_app->expires(
            date(
                'D, d M Y H:i:s O',
                time() + $this->_applicationConfig->cacheDuration
            )
        );

        $acl            = null;
        $authentication = null;

        if (null !== $this->_aclConfig
            && null !== $this->_authentication
        ) {
            $acl            = new Api\Acl($this->_aclConfig);
            $authentication = $this->_authentication;
        }

        try {
            $responseFactory = new Api\ResponseOutputWriter\Factory(
                $this->_app->request,
                $this->_app->response,
                $this->_app->response->headers,
                $this->_applicationConfig->shortName
            );
            $this->_response = $responseFactory->create(
                $this->_app->request->headers->get('Accept')
            );

            if (null !== $authentication && null !== $acl) {
                $clientId = $authentication->authenticate(
                    $this->_app->request->get('token')
                );

                $acl->access(
                    $clientId,
                    $this->_app->router()->getCurrentRoute()->getName()
                );
            }
        } catch (Exception $e) {
            $this->_app->response->setStatus($e->getCode());
            $this->_app->response->setBody($e->getMessage());

            $this->_app->stop();
        }
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

                $response->write($endpoint->get($params));
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
                    $response->write($endpoint->get($params));
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

                $response->write(
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
                    $response->write(
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

                $response->write(
                    $endpoint->put($params, $app->request->put())
                );
            }
        )->name($name);

        $this->_collectionGetEndpoints[] = $name;
    }

    /**
     * @param String                    $route
     * @param String                    $name
     * @param array                     $conditions
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
                    $response->write(
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
