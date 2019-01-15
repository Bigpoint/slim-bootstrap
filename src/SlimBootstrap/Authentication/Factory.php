<?php
namespace SlimBootstrap\Authentication;

use \Monolog;
use \SlimBootstrap;
use \Slim;

/**
 * Class Factory
 *
 * @package SlimBootstrap\Authentication
 */
class Factory
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var Slim\Log
     */
    private $logger;

    /**
     * @param array          $config
     * @param Monolog\Logger $logger
     */
    public function __construct(array $config, Monolog\Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @return SlimBootstrap\Authentication\Auth0
     */
    public function createAuth0()
    {
        return new SlimBootstrap\Authentication\Auth0(
            $this->config['auth0']['authorizedIss'],
            $this->config['auth0']['signingSecret'],
            $this->logger,
            $this->config['auth0']['supportedAlgorithms'],
            $this->config['auth0']['validAudiences']
        );
    }

    /**
     * @return SlimBootstrap\Authentication\Oauth
     */
    public function createOauth()
    {
        return new SlimBootstrap\Authentication\Oauth($this->config['authenticationUrl'], $this->logger);
    }
}
