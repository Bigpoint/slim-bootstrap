<?php
namespace Rest\Api;

use \Flynsarmy\SlimMonolog;
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
     * @var array
     */
    private $_applicationConfig = null;

    /**
     * @var Api\Authentication
     */
    private $_authentication = null;

    /**
     * @var array
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
    private $_responseOutputWriter = null;

    /**
     * @var array
     */
    private $_params = array();

    /**
     * @param array              $applicationConfig
     * @param Api\Authentication $authentication
     * @param array              $aclConfig
     */
    public function __construct(
        array $applicationConfig,
        Api\Authentication $authentication = null,
        array $aclConfig = null
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
        $loggerFactory = new \Logger\Factory(
            $this->_applicationConfig['monolog']
        );
        $handlers = $loggerFactory->createHandlers(
            $this->_applicationConfig['monolog']['logger']['slim']
        );
        $processors = $loggerFactory->createProcessors(
            $this->_applicationConfig['monolog']['logger']['slim']
        );

        $logger = new SlimMonolog\Log\MonologWriter(
            array(
                'handlers'   => $handlers,
                'processors' => $processors,
            )
        );

        $this->_app = new Slim\Slim(
            array(
                'debug'       => $this->_applicationConfig['debug'],
                'log.writer'  => $logger,
                'log.enabled' => true,
                'log.level'   => Slim\Log::DEBUG,
            )
        );

        $this->_params = $this->_app->request->get();
        unset($this->_params['token']);

        $app = $this->_app;

        $this->_app->hook(
            'slim.before.router',
            function () use ($app) {
                $app->getLog()->debug(
                    'Request path: ' . $app->request->getPathInfo()
                );
            }
        );
        $this->_app->hook(
            'slim.before.dispatch',
            array($this, 'authenticationHook')
        );
        $this->_app->hook(
            'slim.after.router',
            function () use ($app) {
                $app->etag(md5($app->response->getBody()));

                $app->getLog()->debug(
                    'Response status: ' . $app->response->getStatus()
                );
            }
        );
    }

    /**
     * This function starts the Slim framework by calling it's run() method.
     */
    public function run()
    {
        $responseOutputWriter = &$this->_responseOutputWriter;

        $indexEndpoint = new Api\Endpoint\Index($this->_collectionGetEndpoints);

        $this->_app->get(
            '/',
            function () use (&$responseOutputWriter, $indexEndpoint) {
                $responseOutputWriter->write($indexEndpoint->get());
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
        try {
            $this->_app->response->headers->set(
                'Access-Control-Allow-Origin',
                '*'
            );
            $this->_app->expires(
                date(
                    'D, d M Y H:i:s O',
                    time() + $this->_applicationConfig['cacheDuration']
                )
            );

            // create output writer
            $responseOutputWriterFactory = new Api\ResponseOutputWriter\Factory(
                $this->_app->request,
                $this->_app->response,
                $this->_app->response->headers,
                $this->_applicationConfig['shortName'],
                $this->_app->getLog()
            );
            $this->_responseOutputWriter = $responseOutputWriterFactory->create(
                $this->_app->request->headers->get('Accept')
            );

            // use authentication for api
            if (null !== $this->_authentication) {

                if (false === is_array($this->_aclConfig)) {
                    throw new Api\Exception('acl config is empty or invalid', 500);
                }

                $this->_app->getLog()->info('using authentication');

                $acl = new Api\Acl($this->_aclConfig);

                $clientId = $this->_authentication->authenticate(
                    $this->_app->request->get('token')
                );

                $this->_app->getLog()->info('authentication successfull');

                /*
                 * Inject the clientId into the parameters.
                 * We have to get all parameters, change the array and set it
                 * again because slim doesn't allow to set a new parameter
                 * directly.
                 */
                $params = $this->_app->router()->getCurrentRoute()->getParams();
                $params['clientId'] = $clientId;
                $this->_app->router()->getCurrentRoute()->setParams($params);

                $this->_app->log->notice('set clientId to parameter: ' . $clientId);
                $this->_app->log->debug(
                    var_export(
                        $this->_app->router()->getCurrentRoute()->getParams(),
                        true
                    )
                );

                $acl->access(
                    $clientId,
                    $this->_app->router()->getCurrentRoute()->getName()
                );

                $this->_app->getLog()->info('access granted');
            }
        } catch (Exception $e) {
            $this->_app->getLog()->error(
                $e->getCode() . ' - ' . $e->getMessage()
            );
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
        $app                  = $this->_app;
        $params               = $this->_params;
        $responseOutputWriter = &$this->_responseOutputWriter;

        $this->_app->get(
            $route,
            function () use (&$responseOutputWriter, $endpoint, $params, $app) {
                if (false === ($endpoint instanceof Api\Endpoint\CollectionGet)) {
                    throw new Exception(
                        'endpoint "' . get_class($endpoint)
                        . '" is not a valid collection GET endpoint'
                    );
                }

                if ($endpoint instanceof Api\Endpoint\InjectClientId) {
                    $endpoint->setClientId(
                        $app->router()->getCurrentRoute()->getParam('clientId')
                    );
                }

                $responseOutputWriter->write($endpoint->get($params));
            }
        )->name($name);

        $this->_collectionGetEndpoints[$name] = $route;
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
        $app                  = $this->_app;
        $responseOutputWriter = &$this->_responseOutputWriter;

        $app->get(
            $route,
            function () use (&$responseOutputWriter, $endpoint, $app) {
                $params = func_get_args();

                if (false === ($endpoint instanceof Api\Endpoint\RessourceGet)) {
                    throw new Exception(
                        'endpoint "' . get_class($endpoint)
                        . '" is not a valid ressource GET endpoint'
                    );
                }

                if ($endpoint instanceof Api\Endpoint\InjectClientId) {
                    $endpoint->setClientId(
                        $app->router()->getCurrentRoute()->getParam('clientId')
                    );
                }

                try {
                    $responseOutputWriter->write($endpoint->get($params));

                } catch (Exception $e) {
                    $app->getLog()->error($e->getCode() . ' - ' . $e->getMessage());
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
        $app                  = $this->_app;
        $params               = $this->_params;
        $responseOutputWriter = &$this->_responseOutputWriter;

        $this->_app->post(
            $route,
            function () use (&$responseOutputWriter, $endpoint, $params, $app) {
                if (false === ($endpoint instanceof Api\Endpoint\CollectionPost)) {
                    throw new Exception(
                        'endpoint "' . get_class($endpoint)
                        . '" is not a valid collection POST endpoint'
                    );
                }

                if ($endpoint instanceof Api\Endpoint\InjectClientId) {
                    $endpoint->setClientId(
                        $app->router()->getCurrentRoute()->getParam('clientId')
                    );
                }

                $responseOutputWriter->write(
                    $endpoint->post($params, $app->request->post())
                );
            }
        )->name($name);

        $this->_collectionGetEndpoints[$name] = $route;
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
        $app                  = $this->_app;
        $responseOutputWriter = &$this->_responseOutputWriter;

        $app->post(
            $route,
            function () use (&$responseOutputWriter, $endpoint, $app) {
                $params = func_get_args();

                if (false === ($endpoint instanceof Api\Endpoint\RessourcePost)) {
                    throw new Exception(
                        'endpoint "' . get_class($endpoint)
                        . '" is not a valid ressource POST endpoint'
                    );
                }

                if ($endpoint instanceof Api\Endpoint\InjectClientId) {
                    $endpoint->setClientId(
                        $app->router()->getCurrentRoute()->getParam('clientId')
                    );
                }

                try {
                    $responseOutputWriter->write(
                        $endpoint->post($params, $app->request->post())
                    );
                } catch (Exception $e) {
                    $app->getLog()->error($e->getCode() . ' - ' . $e->getMessage());
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
        $app                  = $this->_app;
        $params               = $this->_params;
        $responseOutputWriter = &$this->_responseOutputWriter;

        $this->_app->put(
            $route,
            function () use (&$responseOutputWriter, $endpoint, $params, $app) {
                if (false === ($endpoint instanceof Api\Endpoint\CollectionPut)) {
                    throw new Exception(
                        'endpoint "' . get_class($endpoint)
                        . '" is not a valid collection PUT endpoint'
                    );
                }

                if ($endpoint instanceof Api\Endpoint\InjectClientId) {
                    $endpoint->setClientId(
                        $app->router()->getCurrentRoute()->getParam('clientId')
                    );
                }

                $responseOutputWriter->write(
                    $endpoint->put($params, $app->request->put())
                );
            }
        )->name($name);

        $this->_collectionGetEndpoints[$name] = $route;
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
        $app                  = $this->_app;
        $responseOutputWriter = &$this->_responseOutputWriter;

        $app->put(
            $route,
            function () use (&$responseOutputWriter, $endpoint, $app) {
                $params = func_get_args();

                if (false === ($endpoint instanceof Api\Endpoint\RessourcePut)) {
                    throw new Exception(
                        'endpoint "' . get_class($endpoint)
                        . '" is not a valid ressource PUT endpoint'
                    );
                }

                if ($endpoint instanceof Api\Endpoint\InjectClientId) {
                    $endpoint->setClientId(
                        $app->router()->getCurrentRoute()->getParam('clientId')
                    );
                }

                try {
                    $responseOutputWriter->write(
                        $endpoint->put($params, $app->request->put())
                    );
                } catch (Exception $e) {
                    $app->getLog()->error($e->getCode() . ' - ' . $e->getMessage());
                    $app->response->setStatus($e->getCode());
                    $app->response->setBody($e->getMessage());

                    $app->stop();
                }
            }
        )->name($name)->conditions($conditions);
    }
}
