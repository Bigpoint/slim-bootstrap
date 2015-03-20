<?php
namespace SlimBootstrap;

use \Slim;
use \SlimBootstrap;

/**
 * Class Hook
 *
 * @package SlimBootstrap
 */
class Hook
{
    /**
     * @var array
     */
    private $_applicationConfig = null;

    /**
     * @var Slim\Slim
     */
    private $_app = null;

    /**
     * @var SlimBootstrap\Authentication
     */
    private $_authentication = null;

    /**
     * @var array
     */
    private $_aclConfig = null;

    /**
     * @var SlimBootstrap\ResponseOutputWriter
     */
    private $_responseOutputWriter = null;

    /**
     * Array that defines if the current endpoints wants authentication or not.
     * This array is only used if authentication in general is enabled. The
     * idea is to be able to disable authentication for one specific endpoint.
     *
     * @var array
     */
    private $_endpointAuthentication = array();

    /**
     * @param array                        $applicationConfig
     * @param Slim\Slim                    $app
     * @param SlimBootstrap\Authentication $authentication
     * @param array                        $aclConfig
     */
    public function __construct(
        array $applicationConfig,
        Slim\Slim $app,
        SlimBootstrap\Authentication $authentication,
        array $aclConfig
    ) {
        $this->_applicationConfig = $applicationConfig;
        $this->_app               = $app;
        $this->_authentication    = $authentication;
        $this->_aclConfig         = $aclConfig;
    }

    /**
     * @param string $routeId
     * @param bool   $authenticate
     */
    public function setEndpointAuthentication($routeId, $authenticate)
    {
        $this->_endpointAuthentication[$routeId] = $authenticate;
    }

    /**
     * @return SlimBootstrap\ResponseOutputWriter
     */
    public function &getResponseOutputWriter()
    {
        return $this->_responseOutputWriter;
    }

    /**
     * @param SlimBootstrap\Exception $exception
     *
     * @throws Slim\Exception\Stop
     */
    private function _handleError(SlimBootstrap\Exception $exception)
    {
        $this->_app->getLog()->log(
            $exception->getLogLevel(),
            $exception->getCode() . ' - ' . $exception->getMessage()
        );
        $this->_app->response->setStatus($exception->getCode());
        $this->_app->response->setBody($exception->getMessage());

        $this->_app->stop();
    }

    public function requestPath()
    {
        $this->_app->getLog()->debug(
            'Request path: ' . $this->_app->request->getPathInfo()
        );
    }

    public function cacheAndAccessHeader()
    {
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
    }

    public function outputWriter()
    {
        try {
            // create output writer
            $responseOutputWriterFactory =
                new SlimBootstrap\ResponseOutputWriter\Factory(
                    $this->_app->request,
                    $this->_app->response,
                    $this->_app->response->headers,
                    $this->_applicationConfig['shortName']
                );
            $this->_responseOutputWriter = $responseOutputWriterFactory->create(
                $this->_app->request->headers->get('Accept')
            );
        } catch (SlimBootstrap\Exception $exception) {
            $this->_handleError($exception);
        }
    }

    /**
     * This hook is run before the actual route is dispatched and enforces
     * the authentication and ACL if these are provided.
     * Furthermore it sets the Access-Control-Allow-Origin to * and sets
     * the cache duration to the value specified in the config.
     */
    public function authentication()
    {
        try {
            // use authentication for api
            if (null !== $this->_authentication) {
                $currentRoute = $this->_app->router->getCurrentRoute();
                $routeId      = $this->_app->environment->offsetGet(
                    'REQUEST_METHOD'
                ) . $currentRoute->getPattern();

                if (true === array_key_exists($routeId, $this->_endpointAuthentication)
                    && false === $this->_endpointAuthentication[$routeId]
                ) {
                    return;
                }

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
                $params             = $currentRoute->getParams();
                $params['clientId'] = $clientId;
                $currentRoute->setParams($params);

                $this->_app->getLog()->notice(
                    'set clientId to parameter: ' . $clientId
                );
                $this->_app->getLog()->debug(
                    var_export($currentRoute->getParams(), true)
                );

                $acl->access($clientId, $currentRoute->getName());

                $this->_app->getLog()->info('access granted');
            }
        } catch (SlimBootstrap\Exception $exception) {
            $this->_handleError($exception);
        }
    }

    public function responseStatus()
    {
        $this->_app->etag(md5($this->_app->response->getBody()));

        $this->_app->getLog()->debug(
            'Response status: ' . $this->_app->response->getStatus()
        );
    }
}
