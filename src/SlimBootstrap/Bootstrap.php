<?php
namespace SlimBootstrap;

use \Flynsarmy\SlimMonolog;
use \SlimBootstrap;
use \Slim;

/**
 * Class Bootstrap
 *
 * @package SlimBootstrap
 */
class Bootstrap
{
    const HTTP_METHOD_GET  = 'get';
    const HTTP_METHOD_POST = 'post';
    const HTTP_METHOD_PUT  = 'put';

    /**
     * @var array
     */
    private $_applicationConfig = null;

    /**
     * @var SlimBootstrap\Authentication
     */
    private $_authentication = null;

    /**
     * @var array
     */
    private $_aclConfig = null;

    /**
     * @var array
     */
    private $_collectionEndpoints = array();

    /**
     * @var Slim\Slim
     */
    private $_app = null;

    /**
     * @var SlimBootstrap\ResponseOutputWriter
     */
    private $_responseOutputWriter = null;

    /**
     * @var array
     */
    private $_params = array();

    /**
     * @param array                        $applicationConfig
     * @param SlimBootstrap\Authentication $authentication
     * @param array                        $aclConfig
     */
    public function __construct(
        array $applicationConfig,
        SlimBootstrap\Authentication $authentication = null,
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
        $loggerFactory = new \MonologCreator\Factory(
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

        $indexEndpoint = new SlimBootstrap\Endpoint\Index(
            $this->_collectionEndpoints
        );

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
            $responseOutputWriterFactory =
                new SlimBootstrap\ResponseOutputWriter\Factory(
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
                    throw new SlimBootstrap\Exception(
                        'acl config is empty or invalid',
                        500
                    );
                }

                $this->_app->getLog()->info('using authentication');

                $acl = new SlimBootstrap\Acl($this->_aclConfig);

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

                $this->_app->getLog()->notice(
                    'set clientId to parameter: ' . $clientId
                );
                $this->_app->getLog()->debug(
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
        } catch (SlimBootstrap\Exception $e) {
            $this->_app->getLog()->error(
                $e->getCode() . ' - ' . $e->getMessage()
            );
            $this->_app->response->setStatus($e->getCode());
            $this->_app->response->setBody($e->getMessage());

            $this->_app->stop();
        }
    }

    /**
     * @param string $type     should be one of \SlimBootstrap\Bootstrap::HTTP_METHOD_*
     * @param string $route
     * @param string $name     name of the route to add (used in ACL)
     * @param object $endpoint should be one of \SlimBootstrap\Endpoint\Collection*
     *
     * @throws SlimBootstrap\Exception
     */
    public function addCollectionEndpoint(
        $type,
        $route,
        $name,
        $endpoint
    ) {
        $app                  = $this->_app;
        $params               = $this->_params;
        $responseOutputWriter = &$this->_responseOutputWriter;

        // check if $endpoint is valid
        $interfaces = class_implements($endpoint);
        $interface  = 'SlimBootstrap\Endpoint\Collection' . ucfirst($type);

        if (false === array_key_exists($interface, $interfaces)) {
            throw new SlimBootstrap\Exception(
                'endpoint "' . get_class($endpoint)
                . '" is not a valid collection ' . strtoupper($type) . ' endpoint'
            );
        }

        // register endpoint to Slim
        $this->_app->$type(
            $route,
            function () use ($type, &$responseOutputWriter, $endpoint, $params, $app) {
                if ($endpoint instanceof SlimBootstrap\Endpoint\InjectClientId) {
                    $endpoint->setClientId(
                        $app->router()->getCurrentRoute()->getParam('clientId')
                    );
                }

                try {
                    $responseOutputWriter->write(
                        $endpoint->$type($params, $app->$type())
                    );
                } catch (SlimBootstrap\Exception $e) {
                    $app->getLog()->error(
                        $e->getCode() . ' - ' . $e->getMessage()
                    );
                    $app->response->setStatus($e->getCode());
                    $app->response->setBody($e->getMessage());

                    $app->stop();
                }
            }
        )->name($name);

        // add endpoint to collection list to show on hal+json index endpoint
        $this->_collectionEndpoints[$name] = $route;
    }

    /**
     * @param string $type       should be one of \SlimBootstrap\Bootstrap::HTTP_METHOD_*
     * @param string $route
     * @param string $name       name of the route to add (used in ACL)
     * @param array  $conditions
     * @param object $endpoint   should be one of \SlimBootstrap\Endpoint\Resource*
     *
     * @throws SlimBootstrap\Exception
     */
    public function addResourceEndpoint(
        $type,
        $route,
        $name,
        array $conditions,
        $endpoint
    ) {
        $app                  = $this->_app;
        $responseOutputWriter = &$this->_responseOutputWriter;

        // check if $endpoint is valid
        $interfaces = class_implements($endpoint);
        $interface  = 'SlimBootstrap\Endpoint\Resource' . ucfirst($type);

        if (false === array_key_exists($interface, $interfaces)) {
            throw new SlimBootstrap\Exception(
                'endpoint "' . get_class($endpoint)
                . '" is not a valid resource ' . strtoupper($type) . ' endpoint'
            );
        }

        // register endpoint to Slim
        $app->$type(
            $route,
            function () use ($type, &$responseOutputWriter, $endpoint, $app) {
                $params = func_get_args();

                if ($endpoint instanceof SlimBootstrap\Endpoint\InjectClientId) {
                    $endpoint->setClientId(
                        $app->router()->getCurrentRoute()->getParam('clientId')
                    );
                }

                try {
                    $responseOutputWriter->write(
                        $endpoint->$type($params, $app->$type())
                    );
                } catch (SlimBootstrap\Exception $e) {
                    $app->getLog()->error(
                        $e->getCode() . ' - ' . $e->getMessage()
                    );
                    $app->response->setStatus($e->getCode());
                    $app->response->setBody($e->getMessage());

                    $app->stop();
                }
            }
        )->name($name)->conditions($conditions);
    }
}
