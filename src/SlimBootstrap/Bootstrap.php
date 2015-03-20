<?php
namespace SlimBootstrap;

use \Flynsarmy\SlimMonolog;
use \MonologCreator;
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
     * @var array
     */
    private $_params = array();

    /**
     * @var SlimBootstrap\Hook
     */
    private $_hook = null;

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
        // create logger
        $loggerFactory = new MonologCreator\Factory(
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

        // create application
        $this->_app = new Slim\Slim(
            array(
                'debug'       => $this->_applicationConfig['debug'],
                'log.writer'  => $logger,
                'log.enabled' => true,
                'log.level'   => Slim\Log::DEBUG,
            )
        );

        // create hook handler
        $this->_hook = new SlimBootstrap\Hook(
            $this->_applicationConfig,
            $this->_app,
            $this->_authentication,
            $this->_aclConfig
        );

        // define hooks
        $this->_app->hook(
            'slim.before.router',
            array($this->_hook, 'requestPath')
        );
        $this->_app->hook(
            'slim.before.router',
            array($this->_hook, 'cacheAndAccessHeader')
        );
        $this->_app->hook(
            'slim.before.router',
            array($this->_hook, 'outputWriter')
        );
        $this->_app->hook(
            'slim.before.dispatch',
            array($this->_hook, 'authentication')
        );
        $this->_app->hook(
            'slim.after.router',
            array($this->_hook, 'responseStatus')
        );

        // remove token from GET params
        $this->_params = $this->_app->request->get();
        unset($this->_params['token']);
    }

    /**
     * This function starts the Slim framework by calling it's run() method.
     */
    public function run()
    {
        $responseOutputWriter = &$this->_hook->getResponseOutputWriter();

        // define index endpoint
        $indexEndpoint = new SlimBootstrap\Endpoint\Index(
            $this->_collectionEndpoints
        );
        $this->_app->get(
            '/',
            function () use (&$responseOutputWriter, $indexEndpoint) {
                $responseOutputWriter->write($indexEndpoint->get());
            }
        )->name('index');

        // define info endpoint
        $infoEndpoint = new SlimBootstrap\Endpoint\Info();
        $this->_app->get(
            '/info',
            function () use (&$responseOutputWriter, $infoEndpoint) {
                $responseOutputWriter->write($infoEndpoint->get());
            }
        )->name('info');

        $this->_app->run();
    }

    /**
     * @param string $httpType
     * @param object $endpoint
     * @param string $endpointType
     *
     * @throws SlimBootstrap\Exception
     */
    private function _validateEndpoint($httpType, $endpoint, $endpointType)
    {
        $interfaces = class_implements($endpoint);
        $interface  = 'SlimBootstrap\Endpoint\\'
            . $endpointType . ucfirst($httpType);

        if (false === array_key_exists($interface, $interfaces)) {
            throw new SlimBootstrap\Exception(
                'endpoint "' . get_class($endpoint)
                . '" is not a valid collection ' . strtoupper($httpType)
                . ' endpoint'
            );
        }
    }

    /**
     * @param object $endpoint
     * @param string $type
     * @param array  $params
     *
     * @throws Slim\Exception\Stop
     */
    private function _handleEndpointCall($endpoint, $type, array $params)
    {
        if ($endpoint instanceof SlimBootstrap\Endpoint\InjectClientId) {
            $endpoint->setClientId(
                $this->_app->router()->getCurrentRoute()->getParam('clientId')
            );
        }

        try {
            $this->_hook->getResponseOutputWriter()->write(
                $endpoint->$type($params, $this->_app->request->$type())
            );
        } catch (SlimBootstrap\Exception $e) {
            $this->_app->getLog()->log(
                $e->getLogLevel(),
                $e->getCode() . ' - ' . $e->getMessage()
            );
            $this->_app->response->setStatus($e->getCode());
            $this->_app->response->setBody($e->getMessage());

            $this->_app->stop();
        }
    }

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
    ) {
        $params = $this->_params;

        $this->_validateEndpoint($type, $endpoint, 'Collection');

        $this->_hook->setEndpointAuthentication(
            strtoupper($type) . $route,
            $authentication
        );

        // register endpoint to Slim
        $this->_app->$type(
            $route,
            function () use ($type, $endpoint, $params) {
                $this->_handleEndpointCall($endpoint, $type, $params);
            }
        )->name($name);

        // add endpoint to collection list to show on hal+json index endpoint
        $this->_collectionEndpoints[$name] = $route;
    }

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
    ) {
        $this->_validateEndpoint($type, $endpoint, 'Resource');

        $this->_hook->setEndpointAuthentication(
            strtoupper($type) . $route,
            $authentication
        );

        // register endpoint to Slim
        $this->_app->$type(
            $route,
            function () use ($type, $endpoint) {
                $params = func_get_args();

                $this->_handleEndpointCall($endpoint, $type, $params);
            }
        )->name($name)->conditions($conditions);
    }
}
